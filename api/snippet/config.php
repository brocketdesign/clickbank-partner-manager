<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config.php';

// CORS: echo Origin and allow credentials for cross-origin fetches from publisher sites
$origin = '';
if (!empty($_SERVER['HTTP_ORIGIN'])) {
    $origin = $_SERVER['HTTP_ORIGIN'];
} elseif (!empty($_SERVER['HTTP_REFERER'])) {
    $origin = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_SCHEME) . '://' . parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
}
if ($origin) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
    header('Vary: Origin');
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Accept');
    http_response_code(204);
    exit;
}

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

// Determine requesting origin for CORS/Referer checking
$origin = '';
if (!empty($_SERVER['HTTP_ORIGIN'])) {
    $origin = $_SERVER['HTTP_ORIGIN'];
} elseif (!empty($_SERVER['HTTP_REFERER'])) {
    $origin = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_SCHEME) . '://' . parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
}

$allowed = false;
$allowed_domains = array_map('trim', explode(',', $partner['allowed_domains'] ?? ''));
// If no allowed domains configured for this partner, allow any origin (useful for distributed snippet usage)
$all_empty = true;
foreach ($allowed_domains as $d) { if ($d !== '') { $all_empty = false; break; } }
if ($all_empty) {
    $allowed = true;
} elseif ($origin) {
    $origin_host = parse_url($origin, PHP_URL_HOST);
    foreach ($allowed_domains as $d) {
        if ($d === '') continue;
        // Allow exact host or www variants
        if ($origin_host === $d || preg_replace('/^www\./i', '', $origin_host) === preg_replace('/^www\./i', '', $d)) {
            $allowed = true; break;
        }
    }
} 

if (!$allowed) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Origin not allowed']);
    exit;
}

// Set CORS header for the requesting origin
header('Access-Control-Allow-Origin: ' . $origin);
header('Vary: Origin');

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
