<?php
namespace HBM\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use HBM\Helpers\Logger;
use Exception;

class InvoiceService {
    
    private string $storagePath;

    public function __construct() {
        $this->storagePath = __DIR__ . '/../../storage/invoices/';
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }

    public function generateInvoicePdf(array $orderData, array $orderItems): array {
        try {
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');
            
            $dompdf = new Dompdf($options);
            
            $html = $this->getInvoiceHtml($orderData, $orderItems);
            $dompdf->loadHtml($html);
            
            // Setup the paper size and orientation
            $dompdf->setPaper('A4', 'portrait');
            
            // Render the HTML as PDF
            $dompdf->render();
            
            $pdfContent = $dompdf->output();
            $fileName = 'INV-ORD-' . $orderData['order_number'] . '.pdf';
            $filePath = $this->storagePath . $fileName;
            
            file_put_contents($filePath, $pdfContent);
            
            return [
                'file_path' => $filePath,
                'file_name' => $fileName,
                'content' => base64_encode($pdfContent)
            ];
            
        } catch (Exception $e) {
            Logger::error("Failed to generate invoice for order: " . $orderData['order_number'], ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getInvoiceHtml(array $order, array $items): string {
        $logoPath = __DIR__ . '/../../../frontend/assets/images/logo.png';
        $logoHtml = "<h2 style=\"margin:0; color:#106e39; font-size:24px;\">Healthy Mission Bharat</h2>";
        if (file_exists($logoPath)) {
            $base64 = base64_encode(file_get_contents($logoPath));
            $logoHtml = "<img src=\"data:image/png;base64,{$base64}\" style=\"max-height: 60px; margin-bottom: 10px;\" alt=\"Logo\">";
        }
        
        $date = date('F j, Y', strtotime($order['created_at']));
        $invoiceNumber = 'INV-ORD-' . $order['order_number'];
        $status = strtoupper($order['order_status'] ?? 'COMPLETED');
        
        // Calculate Subtotal and Shipping
        $subtotal = number_format($order['subtotal'] ?? $order['total_amount'], 2);
        $shipping = number_format($order['shipping_fee'] ?? 0, 2);
        $tax = number_format(0, 2); // Tax is 0.00 in the reference
        $total = number_format($order['total_amount'] ?? 0, 2);
        
        $customerName = htmlspecialchars(($order['shipping_first_name'] ?? '') . ' ' . ($order['shipping_last_name'] ?? ''));
        $address1 = htmlspecialchars($order['shipping_address_line_1'] ?? '');
        $address2 = htmlspecialchars($order['shipping_address_line_2'] ?? '');
        $address2Html = $address2 ? "{$address2}<br>" : '';
        $cityStateZip = htmlspecialchars(($order['shipping_city'] ?? '') . ', ' . ($order['shipping_state'] ?? '') . ' ' . ($order['shipping_pincode'] ?? ''));
        $country = "India"; 
        
        $itemsHtml = '';
        foreach ($items as $item) {
            $name = htmlspecialchars($item['product_name_snapshot']);
            $qty = (int)$item['quantity'];
            $unitPrice = number_format($item['price_snapshot'], 2);
            $amount = number_format($item['price_snapshot'] * $qty, 2);
            $sku = htmlspecialchars($item['sku'] ?? 'N/A');

            $itemsHtml .= "
                <tr>
                    <td>
                        <div style='color: #1e293b; font-weight: bold; font-size: 14px; margin-bottom: 4px;'>{$name}</div>
                        <div style='color: #64748b; font-size: 12px;'>SKU: {$sku}</div>
                    </td>
                    <td class='center' style='color: #334155;'>{$qty}</td>
                    <td class='right' style='color: #334155;'>₹{$unitPrice}</td>
                    <td class='right' style='font-weight: bold; color: #1e293b;'>₹{$amount}</td>
                </tr>
            ";
        }

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Invoice</title>
            <style>
                @page { margin: 0px; }
                html, body { margin: 0; padding: 0; }
                body { font-family: 'DejaVu Sans', sans-serif; color: #334155; font-size: 13px; line-height: 1.4; }
                .container { padding: 40px 50px; }
                .header { width: 100%; margin-bottom: 15px; }
                .header td { vertical-align: top; }
                .company-info { color: #64748b; font-size: 12px; line-height: 1.5; }
                .invoice-title { font-size: 32px; font-weight: 900; color: #1e293b; text-align: right; margin-bottom: 8px; letter-spacing: 1px; }
                .invoice-details { text-align: right; font-size: 12px; line-height: 1.6; color: #334155; }
                .invoice-details strong { color: #1e293b; }
                
                .divider { border-top: 2px solid #1e293b; margin: 15px 0 25px 0; }
                
                .addresses { width: 100%; margin-bottom: 30px; border-spacing: 0; }
                .addresses td { width: 50%; vertical-align: top; padding-right: 20px; }
                .address-title { font-size: 12px; font-weight: bold; color: #1e293b; letter-spacing: 1px; margin-bottom: 8px; padding-bottom: 5px; border-bottom: 1px solid #e2e8f0; text-transform: uppercase; }
                .customer-name { font-size: 14px; font-weight: bold; color: #1e293b; margin-bottom: 3px; }
                .customer-address { color: #64748b; font-size: 12px; line-height: 1.5; }
                
                .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                .items-table th { text-align: left; font-size: 11px; font-weight: bold; color: #1e293b; letter-spacing: 1px; padding: 8px 0; border-bottom: 2px solid #e2e8f0; text-transform: uppercase; }
                .items-table th.center { text-align: center; }
                .items-table th.right { text-align: right; }
                .items-table td { padding: 8px 0; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
                
                .totals-wrapper { width: 100%; margin-bottom: 20px; }
                .totals-wrapper td { vertical-align: top; }
                .totals-table { width: 100%; border-collapse: collapse; border-top: 1px solid #e2e8f0; }
                .totals-table td { padding: 5px 0; color: #64748b; font-size: 12px; }
                .totals-table td.amount { text-align: right; color: #1e293b; font-weight: 500; }
                .total-row td { padding: 10px 0; font-size: 15px; font-weight: bold; color: #0f172a; }
                .total-row td.amount { font-weight: bold; font-size: 15px; }
                .total-row-bottom { border-bottom: 1px solid #e2e8f0; }
                
                .footer { text-align: center; margin-top: 10px; }
                .footer-thanks { font-size: 13px; font-weight: bold; color: #1e293b; margin-bottom: 3px; }
                .footer-support { color: #64748b; font-size: 11px; }
            </style>
        </head>
        <body>
            <div class="container">
                <table class="header">
                    <tr>
                        <td>
                            {$logoHtml}
                            <div class="company-info">
                                123 Health Avenue<br>
                                Suite 400<br>
                                New Delhi, DL 110001<br>
                                support@healthymissionbharat.com
                            </div>
                        </td>
                        <td>
                            <div class="invoice-title">INVOICE</div>
                            <div class="invoice-details">
                                <strong>Invoice #:</strong> {$invoiceNumber}<br>
                                <strong>Order Date:</strong> {$date}<br>
                                <strong>Status:</strong> {$status}
                            </div>
                        </td>
                    </tr>
                </table>
                <div class="divider"></div>

                <table class="addresses">
                    <tr>
                        <td>
                            <div class="address-title">BILLED TO</div>
                            <div class="customer-name">{$customerName}</div>
                            <div class="customer-address">
                                {$address1}<br>
                                {$address2Html}
                                {$cityStateZip}<br>
                                {$country}
                            </div>
                        </td>
                        <td>
                            <div class="address-title">SHIPPED TO</div>
                            <div class="customer-name">{$customerName}</div>
                            <div class="customer-address">
                                {$address1}<br>
                                {$address2Html}
                                {$cityStateZip}<br>
                                {$country}
                            </div>
                        </td>
                    </tr>
                </table>

                <table class="items-table">
                    <thead>
                        <tr>
                            <th>DESCRIPTION</th>
                            <th class="center">QTY</th>
                            <th class="right">UNIT PRICE</th>
                            <th class="right">AMOUNT</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$itemsHtml}
                    </tbody>
                </table>

                <table class="totals-wrapper">
                    <tr>
                        <td style="width: 55%;"></td>
                        <td style="width: 45%;">
                            <table class="totals-table">
                                <tr>
                                    <td style="padding-top: 15px;">Subtotal</td>
                                    <td class="amount" style="padding-top: 15px;">₹{$subtotal}</td>
                                </tr>
                                <tr>
                                    <td>Shipping</td>
                                    <td class="amount">₹{$shipping}</td>
                                </tr>
                                <tr>
                                    <td style="padding-bottom: 15px;">Tax</td>
                                    <td class="amount" style="padding-bottom: 15px;">₹{$tax}</td>
                                </tr>
                                <tr class="total-row">
                                    <td>Total</td>
                                    <td class="amount">₹{$total}</td>
                                </tr>
                                <tr>
                                    <td colspan="2" class="total-row-bottom"></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                <div class="footer">
                    <div class="footer-thanks">Thank you for your business!</div>
                    <div class="footer-support">If you have any questions concerning this invoice, contact our support team.</div>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }
}
