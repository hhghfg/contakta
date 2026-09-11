<?php
// Скрипт для быстрого входа - просто переустанавливает пароль на известный

$password = "TestPass123!";

// Используем встроенную функцию PHP для хеширования
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

$admins_data = [
    "admins" => [
        [
            "id" => "sa_001",
            "username" => "Главный_Админ",
            "password_hash" => $hash,
            "email" => "admin@kontanta.ru",
            "role" => "super-admin",
            "created_at" => "2024-01-01T00:00:00Z",
            "last_login" => null,
            "status" => "active",
            "failed_attempts" => 0,
            "blocked_until" => null
        ]
    ]
];

file_put_contents(__DIR__ . '/../storage/admins.json', json_encode($admins_data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

echo json_encode([
    'ok' => true,
    'message' => 'Пароль переустановлен',
    'username' => 'Главный_Админ',
    'password' => $password,
    'hash' => $hash
]);
?>
