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
$router->post('/api/auth/resend-otp', [AuthController::class, 'resendOtp']);
$router->post('/api/auth/forgot-password', [AuthController::class, 'forgotPassword']);
$router->post('/api/auth/reset-password', [AuthController::class, 'resetPassword']);

// Admin Authentication Routes
$router->post('/api/auth/admin/login', [AuthController::class, 'adminLogin']);
$router->post('/api/auth/admin/verify-otp', [AuthController::class, 'verifyAdminOtp']);
$router->post('/api/auth/admin/resend-otp', [AuthController::class, 'resendAdminOtp']);
// Authentication Routes (Protected)
$router->get('/api/auth/me', function() use ($router) {
    AuthMiddleware::handle();
    (new AuthController())->me();
});
$router->post('/api/auth/logout', function() use ($router) {
    AuthMiddleware::handle();
    (new AuthController())->logout();
});
$router->post('/api/auth/change-password', function() use ($router) {
    AuthMiddleware::handle();
    (new AuthController())->changePassword();
});
$router->post('/api/auth/delete-account', function() use ($router) {
    AuthMiddleware::handle();
    (new AuthController())->deleteAccount();
});

// User Profile Routes (Protected)
use HBM\Controllers\UserController;
$router->get('/api/user/profile', function() use ($router) {
    AuthMiddleware::handle();
    (new UserController())->getProfile();
});
$router->put('/api/user/profile', function() use ($router) {
    AuthMiddleware::handle();
    (new UserController())->updateProfile();
});
$router->post('/api/user/health-conditions', function() use ($router) {
    AuthMiddleware::handle();
    (new UserController())->addHealthCondition();
});
$router->post('/api/user/allergies', function() use ($router) {
    AuthMiddleware::handle();
    (new UserController())->addAllergy();
});
$router->post('/api/user/emergency-contacts', function() use ($router) {
    AuthMiddleware::handle();
    (new UserController())->addEmergencyContact();
});
$router->get('/api/user/programs', function() use ($router) {
    AuthMiddleware::handle();
    (new UserController())->getPrograms();
});
$router->get('/api/user/documents', function() use ($router) {
    AuthMiddleware::handle();
    (new UserController())->getDocuments();
});
$router->post('/api/user/documents', function() use ($router) {
    AuthMiddleware::handle();
    (new UserController())->uploadDocument();
});
$router->delete('/api/user/documents/{id}', function($id) use ($router) {
    AuthMiddleware::handle();
    (new UserController())->deleteDocument($id);
});
$router->get('/api/user/documents/{id}/download', function($id) use ($router) {
    AuthMiddleware::handle();
    (new UserController())->downloadDocument($id);
});

// Food Charts
use HBM\Controllers\FoodChartController;
$router->get('/api/user/food-charts', function() use ($router) {
    AuthMiddleware::handle();
    (new FoodChartController())->getFoodCharts();
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
$router->get('/api/contact-options', [ContentController::class, 'getContactOptions']);
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

// Admin Contact Options
$router->get('/api/admin/contact-options', function() use ($router) {
    AuthMiddleware::handleAdmin();
    (new ContentController())->getAdminContactOptions();
});
$router->post('/api/admin/contact-options', function() use ($router) {
    AuthMiddleware::handleAdmin();
    (new ContentController())->createContactOption();
});
$router->put('/api/admin/contact-options/{id}', function($id) use ($router) {
    AuthMiddleware::handleAdmin();
    (new ContentController())->updateContactOption($id);
});
$router->delete('/api/admin/contact-options/{id}', function($id) use ($router) {
    AuthMiddleware::handleAdmin();
    (new ContentController())->deleteContactOption($id);
});

// Reviews Routes
$router->post('/api/store/reviews', function() use ($router) {
    AuthMiddleware::handle();
    (new StoreController())->submitReview();
});
$router->get('/api/store/products/{id}/reviews', function($id) use ($router) {
    (new StoreController())->getProductReviews($id);
});

// Checkout & Orders Routes// Store / Checkout APIs
use HBM\Controllers\StoreCouponController;
$router->get('/api/store/coupons', [StoreCouponController::class, 'getActiveCoupons']);
$router->get('/api/store/coupons/validate', [StoreCouponController::class, 'validateCoupon']);

use HBM\Controllers\CheckoutController;
$router->get('/api/user/addresses', function() use ($router) {
    AuthMiddleware::handle();
    (new CheckoutController())->getAddresses();
});
$router->post('/api/user/addresses', function() use ($router) {
    AuthMiddleware::handle();
    (new CheckoutController())->saveAddress();
});
$router->put('/api/user/addresses/{id}', function($id) use ($router) {
    AuthMiddleware::handle();
    (new CheckoutController())->updateAddress((int)$id);
});
$router->delete('/api/user/addresses/{id}', function($id) use ($router) {
    AuthMiddleware::handle();
    (new CheckoutController())->deleteAddress((int)$id);
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
$router->get('/api/orders/{id}/invoice/download', function($id) use ($router) {
    AuthMiddleware::handle();
    (new CheckoutController())->downloadInvoice($id);
});
$router->get('/api/orders/{id}/download/{productId}', function($id, $productId) use ($router) {
    AuthMiddleware::handle();
    (new CheckoutController())->downloadDigitalProduct($id, $productId);
});
$router->post('/api/payments/verify', function() use ($router) {
    AuthMiddleware::handle();
    (new CheckoutController())->verifyPayment();
});
$router->post('/api/payment/webhook', [CheckoutController::class, 'handleWebhook']);


// Admin Dashboard Routes
use HBM\Controllers\AdminDashboardController;
$router->get('/api/admin/dashboard-stats', function() use ($router) {
    AuthMiddleware::requirePermission('view_dashboard');
    (new AdminDashboardController())->getStats();
});

// Admin User Management Routes
use HBM\Controllers\AdminUserController;
use HBM\Controllers\AdminTeamController;

$router->get('/api/admin/roles', function() use ($router) {
    AuthMiddleware::requirePermission('view_team');
    (new AdminTeamController())->getRoles();
});
$router->post('/api/admin/roles', function() use ($router) {
    AuthMiddleware::requirePermission('create_team');
    (new AdminTeamController())->createRole();
});
$router->put('/api/admin/roles/{id}', function($id) use ($router) {
    AuthMiddleware::requirePermission('edit_team');
    (new AdminTeamController())->updateRole($id);
});
$router->get('/api/admin/permissions', function() use ($router) {
    AuthMiddleware::requirePermission('view_team');
    (new AdminTeamController())->getPermissions();
});
$router->get('/api/admin/team', function() use ($router) {
    AuthMiddleware::requirePermission('view_team');
    (new AdminTeamController())->getTeamMembers();
});
$router->post('/api/admin/team', function() use ($router) {
    AuthMiddleware::requirePermission('create_team');
    (new AdminTeamController())->createTeamMember();
});

$router->get('/api/admin/users', function() use ($router) {
    AuthMiddleware::requirePermission('view_users');
    (new AdminUserController())->listUsers();
});
$router->get('/api/admin/users/deleted/all', function() use ($router) {
    AuthMiddleware::requirePermission('view_deleted_accounts');
    (new AdminUserController())->listDeletedUsers();
});
$router->delete('/api/admin/users/{id}', function($id) use ($router) {
    AuthMiddleware::requirePermission('delete_users');
    (new AdminUserController())->deleteUser($id);
});
$router->post('/api/admin/users/{id}/restore', function($id) use ($router) {
    AuthMiddleware::requirePermission('edit_deleted_accounts');
    (new AdminUserController())->restoreUser($id);
});
$router->get('/api/admin/users/{id}', function($id) use ($router) {
    AuthMiddleware::requirePermission('view_users');
    (new AdminUserController())->getUser($id);
});
$router->put('/api/admin/users/{id}/role', function($id) use ($router) {
    AuthMiddleware::requirePermission('edit_users');
    (new AdminUserController())->updateRole($id);
});
$router->put('/api/admin/users/{id}/status', function($id) use ($router) {
    AuthMiddleware::requirePermission('edit_users');
    (new AdminUserController())->updateStatus($id);
});


// Admin Database Backups Routes
use HBM\Controllers\DatabaseBackupController;
$router->get('/api/admin/backups', function() use ($router) {
    AuthMiddleware::requirePermission('view_backups');
    (new DatabaseBackupController())->listBackups();
});
$router->post('/api/admin/backups', function() use ($router) {
    AuthMiddleware::requirePermission('create_backups');
    (new DatabaseBackupController())->createManualBackup();
});
$router->get('/api/admin/backups/{id}/download', function($id) use ($router) {
    AuthMiddleware::requirePermission('view_backups'); // view_backups is appropriate for download
    (new DatabaseBackupController())->downloadBackup($id);
});
$router->put('/api/admin/backups/settings', function() use ($router) {
    AuthMiddleware::requirePermission('create_backups'); // Only those who can create backups should schedule them
    (new DatabaseBackupController())->updateSettings();
});

// Admin Category Management Routes
use HBM\Controllers\AdminCategoryController;
$router->get('/api/admin/categories', function() use ($router) {
    AuthMiddleware::requirePermission('view_categories');
    (new AdminCategoryController())->listCategories();
});
$router->get('/api/admin/categories/{id}', function($id) use ($router) {
    AuthMiddleware::requirePermission('view_categories');
    (new AdminCategoryController())->getCategory($id);
});
$router->post('/api/admin/categories', function() use ($router) {
    AuthMiddleware::requirePermission('create_categories');
    (new AdminCategoryController())->createCategory();
});
$router->put('/api/admin/categories/{id}', function($id) use ($router) {
    AuthMiddleware::requirePermission('edit_categories');
    (new AdminCategoryController())->updateCategory($id);
});
$router->delete('/api/admin/categories/{id}', function($id) use ($router) {
    AuthMiddleware::requirePermission('delete_categories');
    (new AdminCategoryController())->deleteCategory($id);
});

// Admin Product Management Routes
use HBM\Controllers\AdminProductController;
$router->get('/api/admin/products', function() use ($router) {
    AuthMiddleware::requirePermission('view_products');
    (new AdminProductController())->listProducts();
});
$router->post('/api/admin/products', function() use ($router) {
    AuthMiddleware::requirePermission('create_products');
    (new AdminProductController())->createProduct();
});
$router->put('/api/admin/products/{id}', function($id) use ($router) {
    AuthMiddleware::requirePermission('edit_products');
    (new AdminProductController())->updateProduct($id);
});

// Admin Order Management Routes
use HBM\Controllers\AdminOrderController;
$router->get('/api/admin/orders', function() use ($router) {
    AuthMiddleware::requirePermission('view_orders');
    (new AdminOrderController())->listOrders();
});
$router->get('/api/admin/orders/{id}', function($id) use ($router) {
    AuthMiddleware::requirePermission('view_orders');
    (new AdminOrderController())->getOrder($id);
});
$router->put('/api/admin/orders/{id}/status', function($id) use ($router) {
    AuthMiddleware::requirePermission('edit_orders');
    (new AdminOrderController())->updateOrderStatus($id);
});
$router->put('/api/admin/orders/{id}/payment-status', function($id) use ($router) {
    AuthMiddleware::requirePermission('edit_orders');
    (new AdminOrderController())->updatePaymentStatus($id);
});

// Admin Content Routes
use HBM\Controllers\AdminContentController;
$router->get('/api/admin/programs', function() use ($router) {
    AuthMiddleware::requirePermission('view_programs');
    (new AdminContentController())->listPrograms();
});
$router->post('/api/admin/programs', function() use ($router) {
    AuthMiddleware::requirePermission('create_programs');
    (new AdminContentController())->createProgram();
});
$router->put('/api/admin/programs/{id}', function($id) use ($router) {
    AuthMiddleware::requirePermission('edit_programs');
    (new AdminContentController())->updateProgram($id);
});
$router->delete('/api/admin/programs/{id}', function($id) use ($router) {
    AuthMiddleware::requirePermission('delete_programs');
    (new AdminContentController())->deleteProgram($id);
});

$router->get('/api/admin/articles', function() use ($router) {
    AuthMiddleware::requirePermission('view_health_library');
    (new AdminContentController())->listArticles();
});
$router->post('/api/admin/articles', function() use ($router) {
    AuthMiddleware::requirePermission('create_health_library');
    (new AdminContentController())->createArticle();
});


$router->get('/api/admin/articles/{id}', function($id) use ($router) {
    AuthMiddleware::requirePermission('view_health_library');
    (new AdminContentController())->getArticle($id);
});
$router->put('/api/admin/articles/{id}', function($id) use ($router) {
    AuthMiddleware::requirePermission('edit_health_library');
    (new AdminContentController())->updateArticle($id);
});


$router->get('/api/admin/health-conditions', function() use ($router) {
    AuthMiddleware::requirePermission('view_health_conditions');
    (new AdminContentController())->listHealthConditions();
});
$router->post('/api/admin/health-conditions', function() use ($router) {
    AuthMiddleware::requirePermission('create_health_conditions');
    (new AdminContentController())->createHealthCondition();
});
$router->put('/api/admin/health-conditions/{id}', function($id) use ($router) {
    AuthMiddleware::requirePermission('edit_health_conditions');
    (new AdminContentController())->updateHealthCondition($id);
});


$router->get('/api/admin/faqs', function() use ($router) {
    AuthMiddleware::requirePermission('view_faqs');
    (new AdminContentController())->listFaqs();
});
$router->post('/api/admin/faqs', function() use ($router) {
    AuthMiddleware::requirePermission('create_faqs');
    (new AdminContentController())->createFaq();
});
$router->put('/api/admin/faqs/{id}', function($id) use ($router) {
    AuthMiddleware::requirePermission('edit_faqs');
    (new AdminContentController())->updateFaq($id);
});


$router->get('/api/admin/experts', function() use ($router) {
    AuthMiddleware::requirePermission('view_experts');
    (new HBM\Controllers\AdminAppointmentController())->listExperts();
});
$router->put('/api/admin/experts/{id}', function($id) use ($router) {
    AuthMiddleware::requirePermission('edit_experts');
    (new HBM\Controllers\AdminAppointmentController())->updateExpert($id);
});
$router->get('/api/admin/appointments', function() use ($router) {
    AuthMiddleware::requirePermission('view_appointments');
    (new HBM\Controllers\AdminAppointmentController())->listAppointments();
});
$router->put('/api/admin/appointments/{id}', function($id) use ($router) {
    AuthMiddleware::requirePermission('edit_appointments');
    (new HBM\Controllers\AdminAppointmentController())->updateAppointment($id);
});

$router->get('/api/admin/contact-inquiries', function() use ($router) {
    AuthMiddleware::requirePermission('view_inquiries');
    (new AdminContentController())->listInquiries();
});
$router->get('/api/admin/newsletter-subscribers', function() use ($router) {
    AuthMiddleware::requirePermission('view_subscribers');
    (new AdminContentController())->listSubscribers();
});

// Admin Settings Routes
$router->get('/api/admin/settings', function() use ($router) {
    AuthMiddleware::requirePermission('view_settings');
    (new HBM\Controllers\AdminSettingController())->getSettings();
});
$router->put('/api/admin/settings', function() use ($router) {
    AuthMiddleware::handleSuperAdmin(); // Usually Settings should be Super Admin only
    (new HBM\Controllers\AdminSettingController())->updateSettings();
});

// Admin Audit Logs Routes
$router->get('/api/admin/audit-logs', function() use ($router) {
    AuthMiddleware::requirePermission('view_logs');
    (new HBM\Controllers\AdminAuditLogController())->listLogs();
});

// Admin Reviews Routes
$router->get('/api/admin/reviews/counts', function() use ($router) {
    AuthMiddleware::requirePermission('view_reviews');
    (new HBM\Controllers\AdminReviewController())->getReviewCounts();
});
$router->get('/api/admin/reviews', function() use ($router) {
    AuthMiddleware::requirePermission('view_reviews');
    (new HBM\Controllers\AdminReviewController())->getReviews();
});
$router->get('/api/admin/reviews/{id}', function($id) use ($router) {
    AuthMiddleware::requirePermission('view_reviews');
    (new HBM\Controllers\AdminReviewController())->getReviewById($id);
});
$router->put('/api/admin/reviews/{id}/status', function($id) use ($router) {
    AuthMiddleware::requirePermission('edit_reviews');
    (new HBM\Controllers\AdminReviewController())->updateReviewStatus($id);
});

// Admin Offers Routes
$router->get('/api/admin/offers', function() use ($router) {
    AuthMiddleware::requirePermission('view_offers');
    (new HBM\Controllers\AdminOfferController())->getOffers();
});
$router->get('/api/admin/offers/{id}', function($id) use ($router) {
    AuthMiddleware::requirePermission('view_offers');
    (new HBM\Controllers\AdminOfferController())->getOfferById($id);
});
$router->post('/api/admin/offers', function() use ($router) {
    AuthMiddleware::requirePermission('create_offers');
    (new HBM\Controllers\AdminOfferController())->createOffer();
});
$router->put('/api/admin/offers/{id}', function($id) use ($router) {
    AuthMiddleware::requirePermission('edit_offers');
    (new HBM\Controllers\AdminOfferController())->updateOffer($id);
});
$router->delete('/api/admin/offers/{id}', function($id) use ($router) {
    AuthMiddleware::requirePermission('delete_offers');
    (new HBM\Controllers\AdminOfferController())->deleteOffer($id);
});

// Admin Coupons Routes
$router->get('/api/admin/coupons', function() use ($router) {
    AuthMiddleware::requirePermission('view_coupons');
    (new HBM\Controllers\AdminCouponController())->getCoupons();
});
$router->get('/api/admin/coupons/{id}', function($id) use ($router) {
    AuthMiddleware::requirePermission('view_coupons');
    (new HBM\Controllers\AdminCouponController())->getCouponById($id);
});
$router->post('/api/admin/coupons', function() use ($router) {
    AuthMiddleware::requirePermission('create_coupons');
    (new HBM\Controllers\AdminCouponController())->createCoupon();
});
$router->put('/api/admin/coupons/{id}', function($id) use ($router) {
    AuthMiddleware::requirePermission('edit_coupons');
    (new HBM\Controllers\AdminCouponController())->updateCoupon($id);
});
$router->delete('/api/admin/coupons/{id}', function($id) use ($router) {
    AuthMiddleware::requirePermission('delete_coupons');
    (new HBM\Controllers\AdminCouponController())->deleteCoupon($id);
});

return $router;
