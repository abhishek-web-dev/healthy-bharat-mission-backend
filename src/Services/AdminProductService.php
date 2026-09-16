<?php

namespace HBM\Services;

use HBM\Repositories\AdminProductRepository;
use HBM\Services\AdminActivityLogger;
use Exception;

class AdminProductService {
    private AdminProductRepository $repo;

    public function __construct() {
        $this->repo = new AdminProductRepository();
    }
    public function getAllProducts(int $limit = 100, int $offset = 0): array {
        return $this->repo->getAllProducts($limit, $offset);
    }


    
    private function handleImageUpload(?string $thumbnail_url): ?string {
        if (empty($thumbnail_url)) return null;
        
        if (strpos($thumbnail_url, 'http') === 0 || strpos($thumbnail_url, '/') === 0) {
            return $thumbnail_url;
        }

        if (preg_match('/^data:image\/(\w+);base64,/', $thumbnail_url, $type)) {
            $data = substr($thumbnail_url, strpos($thumbnail_url, ',') + 1);
            $type = strtolower($type[1]);
            
            if (!in_array($type, [ 'jpg', 'jpeg', 'gif', 'png', 'webp' ])) {
                throw new \Exception('Invalid image type.');
            }
            $data = base64_decode($data);
            if ($data === false) {
                throw new \Exception('base64_decode failed');
            }
            
            $fileName = uniqid('prod_') . '.' . $type;
            $uploadDir = __DIR__ . '/../../public/uploads/products';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $filePath = $uploadDir . '/' . $fileName;
            file_put_contents($filePath, $data);
            
            return '/uploads/products/' . $fileName;
        }
        
        return $thumbnail_url;
    }

    public function createProduct(int $adminId, array $data): int {
        if (empty($data['name']) || empty($data['price']) || empty($data['category_id'])) {
            throw new Exception("Name, price, and category_id are required.");
        }

        $uploadedImages = [];
        if (!empty($data['images']) && is_array($data['images'])) {
            foreach ($data['images'] as $img) {
                $savedPath = $this->handleImageUpload($img);
                if ($savedPath) {
                    $uploadedImages[] = $savedPath;
                }
            }
            if (!empty($uploadedImages)) {
                $data['thumbnail_url'] = $uploadedImages[0];
            }
        } else if (isset($data['thumbnail_url'])) {
            $data['thumbnail_url'] = $this->handleImageUpload($data['thumbnail_url']);
            if ($data['thumbnail_url']) $uploadedImages[] = $data['thumbnail_url'];
        }

        if (empty($data['slug'])) {
            $data['slug'] = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $data['name'])));
        }

        $productId = $this->repo->createProduct($data);
        if (!empty($uploadedImages)) {
            $this->repo->saveProductImages($productId, $uploadedImages);
        }
        AdminActivityLogger::log($adminId, 'CREATE', 'products', $productId, ['name' => $data['name']]);
        return $productId;
    }

    public function updateProduct(int $adminId, int $id, array $data): void {
        if (empty($data)) {
            throw new Exception("No data provided for update.");
        }

        $uploadedImages = [];
        if (isset($data['images']) && is_array($data['images'])) {
            foreach ($data['images'] as $img) {
                $savedPath = $this->handleImageUpload($img);
                if ($savedPath) {
                    $uploadedImages[] = $savedPath;
                }
            }
            if (!empty($uploadedImages)) {
                $data['thumbnail_url'] = $uploadedImages[0];
            } else {
                $data['thumbnail_url'] = '';
            }
        } else if (isset($data['thumbnail_url'])) {
            $data['thumbnail_url'] = $this->handleImageUpload($data['thumbnail_url']);
        }

        $this->repo->updateProduct($id, $data);
        if (isset($data['images'])) {
            $this->repo->saveProductImages($id, $uploadedImages);
        }
        AdminActivityLogger::log($adminId, 'UPDATE', 'products', $id, $data);
    }
}
