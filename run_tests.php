<?php
$baseUrl = 'http://localhost:8006';
function request($method, $path, $data = null, $token = null) {
    global $baseUrl;
    $url = $baseUrl . $path;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $headers = [];
    if ($data !== null) {
        $json = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        $headers[] = 'Content-Type: application/json';
    }
    if ($token !== null) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => $response
    ];
}

echo "=== 1. HEALTH CHECK ===\n";
$health = request('GET', '/api/health');
echo "Status: " . $health['code'] . "\n";
echo "Response: " . $health['body'] . "\n\n";

if ($health['code'] !== 200 || strpos($health['body'], '"database":"connected"') === false) {
    echo "STOPPING TESTS - DATABASE DISCONNECTED OR ERROR\n";
    exit(1);
}

echo "=== 2. AUTH TESTS ===\n";
$reg = request('POST', '/api/auth/register', [
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'test' . time() . '@example.com',
    'phone' => '9988' . rand(100000, 999999),
    'password' => 'SecurePass123!'
]);
echo "Register (valid): " . $reg['code'] . "\n";

$regDup = request('POST', '/api/auth/register', [
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'admin@healthybharatmission.com',
    'phone' => '1234567890',
    'password' => 'SecurePass123!'
]);
echo "Register (duplicate email): " . $regDup['code'] . "\n";

$login = request('POST', '/api/auth/login', [
    'email' => 'admin@healthybharatmission.com',
    'password' => 'password'
]);
echo "Login (valid admin): " . $login['code'] . "\n";

$loginInvalid = request('POST', '/api/auth/login', [
    'email' => 'admin@healthybharatmission.com',
    'password' => 'WrongPass!'
]);
echo "Login (invalid): " . $loginInvalid['code'] . "\n";

$adminData = json_decode($login['body'], true);
$adminToken = $adminData['data']['token'] ?? null;

$me = request('GET', '/api/auth/me', null, $adminToken);
echo "Auth /me (valid): " . $me['code'] . "\n";

$meInv = request('GET', '/api/auth/me', null, 'invalid_token');
echo "Auth /me (invalid): " . $meInv['code'] . "\n";

$logout = request('POST', '/api/auth/logout', null, $adminToken);
echo "Auth /logout (valid): " . $logout['code'] . "\n";

$forgot = request('POST', '/api/auth/forgot-password', ['email' => 'admin@healthybharatmission.com']);
echo "Forgot Password: " . $forgot['code'] . "\n";

$otp = request('POST', '/api/auth/verify-otp', ['email' => 'admin@healthybharatmission.com', 'otp' => '123456']);
echo "Verify OTP (invalid): " . $otp['code'] . "\n";

echo "\n=== 3. RBAC TESTS ===\n";
$unauthAdmin = request('GET', '/api/admin/programs');
echo "Unauth Admin Endpoint: " . $unauthAdmin['code'] . "\n";

$loginUser = request('POST', '/api/auth/login', [
    'email' => 'user@example.com',
    'password' => 'password'
]);
$userToken = json_decode($loginUser['body'], true)['data']['token'] ?? null;

$userAdmin = request('GET', '/api/admin/programs', null, $userToken);
echo "Normal User Admin Endpoint: " . $userAdmin['code'] . "\n";

$adminAdmin = request('GET', '/api/admin/programs', null, $adminToken);
echo "Admin -> Admin Endpoint: " . $adminAdmin['code'] . "\n";

echo "\n=== 4. CONTENT APIs ===\n";
$endpoints = [
    '/api/health-conditions',
    '/api/programs',
    '/api/articles',
    '/api/article-categories',
    '/api/article-tags',
    '/api/faqs',
    '/api/success-stories'
];
foreach ($endpoints as $ep) {
    $res = request('GET', $ep);
    echo "GET $ep: " . $res['code'] . "\n";
}

$invalidSlug = request('GET', '/api/programs/invalid-slug-not-exist');
echo "GET /api/programs/invalid-slug-not-exist: " . $invalidSlug['code'] . "\n";

echo "\n=== 5. ADMIN APIs ===\n";
$createProgram = request('POST', '/api/admin/programs', [
    'title' => 'Test Program',
    'slug' => 'test-program-' . time(),
    'short_description' => 'Test',
    'status' => 'published'
], $adminToken);
echo "Admin POST Program: " . $createProgram['code'] . "\n";

echo "\n=== 6. SECURITY TESTS ===\n";
$ch = curl_init($baseUrl . '/api/auth/login');
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_POSTFIELDS, '{malformed_json: true');
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
echo "Malformed JSON: " . curl_getinfo($ch, CURLINFO_HTTP_CODE) . "\n";

$missing = request('POST', '/api/auth/register', ['email' => 'test@test.com']);
echo "Missing fields: " . $missing['code'] . "\n";

$sqli = request('POST', '/api/auth/login', [
    'email' => "admin@healthybharatmission.com' OR '1'='1",
    'password' => 'Admin@123'
]);
echo "SQL Injection Login: " . $sqli['code'] . "\n";
