<?php
// Тестирование auth.php

header('Content-Type: application/json');

// Устанавливаем переменные окружения
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'TestClient';

// Пытаемся авторизоваться
$input = json_encode([
    'username' => 'test_admin',
    'password' => 'admin123'
]);

// Симулируем POST запрос
$_POST = json_decode($input, true);

// Читаем тестовые данные
$admins_file = __DIR__ . '/storage/admins.json';
$admins_data = json_decode(file_get_contents($admins_file), true);

echo json_encode([
    'status' => 'Testing auth',
    'admins_file_exists' => file_exists($admins_file),
    'admins_count' => count($admins_data['admins']),
    'first_admin' => [
        'username' => $admins_data['admins'][0]['username'] ?? null,
        'password' => $admins_data['admins'][0]['password'] ?? null,
        'role' => $admins_data['admins'][0]['role'] ?? null,
        'status' => $admins_data['admins'][0]['status'] ?? null,
    ]
], JSON_PRETTY_PRINT);
?>
