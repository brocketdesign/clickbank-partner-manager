<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config.php';

// CORS: echo Origin and allow credentials for cross-origin POSTs from snippet embeds
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (empty($origin) && !empty($_SERVER['HTTP_REFERER'])) {
    $ref = $_SERVER['HTTP_REFERER'];
    $origin = parse_url($ref, PHP_URL_SCHEME) . '://' . parse_url($ref, PHP_URL_HOST);
}
if ($origin) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
    header('Vary: Origin');
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Accept');
    http_response_code(204);
    exit;
}

// POST /api/metrics/impression
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = $_POST;
$partner_pub = $input['partner'] ?? ''; // public id
$creative_id = isset($input['creative_id']) ? (int)$input['creative_id'] : null;

if (!$partner_pub) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing partner']);
    exit;
}

$conn = getDBConnection();
$stmt = $conn->prepare("SELECT id FROM partners_new WHERE partner_id_public = ? AND status = 'approved' LIMIT 1");
$stmt->bind_param('s', $partner_pub);
$stmt->execute();
$partner = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$partner) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Partner not found']);
    exit;
}

$ip = getClientIP();
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$ip_hash = $ip ? hash('sha256', $ip) : null;
$ua_hash = $ua ? hash('sha256', $ua) : null;

$ins = $conn->prepare("INSERT INTO impressions (partner_id, creative_id, ip_hash, ua_hash) VALUES (?, ?, ?, ?)");
$ins->bind_param('iiss', $partner['id'], $creative_id, $ip_hash, $ua_hash);
if (!$ins->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB insert failed']);
    exit;
}
$ins->close();

http_response_code(201);
echo json_encode(['success' => true]);
exit;
