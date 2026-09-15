-- Default Users
-- Passwords are set to 'password123'
-- Hash generated using PHP: password_hash('password123', PASSWORD_DEFAULT)
-- The hash used here is just an example bcrypt hash. For real systems, use a script to generate.

INSERT IGNORE INTO `users` (`id`, `first_name`, `last_name`, `email`, `phone`, `password_hash`, `role_id`, `status`) VALUES
(1, 'Super', 'Admin', 'superadmin@healthybharatmission.com', '0000000000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'active'),
(2, 'System', 'Admin', 'admin@healthybharatmission.com', '1111111111', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 'active'),
(3, 'Health', 'Expert', 'expert@healthybharatmission.com', '2222222222', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 'active'),
(4, 'Test', 'User', 'user@example.com', '3333333333', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, 'active');
