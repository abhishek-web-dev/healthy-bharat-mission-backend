<?php

namespace HBM\Controllers;

use HBM\Services\CheckoutService;
use HBM\Helpers\Response;
use Exception;

class CheckoutController {
    private CheckoutService $checkoutService;

    public function __construct() {
        $this->checkoutService = new CheckoutService();
    }

    public function getAddresses(): void {
        global $authUser;
        try {
            $addresses = $this->checkoutService->getUserAddresses($authUser['id']);
            Response::success('Addresses fetched successfully.', $addresses);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function saveAddress(): void {
        global $authUser;
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        try {
            $address = $this->checkoutService->saveAddress($authUser['id'], $input);
            Response::success('Address saved successfully.', $address, 201);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function updateAddress(int $id): void {
        global $authUser;
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        try {
            $address = $this->checkoutService->updateAddress($authUser['id'], $id, $input);
            Response::success('Address updated successfully.', $address);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function deleteAddress(int $id): void {
        global $authUser;
        try {
            $this->checkoutService->deleteAddress($authUser['id'], $id);
            Response::success('Address deleted successfully.');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function createOrder(): void {
        global $authUser;
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        try {
            $order = $this->checkoutService->createOrder($authUser['id'], $input);
            Response::success('Order created successfully.', $order, 201);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function verifyPayment(): void {
        global $authUser;
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        try {
            $result = $this->checkoutService->verifyPayment($authUser['id'], $input);
            Response::success('Payment verified successfully.', $result);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function getOrders(): void {
        global $authUser;
        try {
            $orders = $this->checkoutService->getUserOrders($authUser['id']);
            Response::success('Orders fetched successfully.', ['orders' => $orders]);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function getOrderDetails(int $id): void {
        global $authUser;
        try {
            $order = $this->checkoutService->getOrderDetails($id, $authUser['id']);
            Response::success('Order details fetched successfully.', $order);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 404);
        }
    }
    public function downloadInvoice(int $orderId): void {
        global $authUser;
        try {
            if (!$authUser) {
                Response::error("Unauthorized", 401);
                return;
            }

            // Verify order belongs to user
            $order = $this->checkoutService->getOrderDetails($orderId, $authUser['id']);
            
            // Check if document exists
            $db = \HBM\Core\Database::getConnection();
            $stmt = $db->prepare("
                SELECT file_path FROM user_documents 
                WHERE user_id = :user_id AND title = :title AND document_type = 'invoice'
            ");
            $stmt->execute([
                'user_id' => $authUser['id'],
                'title' => 'Invoice ' . $order['order_number']
            ]);
            $document = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$document || !file_exists(__DIR__ . '/../../' . ltrim($document['file_path'], '/'))) {
                // Generate invoice on the fly for old orders
                $invoiceService = new \HBM\Services\InvoiceService();
                $items = $order['items'] ?? [];
                $invoiceData = $invoiceService->generateInvoicePdf($order, $items);
                
                // If there wasn't a document record, insert it
                if (!$document) {
                    $insertStmt = $db->prepare("
                        INSERT INTO user_documents (user_id, title, document_type, file_path)
                        VALUES (:user_id, :title, 'invoice', :file_path)
                    ");
                    $insertStmt->execute([
                        'user_id' => $authUser['id'],
                        'title' => 'Invoice ' . $order['order_number'],
                        'file_path' => '/storage/invoices/' . $invoiceData['file_name']
                    ]);
                }
                $document = ['file_path' => '/storage/invoices/' . $invoiceData['file_name']];
            }

            $filePath = __DIR__ . '/../../' . ltrim($document['file_path'], '/');
            
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="Invoice-'.$order['order_number'].'.pdf"');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;

        } catch (\Throwable $e) {
            file_put_contents(__DIR__ . '/../../../error_log.txt', "ERROR: " . $e->getMessage() . "\nTrace:\n" . $e->getTraceAsString());
            Response::error($e->getMessage(), 400);
        }
    }
}
