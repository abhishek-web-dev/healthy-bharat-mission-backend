<?php

namespace HBM\Controllers;

use HBM\Helpers\Response;
use HBM\Services\UserService;
use Exception;

class UserController {
    private UserService $userService;

    public function __construct() {
        $this->userService = new UserService();
    }

    public function getProfile(): void {
        global $authUser;
        try {
            $profile = $this->userService->getUserProfile($authUser['id']);
            Response::success('Profile fetched successfully.', $profile);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function updateProfile(): void {
        global $authUser;
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                throw new Exception("Invalid request data.");
            }

            $this->userService->updateProfile($authUser['id'], $data);
            
            // Fetch updated profile
            $profile = $this->userService->getUserProfile($authUser['id']);
            Response::success('Profile updated successfully.', $profile);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function addHealthCondition(): void {
        global $authUser;
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['condition_name'])) {
                throw new Exception("Condition name is required.");
            }
            $this->userService->addHealthCondition($authUser['id'], $data['condition_name']);
            Response::success('Health condition added successfully.');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function addAllergy(): void {
        global $authUser;
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['allergy_name'])) {
                throw new Exception("Allergy name is required.");
            }
            $this->userService->addAllergy($authUser['id'], $data['allergy_name']);
            Response::success('Allergy added successfully.');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function addEmergencyContact(): void {
        global $authUser;
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $this->userService->addEmergencyContact($authUser['id'], $data);
            Response::success('Emergency contact added successfully.');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function getPrograms(): void {
        global $authUser;
        try {
            $programs = $this->userService->getUserPrograms($authUser['id']);
            Response::success('Programs fetched successfully.', $programs);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function getDocuments(): void {
        global $authUser;
        try {
            $documents = $this->userService->getUserDocuments($authUser['id']);
            Response::success('Documents fetched successfully.', $documents);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function uploadDocument(): void {
        global $authUser;
        try {
            if (!isset($_FILES['document'])) {
                throw new Exception("No document file provided.");
            }

            $file = $_FILES['document'];
            
            // Validate size (max 5MB)
            if ($file['size'] > 5 * 1024 * 1024) {
                throw new Exception("File size exceeds 5MB limit.");
            }

            // Validate type
            $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            if (!in_array($file['type'], $allowedTypes)) {
                throw new Exception("Unsupported file type.");
            }

            // Create uploads directory if not exists
            $uploadDir = __DIR__ . '/../../public/uploads/documents/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Generate unique filename
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid('doc_') . '.' . $ext;
            $destPath = $uploadDir . $filename;

            if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                throw new Exception("Failed to save uploaded file.");
            }

            // Prepare data for DB
            $data = [
                'title' => $_POST['title'] ?? pathinfo($file['name'], PATHINFO_FILENAME),
                'document_type' => $_POST['category'] ?? 'other',
                'file_path' => 'uploads/documents/' . $filename,
                'file_size' => $file['size'],
                'mime_type' => $file['type']
            ];

            $savedDoc = $this->userService->saveDocument($authUser['id'], $data);
            Response::success('Document uploaded successfully.', $savedDoc);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function deleteDocument($id) {
        global $authUser;
        $userId = $authUser['id'];
        
        try {
            $doc = $this->userService->getDocumentById($userId, $id);
            if (!$doc) {
                throw new Exception("Document not found or you don't have permission to delete it.");
            }

            // Delete file from disk
            $filePath = __DIR__ . '/../../public/' . $doc['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Delete from database
            $this->userService->deleteDocument($userId, $id);
            
            Response::success('Document deleted successfully.');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function downloadDocument($id) {
        global $authUser;
        $userId = $authUser['id'];
        
        try {
            $doc = $this->userService->getDocumentById($userId, $id);
            if (!$doc) {
                throw new Exception("Document not found or you don't have permission to download it.");
            }

            $filePath = __DIR__ . '/../../public/' . $doc['file_path'];
            if (!file_exists($filePath)) {
                throw new Exception("File not found on server.");
            }

            // Force download headers
            header('Content-Description: File Transfer');
            header('Content-Type: ' . $doc['mime_type']);
            header('Content-Disposition: attachment; filename="' . basename($doc['file_path']) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            
            readfile($filePath);
            exit;
        } catch (Exception $e) {
            Response::error($e->getMessage(), 404);
        }
    }
}
