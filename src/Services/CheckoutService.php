<?php

namespace HBM\Services;

use HBM\Repositories\CheckoutRepository;
use HBM\Repositories\StoreRepository;
use HBM\Services\InvoiceService;
use HBM\Services\EmailService;
use HBM\Services\EmailTemplateService;
use Exception;

class CheckoutService {
    private CheckoutRepository $checkoutRepo;
    private StoreRepository $storeRepo;
    private InvoiceService $invoiceService;
    private EmailService $emailService;

    public function __construct() {
        $this->checkoutRepo = new CheckoutRepository();
        $this->storeRepo = new StoreRepository();
        $this->invoiceService = new InvoiceService();
        $this->emailService = new EmailService();
    }

    public function getUserAddresses(int $userId): array {
        return $this->checkoutRepo->getUserAddresses($userId);
    }

    public function saveAddress(int $userId, array $data): array {
        $required = ['first_name', 'last_name', 'phone', 'address_line_1', 'city', 'state', 'pincode'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Field '$field' is required.");
            }
        }
        
        $addressId = $this->checkoutRepo->saveAddress($userId, $data);
        return $this->checkoutRepo->getAddressById($addressId, $userId);
    }

    public function updateAddress(int $userId, int $addressId, array $data): array {
        $required = ['first_name', 'last_name', 'phone', 'address_line_1', 'city', 'state', 'pincode'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Field '$field' is required.");
            }
        }
        
        $address = $this->checkoutRepo->getAddressById($addressId, $userId);
        if (!$address) {
            throw new Exception("Address not found.");
        }
        
        $this->checkoutRepo->updateAddress($userId, $addressId, $data);
        return $this->checkoutRepo->getAddressById($addressId, $userId);
    }

    public function deleteAddress(int $userId, int $addressId): void {
        $address = $this->checkoutRepo->getAddressById($addressId, $userId);
        if (!$address) {
            throw new Exception("Address not found.");
        }
        $this->checkoutRepo->deleteAddress($userId, $addressId);
    }

    public function createOrder(int $userId, array $orderData): array {
        $cartItems = $this->storeRepo->getCartByUserId($userId);
        if (empty($cartItems)) {
            throw new Exception("Cart is empty. Cannot create order.");
        }

        $addressId = $orderData['address_id'] ?? null;
        if (!$addressId) {
            throw new Exception("Shipping address is required.");
        }

        $address = $this->checkoutRepo->getAddressById((int)$addressId, $userId);
        if (!$address) {
            throw new Exception("Invalid shipping address.");
        }

        $paymentMethod = $orderData['payment_method'] ?? 'cod';
        if (!in_array($paymentMethod, ['upi', 'card', 'net_banking', 'cod'])) {
            throw new Exception("Invalid payment method.");
        }

        // Calculate totals server-side
        $subtotal = 0;
        foreach ($cartItems as $item) {
            $subtotal += ($item['price'] * $item['quantity']);
        }
        
        $discount = isset($orderData['discount']) ? (float)$orderData['discount'] : 0.0;
        $taxableAmount = max(0, $subtotal - $discount);
        $shippingFee = ($taxableAmount > 0 && $taxableAmount < 499) ? 59 : 0;
        $tax = $taxableAmount * 0.05;
        $totalAmount = $taxableAmount + $shippingFee + $tax;
        
        $orderNumber = 'HBM' . date('ymdHis') . rand(100, 999);

        try {
            $this->checkoutRepo->beginTransaction();

            $orderId = $this->checkoutRepo->createOrder([
                'order_number' => $orderNumber,
                'user_id' => $userId,
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'total_amount' => $totalAmount,
                'payment_method' => $paymentMethod,
                'payment_status' => 'pending',
                'order_status' => 'processing',
                
                // Snapshot address
                'shipping_first_name' => $address['first_name'],
                'shipping_last_name' => $address['last_name'],
                'shipping_phone' => $address['phone'],
                'shipping_email' => $address['email'],
                'shipping_address_line_1' => $address['address_line_1'],
                'shipping_address_line_2' => $address['address_line_2'],
                'shipping_city' => $address['city'],
                'shipping_state' => $address['state'],
                'shipping_pincode' => $address['pincode'],
                'shipping_landmark' => $address['landmark']
            ]);

            foreach ($cartItems as $item) {
                // Reduce stock
                $this->checkoutRepo->updateProductStock($item['id'], $item['quantity']);

                // Create Order Item
                $this->checkoutRepo->createOrderItem([
                    'order_id' => $orderId,
                    'product_id' => $item['id'],
                    'product_name_snapshot' => $item['name'],
                    'price_snapshot' => $item['price'],
                    'quantity' => $item['quantity'],
                    'is_digital' => $item['is_digital']
                ]);
            }

            // Create Payment Record
            $razorpayOrderId = null;
            if ($paymentMethod !== 'cod') {
                $razorpayKeyId = \HBM\Helpers\Env::get('RAZORPAY_KEY_ID');
                $razorpayKeySecret = \HBM\Helpers\Env::get('RAZORPAY_KEY_SECRET');
                if (!$razorpayKeyId || !$razorpayKeySecret) {
                    throw new Exception("Payment gateway is not configured properly.");
                }
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/orders');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                    'amount' => (int) round($totalAmount * 100), // Amount in paise
                    'currency' => 'INR',
                    'receipt' => $orderNumber
                ]));
                curl_setopt($ch, CURLOPT_USERPWD, $razorpayKeyId . ':' . $razorpayKeySecret);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                
                $response = curl_exec($ch);
                if (curl_errno($ch)) {
                    throw new Exception("Payment gateway error: " . curl_error($ch));
                }
                curl_close($ch);
                
                $rzpData = json_decode($response, true);
                if (empty($rzpData['id'])) {
                    throw new Exception("Failed to create Razorpay order.");
                }
                $razorpayOrderId = $rzpData['id'];
            }

            $this->checkoutRepo->createPaymentRecord([
                'order_id' => $orderId,
                'amount' => $totalAmount,
                'currency' => 'INR',
                'payment_method' => $paymentMethod,
                'status' => 'created',
                'razorpay_order_id' => $razorpayOrderId
            ]);

            // Clear Cart
            $this->checkoutRepo->clearCart($userId);

            $this->checkoutRepo->commit();

            if ($paymentMethod === 'cod') {
                $this->processOrderSuccess($userId, $orderId);
            }

            return [
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'total_amount' => $totalAmount,
                'payment_method' => $paymentMethod,
                'razorpay_order_id' => $razorpayOrderId,
                'razorpay_key_id' => \HBM\Helpers\Env::get('RAZORPAY_KEY_ID')
            ];

        } catch (Exception $e) {
            $this->checkoutRepo->rollBack();
            throw $e;
        }
    }

    public function verifyPayment(int $userId, array $data): array {
        $orderId = $data['order_id'] ?? null;
        $razorpayPaymentId = $data['razorpay_payment_id'] ?? null;
        $status = $data['status'] ?? 'captured'; // Mock flow

        if (!$orderId) {
            throw new Exception("Order ID is required.");
        }

        $order = $this->checkoutRepo->getOrderDetails((int)$orderId, $userId);
        if (!$order) {
            throw new Exception("Order not found or access denied.");
        }

        if ($order['payment_status'] === 'success') {
            throw new Exception("Order is already paid.");
        }

        $razorpayOrderId = $data['razorpay_order_id'] ?? null;
        $razorpaySignature = $data['razorpay_signature'] ?? null;
        
        if ($order['payment_method'] !== 'cod') {
            if (!$razorpayPaymentId || !$razorpayOrderId || !$razorpaySignature) {
                throw new Exception("Missing Razorpay payment details.");
            }
            
            $razorpayKeySecret = \HBM\Helpers\Env::get('RAZORPAY_KEY_SECRET');
            if (!$razorpayKeySecret) {
                throw new Exception("Payment gateway configuration error.");
            }
            $generatedSignature = hash_hmac('sha256', $razorpayOrderId . "|" . $razorpayPaymentId, $razorpayKeySecret);
            
            if ($generatedSignature !== $razorpaySignature) {
                \HBM\Helpers\Logger::error("Razorpay Signature mismatch! Expected: $generatedSignature, Got: $razorpaySignature");
                throw new Exception("Payment signature verification failed.");
            }
        }
        
        $this->checkoutRepo->updatePaymentStatus((int)$orderId, $status, [
            'razorpay_payment_id' => $razorpayPaymentId,
            'razorpay_signature' => $razorpaySignature
        ]);

        if ($status === 'captured') {
            $this->checkoutRepo->updateOrderStatus((int)$orderId, 'processing');
            $this->processOrderSuccess($userId, (int)$orderId);
        }

        return ['status' => 'success', 'order_id' => $orderId];
    }

    public function getUserOrders(int $userId): array {
        return $this->checkoutRepo->getUserOrders($userId);
    }

    public function getOrderDetails(int $orderId, int $userId): array {
        $order = $this->checkoutRepo->getOrderDetails($orderId, $userId);
        if (!$order) {
            throw new Exception("Order not found.");
        }
        return $order;
    }

    private function processOrderSuccess(int $userId, int $orderId): void {
        try {
            $order = $this->checkoutRepo->getOrderDetails($orderId, $userId);
            if (!$order) return;

            $items = $order['items'] ?? [];
            
            // Generate Invoice PDF
            $invoiceData = $this->invoiceService->generateInvoicePdf($order, $items);
            
            // Save to user_documents
            $stmt = $this->checkoutRepo->getDb()->prepare("
                INSERT INTO user_documents (user_id, title, document_type, file_path)
                VALUES (:user_id, :title, 'invoice', :file_path)
            ");
            $stmt->execute([
                'user_id' => $userId,
                'title' => 'Invoice ' . $order['order_number'],
                'file_path' => '/storage/invoices/' . $invoiceData['file_name']
            ]);

            // Email it
            $toEmail = $order['shipping_email'] ?? null;
            if ($toEmail && filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
                $htmlBody = EmailTemplateService::getOrderConfirmationEmail($order);
                $this->emailService->sendEmail(
                    $toEmail, 
                    "Order Confirmation - " . $order['order_number'], 
                    $htmlBody, 
                    $order['shipping_first_name'] ?? '',
                    [
                        [
                            'name' => $invoiceData['file_name'],
                            'content' => $invoiceData['content'],
                            'mime_type' => 'application/pdf'
                        ]
                    ]
                );
            }
        } catch (\Throwable $e) {
            \HBM\Helpers\Logger::error("Failed to process order success for Order ID: " . $orderId, ['error' => $e->getMessage()]);
        }
    }
    public function processWebhook(string $payload, string $signature): array {
        $webhookSecret = \HBM\Helpers\Env::get('RAZORPAY_WEBHOOK_SECRET');
        if (!$webhookSecret) {
            throw new Exception("Webhook secret is not configured.");
        }

        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);
        if (!hash_equals($expectedSignature, $signature)) {
            \HBM\Helpers\Logger::error("Webhook signature mismatch.");
            throw new Exception("Invalid webhook signature.");
        }

        $data = json_decode($payload, true);
        if (!$data || !isset($data['event'])) {
            throw new Exception("Invalid webhook payload.");
        }

        $event = $data['event'];
        $paymentData = $data['payload']['payment']['entity'] ?? null;
        $orderIdRzp = $data['payload']['order']['entity']['id'] ?? ($paymentData['order_id'] ?? null);

        if (!$paymentData || !$orderIdRzp) {
            throw new Exception("Missing essential payment/order entity in payload.");
        }

        $db = $this->checkoutRepo->getDb();
        $stmt = $db->prepare("SELECT id, order_status, payment_status, user_id FROM orders WHERE id = (SELECT order_id FROM payments WHERE razorpay_order_id = :rzp_order_id LIMIT 1)");
        $stmt->execute(['rzp_order_id' => $orderIdRzp]);
        $order = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$order) {
            \HBM\Helpers\Logger::info("Webhook order not found locally for Razorpay Order: " . $orderIdRzp);
            return ['status' => 'ignored', 'reason' => 'Order not found'];
        }

        $localOrderId = (int)$order['id'];
        $userId = (int)$order['user_id'];
        $razorpayPaymentId = $paymentData['id'];

        if ($order['payment_status'] === 'success' || $order['payment_status'] === 'failed') {
            return ['status' => 'ignored', 'reason' => 'Order already processed'];
        }

        if ($event === 'payment.captured' || $event === 'order.paid') {
            try {
                $this->checkoutRepo->beginTransaction();
                $this->checkoutRepo->updatePaymentStatus($localOrderId, 'captured', [
                    'razorpay_payment_id' => $razorpayPaymentId
                ]);
                $this->checkoutRepo->updateOrderStatus($localOrderId, 'processing');
                $this->checkoutRepo->commit();
                
                $this->processOrderSuccess($userId, $localOrderId);
                return ['status' => 'success', 'message' => 'Payment captured and processed'];
            } catch (Exception $e) {
                $this->checkoutRepo->rollBack();
                throw $e;
            }
        } elseif ($event === 'payment.failed') {
            $this->checkoutRepo->updatePaymentStatus($localOrderId, 'failed', [
                'razorpay_payment_id' => $razorpayPaymentId
            ]);
            return ['status' => 'success', 'message' => 'Payment marked as failed'];
        }

        return ['status' => 'ignored', 'reason' => 'Unsupported event type'];
    }
}
