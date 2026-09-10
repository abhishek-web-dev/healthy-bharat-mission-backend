<?php
$url = 'http://localhost:8008/api/auth/login';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['email'=>'admin@healthybharatmission.com', 'password'=>'password']));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
$data = json_decode($res, true);
$token = $data['data']['token'] ?? '';
echo "Token: $token\n";

$url2 = 'http://localhost:8008/api/auth/me';
$ch2 = curl_init($url2);
curl_setopt($ch2, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
$res2 = curl_exec($ch2);
echo "Auth /me Response: $res2\n";
