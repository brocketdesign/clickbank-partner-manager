<?php
// CORS headers MUST be sent first, before any other output or headers
// This ensures CORS works even for error responses (404, 400, etc.)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

// Handle preflight OPTIONS request immediately
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config.php';

// GET /api/snippet/config?partner=PARTNER_ID
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
} 

$partner_pub = $_GET['partner'] ?? '';
if (!$partner_pub) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing partner id']);
    exit;
}

$conn = getDBConnection();
$stmt = $conn->prepare("SELECT * FROM partners_new WHERE partner_id_public = ? AND status = 'approved' LIMIT 1");
$stmt->bind_param('s', $partner_pub);
$stmt->execute();
$partner = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$partner) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Partner not found or not approved']);
    exit;
}

// Fetch creatives
$cStmt = $conn->prepare("SELECT id, name, type, destination_hoplink, weight, html FROM creatives WHERE partner_id = ? AND active = 1 ORDER BY id ASC");
$cStmt->bind_param('i', $partner['id']);
$cStmt->execute();
$creatives = $cStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$cStmt->close();

// Basic config - editable later in admin
$response = [
    'success' => true,
    'partner' => [
        'id' => $partner['partner_id_public'],
        'name' => $partner['name']
    ],
    'config' => [
        'selectors' => ['body'],
        'creatives' => $creatives,
        'cache_ttl_seconds' => 60
    ]
];

echo json_encode($response);
exit;
