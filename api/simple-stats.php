<?php
/**
 * GET /api/simple-stats.php?token=xxx
 * Simplified statistics - no role checking
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

$leads = [];
if (file_exists($leadsFile)) {
    $data = json_decode(file_get_contents($leadsFile), true);
    $leads = $data['leads'] ?? $data ?? [];
}

$stats = [
    'total' => 0,
    'new' => 0,
    'processing' => 0,
    'completed' => 0,
    'rejected' => 0,
];

$stats['total'] = count($leads);

foreach ($leads as $lead) {
    $status = $lead['status'] ?? 'new';
    
    if ($status === 'new') $stats['new']++;
    else if ($status === 'processing') $stats['processing']++;
    else if ($status === 'completed') $stats['completed']++;
    else if ($status === 'rejected') $stats['rejected']++;
}

echo json_encode([
    'ok' => true,
    'stats' => $stats,
    'leads_total' => count($leads)
], JSON_UNESCAPED_UNICODE);
?>
