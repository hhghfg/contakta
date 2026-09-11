<?php
// Просто используем plain text пароль для тестирования
// В production это нельзя делать!

$password = "admin123";
$hash = password_hash($password, PASSWORD_BCRYPT);

echo "Пароль: $password\n";
echo "Хеш: $hash\n";
?>
