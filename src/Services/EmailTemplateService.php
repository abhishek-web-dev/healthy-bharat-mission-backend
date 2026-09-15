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
        $content = "
            <h2 style='color:#052b14; margin-bottom:15px;'>Thank you for your order!</h2>
            <p style='color:#475569; margin-bottom:15px;'>Your order <strong>#{$orderNumber}</strong> has been successfully placed.</p>
            <p style='color:#475569; margin-bottom:15px;'>We have attached your invoice to this email for your records.</p>
            <p style='color:#475569;'>You can also download your invoice and view your order details anytime from your account dashboard.</p>
        ";
        return self::getBaseTemplate($title, $content);
    }
}
