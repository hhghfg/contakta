<?php
// Скрипт для генерации BCRYPT хеша пароля
// Используется для обновления пароля администратора

$password = "dskjfbdhsbfi932ucvw92cgcbw8ygf7fb397gf97gf9h";
$hash = password_hash($password, PASSWORD_BCRYPT);

echo "Пароль: " . $password . "\n";
echo "BCRYPT Хеш: " . $hash . "\n";
echo "\nСкопируйте хеш выше и вставьте в storage/admins.json\n";
echo "как значение для 'password_hash' админа Nilita_Sinitsin\n";

// Автоматическое обновление storage/admins.json
$admins_file = dirname(__FILE__) . '/storage/admins.json';

if (file_exists($admins_file)) {
    $admins_data = json_decode(file_get_contents($admins_file), true);
    
    // Найти и обновить пароль для Nilita_Sinitsin
    foreach ($admins_data['admins'] as &$admin) {
        if ($admin['username'] === 'Nilita_Sinitsin') {
            $admin['password_hash'] = $hash;
            echo "\n✅ Пароль обновлен для админа Nilita_Sinitsin\n";
            break;
        }
    }
    
    file_put_contents($admins_file, json_encode($admins_data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), LOCK_EX);
    echo "✅ Файл storage/admins.json обновлен!\n";
} else {
    echo "❌ Файл storage/admins.json не найден\n";
}
?>
