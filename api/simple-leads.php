<?php
/** Simplified authenticated leads API. */
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$storageDir = dirname(__FILE__) . '/../storage';
$leadsFile = $storageDir . '/leads.json';

function api_response(int $status, array $body): never {
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function bearer_token(): string {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    return preg_match('/^Bearer\s+([A-Fa-f0-9]{64})$/', trim($header), $m) === 1 ? $m[1] : '';
}
function verify_token(string $token, string $storageDir): bool {
    if ($token === '') return false;
    $sessions = json_decode((string) @file_get_contents($storageDir . '/sessions.json'), true);
    if (!is_array($sessions) || !isset($sessions[$token]) || !is_array($sessions[$token])) return false;
    $expires = $sessions[$token]['expires_at'] ?? 0;
    $timestamp = is_numeric($expires) ? (int) $expires : (int) strtotime((string) $expires);
    return $timestamp > time();
}
function load_leads(string $file): array {
    $data = json_decode((string) @file_get_contents($file), true);
    if (!is_array($data)) return [];
    $leads = $data['leads'] ?? $data;
    return is_array($leads) ? array_values($leads) : [];
}

if (!verify_token(bearer_token(), $storageDir)) api_response(401, ['ok' => false, 'error' => 'Unauthorized']);
$action = $_GET['action'] ?? 'list';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET' && $action === 'list') {
    $leads = load_leads($leadsFile);
    api_response(200, ['ok' => true, 'leads' => $leads, 'total' => count($leads)]);
}
if ($method === 'GET' && $action === 'get') {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$id) api_response(400, ['ok' => false, 'error' => 'Lead ID is required']);
    foreach (load_leads($leadsFile) as $lead) {
        if ((int) ($lead['id'] ?? 0) === $id) api_response(200, ['ok' => true, 'lead' => $lead]);
    }
    api_response(404, ['ok' => false, 'error' => 'Lead not found']);
}
if ($method === 'POST' && $action === 'delete') {
    $input = json_decode((string) file_get_contents('php://input'), true);
    $id = is_array($input) ? filter_var($input['lead_id'] ?? null, FILTER_VALIDATE_INT) : false;
    if (!$id) api_response(400, ['ok' => false, 'error' => 'Lead ID is required']);
    $leads = load_leads($leadsFile);
    $filtered = array_values(array_filter($leads, fn(array $lead): bool => (int) ($lead['id'] ?? 0) !== $id));
    if (count($filtered) === count($leads)) api_response(404, ['ok' => false, 'error' => 'Lead not found']);
    $json = json_encode(['leads' => $filtered], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false || file_put_contents($leadsFile, $json . "\n", LOCK_EX) === false) api_response(500, ['ok' => false, 'error' => 'Could not save leads']);
    api_response(200, ['ok' => true]);
}
api_response(405, ['ok' => false, 'error' => 'Method not allowed']);
