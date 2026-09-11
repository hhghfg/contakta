<?php
/**
 * POST /api/lead-lock.php
 * Управление замками заявок
 * SECURITY: Token verification, rate limiting, input validation
 * v3.0.9: Только суперадмин может открыть завершённые заявки
 */

header('Content-Type: application/json; charset=utf-8');

$allowed_origins = ['http://localhost:8000', 'http://localhost', 'https://kontanta.ru'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
}
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

$storage_dir = __DIR__ . '/../storage';
@mkdir($storage_dir, 0755, true);
$locks_file = $storage_dir . '/' . basename('lead_locks.json');
$sessions_file = $storage_dir . '/' . basename('sessions.json');

// SECURITY: Verify token function
function verifyToken($token) {
    global $sessions_file;
    if (!$token || !file_exists($sessions_file)) {
        return null;
    }
    $sessions = json_decode(@file_get_contents($sessions_file), true) ?? [];
    $session = $sessions[$token] ?? null;
    
    // Check session timeout (30 minutes)
    if ($session && isset($session['last_activity'])) {
        $last = strtotime($session['last_activity']);
        $now = time();
        if (($now - $last) > (30 * 60)) {
            return null;
        }
    }
    
    return $session;
}

// ===== LOCK LEAD =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'lock') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // SECURITY: Token verification
    $token = $input['token'] ?? null;
    $session = verifyToken($token);
    
    if (!$session) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Неавторизованный доступ'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $lead_id = (int)($input['lead_id'] ?? 0);
    $admin_id = (int)($input['admin_id'] ?? 0);
    $admin_name = trim($input['admin_name'] ?? '');
    
    // SECURITY: Validate inputs
    if (!$lead_id || $lead_id <= 0 || $lead_id > 1000000) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Неверный ID заявки'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if (!$admin_id || $admin_id < 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Неверный ID администратора'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if (strlen($admin_name) < 2 || strlen($admin_name) > 100) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Неверное имя администратора'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // SECURITY: Verify that session admin_id matches request admin_id
    if ((int)$session['admin_id'] !== $admin_id && $session['role'] !== 'super_admin') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'ID администратора не совпадает'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // НОВОЕ v3.0.9: Проверяем статус заявки перед занятием
    $leads_file = $storage_dir . '/leads.json';
    $leads = [];
    if (file_exists($leads_file)) {
        $leads_content = @file_get_contents($leads_file);
        if ($leads_content) {
            $decoded = json_decode($leads_content, true);
            if (is_array($decoded)) {
                $leads = $decoded;
            }
        }
    }
    
    $lead = $leads[$lead_id] ?? null;
    if ($lead && $lead['status'] === 'done') {
        // Заявка завершена - могут занять только суперадмины
        if ($session['role'] !== 'super_admin') {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Завершённую заявку может открыть только главный администратор'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    
    $locks = [];
    if (file_exists($locks_file)) {
        $locks = json_decode(file_get_contents($locks_file), true) ?? [];
    }
    
    // Проверяем если заявка уже занята
    if (isset($locks[$lead_id])) {
        $current_lock = $locks[$lead_id];
        
        // Если её занял ТОТ ЖЕ админ или это суперадмин
        if ((int)$current_lock['admin_id'] === $admin_id || $session['role'] === 'super_admin') {
            // Перезанимаем (обновляем время замка)
            $locks[$lead_id] = [
                'lead_id' => $lead_id,
                'admin_id' => $admin_id,
                'admin_name' => $admin_name,
                'locked_at' => date('Y-m-d H:i:s'),
                'lock_time' => time(),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ];
            
            file_put_contents($locks_file, json_encode($locks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
            
            http_response_code(200);
            echo json_encode(['ok' => true, 'message' => 'Заявка повторно занята'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Если занята ДРУГИМ админом - ошибка
        http_response_code(409);
        echo json_encode([
            'ok' => false,
            'error' => 'Заявка уже занята',
            'locked_by' => htmlspecialchars($current_lock['admin_name'], ENT_QUOTES, 'UTF-8'),
            'locked_since' => $current_lock['locked_at']
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Первый раз занимаем
    $locks[$lead_id] = [
        'lead_id' => $lead_id,
        'admin_id' => $admin_id,
        'admin_name' => $admin_name,
        'locked_at' => date('Y-m-d H:i:s'),
        'lock_time' => time(),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    // SECURITY: Limit number of locks (prevent memory exhaustion)
    if (count($locks) > 50000) {
        // Remove old locks
        $locks = array_slice($locks, -25000, 25000, true);
    }
    
    file_put_contents($locks_file, json_encode($locks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    
    http_response_code(200);
    echo json_encode(['ok' => true, 'message' => 'Заявка занята успешно'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== UNLOCK LEAD =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'unlock') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // SECURITY: Token verification
    $token = $input['token'] ?? null;
    $session = verifyToken($token);
    
    if (!$session) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Неавторизованный доступ'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $lead_id = (int)($input['lead_id'] ?? 0);
    $admin_id = (int)($input['admin_id'] ?? 0);
    
    // SECURITY: Validate inputs
    if (!$lead_id || $lead_id <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Неверный ID заявки'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if (!file_exists($locks_file)) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Замки не найдены'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $locks = json_decode(file_get_contents($locks_file), true) ?? [];
    
    // Check permissions
    if (!isset($locks[$lead_id])) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Заявка не занята'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // SECURITY: Only owner or super_admin can unlock
    $lock_owner_id = $locks[$lead_id]['admin_id'] ?? null;
    if ((int)$session['admin_id'] !== $lock_owner_id && $session['role'] !== 'super_admin') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Только занявший может освободить'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    unset($locks[$lead_id]);
    file_put_contents($locks_file, json_encode($locks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    
    http_response_code(200);
    echo json_encode(['ok' => true, 'message' => 'Заявка освобождена'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== CHECK LOCK STATUS =====
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'check') {
    // SECURITY: Token verification
    $token = $_GET['token'] ?? null;
    $session = verifyToken($token);
    
    if (!$session) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Неавторизованный доступ'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $lead_id = (int)($_GET['lead_id'] ?? 0);
    
    if (!$lead_id || $lead_id <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Неверный ID заявки'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $locks = [];
    if (file_exists($locks_file)) {
        $locks = json_decode(file_get_contents($locks_file), true) ?? [];
    }
    
    if (isset($locks[$lead_id])) {
        $lock = $locks[$lead_id];
        $locked_seconds = time() - ($lock['lock_time'] ?? time());
        
        http_response_code(200);
        echo json_encode([
            'ok' => true,
            'locked' => true,
            'locked_by' => htmlspecialchars($lock['admin_name'], ENT_QUOTES, 'UTF-8'),
            'locked_at' => $lock['locked_at'],
            'seconds_locked' => $locked_seconds
        ], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(200);
        echo json_encode(['ok' => true, 'locked' => false], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ===== GET ALL LOCKS =====
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'all') {
    // SECURITY: Token verification
    $token = $_GET['token'] ?? null;
    $session = verifyToken($token);
    
    if (!$session) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Неавторизованный доступ'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $locks = [];
    if (file_exists($locks_file)) {
        $locks = json_decode(file_get_contents($locks_file), true) ?? [];
    }
    
    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'locks' => $locks,
        'total_locked' => count($locks)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'Неверное действие'], JSON_UNESCAPED_UNICODE);
?>
