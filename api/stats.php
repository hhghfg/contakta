<?php
/**
 * GET /api/stats.php?token=xxx
 * Получить статистику
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $token = trim($_GET['token'] ?? '');

    if (empty($token)) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'No token']);
        exit;
    }

    $storageDir = dirname(__FILE__) . '/../storage';

    // Проверяем сессию
    $sessionsFile = $storageDir . '/sessions.json';
    $sessions = json_decode(@file_get_contents($sessionsFile), true) ?: [];

    if (!isset($sessions[$token])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Invalid session']);
        exit;
    }

    $session = $sessions[$token];
    $adminId = $session['admin_id'];
    $role = $session['role'];

    // Читаем заявки
    $leadsFile = $storageDir . '/leads.json';
    $leads = [];

    if (file_exists($leadsFile)) {
        $content = @file_get_contents($leadsFile);
        if ($content) {
            $leads = json_decode($content, true) ?: [];
        }
    }

    // Считаем статистику
    $stats = [
        'total_leads' => 0,
        'new_leads' => 0,
        'my_assigned' => 0,
        'processing' => 0,
        'completed' => 0,
        'rejected' => 0,
        'my_processing' => 0,
        'my_completed' => 0
    ];

    $stats['total_leads'] = count($leads);

    foreach ($leads as $lead) {
        $stats['new_leads'] += ($lead['status'] === 'new' ? 1 : 0);
        $stats['processing'] += ($lead['status'] === 'processing' ? 1 : 0);
        $stats['completed'] += ($lead['status'] === 'completed' ? 1 : 0);
        $stats['rejected'] += ($lead['status'] === 'rejected' ? 1 : 0);

        if (!$role || $role === 'admin' || $role === 'moderator') {
            if (($lead['assigned_to_admin_id'] ?? null) == $adminId) {
                $stats['my_assigned']++;
                $stats['my_processing'] += ($lead['status'] === 'processing' ? 1 : 0);
                $stats['my_completed'] += ($lead['status'] === 'completed' ? 1 : 0);
            }
        }
    }

    // Читаем админов
    $adminsFile = $storageDir . '/admins.json';
    $totalAdmins = 0;

    if (file_exists($adminsFile)) {
        $admins = json_decode(@file_get_contents($adminsFile), true) ?: [];
        $totalAdmins = count($admins);
    }

    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'stats' => $stats,
        'total_admins' => $totalAdmins
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Server error']);
    error_log('[STATS_ERROR] ' . $e->getMessage());
}
?>
