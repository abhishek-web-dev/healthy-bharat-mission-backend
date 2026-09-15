<?php
require_once __DIR__ . '/../src/Helpers/Env.php';
require_once __DIR__ . '/../src/Core/Database.php';

\HBM\Helpers\Env::load(__DIR__ . '/../.env');
$db = \HBM\Core\Database::getConnection();

try {

    $db->exec("
        CREATE TABLE IF NOT EXISTS user_diet_plans (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            plan_name VARCHAR(255) NOT NULL,
            plan_description TEXT,
            target_calories INT DEFAULT 0,
            target_carbs_pct INT DEFAULT 0,
            target_protein_pct INT DEFAULT 0,
            target_fats_pct INT DEFAULT 0,
            target_fiber_pct INT DEFAULT 0,
            tip_message TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS user_meals (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            diet_plan_id BIGINT UNSIGNED NOT NULL,
            meal_type VARCHAR(50) NOT NULL,
            time_range VARCHAR(100),
            name VARCHAR(255) NOT NULL,
            description TEXT,
            calories INT DEFAULT 0,
            image_url VARCHAR(255),
            display_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (diet_plan_id) REFERENCES user_diet_plans(id) ON DELETE CASCADE
        );
    ");

    $stmt = $db->prepare("SELECT id FROM users LIMIT 1");
    $stmt->execute();
    $userId = $stmt->fetchColumn();

    if ($userId) {
        $db->exec("DELETE FROM user_diet_plans WHERE user_id = " . $userId);

        $stmt = $db->prepare("
            INSERT INTO user_diet_plans (user_id, plan_name, plan_description, target_calories, target_carbs_pct, target_protein_pct, target_fats_pct, target_fiber_pct, tip_message)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            'Diabetes Friendly Meal Plan',
            'A balanced and nutritious plan designed for better blood sugar control.',
            1800,
            45, 25, 20, 10,
            'Stay hydrated! Drink at least 2-3 litres of water daily.'
        ]);
        $planId = $db->lastInsertId();

        $meals = [
            ['Breakfast', '7:00 AM - 8:00 AM', 'Oats with Nuts & Berries', 'Oats, Almonds, Blueberries, Low-fat Milk', 320, '../assets/images/meals/breakfast.jpg', 1],
            ['Mid Morning', '10:00 AM - 10:30 AM', 'Mixed Fruits', 'Apple, Papaya, Guava', 120, '../assets/images/meals/mid_morning.jpg', 2],
            ['Lunch', '1:00 PM - 2:00 PM', 'Multigrain Roti, Dal & Veggies', '2 Roti, Moong Dal, Steamed Vegetables, Salad', 450, '../assets/images/meals/lunch.jpg', 3],
            ['Evening Snack', '4:00 PM - 4:30 PM', 'Green Tea & Roasted Nuts', 'Green Tea, Almonds, Walnuts', 150, '../assets/images/meals/snack.jpg', 4],
            ['Dinner', '7:00 PM - 8:00 PM', 'Vegetable Soup & Paneer Salad', 'Mixed Vegetable Soup, Grilled Paneer, Salad', 300, '../assets/images/meals/dinner.jpg', 5]
        ];

        $stmt = $db->prepare("
            INSERT INTO user_meals (diet_plan_id, meal_type, time_range, name, description, calories, image_url, display_order)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($meals as $m) {
            $stmt->execute([$planId, $m[0], $m[1], $m[2], $m[3], $m[4], $m[5], $m[6]]);
        }
    }

    echo "Food charts tables created and seeded successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
