<?php
/**
 * POST /api/logout.php
 * Выход из системы
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    $token = trim((string)($data['token'] ?? ''));

    if (empty($token)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'No token']);
        exit;
    }

    $storageDir = dirname(__FILE__) . '/../storage';
    $sessionsFile = $storageDir . '/sessions.json';

    if (file_exists($sessionsFile)) {
        $sessions = json_decode(@file_get_contents($sessionsFile), true) ?: [];
        
        if (isset($sessions[$token])) {
            // Логируем logout
            $activityFile = $storageDir . '/lead_activity.jsonl';
            $activity = [
                'timestamp' => date('Y-m-d H:i:s'),
                'admin_id' => $sessions[$token]['admin_id'] ?? null,
                'admin_name' => $sessions[$token]['name'] ?? null,
                'action' => 'logout',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ];
            @file_put_contents($activityFile, json_encode($activity, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
            
            unset($sessions[$token]);
            @file_put_contents($sessionsFile, json_encode($sessions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    http_response_code(200);
    echo json_encode(['ok' => true, 'message' => 'Logged out']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Server error']);
}
?>
