<?php
/**
 * GET /api/leads-extended.php?token=xxx
 * Получить заявки с расширенной информацией и ЗАЩИТОЙ
 * SECURITY: Token validation, Rate limiting, IP checking
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

require_once 'auth-middleware.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $auth = new AuthMiddleware();
    
    // Проверяем rate limit
    $auth->checkRateLimit('/api/leads-extended');
    
    // Проверяем токен
    $token = $_GET['token'] ?? '';
    $session = $auth->validateToken($token);
    
    $storageDir = dirname(__FILE__) . '/../storage';

    // Обновляем last_activity
    $sessionsFile = $storageDir . '/sessions.json';
    $sessions = json_decode(@file_get_contents($sessionsFile), true) ?: [];
    if (isset($sessions[$token])) {
        $sessions[$token]['last_activity'] = date('Y-m-d H:i:s');
        @file_put_contents($sessionsFile, json_encode($sessions, JSON_PRETTY_PRINT));
    }

    // Читаем заявки
    $leadsFile = $storageDir . '/leads.json';
    $leads = [];

    if (file_exists($leadsFile)) {
        $content = @file_get_contents($leadsFile);
        if ($content) {
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                $leads = $decoded;
            }
        }
    }

    // Обогащаем заявки информацией об админе
    foreach ($leads as &$lead) {
        $lead['assigned_to'] = null;
        $lead['assigned_to_id'] = null;

        if (!empty($lead['assigned_to_admin_id'])) {
            $adminsFile = $storageDir . '/admins.json';
            if (file_exists($adminsFile)) {
                $admins = json_decode(@file_get_contents($adminsFile), true) ?: [];
                foreach ($admins as $admin) {
                    if ($admin['id'] === $lead['assigned_to_admin_id']) {
                        $lead['assigned_to'] = $admin['name'];
                        $lead['assigned_to_id'] = $admin['id'];
                        break;
                    }
                }
            }
        }
    }

    // Логируем доступ
    $activityFile = $storageDir . '/lead_activity.jsonl';
    $activity = [
        'timestamp' => date('Y-m-d H:i:s'),
        'admin_id' => $session['admin_id'],
        'admin_name' => $session['name'],
        'action' => 'leads_viewed',
        'count' => count($leads),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    @file_put_contents($activityFile, json_encode($activity, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);

    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'leads' => $leads,
        'total' => count($leads),
        'user_id' => $session['admin_id'],
        'user_role' => $session['role']
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Server error']);
    error_log('[LEADS_ERROR] ' . $e->getMessage());
}
?>
