<?php
require_once 'security.php';
require_once 'roles.php';

header('Content-Type: application/json');

$token = get_bearer_token();
$session_data = get_session_data($token);

if (!$session_data) {
    http_response_code(401);
    die(json_encode(['ok' => false, 'error' => 'Не авторизован']));
}

require_permission($session_data['role'], 'capture_leads');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $lead_id = $input['lead_id'] ?? null;
    
    if (!$lead_id) {
        http_response_code(400);
        die(json_encode(['ok' => false, 'error' => 'Не указан ID заявки']));
    }
    
    $leads_file = STORAGE_PATH . 'leads.json';
    
    if (!file_exists($leads_file)) {
        http_response_code(404);
        die(json_encode(['ok' => false, 'error' => 'Заявок не найдено']));
    }
    
    $leads_data = json_decode(file_get_contents($leads_file), true);
    $leads = $leads_data['leads'];
    
    $lead = null;
    $lead_index = -1;
    
    foreach ($leads as $i => $l) {
        if ($l['id'] === $lead_id) {
            $lead = $l;
            $lead_index = $i;
            break;
        }
    }
    
    if (!$lead) {
        http_response_code(404);
        die(json_encode(['ok' => false, 'error' => 'Заявка не найдена']));
    }
    
    // Проверка, не захвачена ли уже
    if ($lead['status'] === 'captured' || $lead['captured_by']) {
        http_response_code(409);
        die(json_encode(['ok' => false, 'error' => 'Эта заявка уже захвачена другим администратором']));
    }
    
    // Захватить заявку
    $lead['status'] = 'captured';
    $lead['captured_by'] = $session_data['admin_id'];
    $lead['captured_by_name'] = $session_data['username'] ?? 'Unknown';
    $lead['captured_at'] = date('c');
    
    $leads_data['leads'][$lead_index] = $lead;
    file_put_contents($leads_file, json_encode($leads_data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), LOCK_EX);
    
    log_security_event('LEAD_CAPTURED', [
        'admin_id' => $session_data['admin_id'],
        'admin_role' => $session_data['role'],
        'lead_id' => $lead_id,
        'lead_name' => $lead['name']
    ]);
    
    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'message' => 'Заявка захвачена',
        'lead' => $lead
    ]);
    
} else {
    http_response_code(405);
    die(json_encode(['ok' => false, 'error' => 'Method not allowed']));
}
?>
