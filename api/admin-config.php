<?php
/**
 * Конфиг скрытого доступа в админку
 * ВАЖНО: Безопасность критична!
 */

// ============ ОСНОВНЫЕ НАСТРОЙКИ БЕЗОПАСНОСТИ ============

// Скрытая ссылка администратора (генерируется через generate-admin-key.php)
// Пример: /контанта/a2F0YW50YV9hZG1pbl9zY2V0X3BhZWw=
// ЗАМЕНИТЕ НА СВОЮ СГЕНЕРИРОВАННУЮ ССЫЛКУ!
define('ADMIN_SECRET_PATH', 'контанта');

// Хэш пути для проверки (SHA512)
// Генерируется при создании ключа
define('ADMIN_KEY_HASH', 'f8e9a7c4b2d1f3e5a9c7b5d3f1e9a7c4b2d1f3e5a9c7b5d3f1e9a7c4b2d1f3e5a9c7b5d3f1e9a7c4b2d1f3e5a9c7b5d3');

// Максимальные попытки входа перед блокировкой
define('MAX_LOGIN_ATTEMPTS', 3);

// Время блокировки после превышения попыток (в секундах)
define('LOCKOUT_DURATION', 3600); // 1 час

// IP адреса, которые НЕ могут войти (для дополнительной защиты)
define('BLOCKED_IPS', [
    // '192.168.1.1',
    // '10.0.0.1',
]);

// Разрешенные IP адреса (если пусто = любые; укажите для максимальной защиты)
define('ALLOWED_IPS', [
    // '192.168.1.100',
    // '203.0.113.45',
]);

// Требовать HTTPS для админки
define('REQUIRE_HTTPS', true);

// Требовать User-Agent (браузер)
define('REQUIRE_USER_AGENT', true);

// Время жизни сессии админа (в секундах)
define('ADMIN_SESSION_LIFETIME', 1800); // 30 минут

// Время жизни токена доступа
define('ADMIN_TOKEN_LIFETIME', 3600); // 1 час

// Директория для хранения данных админки
define('ADMIN_STORAGE_PATH', dirname(__FILE__) . '/../storage/admin/');

// Директория логов безопасности
define('ADMIN_LOGS_PATH', dirname(__FILE__) . '/../storage/admin/logs/');

// Логировать все попытки доступа
define('LOG_ALL_ACCESS', true);

// Отправлять алерты на почту при подозрительной активности
define('ALERT_EMAIL', 'admin@example.com');

// ============ ФУНКЦИИ БЕЗОПАСНОСТИ ============

function verify_admin_access() {
    // Проверка HTTPS
    if (REQUIRE_HTTPS && $_SERVER['REQUEST_SCHEME'] !== 'https' && $_ENV['APP_ENV'] !== 'development') {
        return false;
    }
    
    // Проверка User-Agent
    if (REQUIRE_USER_AGENT && empty($_SERVER['HTTP_USER_AGENT'])) {
        return false;
    }
    
    // Проверка IP
    $client_ip = get_client_ip();
    
    if (!empty(BLOCKED_IPS) && in_array($client_ip, BLOCKED_IPS)) {
        return false;
    }
    
    if (!empty(ALLOWED_IPS) && !in_array($client_ip, ALLOWED_IPS)) {
        return false;
    }
    
    return true;
}

function get_client_ip() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP']; // CloudFlare
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        return $_SERVER['REMOTE_ADDR'];
    }
    return 'unknown';
}

function log_admin_access($action, $status, $details = []) {
    if (!LOG_ALL_ACCESS) return;
    
    if (!is_dir(ADMIN_LOGS_PATH)) {
        mkdir(ADMIN_LOGS_PATH, 0700, true);
    }
    
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'action' => $action,
        'status' => $status,
        'ip' => get_client_ip(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'details' => $details
    ];
    
    $log_file = ADMIN_LOGS_PATH . date('Y-m-d') . '.jsonl';
    file_put_contents($log_file, json_encode($log_entry, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
}

function check_brute_force_protection() {
    $cache_file = ADMIN_LOGS_PATH . 'brute_force_' . md5(get_client_ip()) . '.txt';
    
    if (!is_dir(ADMIN_LOGS_PATH)) {
        mkdir(ADMIN_LOGS_PATH, 0700, true);
    }
    
    if (file_exists($cache_file)) {
        $data = json_decode(file_get_contents($cache_file), true);
        
        // Проверяем, не заблокирован ли IP
        if ($data['blocked_until'] > time()) {
            return [
                'blocked' => true,
                'remaining' => $data['blocked_until'] - time()
            ];
        }
        
        // Проверяем количество попыток
        if ($data['attempts'] >= MAX_LOGIN_ATTEMPTS) {
            $data['blocked_until'] = time() + LOCKOUT_DURATION;
            file_put_contents($cache_file, json_encode($data));
            
            return [
                'blocked' => true,
                'remaining' => LOCKOUT_DURATION
            ];
        }
    }
    
    return ['blocked' => false];
}

function increment_brute_force_counter() {
    $cache_file = ADMIN_LOGS_PATH . 'brute_force_' . md5(get_client_ip()) . '.txt';
    
    if (!is_dir(ADMIN_LOGS_PATH)) {
        mkdir(ADMIN_LOGS_PATH, 0700, true);
    }
    
    $data = [];
    if (file_exists($cache_file)) {
        $data = json_decode(file_get_contents($cache_file), true);
    } else {
        $data = ['attempts' => 0, 'blocked_until' => 0];
    }
    
    $data['attempts']++;
    file_put_contents($cache_file, json_encode($data));
}

function reset_brute_force_counter() {
    $cache_file = ADMIN_LOGS_PATH . 'brute_force_' . md5(get_client_ip()) . '.txt';
    if (file_exists($cache_file)) {
        unlink($cache_file);
    }
}

// Создаем директории при первом включении
if (!is_dir(ADMIN_STORAGE_PATH)) {
    mkdir(ADMIN_STORAGE_PATH, 0700, true);
}
if (!is_dir(ADMIN_LOGS_PATH)) {
    mkdir(ADMIN_LOGS_PATH, 0700, true);
}
?>
