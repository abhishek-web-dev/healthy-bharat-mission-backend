<?php

namespace HBM\Services;

use HBM\Repositories\UserRepository;
use Exception;

class UserService {
    private UserRepository $userRepo;

    public function __construct() {
        $this->userRepo = new UserRepository();
    }

    public function getUserProfile(int $userId): array {
        return $this->userRepo->getUserProfile($userId);
    }

    public function updateProfile(int $userId, array $data): void {
        // Validate required fields
        if (empty($data['first_name']) || empty($data['last_name'])) {
            throw new Exception("First name and last name are required.");
        }

        $this->userRepo->updateProfile($userId, $data);
    }

    public function addHealthCondition(int $userId, string $conditionName): void {
        if (empty(trim($conditionName))) {
            throw new Exception("Condition name cannot be empty.");
        }
        $this->userRepo->addHealthCondition($userId, trim($conditionName));
    }

    public function addAllergy(int $userId, string $allergyName): void {
        if (empty(trim($allergyName))) {
            throw new Exception("Allergy name cannot be empty.");
        }
        $this->userRepo->addAllergy($userId, trim($allergyName));
    }

    public function addEmergencyContact(int $userId, array $data): void {
        if (empty(trim($data['contact_name'])) || empty(trim($data['phone_number']))) {
            throw new Exception("Contact name and phone number are required.");
        }
        $this->userRepo->addEmergencyContact($userId, $data);
    }

    public function getUserPrograms(int $userId): array {
        return $this->userRepo->getUserPrograms($userId);
    }

    public function getUserDocuments(int $userId): array {
        return $this->userRepo->getUserDocuments($userId);
    }

    public function saveDocument(int $userId, array $data): array {
        if (empty(trim($data['title']))) {
            throw new Exception("Document title is required.");
        }
        return $this->userRepo->saveDocument($userId, $data);
    }

    public function getDocumentById(int $userId, int $docId): ?array {
        return $this->userRepo->getDocumentById($userId, $docId);
    }

    public function deleteDocument(int $userId, int $docId): bool {
        return $this->userRepo->deleteDocument($userId, $docId);
    }

    public function getSettings(int $userId): array {
        return $this->userRepo->getSettings($userId);
    }

    public function updateTwoFactor(int $userId, bool $enabled): void {
        $this->userRepo->updateTwoFactor($userId, $enabled);
    }
}
