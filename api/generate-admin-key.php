<?php
/**
 * Генератор скрытой ссылки для доступа в админку
 */

header('Content-Type: application/json; charset=utf-8');

try {
    // Генерируем стойкий ключ
    $timestamp = time();
    $random_bytes = bin2hex(random_bytes(64));
    
    // Создаем хэш ключа
    $admin_key = hash('sha256', $random_bytes . $timestamp . $_SERVER['HTTP_HOST']);
    $key_hash = hash('sha512', $admin_key);
    
    // Создаем скрытую URL часть
    $final_path = str_replace(['+', '/', '='], ['_', '-', ''], base64_encode($admin_key));
    
    http_response_code(200);
    echo json_encode([
        'статус' => 'успешно',
        'скрытая_ссылка' => '/контанта/' . $final_path,
        'полная_ссылка' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/контанта/' . $final_path,
        'ключ_для_хранения' => $key_hash,
        'инструкция' => 'Сохраните ключ в api/admin-config.php'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'статус' => 'ошибка',
        'сообщение' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

exit;
?>
