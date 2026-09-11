<?php
/**
 * POST /api/lead-delete.php
 * Удаление заявок (только для суперадмина)
 * SECURITY: Token verification, super admin check
 * v3.1.0: При удалении заявки - удаляется и её статистика
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['ok' => false, 'error' => 'Метод не разрешен'], JSON_UNESCAPED_UNICODE));
}

$input = json_decode(file_get_contents('php://input'), true);

$storage_dir = __DIR__ . '/../storage';
@mkdir($storage_dir, 0755, true);
$sessions_file = $storage_dir . '/sessions.json';

// SECURITY: Verify token
$token = $input['token'] ?? null;
if (!$token || !file_exists($sessions_file)) {
    http_response_code(401);
    exit(json_encode(['ok' => false, 'error' => 'Неавторизованный доступ'], JSON_UNESCAPED_UNICODE));
}

$sessions = json_decode(@file_get_contents($sessions_file), true) ?? [];
$session = $sessions[$token] ?? null;

if (!$session) {
    http_response_code(401);
    exit(json_encode(['ok' => false, 'error' => 'Неверный токен'], JSON_UNESCAPED_UNICODE));
}

// SECURITY: Only super_admin can delete leads
if ($session['role'] !== 'super_admin') {
    http_response_code(403);
    exit(json_encode(['ok' => false, 'error' => 'Только главный администратор может удалять заявки'], JSON_UNESCAPED_UNICODE));
}

$action = $input['action'] ?? null;

if (!$action) {
    http_response_code(400);
    exit(json_encode(['ok' => false, 'error' => 'Действие не указано'], JSON_UNESCAPED_UNICODE));
}

$leads_file = $storage_dir . '/leads.json';
$meta_file = $storage_dir . '/leads_meta.json';
$locks_file = $storage_dir . '/lead_locks.json';
$activity_file = $storage_dir . '/lead_activity.jsonl';
$stats_file = $storage_dir . '/lead_stats.json';

// ===== DELETE SINGLE LEAD =====
if ($action === 'delete_single') {
    $lead_id = (int)($input['lead_id'] ?? 0);
    
    if (!$lead_id || $lead_id <= 0) {
        http_response_code(400);
        exit(json_encode(['ok' => false, 'error' => 'Неверный ID заявки'], JSON_UNESCAPED_UNICODE));
    }
    
    // Load leads with RESILIENCE
    $leads = [];
    if (file_exists($leads_file)) {
        $leads_content = @file_get_contents($leads_file);
        if ($leads_content && !empty($leads_content)) {
            $decoded = json_decode($leads_content, true);
            if (is_array($decoded)) {
                $leads = $decoded;
            } elseif (is_object($decoded)) {
                $leads = (array)$decoded;
            }
        }
    }
    
    // Проверяем если заявка существует
    $lead_exists = false;
    $actual_lead_id = null;
    
    if (isset($leads[$lead_id])) {
        $lead_exists = true;
        $actual_lead_id = $lead_id;
    } else {
        foreach ($leads as $key => $lead) {
            if (isset($lead['id']) && (int)$lead['id'] === $lead_id) {
                $lead_exists = true;
                $actual_lead_id = $key;
                break;
            }
        }
    }
    
    if (!$lead_exists) {
        http_response_code(404);
        exit(json_encode(['ok' => false, 'error' => 'Заявка не найдена'], JSON_UNESCAPED_UNICODE));
    }
    
    $deleted_lead = $leads[$actual_lead_id];
    unset($leads[$actual_lead_id]);
    
    // *** НОВОЕ v3.1.0: АРХИВИРУЕМ И УДАЛЯЕМ СТАТИСТИКУ ***
    
    // 1. Собираем активность по этой заявке
    $archive_data = [
        'lead_id' => $lead_id,
        'lead_name' => $deleted_lead['name'] ?? 'Unknown',
        'archived_by' => $session['name'] ?? 'Unknown',
        'archived_at' => date('Y-m-d H:i:s'),
        'admin_activities' => []
    ];
    
    if (file_exists($activity_file)) {
        $lines = file($activity_file, FILE_IGNORE_NEW_LINES);
        $lead_activity = [];
        foreach ($lines as $line) {
            if (trim($line)) {
                $data = json_decode($line, true);
                if ($data && $data['lead_id'] === $lead_id) {
                    $lead_activity[] = $data;
                    
                    // Собираем активность по админам
                    $admin_id = $data['admin_id'] ?? null;
                    $action_type = $data['action'] ?? null;
                    if ($admin_id && $action_type) {
                        if (!isset($archive_data['admin_activities'][$admin_id])) {
                            $archive_data['admin_activities'][$admin_id] = [
                                'admin_id' => $admin_id,
                                'admin_name' => $data['admin_name'] ?? 'Unknown',
                                'opened' => 0,
                                'closed' => 0,
                                'viewed' => 0,
                                'edited' => 0
                            ];
                        }
                        
                        // Считаем по типам действий
                        switch ($action_type) {
                            case 'opened':
                                $archive_data['admin_activities'][$admin_id]['opened']++;
                                break;
                            case 'closed':
                                $archive_data['admin_activities'][$admin_id]['closed']++;
                                break;
                            case 'viewed':
                                $archive_data['admin_activities'][$admin_id]['viewed']++;
                                break;
                            case 'edited':
                                $archive_data['admin_activities'][$admin_id]['edited']++;
                                break;
                        }
                    }
                }
            }
        }
        $archive_data['activity_count'] = count($lead_activity);
    }
    
    // 2. Архивируем
    $archived_file = $storage_dir . '/lead_archived_stats.json';
    $archived = [];
    if (file_exists($archived_file)) {
        $archived = json_decode(file_get_contents($archived_file), true) ?? [];
    }
    $archived[$lead_id] = $archive_data;
    file_put_contents($archived_file, json_encode($archived, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // 3. НОВОЕ: Вычитаем статистику из lead_stats.json
    $stats = [];
    if (file_exists($stats_file)) {
        $stats = json_decode(file_get_contents($stats_file), true) ?? [];
    }
    
    // Вычитаем активность из статистики каждого администратора
    foreach ($archive_data['admin_activities'] as $admin_id => $activities) {
        if (isset($stats[$admin_id])) {
            // Вычитаем действия
            $stats[$admin_id]['total_opened'] = max(0, ($stats[$admin_id]['total_opened'] ?? 0) - $activities['opened']);
            $stats[$admin_id]['total_closed'] = max(0, ($stats[$admin_id]['total_closed'] ?? 0) - $activities['closed']);
            $stats[$admin_id]['total_viewed'] = max(0, ($stats[$admin_id]['total_viewed'] ?? 0) - $activities['viewed']);
            $stats[$admin_id]['total_edited'] = max(0, ($stats[$admin_id]['total_edited'] ?? 0) - $activities['edited']);
        }
    }
    file_put_contents($stats_file, json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // 4. НОВОЕ: Удаляем активность этой заявки из lead_activity.jsonl
    if (file_exists($activity_file)) {
        $lines = file($activity_file, FILE_IGNORE_NEW_LINES);
        $new_lines = [];
        foreach ($lines as $line) {
            if (trim($line)) {
                $data = json_decode($line, true);
                if (!($data && $data['lead_id'] === $lead_id)) {
                    // Сохраняем только если это НЕ наша заявка
                    $new_lines[] = $line;
                }
            }
        }
        file_put_contents($activity_file, implode("\n", $new_lines) . "\n");
    }
    
    // *** КОНЕЦ УДАЛЕНИЯ СТАТИСТИКИ ***
    
    // Save leads (переиндексируем)
    $leads = array_values($leads);
    $json_content = json_encode($leads, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    file_put_contents($leads_file, $json_content);
    
    // Remove from meta
    $meta = [];
    if (file_exists($meta_file)) {
        $meta_content = @file_get_contents($meta_file);
        if ($meta_content && !empty($meta_content)) {
            $decoded = json_decode($meta_content, true);
            if (is_array($decoded)) {
                $meta = $decoded;
            }
        }
    }
    unset($meta[$lead_id]);
    file_put_contents($meta_file, json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // Remove lock
    $locks = [];
    if (file_exists($locks_file)) {
        $locks_content = @file_get_contents($locks_file);
        if ($locks_content && !empty($locks_content)) {
            $decoded = json_decode($locks_content, true);
            if (is_array($decoded)) {
                $locks = $decoded;
            }
        }
    }
    unset($locks[$lead_id]);
    file_put_contents($locks_file, json_encode($locks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // Log action
    $logs_file = $storage_dir . '/admin_logs.jsonl';
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'action' => 'lead_deleted',
        'lead_id' => $lead_id,
        'admin' => $session['name'] ?? 'Unknown',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    @file_put_contents($logs_file, json_encode($log_entry, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
    
    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'message' => 'Заявка удалена успешно',
        'lead_id' => $lead_id,
        'lead_name' => $deleted_lead['name'] ?? 'Unknown'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== DELETE ALL LEADS (CLEAR ALL) =====
if ($action === 'delete_all') {
    // Double confirmation check
    $confirm = $input['confirm'] ?? false;
    if (!$confirm) {
        http_response_code(400);
        exit(json_encode(['ok' => false, 'error' => 'Требуется подтверждение'], JSON_UNESCAPED_UNICODE));
    }
    
    // Load leads to count
    $leads = [];
    if (file_exists($leads_file)) {
        $leads_content = @file_get_contents($leads_file);
        if ($leads_content && !empty($leads_content)) {
            $decoded = json_decode($leads_content, true);
            if (is_array($decoded)) {
                $leads = $decoded;
            }
        }
    }
    
    $deleted_count = count($leads);
    
    // НОВОЕ v3.1.0: Очищаем статистику при удалении всех заявок
    file_put_contents($stats_file, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // Очищаем активность
    file_put_contents($activity_file, '');
    
    // Clear all files
    file_put_contents($leads_file, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    file_put_contents($meta_file, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    file_put_contents($locks_file, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // Log action
    $logs_file = $storage_dir . '/admin_logs.jsonl';
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'action' => 'all_leads_deleted',
        'count' => $deleted_count,
        'admin' => $session['name'] ?? 'Unknown',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    @file_put_contents($logs_file, json_encode($log_entry, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
    
    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'message' => 'Все заявки удалены успешно',
        'deleted_count' => $deleted_count
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'Неверное действие'], JSON_UNESCAPED_UNICODE);
?>
