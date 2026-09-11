<?php
/**
 * GET /api/simple-leads.php?token=xxx&action=list
 * Simplified leads API - no role checking
 */

header('Content-Type: application/json; charset=utf-8');

$storageDir = dirname(__FILE__) . '/../storage';
$leadsFile = $storageDir . '/leads.json';
$token = trim($_GET['token'] ?? '');

// Verify token
function verify_token($token) {
    global $storageDir;
    $sessionsFile = $storageDir . '/sessions.json';
    
    if (empty($token)) return false;
    
    $sessions = json_decode(@file_get_contents($sessionsFile), true) ?: [];
    return isset($sessions[$token]);
}

if (!verify_token($token)) {
    http_response_code(401);
    die(json_encode(['ok' => false, 'error' => 'Unauthorized']));
}

$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $leads = [];
    if (file_exists($leadsFile)) {
        $data = json_decode(file_get_contents($leadsFile), true);
        $leads = $data['leads'] ?? $data ?? [];
    }
    
    echo json_encode([
        'ok' => true,
        'leads' => $leads,
        'total' => count($leads)
    ], JSON_UNESCAPED_UNICODE);
}
else if ($action === 'get') {
    $lead_id = intval($_GET['id'] ?? 0);
    
    if (!$lead_id) {
        http_response_code(400);
        die(json_encode(['ok' => false, 'error' => 'Lead ID required']));
    }
    
    $leads = [];
    if (file_exists($leadsFile)) {
        $data = json_decode(file_get_contents($leadsFile), true);
        $leads = $data['leads'] ?? $data ?? [];
    }
    
    $lead = null;
    foreach ($leads as $l) {
        if ($l['id'] === $lead_id) {
            $lead = $l;
            break;
        }
    }
    
    if (!$lead) {
        http_response_code(404);
        die(json_encode(['ok' => false, 'error' => 'Lead not found']));
    }
    
    echo json_encode([
        'ok' => true,
        'lead' => $lead
    ], JSON_UNESCAPED_UNICODE);
}
else if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $lead_id = intval($input['lead_id'] ?? 0);
    
    if (!$lead_id) {
        http_response_code(400);
        die(json_encode(['ok' => false, 'error' => 'Lead ID required']));
    }
    
    $data = [];
    if (file_exists($leadsFile)) {
        $data = json_decode(file_get_contents($leadsFile), true) ?: [];
    }
    
    $leads = $data['leads'] ?? [];
    $found = false;
    
    foreach ($leads as $idx => $l) {
        if ($l['id'] === $lead_id) {
            unset($leads[$idx]);
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        http_response_code(404);
        die(json_encode(['ok' => false, 'error' => 'Lead not found']));
    }
    
    $data['leads'] = array_values($leads);
    file_put_contents($leadsFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    
    echo json_encode([
        'ok' => true,
        'message' => 'Lead deleted'
    ]);
}
else {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Unknown action']);
}
?>
