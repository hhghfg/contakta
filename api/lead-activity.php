<?php
/**
 * POST /api/lead-activity.php
 * Отслеживание активности - кто открывал/закрывал лиды
 * Статистика по администраторам
 * v3.2.1: Фильтрация удалённых администраторов
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

$storage_dir = __DIR__ . '/../storage';
@mkdir($storage_dir, 0755, true);

$activity_file = $storage_dir . '/lead_activity.jsonl';
$stats_file = $storage_dir . '/lead_stats.json';
$archived_stats_file = $storage_dir . '/lead_archived_stats.json';
$admins_file = $storage_dir . '/admins.json';
$leads_stats_file = $storage_dir . '/leads_stats_by_status.json';

// ===== LOG ACTIVITY =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'log') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $lead_id = (int)($input['lead_id'] ?? 0);
    $admin_id = (int)($input['admin_id'] ?? 0);
    $admin_name = $input['admin_name'] ?? '';
    $action = $input['action'] ?? '';
    $reason = $input['reason'] ?? null;
    
    if (!$lead_id || !$admin_id || !$action) {
        http_response_code(400);
        exit(json_encode(['ok' => false, 'error' => 'Не хватает обязательных полей'], JSON_UNESCAPED_UNICODE));
    }
    
    $activity = [
        'timestamp' => date('Y-m-d H:i:s'),
        'lead_id' => $lead_id,
        'admin_id' => $admin_id,
        'admin_name' => $admin_name,
        'action' => $action,
        'reason' => $reason,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    @file_put_contents($activity_file, json_encode($activity, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
    
    $stats = [];
    if (file_exists($stats_file)) {
        $stats = json_decode(file_get_contents($stats_file), true) ?? [];
    }
    
    if (!isset($stats[$admin_id])) {
        $stats[$admin_id] = [
            'admin_id' => $admin_id,
            'admin_name' => $admin_name,
            'total_opened' => 0,
            'total_closed' => 0,
            'total_viewed' => 0,
            'total_edited' => 0,
            'last_activity' => null,
            'first_seen' => date('Y-m-d H:i:s')
        ];
    }
    
    switch ($action) {
        case 'opened':
            $stats[$admin_id]['total_opened']++;
            break;
        case 'closed':
            $stats[$admin_id]['total_closed']++;
            break;
        case 'viewed':
            $stats[$admin_id]['total_viewed']++;
            break;
        case 'edited':
            $stats[$admin_id]['total_edited']++;
            break;
    }
    
    $stats[$admin_id]['last_activity'] = date('Y-m-d H:i:s');
    
    file_put_contents($stats_file, json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    http_response_code(201);
    echo json_encode([
        'ok' => true,
        'message' => 'Активность зафиксирована'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== ARCHIVE LEAD STATS =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'archive') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $lead_id = (int)($input['lead_id'] ?? 0);
    $lead_name = $input['lead_name'] ?? 'Unknown';
    
    if (!$lead_id) {
        http_response_code(400);
        exit(json_encode(['ok' => false, 'error' => 'lead_id не указан'], JSON_UNESCAPED_UNICODE));
    }
    
    $lead_history = [];
    if (file_exists($activity_file)) {
        $lines = file($activity_file, FILE_IGNORE_NEW_LINES);
        foreach ($lines as $line) {
            if (trim($line)) {
                $data = json_decode($line, true);
                if ($data && $data['lead_id'] === $lead_id) {
                    $lead_history[] = $data;
                }
            }
        }
    }
    
    $archived = [];
    if (file_exists($archived_stats_file)) {
        $archived = json_decode(file_get_contents($archived_stats_file), true) ?? [];
    }
    
    $archived[$lead_id] = [
        'lead_id' => $lead_id,
        'lead_name' => $lead_name,
        'archived_at' => date('Y-m-d H:i:s'),
        'activity_count' => count($lead_history),
        'history' => $lead_history
    ];
    
    file_put_contents($archived_stats_file, json_encode($archived, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'message' => 'Статистика заявки архивирована'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== GET ACTIVITY FOR LEAD =====
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'lead') {
    $lead_id = (int)($_GET['lead_id'] ?? 0);
    
    if (!$lead_id) {
        http_response_code(400);
        exit(json_encode(['ok' => false, 'error' => 'lead_id не указан'], JSON_UNESCAPED_UNICODE));
    }
    
    $activity = [];
    if (file_exists($activity_file)) {
        $lines = file($activity_file, FILE_IGNORE_NEW_LINES);
        foreach ($lines as $line) {
            if (trim($line)) {
                $data = json_decode($line, true);
                if ($data && $data['lead_id'] === $lead_id) {
                    $activity[] = $data;
                }
            }
        }
    }
    
    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'activity' => array_reverse($activity)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== GET ADMIN STATS (v3.2.1: ТОЛЬКО АКТИВНЫХ) =====
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'stats') {
    $stats = [];
    if (file_exists($stats_file)) {
        $stats = json_decode(file_get_contents($stats_file), true) ?? [];
    }
    
    // НОВОЕ v3.2.1: Загружаем активных администраторов
    $active_admin_ids = [];
    if (file_exists($admins_file)) {
        $admins_content = @file_get_contents($admins_file);
        if ($admins_content) {
            $admins = json_decode($admins_content, true) ?? [];
            foreach ($admins as $admin) {
                if ($admin['active'] ?? true) { // По умолчанию активен
                    $active_admin_ids[] = $admin['id'];
                }
            }
        }
    }
    
    // Фильтруем только активных администраторов
    $filtered_stats = [];
    foreach ($stats as $stat) {
        if (in_array($stat['admin_id'] ?? null, $active_admin_ids)) {
            $filtered_stats[] = $stat;
        }
    }
    
    // Сортируем по total_opened
    usort($filtered_stats, function($a, $b) {
        return ($b['total_opened'] + $b['total_closed']) - ($a['total_opened'] + $a['total_closed']);
    });
    
    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'stats' => $filtered_stats,
        'total_admins' => count($filtered_stats)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== GET ARCHIVED STATS =====
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'archived') {
    $archived = [];
    if (file_exists($archived_stats_file)) {
        $archived = json_decode(file_get_contents($archived_stats_file), true) ?? [];
    }
    
    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'archived' => $archived,
        'total_archived' => count($archived)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== GET LEAD HISTORY =====
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'all') {
    $activity = [];
    if (file_exists($activity_file)) {
        $lines = file($activity_file, FILE_IGNORE_NEW_LINES);
        foreach (array_reverse($lines) as $line) {
            if (trim($line)) {
                $activity[] = json_decode($line, true);
            }
        }
    }
    
    $limit = (int)($_GET['limit'] ?? 100);
    
    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'activity' => array_slice($activity, 0, $limit),
        'total' => count($activity)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== GET LEADS STATS BY STATUS =====
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'by_status') {
    $leads_file = $storage_dir . '/leads.json';
    $leads = [];
    
    if (file_exists($leads_file)) {
        $leads_content = file_get_contents($leads_file);
        if ($leads_content) {
            $leads = json_decode($leads_content, true) ?? [];
        }
    }
    
    $stats_by_status = [
        'new' => 0,
        'in_work' => 0,
        'processed' => 0,
        'done' => 0
    ];
    
    foreach ($leads as $lead) {
        $status = $lead['status'] ?? 'new';
        if (isset($stats_by_status[$status])) {
            $stats_by_status[$status]++;
        }
    }
    
    $archived_stats = [
        'total_active' => count($leads),
        'total_archived' => 0
    ];
    
    if (file_exists($archived_stats_file)) {
        $archived = json_decode(file_get_contents($archived_stats_file), true) ?? [];
        $archived_stats['total_archived'] = count($archived);
    }
    
    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'by_status' => $stats_by_status,
        'totals' => $archived_stats
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'Неверное действие'], JSON_UNESCAPED_UNICODE);
?>
