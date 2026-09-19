<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Load .env
$dotenvPath = __DIR__ . '/../.env';
if (file_exists($dotenvPath)) {
    $lines = file($dotenvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2) + [NULL, NULL];
        if (!empty($name)) {
            putenv(trim($name) . '=' . trim($value));
        }
    }
}

$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_DATABASE') ?: 'healthy_bharat_mission';
$dbUser = getenv('DB_USERNAME') ?: 'hbm_app';
$dbPass = getenv('DB_PASSWORD') ?: 'HBM_Dev_2026_Strong!';

try {
    $pdo = new PDO("mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "Connected to database.\n";
    
    // Check if read_time_minutes exists, if not create it
    $colCheck = $pdo->query("SHOW COLUMNS FROM `articles` LIKE 'read_time_minutes'");
    if ($colCheck->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `articles` ADD COLUMN `read_time_minutes` INT NOT NULL DEFAULT 5 AFTER `published_at`");
        echo "Added read_time_minutes column.\n";
    }

    // 1. Categories
    $categories = [
        ['Nutrition & Diet', 'nutrition-diet'],
        ['Diabetes Care', 'diabetes-care'],
        ['Healthy Living', 'healthy-living'],
        ['Immunity', 'immunity'],
        ['Mental Wellness', 'mental-wellness'],
        ['Digestive Health', 'digestive-health'],
        ['Heart Health', 'heart-health'],
        ['Women\'s Health', 'womens-health'],
        ['Weight Management', 'weight-management']
    ];
    
    $catStmt = $pdo->prepare("INSERT INTO `article_categories` (`name`, `slug`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `name` = VALUES(`name`)");
    $catMap = [];
    foreach ($categories as $cat) {
        $catStmt->execute($cat);
        $catMap[$cat[0]] = $pdo->query("SELECT id FROM `article_categories` WHERE slug = '{$cat[1]}'")->fetchColumn();
    }
    echo "Categories seeded.\n";

    // Original Article Body Template
    $bodyTemplate = <<<HTML
<h2>Introduction</h2>
<p>{excerpt}</p>
<img src="{image_url}" alt="{title}" class="w-full rounded-xl my-8 shadow-sm border border-gray-100">
<h2>What is {concept}?</h2>
<p>Type 2 diabetes is a long-term condition that affects how your body uses glucose for energy. Unlike type 1 diabetes, the body still produces insulin, but the cells become resistant to it over time.</p>
<h2>Common Symptoms</h2>
<p>The symptoms can be mild at first. Some common signs include:</p>
<ul>
    <li>Increased thirst</li>
    <li>Frequent urination</li>
    <li>Unexplained weight loss</li>
    <li>Fatigue</li>
    <li>Blurred vision</li>
    <li>Slow healing of wounds</li>
</ul>
<h2>Causes and Risk Factors</h2>
<p>Several factors can increase the risk, including:</p>
<ul>
    <li>Being overweight or obese</li>
    <li>Physical inactivity</li>
    <li>Unhealthy diet (high in sugar and processed foods)</li>
    <li>Family history of diabetes</li>
    <li>Increasing age (especially over 45)</li>
</ul>
<h2>How to Manage It</h2>
<p>While there is no permanent cure, it can be managed with:</p>
<ul>
    <li>A balanced and nutritious diet</li>
    <li>Regular physical activity</li>
    <li>Maintaining a healthy weight</li>
    <li>Taking prescribed medications</li>
    <li>Regular monitoring of blood sugar levels</li>
    <li>Stress management and adequate sleep</li>
</ul>
<div class="article-quote">
    <i class="fa-solid fa-leaf"></i>
    <p>"Small, consistent changes in your lifestyle can make a big difference in managing your health and living a happier life."</p>
</div>
<h2>Conclusion</h2>
<p>It is manageable with the right approach. Early detection, a healthy lifestyle, and regular check-ups can help prevent complications and improve your quality of life.</p>
HTML;

    // 2. Articles
    $articles = [
        [
            'A Beginner\'s Guide to a Balanced Diet', 
            'beginners-guide-balanced-diet', 
            'Nutrition & Diet', 
            'Learn how to create simple, sustainable eating habits for long-term health.', 
            'assets/article_diet.png', 
            'Aug 24, 2025', 
            5,
            'Balanced Diet'
        ],
        [
            '10 Daily Habits to Manage Diabetes Naturally', 
            '10-daily-habits-manage-diabetes', 
            'Diabetes Care', 
            'Small changes can make a big difference in your blood sugar levels.', 
            'assets/article_diabetes.png', 
            'Aug 20, 2025', 
            6,
            'Diabetes Management'
        ],
        [
            'The Power of Daily Movement', 
            'power-of-daily-movement', 
            'Healthy Living', 
            'Discover how regular exercise improves your overall well-being.', 
            'assets/article_living.png', 
            'Aug 18, 2025', 
            4,
            'Daily Movement'
        ],
        [
            '5 Natural Ways to Boost Your Immunity', 
            '5-natural-ways-boost-immunity', 
            'Immunity', 
            'Top natural methods to keep your immune system strong and resilient.', 
            'assets/latest_immunity.png', 
            'Aug 16, 2025', 
            4,
            'Immunity'
        ],
        [
            'How Better Sleep Improves Your Health', 
            'how-better-sleep-improves-health', 
            'Mental Wellness', 
            'The science of restful sleep and why it is critical for your well-being.', 
            'assets/latest_mental.png', 
            'Aug 14, 2025', 
            5,
            'Restful Sleep'
        ],
        [
            'Foods for a Happier Gut', 
            'foods-for-happier-gut', 
            'Digestive Health', 
            'Understanding your microbiome and eating the right foods for digestive health.', 
            'assets/latest_digestive.png', 
            'Aug 12, 2025', 
            4,
            'Microbiome'
        ],
        [
            'Keep Your Heart Healthy with Simple Lifestyle Changes', 
            'keep-heart-healthy', 
            'Heart Health', 
            'Cardio exercises and dietary changes to protect your heart health over time.', 
            'assets/latest_heart.png', 
            'Aug 10, 2025', 
            6,
            'Heart Health'
        ],
        [
            'PCOS: Diet, Exercise & Lifestyle Tips', 
            'pcos-diet-exercise-tips', 
            'Women\'s Health', 
            'Manage PCOS effectively through proven lifestyle changes and habits.', 
            'assets/article_living.png', 
            'Aug 08, 2025', 
            5,
            'PCOS Management'
        ],
        [
            'Understanding Type 2 Diabetes: Causes, Symptoms and How to Manage It', 
            'type-2-diabetes', 
            'Diabetes Care', 
            'Learn about the early signs, risk factors and simple lifestyle changes that can help you keep diabetes under control.', 
            'assets/cond_diabetes.png', 
            'Aug 12, 2024', 
            8,
            'Type 2 Diabetes'
        ]
    ];
    
    // Get an author (first admin or user)
    $authorId = $pdo->query("SELECT id FROM users ORDER BY id ASC LIMIT 1")->fetchColumn();
    if (!$authorId) {
        $pdo->exec("INSERT INTO users (first_name, last_name, email, password, role) VALUES ('Admin', 'User', 'admin@healthybharat.com', 'dummy', 'admin')");
        $authorId = $pdo->lastInsertId();
    }
    
    $artStmt = $pdo->prepare("
        INSERT INTO `articles` 
        (`title`, `slug`, `excerpt`, `content`, `category_id`, `author_id`, `image_url`, `status`, `published_at`, `read_time_minutes`) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'published', ?, ?)
        ON DUPLICATE KEY UPDATE 
        `title` = VALUES(`title`),
        `excerpt` = VALUES(`excerpt`),
        `content` = VALUES(`content`),
        `category_id` = VALUES(`category_id`),
        `image_url` = VALUES(`image_url`),
        `status` = VALUES(`status`),
        `published_at` = VALUES(`published_at`),
        `read_time_minutes` = VALUES(`read_time_minutes`)
    ");
    
    foreach ($articles as $a) {
        $content = str_replace(
            ['{excerpt}', '{image_url}', '{title}', '{concept}'],
            [$a[3], $a[4], htmlspecialchars($a[0]), $a[7]],
            $bodyTemplate
        );
        $catId = isset($catMap[$a[2]]) ? $catMap[$a[2]] : null;
        $publishedAt = date('Y-m-d H:i:s', strtotime($a[5]));
        
        $artStmt->execute([
            $a[0], // title
            $a[1], // slug
            $a[3], // excerpt
            $content, // content
            $catId, // category_id
            $authorId, // author_id
            $a[4], // image_url
            $publishedAt, // published_at
            $a[6] // read_time_minutes
        ]);
    }
    echo "Articles seeded successfully. (Idempotent upsert completed).\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
