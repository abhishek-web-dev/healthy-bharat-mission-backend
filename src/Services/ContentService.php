<?php

namespace HBM\Services;

use HBM\Repositories\ContentRepository;
use HBM\Services\EmailService;
use Exception;

class ContentService {
    private ContentRepository $repo;
    private EmailService $emailService;

    public function __construct() {
        $this->repo = new ContentRepository();
        $this->emailService = new EmailService();
    }

    public function getHealthConditions(bool $public = true, int $page = 1, int $perPage = 12): array {
        return $this->repo->getHealthConditions($public, $page, $perPage);
    }

    public function getHealthConditionBySlug(string $slug, bool $public = true): array {
        $condition = $this->repo->getHealthConditionBySlug($slug, $public);
        if (!$condition) {
            throw new Exception("Health condition not found", 404);
        }
        return $condition;
    }

    public function getPrograms(bool $public = true, int $page = 1, int $perPage = 12): array {
        return $this->repo->getPrograms($public, $page, $perPage);
    }

    public function getProgramBySlug(string $slug, bool $public = true): array {
        $program = $this->repo->getProgramBySlug($slug, $public);
        if (!$program) {
            throw new Exception("Program not found", 404);
        }
        return $program;
    }

    
    public function createProgram(array $data): int {
        return $this->repo->createProgram($data);
    }

    public function updateProgram(int $id, array $data): void {
        $this->repo->updateProgram($id, $data);
    }

    public function getArticles(array $filters = [], int $page = 1, int $perPage = 12): array {
        return $this->repo->getArticles($filters, $page, $perPage);
    }

    public function getArticleBySlug(string $slug, bool $public = true): array {
        $article = $this->repo->getArticleBySlug($slug, $public);
        if (!$article) {
            throw new Exception("Article not found", 404);
        }
        return $article;
    }

    
    public function getArticleById(int $id): array {
        $article = $this->repo->getArticleById($id);
        if (!$article) throw new Exception("Article not found", 404);
        return $article;
    }


    private function cleanSlug(string $title): string {
        $slug = strtolower($title);
        $slug = preg_replace('/^\d+\s+/', '', $slug);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug;
    }

    public function createArticle(array $data, int $authorId): int {
        $data['slug'] = $this->cleanSlug($data['title']);
        return $this->repo->createArticle($data, $authorId);
    }

    public function updateArticle(int $id, array $data): void {
        if (isset($data['title'])) {
            $data['slug'] = $this->cleanSlug($data['title']);
        }
        $this->repo->updateArticle($id, $data);
    }

    public function getArticleCategories(): array {
        return $this->repo->getArticleCategories();
    }

    public function getArticleTags(): array {
        return $this->repo->getArticleTags();
    }

    
    public function getAdminHealthConditions(int $page = 1, int $perPage = 50): array {
        return $this->repo->getAdminHealthConditions($page, $perPage);
    }

    public function createHealthCondition(array $data): int {
        return $this->repo->createHealthCondition($data);
    }

    public function updateHealthCondition(int $id, array $data): void {
        $this->repo->updateHealthCondition($id, $data);
    }

    
    
    public function getAdminInquiries(int $page = 1, int $perPage = 50): array {
        return $this->repo->getAdminInquiries($page, $perPage);
    }
    public function getInquiryById(int $id): array {
        $inquiry = $this->repo->getInquiryById($id);
        if (!$inquiry) {
            throw new \Exception("Inquiry not found.", 404);
        }
        return $inquiry;
    }
    public function updateInquiryStatus(int $id, string $status): void {
        $this->repo->updateInquiryStatus($id, $status);
    }
    public function getAdminSubscribers(int $page = 1, int $perPage = 50): array {
        return $this->repo->getAdminSubscribers($page, $perPage);
    }
    public function updateSubscriberStatus(int $id, int $is_active): void {
        $this->repo->updateSubscriberStatus($id, $is_active);
    }

    public function getAdminFaqs(int $page = 1, int $perPage = 50): array {
        return $this->repo->getAdminFaqs($page, $perPage);
    }

    public function createFaq(array $data): int {
        return $this->repo->createFaq($data);
    }

    public function updateFaq(int $id, array $data): void {
        $this->repo->updateFaq($id, $data);
    }

    public function getFaqs(bool $public = true): array {
        return $this->repo->getFaqs($public);
    }

    public function getSuccessStories(bool $public = true, int $page = 1, int $perPage = 12): array {
        return $this->repo->getSuccessStories($public, $page, $perPage);
    }


    public function submitContactInquiry(array $data, string $ip = '0.0.0.0'): bool {
        if (!$this->repo->checkRateLimit($ip)) {
            throw new Exception("Too many requests. Please try again later.", 429);
        }

        if (empty($data['name']) || empty($data['message'])) {
            throw new Exception("Please fill all required fields (Name and Message).", 422);
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            // Ignore strict email validation if it's our frontend fallback
            if (!str_ends_with($data['email'], '@no-email.provided')) {
                throw new Exception("Invalid email address.", 422);
            }
        }
        
        if (empty($data['email'])) {
            $data['email'] = 'no-email@provided.local';
        }
        
        if (empty($data['subject'])) {
            $data['subject'] = 'general';
        }


        if (!empty($data['phone'])) {
            if (!preg_match('/^[6-9]\d{9}$/', $data['phone'])) {
                throw new Exception("Please enter a valid 10-digit Indian mobile number starting with 6-9.", 422);
            }
        }

        // Basic sanitize
        $data['name'] = htmlspecialchars(strip_tags(trim($data['name'])));
        $data['message'] = htmlspecialchars(strip_tags(trim($data['message'])));
        
        if (strlen($data['message']) > 2000) {
             throw new Exception("Message is too long. Maximum 2000 characters.", 422);
        }
        
        $this->repo->createContactInquiry($data);
        
        // Send autoresponder email to the user
        if (!empty($data['email']) && !str_ends_with($data['email'], '@no-email.provided')) {
            try {
                $subjKey = strtolower($data['subject']);
                
                if (str_contains($subjKey, 'consultation') || str_contains($subjKey, 'appointment')) {
                    $userSubject = "Consultation Request Received";
                    $userMsg = \HBM\Services\EmailTemplateService::getAppointmentEmail($data['name']);
                } elseif (str_contains($subjKey, 'program') || str_contains($subjKey, 'course')) {
                    $userSubject = "Program Inquiry Received";
                    $userMsg = \HBM\Services\EmailTemplateService::getProgramEmail($data['name']);
                } elseif (str_contains($subjKey, 'support') || str_contains($subjKey, 'help')) {
                    $userSubject = "Support Request Received";
                    $userMsg = \HBM\Services\EmailTemplateService::getCustomerSupportEmail($data['name']);
                } else {
                    $userSubject = "We've Received Your Inquiry";
                    $userMsg = \HBM\Services\EmailTemplateService::getContactInquiryEmail($data['name'], $data['subject']);
                }
                
                $this->emailService->sendEmail($data['email'], $userSubject, $userMsg);
            } catch (\Exception $e) {
                error_log("Failed to send user autoresponder email: " . $e->getMessage());
            }
        }

        // Send email notification to admin
        try {
            $subject = "New Contact Inquiry: " . $data['subject'];
            $date = date('F j, Y, g:i a');
            $phone = $data['phone'] ?? 'N/A';
            $body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;'>
                    <div style='background-color: #106e39; padding: 20px; text-align: center;'>
                        <h2 style='color: #ffffff; margin: 0; font-size: 20px;'>Healthy Bharat Mission</h2>
                    </div>
                    <div style='padding: 30px; background-color: #ffffff;'>
                        <h3 style='color: #111827; margin-top: 0; margin-bottom: 20px; font-size: 18px; border-bottom: 2px solid #f3f4f6; padding-bottom: 10px;'>New Contact Inquiry Received</h3>
                        
                        <table style='width: 100%; border-collapse: collapse;'>
                            <tr>
                                <td style='padding: 10px 0; border-bottom: 1px solid #f3f4f6; color: #6b7280; width: 120px; font-weight: bold;'>Name</td>
                                <td style='padding: 10px 0; border-bottom: 1px solid #f3f4f6; color: #111827;'>{$data['name']}</td>
                            </tr>
                            <tr>
                                <td style='padding: 10px 0; border-bottom: 1px solid #f3f4f6; color: #6b7280; font-weight: bold;'>Email</td>
                                <td style='padding: 10px 0; border-bottom: 1px solid #f3f4f6; color: #111827;'>
                                    <a href='mailto:{$data['email']}' style='color: #106e39; text-decoration: none;'>{$data['email']}</a>
                                </td>
                            </tr>
                            <tr>
                                <td style='padding: 10px 0; border-bottom: 1px solid #f3f4f6; color: #6b7280; font-weight: bold;'>Phone</td>
                                <td style='padding: 10px 0; border-bottom: 1px solid #f3f4f6; color: #111827;'>{$phone}</td>
                            </tr>
                            <tr>
                                <td style='padding: 10px 0; border-bottom: 1px solid #f3f4f6; color: #6b7280; font-weight: bold;'>Subject</td>
                                <td style='padding: 10px 0; border-bottom: 1px solid #f3f4f6; color: #111827;'>{$data['subject']}</td>
                            </tr>
                            <tr>
                                <td style='padding: 10px 0; border-bottom: 1px solid #f3f4f6; color: #6b7280; font-weight: bold;'>Date Submitted</td>
                                <td style='padding: 10px 0; border-bottom: 1px solid #f3f4f6; color: #111827;'>{$date}</td>
                            </tr>
                        </table>
                        
                        <div style='margin-top: 25px; background-color: #f9fafb; padding: 20px; border-radius: 6px; border: 1px solid #f3f4f6;'>
                            <h4 style='margin-top: 0; margin-bottom: 10px; color: #4b5563; font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em;'>Message</h4>
                            <p style='margin: 0; color: #111827; line-height: 1.6; white-space: pre-wrap;'>{$data['message']}</p>
                        </div>
                    </div>
                    <div style='background-color: #f3f4f6; padding: 15px; text-align: center; color: #6b7280; font-size: 12px;'>
                        <p style='margin: 0;'>This is an automated notification from the Healthy Bharat Mission website.</p>
                    </div>
                </div>
            ";
            $emailSent = $this->emailService->sendEmail('abhishek@brandkingmedia.com', $subject, $body);
        } catch (\Exception $e) {
            // Log or ignore email failures so it doesn't break the form submission
            error_log("Failed to send admin notification email: " . $e->getMessage());
        }
        
        return $emailSent ?? false;
    }

    public function subscribeNewsletter(string $email): void {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email address.", 422);
        }
        $this->repo->subscribeNewsletter($email);

        // Send email notification to admin
        try {
            $subject = "New Newsletter Subscriber";
            $body = "
                <h3>New Newsletter Subscriber</h3>
                <p>A new user has subscribed to the newsletter.</p>
                <p><strong>Email:</strong> {$email}</p>
            ";
            $this->emailService->sendEmail('abhishek@brandkingmedia.com', $subject, $body);
        } catch (\Exception $e) {
            error_log("Failed to send admin notification email: " . $e->getMessage());
        }
    }

    public function getContactOptions(bool $activeOnly = false): array {
        return $this->repo->getContactOptions($activeOnly);
    }

    public function createContactOption(array $data): int {
        if (empty($data['label']) || empty($data['value'])) {
            throw new Exception("Label and value are required", 422);
        }
        return $this->repo->createContactOption($data);
    }

    public function updateContactOption(int $id, array $data): void {
        if (empty($data['label']) || empty($data['value'])) {
            throw new Exception("Label and value are required", 422);
        }
        $this->repo->updateContactOption($id, $data);
    }

    public function deleteContactOption(int $id): void {
        $this->repo->deleteContactOption($id);
    }
}
