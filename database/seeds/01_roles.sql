-- Default Roles
INSERT IGNORE INTO `roles` (`id`, `name`, `slug`) VALUES
(1, 'Super Admin', 'superadmin'),
(2, 'Admin', 'admin'),
(3, 'Expert', 'expert'),
(4, 'User', 'user');

-- Basic Permissions
INSERT IGNORE INTO `permissions` (`id`, `name`, `slug`) VALUES
(1, 'Manage Users', 'manage_users'),
(2, 'Manage Programs', 'manage_programs'),
(3, 'Manage Products', 'manage_products'),
(4, 'Manage Orders', 'manage_orders'),
(5, 'View Dashboard', 'view_dashboard');

-- Role Permissions (Super Admin gets all)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5),
(2, 2), (2, 3), (2, 4), (2, 5); -- Admin gets some
