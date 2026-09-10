<?php
namespace HBM\Services;

use HBM\Helpers\Logger;
use Exception;

class EmailService {
    
    private string $apiUrl;
    private string $apiKey;
    private string $fromAddress;
    private string $fromName;

    public function __construct() {
        $this->apiUrl = "https://" . ($_ENV['MAIL_HOST'] ?? 'api.zeptomail.in') . "/v1.1/email";
        $this->apiKey = $_ENV['MAIL_API_KEY'] ?? '';
        $this->fromAddress = $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@healthybharatmission.com';
        $this->fromName = $_ENV['MAIL_FROM_NAME'] ?? 'Healthy Bharat Mission';
    }

    /**
     * Send an email using ZeptoMail REST API
     * 
     * @param string $toEmail
     * @param string $subject
     * @param string $htmlBody
     * @param string $toName
     * @return bool
     * @throws Exception
     */
    public function sendEmail(string $toEmail, string $subject, string $htmlBody, string $toName = ""): bool {
        if (empty($this->apiKey)) {
            Logger::error("EmailService: Missing ZeptoMail API Key in .env");
            return false;
        }

        $payload = [
            "from" => [
                "address" => $this->fromAddress,
                "name" => $this->fromName
            ],
            "to" => [
                [
                    "email_address" => [
                        "address" => $toEmail,
                        "name" => $toName
                    ]
                ]
            ],
            "subject" => $subject,
            "htmlbody" => $htmlBody
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Accept: application/json",
            "Content-Type: application/json",
            "Authorization: Zoho-enczapikey " . $this->apiKey
        ]);

        // In development, you might want to set this to false if SSL issues occur.
        // But for production always verify SSL.
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); 

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            Logger::error("EmailService: CURL Error sending email to $toEmail", ['error' => $error]);
            throw new Exception("Failed to send email. Communication error.");
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            Logger::info("EmailService: Email sent successfully to $toEmail");
            return true;
        } else {
            Logger::error("EmailService: API Error sending email", ['http_code' => $httpCode, 'response' => $response]);
            return false;
        }
    }
}
