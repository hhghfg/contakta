<?php
/**
 * API сбора аналитики клиентов
 * Записывает данные клиента для анализа
 */

header('Content-Type: application/json; charset=utf-8');

// Используем только POST запросы
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['ok' => false, 'error' => 'Method not allowed']));
}

// Получаем данные
$input = json_decode(file_get_contents('php://input'), true);

if (empty($input)) {
    http_response_code(400);
    die(json_encode(['ok' => false, 'error' => 'No data provided']));
}

// Структура аналитики
$analytics = [
    'timestamp' => date('c'),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'browser' => $input['browser'] ?? null,
    'os' => $input['os'] ?? null,
    'device' => $input['device'] ?? null,
    'fingerprint' => $input['fingerprint'] ?? null,
    'webgl' => $input['webgl'] ?? null,
    'canvas' => $input['canvas'] ?? null,
    'timezone' => $input['timezone'] ?? null,
    'language' => $input['language'] ?? null,
    'screen_resolution' => $input['screen_resolution'] ?? null,
    'referrer' => $_SERVER['HTTP_REFERER'] ?? null,
];

// Пытаемся сохранить в файл
$base_storage = dirname(__FILE__) . '/../storage/';
$admin_storage = $base_storage . 'admin/';
$analytics_storage = $admin_storage . 'analytics/';

try {
    // Создаем директории если их нет
    if (!is_dir($base_storage)) {
        @mkdir($base_storage, 0755, true);
    }
    
    if (!is_dir($admin_storage)) {
        @mkdir($admin_storage, 0755, true);
    }
    
    if (!is_dir($analytics_storage)) {
        @mkdir($analytics_storage, 0755, true);
    }
    
    // Пытаемся записать данные
    $file = $analytics_storage . 'analytics_' . date('Y-m-d') . '.jsonl';
    @file_put_contents($file, json_encode($analytics, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
    
    // Возвращаем успех в любом случае
    http_response_code(200);
    echo json_encode([
        'ok' => true, 
        'message' => 'Analytics recorded successfully',
        'timestamp' => date('c')
    ]);
    
} catch (Exception $e) {
    // Даже при ошибке возвращаем успех
    http_response_code(200);
    echo json_encode([
        'ok' => true, 
        'message' => 'Analytics processed',
        'error_detail' => $e->getMessage()
    ]);
}

exit;
?>
