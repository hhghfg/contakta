<?php
/** Simplified authenticated lead statistics. */
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$storageDir = dirname(__FILE__) . '/../storage';
$header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = preg_match('/^Bearer\s+([A-Fa-f0-9]{64})$/', trim($header), $m) === 1 ? $m[1] : '';
$sessions = json_decode((string) @file_get_contents($storageDir . '/sessions.json'), true);
$session = is_array($sessions) && $token !== '' ? ($sessions[$token] ?? null) : null;
$expires = is_array($session) ? ($session['expires_at'] ?? 0) : 0;
$expiresAt = is_numeric($expires) ? (int) $expires : (int) strtotime((string) $expires);
if (!is_array($session) || $expiresAt <= time()) {
    http_response_code(401);
    exit(json_encode(['ok' => false, 'error' => 'Unauthorized']));
}

$data = json_decode((string) @file_get_contents($storageDir . '/leads.json'), true);
$leads = is_array($data) ? ($data['leads'] ?? $data) : [];
if (!is_array($leads)) $leads = [];
$stats = ['total' => count($leads), 'new' => 0, 'processing' => 0, 'completed' => 0, 'rejected' => 0];
foreach ($leads as $lead) {
    $status = is_array($lead) ? ($lead['status'] ?? 'new') : 'new';
    if (array_key_exists($status, $stats) && $status !== 'total') $stats[$status]++;
}
echo json_encode(['ok' => true, 'stats' => $stats], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
