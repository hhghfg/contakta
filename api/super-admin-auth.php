<?php
/**
 * POST /api/super-admin-auth.php
 * Специальная аутентификация для суперадмина Никиты Синицина
 * С максимальным функционалом и защитой
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

define('SUPER_ADMIN_USERNAME', 'nikita_sinitsyn');
define('SUPER_ADMIN_PASSWORD', 'K7$mP2@xQw9#nL4$R8%vB1^jD5&fG3!h_SUPER_NIKITA_2025');
define('SUPER_ADMIN_NAME', 'Никита Синицин');

$storage_dir = __DIR__ . '/../storage';
@mkdir($storage_dir, 0755, true);

$super_admin_file = $storage_dir . '/super_admin_sessions.json';

$action = $_GET['action'] ?? $_POST['action'] ?? 'login';
$method = $_SERVER['REQUEST_METHOD'];

// ===== SUPER ADMIN LOGIN =====
if ($method === 'POST' && $action === 'login') {
    $input = json_decode(file_get_contents('php://input'), true);
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';
    
    // Логируем попытку входа (даже неудачную)
    $log_file = $storage_dir . '/super_admin_logs.jsonl';
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'action' => 'super_admin_login_attempt',
        'username' => $username,
        'success' => false,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];
    
    if ($username !== SUPER_ADMIN_USERNAME || $password !== SUPER_ADMIN_PASSWORD) {
        // Отправляем уведомление об попытке несанкционированного доступа
        $alert_email = 'nikita@kontanta.ru';
        $alert_subject = '=?UTF-8?B?' . base64_encode('🚨 Попытка входа в УЗ суперадмина') . '?=';
        $alert_message = "⚠️ ВНИМАНИЕ! Попытка входа в учетную запись суперадмина!\n\n";
        $alert_message .= "Логин: " . $username . "\n";
        $alert_message .= "IP адрес: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
        $alert_message .= "Время: " . date('Y-m-d H:i:s') . "\n";
        $alert_message .= "Браузер: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown') . "\n\n";
        $alert_message .= "Если это были вы - проигнорируйте.\n";
        $alert_message .= "Если нет - измените пароль немедленно!";
        
        $alert_headers = "Content-Type: text/plain; charset=UTF-8\r\n";
        $alert_headers .= "From: security@kontanta.ru\r\n";
        
        @mail($alert_email, $alert_subject, $alert_message, $alert_headers);
        
        @file_put_contents($log_file, json_encode($log_entry, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
        
        http_response_code(401);
        exit(json_encode(['ok' => false, 'error' => 'Invalid credentials'], JSON_UNESCAPED_UNICODE));
    }
    
    // Успешный вход
    $token = bin2hex(random_bytes(32));
    
    // Сохраняем сессию суперадмина
    $sessions = [];
    if (file_exists($super_admin_file)) {
        $sessions = json_decode(file_get_contents($super_admin_file), true) ?? [];
    }
    
    $sessions[$token] = [
        'username' => SUPER_ADMIN_USERNAME,
        'name' => SUPER_ADMIN_NAME,
        'role' => 'super_admin',
        'login_time' => date('Y-m-d H:i:s'),
        'last_activity' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    file_put_contents($super_admin_file, json_encode($sessions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // Логируем успешный вход
    $log_entry['success'] = true;
    @file_put_contents($log_file, json_encode($log_entry, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
    
    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'token' => $token,
        'admin' => [
            'username' => SUPER_ADMIN_USERNAME,
            'name' => SUPER_ADMIN_NAME,
            'role' => 'super_admin',
            'avatar' => '👨‍💼'
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== VERIFY SUPER ADMIN TOKEN =====
if ($method === 'POST' && $action === 'verify') {
    $input = json_decode(file_get_contents('php://input'), true);
    $token = $input['token'] ?? null;
    
    if (!$token) {
        http_response_code(401);
        exit(json_encode(['ok' => false, 'error' => 'No token'], JSON_UNESCAPED_UNICODE));
    }
    
    if (!file_exists($super_admin_file)) {
        http_response_code(401);
        exit(json_encode(['ok' => false, 'error' => 'Invalid token'], JSON_UNESCAPED_UNICODE));
    }
    
    $sessions = json_decode(file_get_contents($super_admin_file), true) ?? [];
    
    if (!isset($sessions[$token])) {
        http_response_code(401);
        exit(json_encode(['ok' => false, 'error' => 'Invalid token'], JSON_UNESCAPED_UNICODE));
    }
    
    $session = $sessions[$token];
    
    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'admin' => $session
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== LOGOUT =====
if ($method === 'POST' && $action === 'logout') {
    $input = json_decode(file_get_contents('php://input'), true);
    $token = $input['token'] ?? null;
    
    if ($token && file_exists($super_admin_file)) {
        $sessions = json_decode(file_get_contents($super_admin_file), true) ?? [];
        unset($sessions[$token]);
        file_put_contents($super_admin_file, json_encode($sessions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    http_response_code(200);
    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'Invalid action'], JSON_UNESCAPED_UNICODE);
?>
