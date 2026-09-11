<?php
/**
 * POST /api/super-admin-manage.php
 * Управление администраторами (удаление, блокировка, смена ролей)
 * Только для суперадмина
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

$super_admin_file = $storage_dir . '/super_admin_sessions.json';

// Проверяем что это суперадмин
function verifySuperAdmin($token) {
    global $super_admin_file;
    
    if (!$token || !file_exists($super_admin_file)) {
        return false;
    }
    
    $sessions = json_decode(file_get_contents($super_admin_file), true) ?? [];
    return isset($sessions[$token]);
}

$action = $_GET['action'] ?? $_POST['action'] ?? null;
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$token = $input['token'] ?? null;

// Проверка токена
if (!verifySuperAdmin($token)) {
    http_response_code(403);
    exit(json_encode(['ok' => false, 'error' => 'Forbidden'], JSON_UNESCAPED_UNICODE));
}

$admins_file = $storage_dir . '/admins.json';
$logs_file = $storage_dir . '/super_admin_logs.jsonl';

// ===== DELETE ADMIN =====
if ($method === 'POST' && $action === 'delete_admin') {
    $admin_id = (int)($input['admin_id'] ?? 0);
    
    if ($admin_id <= 0) {
        http_response_code(400);
        exit(json_encode(['ok' => false, 'error' => 'Invalid admin ID'], JSON_UNESCAPED_UNICODE));
    }
    
    if (!file_exists($admins_file)) {
        http_response_code(404);
        exit(json_encode(['ok' => false, 'error' => 'Admins file not found'], JSON_UNESCAPED_UNICODE));
    }
    
    $admins = json_decode(file_get_contents($admins_file), true) ?? [];
    
    $deleted = null;
    $admins = array_filter($admins, function($a) use ($admin_id, &$deleted) {
        if ($a['id'] === $admin_id) {
            $deleted = $a;
            return false;
        }
        return true;
    });
    
    if (!$deleted) {
        http_response_code(404);
        exit(json_encode(['ok' => false, 'error' => 'Admin not found'], JSON_UNESCAPED_UNICODE));
    }
    
    file_put_contents($admins_file, json_encode(array_values($admins), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // Логируем
    $log = [
        'timestamp' => date('Y-m-d H:i:s'),
        'action' => 'delete_admin',
        'deleted_admin_id' => $admin_id,
        'deleted_admin_username' => $deleted['username'],
        'deleted_admin_name' => $deleted['name']
    ];
    @file_put_contents($logs_file, json_encode($log, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
    
    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'message' => 'Administrator deleted',
        'deleted' => $deleted
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== CHANGE ADMIN ROLE =====
if ($method === 'POST' && $action === 'change_role') {
    $admin_id = (int)($input['admin_id'] ?? 0);
    $new_role = $input['role'] ?? null;
    
    $valid_roles = ['super_admin', 'manager', 'operator'];
    if (!in_array($new_role, $valid_roles)) {
        http_response_code(400);
        exit(json_encode(['ok' => false, 'error' => 'Invalid role'], JSON_UNESCAPED_UNICODE));
    }
    
    $admins = json_decode(file_get_contents($admins_file), true) ?? [];
    
    $found = false;
    foreach ($admins as &$a) {
        if ($a['id'] === $admin_id) {
            $old_role = $a['role'];
            $a['role'] = $new_role;
            $found = true;
            
            // Логируем
            $log = [
                'timestamp' => date('Y-m-d H:i:s'),
                'action' => 'change_admin_role',
                'admin_id' => $admin_id,
                'admin_username' => $a['username'],
                'old_role' => $old_role,
                'new_role' => $new_role
            ];
            @file_put_contents($logs_file, json_encode($log, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
            
            break;
        }
    }
    
    if (!$found) {
        http_response_code(404);
        exit(json_encode(['ok' => false, 'error' => 'Admin not found'], JSON_UNESCAPED_UNICODE));
    }
    
    file_put_contents($admins_file, json_encode($admins, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'message' => 'Role changed successfully'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== BLOCK/UNBLOCK ADMIN =====
if ($method === 'POST' && $action === 'toggle_admin') {
    $admin_id = (int)($input['admin_id'] ?? 0);
    
    $admins = json_decode(file_get_contents($admins_file), true) ?? [];
    
    $found = false;
    foreach ($admins as &$a) {
        if ($a['id'] === $admin_id) {
            $old_status = $a['active'];
            $a['active'] = !$a['active'];
            $found = true;
            
            $log = [
                'timestamp' => date('Y-m-d H:i:s'),
                'action' => $a['active'] ? 'activate_admin' : 'deactivate_admin',
                'admin_id' => $admin_id,
                'admin_username' => $a['username']
            ];
            @file_put_contents($logs_file, json_encode($log, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
            
            break;
        }
    }
    
    if (!$found) {
        http_response_code(404);
        exit(json_encode(['ok' => false, 'error' => 'Admin not found'], JSON_UNESCAPED_UNICODE));
    }
    
    file_put_contents($admins_file, json_encode($admins, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'message' => 'Status changed'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== GET ALL ADMINS WITH STATS =====
if ($method === 'GET' && $action === 'list_admins') {
    $admins = json_decode(file_get_contents($admins_file), true) ?? [];
    
    // Добавляем статистику по каждому админу
    $sessions_file = $storage_dir . '/sessions.json';
    $sessions = [];
    if (file_exists($sessions_file)) {
        $all_sessions = json_decode(file_get_contents($sessions_file), true) ?? [];
        foreach ($all_sessions as $sess) {
            $admin_id = $sess['admin_id'];
            if (!isset($sessions[$admin_id])) {
                $sessions[$admin_id] = 0;
            }
            $sessions[$admin_id]++;
        }
    }
    
    foreach ($admins as &$a) {
        $a['active_sessions'] = $sessions[$a['id']] ?? 0;
        $a['status'] = $a['active'] ? 'active' : 'blocked';
    }
    
    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'admins' => $admins
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== GET AUDIT LOGS =====
if ($method === 'GET' && $action === 'audit_logs') {
    if (!file_exists($logs_file)) {
        http_response_code(200);
        echo json_encode(['ok' => true, 'logs' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $logs = [];
    $file = file($logs_file, FILE_IGNORE_NEW_LINES);
    foreach (array_reverse($file) as $line) {
        if (trim($line)) {
            $logs[] = json_decode($line, true);
        }
    }
    
    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'logs' => array_slice($logs, 0, 100) // Последние 100 логов
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== CLOSE LEAD =====
if ($method === 'POST' && $action === 'close_lead') {
    $lead_id = (int)($input['lead_id'] ?? 0);
    $reason = $input['reason'] ?? '';
    
    $leads_meta_file = $storage_dir . '/leads_meta.json';
    $meta = [];
    
    if (file_exists($leads_meta_file)) {
        $meta = json_decode(file_get_contents($leads_meta_file), true) ?? [];
    }
    
    if (!isset($meta[$lead_id])) {
        $meta[$lead_id] = [
            'status' => 'new',
            'assigned_to' => null,
            'notes' => [],
            'history' => [],
            'closed' => false
        ];
    }
    
    $meta[$lead_id]['status'] = 'closed';
    $meta[$lead_id]['closed'] = true;
    $meta[$lead_id]['closed_at'] = date('Y-m-d H:i:s');
    $meta[$lead_id]['closed_by'] = 'Никита Синицин';
    $meta[$lead_id]['close_reason'] = $reason;
    
    $meta[$lead_id]['history'][] = [
        'timestamp' => date('Y-m-d H:i:s'),
        'action' => 'lead_closed',
        'reason' => $reason,
        'admin' => 'Никита Синицин'
    ];
    
    file_put_contents($leads_meta_file, json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // Логируем
    $log = [
        'timestamp' => date('Y-m-d H:i:s'),
        'action' => 'close_lead',
        'lead_id' => $lead_id,
        'reason' => $reason
    ];
    @file_put_contents($logs_file, json_encode($log, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
    
    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'message' => 'Lead closed'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== GET LEADS REPORT =====
if ($method === 'GET' && $action === 'leads_report') {
    $leads_file = $storage_dir . '/leads.json';
    $meta_file = $storage_dir . '/leads_meta.json';
    
    $leads = json_decode(file_get_contents($leads_file), true) ?? [];
    $meta = file_exists($meta_file) ? json_decode(file_get_contents($meta_file), true) ?? [] : [];
    
    $report = [
        'total' => count($leads),
        'by_status' => [
            'new' => 0,
            'in_work' => 0,
            'processed' => 0,
            'done' => 0,
            'closed' => 0
        ],
        'by_city' => [],
        'by_age' => [
            '18-25' => 0,
            '26-35' => 0,
            '36-45' => 0,
            '46-55' => 0,
            '56+' => 0
        ]
    ];
    
    foreach ($leads as $lead) {
        $lead_id = $lead['id'];
        $status = $meta[$lead_id]['status'] ?? $lead['status'] ?? 'new';
        
        if (isset($report['by_status'][$status])) {
            $report['by_status'][$status]++;
        }
        
        if (isset($lead['city'])) {
            if (!isset($report['by_city'][$lead['city']])) {
                $report['by_city'][$lead['city']] = 0;
            }
            $report['by_city'][$lead['city']]++;
        }
        
        if (isset($lead['age'])) {
            $age = (int)$lead['age'];
            if ($age >= 18 && $age <= 25) {
                $report['by_age']['18-25']++;
            } elseif ($age >= 26 && $age <= 35) {
                $report['by_age']['26-35']++;
            } elseif ($age >= 36 && $age <= 45) {
                $report['by_age']['36-45']++;
            } elseif ($age >= 46 && $age <= 55) {
                $report['by_age']['46-55']++;
            } else {
                $report['by_age']['56+']++;
            }
        }
    }
    
    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'report' => $report
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'Invalid action'], JSON_UNESCAPED_UNICODE);
?>
