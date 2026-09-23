<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/src/Services/EmailTemplateService.php';

use HBM\Services\EmailTemplateService;

echo "--- Testing EmailTemplateService ---\n";
try {
    $welcome = EmailTemplateService::getWelcomeEmail();
    echo "Welcome email template loaded (len: " . strlen($welcome) . ")\n";
    $payment = EmailTemplateService::getPaymentConfirmationEmail(['order_number' => 'ORD-123']);
    echo "Payment email template loaded (len: " . strlen($payment) . ")\n";
    $contact = EmailTemplateService::getContactInquiryEmail('Test', 'General');
    echo "Contact email template loaded (len: " . strlen($contact) . ")\n";
    $support = EmailTemplateService::getCustomerSupportEmail('Test');
    echo "Support email template loaded (len: " . strlen($support) . ")\n";
    $program = EmailTemplateService::getProgramEmail('Test');
    echo "Program email template loaded (len: " . strlen($program) . ")\n";
    $appointment = EmailTemplateService::getAppointmentEmail('Test');
    echo "Appointment email template loaded (len: " . strlen($appointment) . ")\n";
} catch (Exception $e) {
    echo "Error in Templates: " . $e->getMessage() . "\n";
}
?>
