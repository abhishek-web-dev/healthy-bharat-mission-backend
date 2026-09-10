<?php

use HBM\Core\Router;
use HBM\Controllers\HealthController;
use HBM\Controllers\AuthController;
use HBM\Middleware\AuthMiddleware;

$router = new Router();

// Health Check
$router->get('/api/health', [HealthController::class, 'check']);

// Authentication Routes (Public)
$router->post('/api/auth/register', [AuthController::class, 'register']);
$router->post('/api/auth/login', [AuthController::class, 'login']);
$router->post('/api/auth/verify-otp', [AuthController::class, 'verifyOtp']);
$router->post('/api/auth/forgot-password', [AuthController::class, 'forgotPassword']);
$router->post('/api/auth/reset-password', [AuthController::class, 'resetPassword']);

// Authentication Routes (Protected)
$router->get('/api/auth/me', function() use ($router) {
    AuthMiddleware::handle();
    (new AuthController())->me();
});
$router->post('/api/auth/logout', function() use ($router) {
    AuthMiddleware::handle();
    (new AuthController())->logout();
});

// Content Routes (Public)
use HBM\Controllers\ContentController;
$router->get('/api/health-conditions', [ContentController::class, 'getHealthConditionsPaginated']);
$router->get('/api/health-conditions/{slug}', [ContentController::class, 'getHealthConditionBySlug']);

$router->get('/api/programs', [ContentController::class, 'getPrograms']);
$router->get('/api/programs/{slug}', [ContentController::class, 'getProgramBySlug']);

$router->get('/api/articles', [ContentController::class, 'getArticles']);
$router->get('/api/articles/{slug}', [ContentController::class, 'getArticleBySlug']);
$router->get('/api/article-categories', [ContentController::class, 'getArticleCategories']);
$router->get('/api/article-tags', [ContentController::class, 'getArticleTags']);

$router->get('/api/faqs', [ContentController::class, 'getFaqs']);
$router->get('/api/success-stories', [ContentController::class, 'getSuccessStories']);

$router->post('/api/contact', [ContentController::class, 'submitContact']);
$router->post('/api/newsletter/subscribe', [ContentController::class, 'subscribeNewsletter']);

// Store Routes (Public)
use HBM\Controllers\StoreController;
$router->get('/api/products', [StoreController::class, 'getProducts']);
$router->get('/api/products/{id}', [StoreController::class, 'getProductDetails']);
$router->get('/api/product-categories', [StoreController::class, 'getCategories']);

// Store Routes (Protected)
$router->get('/api/cart', function() use ($router) {
    AuthMiddleware::handle();
    (new StoreController())->getCart();
});
$router->get('/api/cart/count', function() use ($router) {
    AuthMiddleware::handle();
    (new StoreController())->getCartCount();
});
$router->post('/api/cart', function() use ($router) {
    AuthMiddleware::handle();
    (new StoreController())->addToCart();
});
$router->put('/api/cart/{id}', function($id) use ($router) {
    AuthMiddleware::handle();
    (new StoreController())->updateCartQuantity($id);
});
$router->delete('/api/cart/{id}', function($id) use ($router) {
    AuthMiddleware::handle();
    (new StoreController())->removeCartItem($id);
});

$router->get('/api/wishlist', function() use ($router) {
    AuthMiddleware::handle();
    (new StoreController())->getWishlist();
});
$router->post('/api/wishlist', function() use ($router) {
    AuthMiddleware::handle();
    (new StoreController())->toggleWishlist();
});
$router->delete('/api/wishlist/{id}', function($id) use ($router) {
    AuthMiddleware::handle();
    (new StoreController())->removeWishlistItem($id);
});

// Checkout & Orders Routes (Protected)
use HBM\Controllers\CheckoutController;
$router->get('/api/user/addresses', function() use ($router) {
    AuthMiddleware::handle();
    (new CheckoutController())->getAddresses();
});
$router->post('/api/user/addresses', function() use ($router) {
    AuthMiddleware::handle();
    (new CheckoutController())->saveAddress();
});
$router->post('/api/orders', function() use ($router) {
    AuthMiddleware::handle();
    (new CheckoutController())->createOrder();
});
$router->get('/api/orders', function() use ($router) {
    AuthMiddleware::handle();
    (new CheckoutController())->getOrders();
});
$router->get('/api/orders/{id}', function($id) use ($router) {
    AuthMiddleware::handle();
    (new CheckoutController())->getOrderDetails($id);
});
$router->post('/api/payments/verify', function() use ($router) {
    AuthMiddleware::handle();
    (new CheckoutController())->verifyPayment();
});

// Admin Dashboard Routes
use HBM\Controllers\AdminDashboardController;
$router->get('/api/admin/dashboard-stats', function() use ($router) {
    AuthMiddleware::handleAdmin();
    (new AdminDashboardController())->getStats();
});

// Admin User Management Routes
use HBM\Controllers\AdminUserController;
$router->get('/api/admin/users', function() use ($router) {
    AuthMiddleware::handleAdmin();
    (new AdminUserController())->listUsers();
});
$router->get('/api/admin/users/{id}', function($id) use ($router) {
    AuthMiddleware::handleAdmin();
    (new AdminUserController())->getUser($id);
});
$router->put('/api/admin/users/{id}/role', function($id) use ($router) {
    AuthMiddleware::handleSuperAdmin();
    (new AdminUserController())->updateRole($id);
});
$router->put('/api/admin/users/{id}/status', function($id) use ($router) {
    AuthMiddleware::handleAdmin();
    (new AdminUserController())->updateStatus($id);
});

// Admin Product Management Routes
use HBM\Controllers\AdminProductController;
$router->post('/api/admin/products', function() use ($router) {
    AuthMiddleware::handleAdmin();
    (new AdminProductController())->createProduct();
});
$router->put('/api/admin/products/{id}', function($id) use ($router) {
    AuthMiddleware::handleAdmin();
    (new AdminProductController())->updateProduct($id);
});

// Admin Order Management Routes
use HBM\Controllers\AdminOrderController;
$router->get('/api/admin/orders', function() use ($router) {
    AuthMiddleware::handleAdmin();
    (new AdminOrderController())->listOrders();
});
$router->get('/api/admin/orders/{id}', function($id) use ($router) {
    AuthMiddleware::handleAdmin();
    (new AdminOrderController())->getOrder($id);
});
$router->put('/api/admin/orders/{id}/status', function($id) use ($router) {
    AuthMiddleware::handleAdmin();
    (new AdminOrderController())->updateOrderStatus($id);
});
$router->put('/api/admin/orders/{id}/payment-status', function($id) use ($router) {
    AuthMiddleware::handleAdmin();
    (new AdminOrderController())->updatePaymentStatus($id);
});

// Admin Content Routes
use HBM\Controllers\AdminContentController;
$router->get('/api/admin/programs', function() use ($router) {
    AuthMiddleware::handleAdmin();
    (new AdminContentController())->listPrograms();
});
$router->post('/api/admin/programs', function() use ($router) {
    AuthMiddleware::handleAdmin();
    (new AdminContentController())->createProgram();
});
$router->put('/api/admin/programs/{id}', function($id) use ($router) {
    AuthMiddleware::handleAdmin();
    (new AdminContentController())->updateProgram($id);
});
$router->delete('/api/admin/programs/{id}', function($id) use ($router) {
    AuthMiddleware::handleAdmin();
    (new AdminContentController())->deleteProgram($id);
});

$router->get('/api/admin/articles', function() use ($router) {
    AuthMiddleware::handleAdmin();
    (new AdminContentController())->listArticles();
});
$router->post('/api/admin/articles', function() use ($router) {
    AuthMiddleware::handleAdmin();
    (new AdminContentController())->createArticle();
});

$router->get('/api/admin/contact-inquiries', function() use ($router) {
    AuthMiddleware::handleAdmin();
    (new AdminContentController())->listInquiries();
});
$router->get('/api/admin/newsletter-subscribers', function() use ($router) {
    AuthMiddleware::handleAdmin();
    (new AdminContentController())->listSubscribers();
});

return $router;
