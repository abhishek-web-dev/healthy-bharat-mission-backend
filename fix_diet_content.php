<?php
// Basic Class Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'HBM\\';
    $base_dir = __DIR__ . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

use HBM\Helpers\Env;
use HBM\Core\Database;

Env::load(__DIR__ . '/.env');

$db = Database::getConnection();

$content = '<h2>Introduction</h2>
<p>Learn how to create simple, sustainable eating habits for long-term health.</p>
<img src="assets/article_diet.png" alt="A Beginner\'s Guide to a Balanced Diet" class="w-full rounded-xl my-8 shadow-sm border border-gray-100">
<h2>What is a Balanced Diet?</h2>
<p>A balanced diet is a diet that contains differing kinds of foods in certain quantities and proportions so that the requirement for calories, proteins, minerals, vitamins and alternative nutrients is adequate and a small provision is reserved for additional nutrients to endure the short length of leanness.</p>
<h2>Why is it Important?</h2>
<p>Eating a balanced diet is vital for good health and wellbeing. Food provides our bodies with the energy, protein, essential fats, vitamins and minerals to live, grow and function properly. We need a wide variety of different foods to provide the right amounts of nutrients for good health.</p>
<h2>How to Achieve It</h2>
<ul>
    <li>Eat at least 5 portions of a variety of fruit and vegetables every day.</li>
    <li>Base meals on higher fibre starchy foods like potatoes, bread, rice or pasta.</li>
    <li>Have some dairy or dairy alternatives.</li>
    <li>Eat some beans, pulses, fish, eggs, meat and other protein.</li>
    <li>Choose unsaturated oils and spreads, and eat them in small amounts.</li>
    <li>Drink plenty of fluids.</li>
</ul>
<div class="article-quote">
    <i class="fa-solid fa-leaf"></i>
    <p>"Your diet is a bank account. Good food choices are good investments."</p>
</div>
<h2>Conclusion</h2>
<p>A balanced diet supplies the nutrients your body needs to work effectively. Without balanced nutrition, your body is more prone to disease, infection, fatigue, and low performance.</p>';

$stmt = $db->prepare("UPDATE articles SET content = :content WHERE slug = 'beginners-guide-balanced-diet'");
$stmt->execute(['content' => $content]);
echo "Updated successfully.\n";
