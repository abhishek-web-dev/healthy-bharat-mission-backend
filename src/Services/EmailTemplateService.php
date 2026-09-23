<?php
namespace HBM\Services;

class EmailTemplateService {
    
    private static function getBaseTemplate(string $title, string $content): string {
        $primaryColor = '#064e3b';
        $backgroundColor = '#f8fafc';
        
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body {
                    margin: 0;
                    padding: 40px 20px;
                    background-color: {$backgroundColor};
                    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                    color: #333333;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    background-color: #ffffff;
                    border-radius: 8px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
                    overflow: hidden;
                    border: 1px solid #eaeaea;
                }
                .header {
                    padding: 30px 40px 10px;
                }
                .header h1 {
                    color: {$primaryColor};
                    font-size: 22px;
                    margin: 0;
                    font-weight: 600;
                }
                .divider {
                    height: 2px;
                    background-color: {$primaryColor};
                    margin: 15px 40px 25px;
                }
                .content {
                    padding: 0 40px 30px;
                    font-size: 15px;
                    line-height: 1.6;
                }
                .otp-box {
                    background-color: #f9fafb;
                    border-left: 4px solid {$primaryColor};
                    padding: 20px;
                    margin: 30px 0;
                    text-align: center;
                    border-radius: 0 6px 6px 0;
                }
                .otp-code {
                    font-size: 32px;
                    font-weight: bold;
                    color: {$primaryColor};
                    letter-spacing: 4px;
                    margin: 0;
                }
                .btn-container {
                    text-align: center;
                    margin: 30px 0;
                }
                .btn {
                    display: inline-block;
                    background-color: {$primaryColor};
                    color: #ffffff;
                    text-decoration: none;
                    padding: 12px 30px;
                    border-radius: 6px;
                    font-weight: bold;
                    font-size: 16px;
                }
                .footer {
                    padding-top: 15px;
                    margin-top: 30px;
                    font-size: 14px;
                    color: #555555;
                }
                .footer-brand {
                    font-weight: bold;
                    color: #222222;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>{$title}</h1>
                </div>
                <div class="divider"></div>
                <div class="content">
                    {$content}
                    
                    <div class="footer">
                        Best Regards,<br>
                        <span class="footer-brand">The Healthy Bharat Mission Team</span>
                    </div>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }

    public static function getOtpEmail(string $type, string $code): string {
        $title = "Verify Your Email Address";
        $context = "complete your " . $type;
        
        if ($type === 'registration') {
            $context = "verify your email address";
        } elseif ($type === 'login') {
            $context = "complete your login";
        }
        
        $content = <<<HTML
        <p>Hello,</p>
        <p>Please use the verification code below to {$context}. This code is valid for 10 minutes.</p>
        <div class="otp-box">
            <p class="otp-code">{$code}</p>
        </div>
        <p>If you did not request this code, you can safely ignore this email.</p>
        HTML;

        return self::getBaseTemplate($title, $content);
    }

    public static function getAdminOtpEmail(string $code): string {
        $title = "HBM Admin Login Verification Code";
        
        $content = <<<HTML
        <p>Hello,</p>
        <p>Your Healthy Bharat Mission Admin Panel verification code is:</p>
        <div class="otp-box">
            <p class="otp-code">{$code}</p>
        </div>
        <p>This code expires in 5 minutes.</p>
        <p>If you did not attempt to sign in, please ignore this email.</p>
        HTML;

        return self::getBaseTemplate($title, $content);
    }

    public static function getPasswordResetEmail(string $resetLink): string {
        $title = "Reset Your Password";
        
        $content = <<<HTML
        <p>Hello,</p>
        <p>We received a request to reset the password associated with your account.</p>
        <p>You can reset your password by clicking the button below. This link will expire in 1 hour.</p>
        <div class="btn-container">
            <a href="{$resetLink}" class="btn" style="color: #ffffff;">Reset Password</a>
        </div>
        <p>If you did not request a password reset, you can safely ignore this email and your password will remain unchanged.</p>
        HTML;

        return self::getBaseTemplate($title, $content);
    }

    public static function getOrderConfirmationEmail(array $order): string {
        $title = "Order Confirmation";
        $orderNumber = htmlspecialchars($order['order_number'] ?? '');
        
        $hasDigitalProducts = false;
        if (!empty($order['items']) && is_array($order['items'])) {
            foreach ($order['items'] as $item) {
                if (!empty($item['is_digital'])) {
                    $hasDigitalProducts = true;
                    break;
                }
            }
        }
        
        $digitalNotice = '';
        if ($hasDigitalProducts) {
            $digitalNotice = "<div style='background-color: #f0fdf4; border-left: 4px solid #106e39; padding: 15px; margin: 20px 0;'>
                <strong>Digital Downloads Available!</strong><br>
                Your digital products are ready for download. Please log in to your account and go to <strong>Dashboard > My Orders</strong> to access your files.
            </div>";
        }
        
        $content = "
            <h2 style='color:#052b14; margin-bottom:15px;'>Thank you for your order!</h2>
            <p style='color:#475569; margin-bottom:15px;'>Your order <strong>#{$orderNumber}</strong> has been successfully placed.</p>
            {$digitalNotice}
            <p style='color:#475569; margin-bottom:15px;'>We have attached your invoice to this email for your records.</p>
            <p style='color:#475569;'>You can also download your invoice and view your order details anytime from your account dashboard.</p>
        ";
        return self::getBaseTemplate($title, $content);
    }

    public static function getWelcomeEmail(): string {
        $title = "Welcome to Healthy Bharat Mission!";
        $content = <<<HTML
        <p>Hello,</p>
        <p>Welcome to Healthy Bharat Mission! We're thrilled to have you join our community dedicated to health and wellness.</p>
        <p>You can now log in to your dashboard to track your orders, manage your health profile, and explore our programs.</p>
        <div class="btn-container">
            <a href="https://healthybharatmission.com/auth/login" class="btn" style="color: #ffffff;">Go to Dashboard</a>
        </div>
        HTML;
        return self::getBaseTemplate($title, $content);
    }

    public static function getPaymentConfirmationEmail(array $order): string {
        $title = "Payment Received";
        $orderNumber = htmlspecialchars($order['order_number'] ?? '');
        $content = <<<HTML
        <p>Hello,</p>
        <p>We have successfully received your payment for Order <strong>#{$orderNumber}</strong>.</p>
        <p>Thank you for your purchase. We are now processing your order and will notify you once it's on its way.</p>
        HTML;
        return self::getBaseTemplate($title, $content);
    }

    public static function getContactInquiryEmail(string $name, string $subject): string {
        $title = "We've Received Your Inquiry";
        $content = <<<HTML
        <p>Hello {$name},</p>
        <p>Thank you for reaching out to us regarding "{$subject}".</p>
        <p>We have received your message and our team will get back to you as soon as possible, usually within 1-2 business days.</p>
        <p>If you have any urgent concerns, please feel free to reply directly to this email.</p>
        HTML;
        return self::getBaseTemplate($title, $content);
    }

    public static function getCustomerSupportEmail(string $name): string {
        $title = "Support Request Received";
        $content = <<<HTML
        <p>Hello {$name},</p>
        <p>Thank you for contacting Healthy Bharat Mission Customer Support.</p>
        <p>We have received your support request and a member of our team is reviewing it. We will be in touch with you shortly.</p>
        HTML;
        return self::getBaseTemplate($title, $content);
    }

    public static function getProgramEmail(string $name): string {
        $title = "Program Inquiry Received";
        $content = <<<HTML
        <p>Hello {$name},</p>
        <p>Thank you for your interest in our Health Programs & Courses.</p>
        <p>Our program coordinators are reviewing your inquiry and will contact you with more details shortly to help you get started on your health journey.</p>
        HTML;
        return self::getBaseTemplate($title, $content);
    }

    public static function getAppointmentEmail(string $name): string {
        $title = "Consultation Request Received";
        $content = <<<HTML
        <p>Hello {$name},</p>
        <p>Thank you for requesting a consultation with us.</p>
        <p>Our team has received your request and will contact you shortly to confirm the date and time of your appointment.</p>
        HTML;
        return self::getBaseTemplate($title, $content);
    }
}
