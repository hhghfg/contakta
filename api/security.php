<?php
/**
 * КОНТАНТА - Максимальная система безопасности
 * Все API защищены на максимум
 */

// Отключить вывод ошибок (только логирование)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Константы безопасности
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_WINDOW', 1800); // 30 минут
define('BCRYPT_COST', 10);
define('SESSION_LIFETIME', 3600); // 1 час
define('CSRF_TOKEN_LIFETIME', 3600);
define('API_RATE_LIMIT', 120);
define('STORAGE_PATH', __DIR__ . '/../storage/');

// ============== ФУНКЦИИ БЕЗОПАСНОСТИ ==============

function get_bearer_token() {
    $headers = null;
    
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER['Authorization']);
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
    } elseif (function_exists('apache_request_headers')) {
        $request_headers = apache_request_headers();
        $headers = isset($request_headers['Authorization']) ? trim($request_headers['Authorization']) : null;
    }
    
    if (!empty($headers) && preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
        return $matches[1];
    }
    
    return null;
}

function get_session_data($token) {
    if (!$token) {
        return null;
    }
    
    $session = validate_session($token);
    
    if (!$session) {
        return null;
    }
    
    $admins_file = STORAGE_PATH . 'admins.json';
    if (!file_exists($admins_file)) {
        return null;
    }
    
    $admins_data = json_decode(file_get_contents($admins_file), true);
    
    foreach ($admins_data['admins'] as $admin) {
        if ($admin['id'] === $session['admin_id']) {
            return [
                'admin_id' => $admin['id'],
                'username' => $admin['username'],
                'email' => $admin['email'],
                'role' => $admin['role'],
                'token' => $token
            ];
        }
    }
    
    return null;
}

function log_security_event($event_type, $details) {
    $log_file = STORAGE_PATH . 'security_log.jsonl';
    $log_entry = json_encode([
        'timestamp' => date('c'),
        'type' => $event_type,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'details' => $details
    ]) . "\n";
    
    @file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

function generate_secure_token($length = 64) {
    return bin2hex(random_bytes($length / 2));
}

function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
}

function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

function check_login_rate_limit() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rate_limit_file = STORAGE_PATH . 'rate_limit.json';
    
    if (!file_exists($rate_limit_file)) {
        file_put_contents($rate_limit_file, json_encode([]), LOCK_EX);
    }
    
    $attempts = json_decode(file_get_contents($rate_limit_file), true) ?? [];
    $today = date('Y-m-d');
    
    if (!isset($attempts[$ip][$today])) {
        $attempts[$ip][$today] = ['count' => 0, 'blocked_until' => null];
    }
    
    $attempt = $attempts[$ip][$today];
    
    if ($attempt['blocked_until'] && time() < strtotime($attempt['blocked_until'])) {
        log_security_event('LOGIN_ATTEMPT_BLOCKED', ['ip' => $ip, 'reason' => 'rate_limit']);
        return false;
    }
    
    if ($attempt['blocked_until'] && time() >= strtotime($attempt['blocked_until'])) {
        $attempts[$ip][$today] = ['count' => 0, 'blocked_until' => null];
    }
    
    return true;
}

function record_login_attempt($success = false) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rate_limit_file = STORAGE_PATH . 'rate_limit.json';
    
    if (!file_exists($rate_limit_file)) {
        file_put_contents($rate_limit_file, json_encode([]), LOCK_EX);
    }
    
    $attempts = json_decode(file_get_contents($rate_limit_file), true) ?? [];
    $today = date('Y-m-d');
    
    if (!isset($attempts[$ip][$today])) {
        $attempts[$ip][$today] = ['count' => 0, 'blocked_until' => null];
    }
    
    if ($success) {
        $attempts[$ip][$today] = ['count' => 0, 'blocked_until' => null];
    } else {
        $attempts[$ip][$today]['count']++;
        
        if ($attempts[$ip][$today]['count'] >= MAX_LOGIN_ATTEMPTS) {
            $attempts[$ip][$today]['blocked_until'] = date('c', time() + LOGIN_ATTEMPT_WINDOW);
            log_security_event('LOGIN_BLOCKED', ['ip' => $ip, 'reason' => 'max_attempts']);
        }
    }
    
    file_put_contents($rate_limit_file, json_encode($attempts, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), LOCK_EX);
}

function create_session($admin_id, $admin_data) {
    $session_file = STORAGE_PATH . 'sessions.json';
    
    if (!file_exists($session_file)) {
        file_put_contents($session_file, json_encode([]), LOCK_EX);
    }
    
    $sessions = json_decode(file_get_contents($session_file), true) ?? [];
    
    $session_token = generate_secure_token();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $session = [
        'token' => $session_token,
        'admin_id' => $admin_id,
        'role' => $admin_data['role'],
        'username' => $admin_data['username'] ?? '',
        'email' => $admin_data['email'] ?? '',
        'created_at' => date('c'),
        'expires_at' => date('c', time() + SESSION_LIFETIME),
        'ip' => $ip,
        'user_agent' => $user_agent,
        'last_activity' => date('c')
    ];
    
    $sessions[$session_token] = $session;
    
    // Ограничить до 1000 последних сессий
    if (count($sessions) > 1000) {
        $sessions = array_slice($sessions, -1000, 1000, true);
    }
    
    file_put_contents($session_file, json_encode($sessions, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), LOCK_EX);
    
    log_security_event('SESSION_CREATED', ['admin_id' => $admin_id, 'role' => $admin_data['role']]);
    
    return $session_token;
}

function validate_session($token) {
    $session_file = STORAGE_PATH . 'sessions.json';
    
    if (!file_exists($session_file)) {
        return false;
    }
    
    $sessions = json_decode(file_get_contents($session_file), true) ?? [];
    
    if (!isset($sessions[$token])) {
        log_security_event('INVALID_SESSION', ['token_prefix' => substr($token, 0, 10)]);
        return false;
    }
    
    $session = $sessions[$token];
    
    // Проверка срока действия
    if (strtotime($session['expires_at']) < time()) {
        unset($sessions[$token]);
        file_put_contents($session_file, json_encode($sessions, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), LOCK_EX);
        log_security_event('SESSION_EXPIRED', ['admin_id' => $session['admin_id']]);
        return false;
    }
    
    // Проверка IP
    if ($session['ip'] !== ($_SERVER['REMOTE_ADDR'] ?? 'unknown')) {
        log_security_event('SESSION_HIJACKING_ATTEMPT', ['admin_id' => $session['admin_id']]);
        return false;
    }
    
    // Проверка User Agent
    if ($session['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown')) {
        log_security_event('USER_AGENT_MISMATCH', ['admin_id' => $session['admin_id']]);
        return false;
    }
    
    // Обновление времени активности
    $session['last_activity'] = date('c');
    $sessions[$token] = $session;
    file_put_contents($session_file, json_encode($sessions, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), LOCK_EX);
    
    return $session;
}

// CORS и Security Headers
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? 'http://localhost'));
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');
header('Access-Control-Allow-Credentials: true');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Content-Type: application/json; charset=UTF-8');
?>
