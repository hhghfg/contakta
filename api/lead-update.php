<?php
/**
 * POST /api/lead-update.php
 * Обновить заявку с полной защитой от несанкционированного доступа
 * SECURITY: Token validation, Role checking, Rate limiting, Logging
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

require_once 'auth-middleware.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $auth = new AuthMiddleware();
    
    // Проверяем rate limit
    $auth->checkRateLimit('/api/lead-update');
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!isset($data['token'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Token required']);
        exit;
    }

    // Валидируем токен и получаем сессию
    $session = $auth->validateToken($data['token']);
    
    // Требуем роль admin
    $auth->requireRole($session, 'admin');

    if (!isset($data['lead_id'])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Lead ID required']);
        exit;
    }

    $token = $data['token'];
    $leadId = (int)$data['lead_id'];
    $status = isset($data['status']) ? trim((string)$data['status']) : null;
    $notes = isset($data['notes']) ? trim((string)$data['notes']) : null;
    $assignToMe = isset($data['assign_to_me']) ? (bool)$data['assign_to_me'] : false;

    $storageDir = dirname(__FILE__) . '/../storage';

    // Читаем заявки
    $leadsFile = $storageDir . '/leads.json';
    $leads = [];

    if (file_exists($leadsFile)) {
        $content = @file_get_contents($leadsFile);
        if ($content) {
            $leads = json_decode($content, true) ?: [];
        }
    }

    // Ищем заявку
    $leadIndex = null;
    $lead = null;

    foreach ($leads as $idx => $l) {
        if ($l['id'] === $leadId) {
            $leadIndex = $idx;
            $lead = $l;
            break;
        }
    }

    if ($leadIndex === null) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Lead not found']);
        exit;
    }

    // Логируем изменения (для аудита)
    $oldLead = $lead;

    // Обновляем данные
    if ($assignToMe) {
        $leads[$leadIndex]['assigned_to_admin_id'] = $session['admin_id'];
        $leads[$leadIndex]['status'] = 'assigned';
    }

    if ($status && in_array($status, ['new', 'assigned', 'processing', 'completed', 'rejected'])) {
        $leads[$leadIndex]['status'] = $status;
    }

    if ($notes !== null) {
        $leads[$leadIndex]['notes'] = is_array($leads[$leadIndex]['notes'] ?? []) 
            ? $leads[$leadIndex]['notes'] 
            : [];
        
        $leads[$leadIndex]['notes'][] = [
            'admin_id' => $session['admin_id'],
            'admin_name' => $session['name'],
            'text' => $notes,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    $leads[$leadIndex]['updated_at'] = date('Y-m-d H:i:s');
    $leads[$leadIndex]['updated_by_admin_id'] = $session['admin_id'];

    // Создаём backup перед сохранением
    $backup = $leadsFile . '.backup';
    if (file_exists($leadsFile)) {
        @copy($leadsFile, $backup);
    }

    // Сохраняем
    @file_put_contents($leadsFile, json_encode($leads, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // Логируем действие
    $activityFile = $storageDir . '/lead_activity.jsonl';
    $activity = [
        'timestamp' => date('Y-m-d H:i:s'),
        'lead_id' => $leadId,
        'admin_id' => $session['admin_id'],
        'admin_name' => $session['name'],
        'action' => $assignToMe ? 'assigned' : 'updated',
        'status_before' => $oldLead['status'],
        'status_after' => $leads[$leadIndex]['status'],
        'assigned_to_admin_id' => $leads[$leadIndex]['assigned_to_admin_id'] ?? null,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];

    @file_put_contents($activityFile, json_encode($activity, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);

    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'lead_id' => $leadId,
        'message' => 'Lead updated successfully',
        'updated_at' => $leads[$leadIndex]['updated_at']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Server error']);
    error_log('[LEAD_UPDATE_ERROR] ' . $e->getMessage());
}
?>
