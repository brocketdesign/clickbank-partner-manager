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
$domain_param = $_GET['domain'] ?? '';

if (!$partner_pub) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing partner id']);
    exit;
}

if (!$domain_param) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing domain']);
    exit;
}

$conn = getDBConnection();

// Check if the domain is active in the domains table
$domainStmt = $conn->prepare("SELECT id, is_active FROM domains WHERE domain_name = ? LIMIT 1");
$domainStmt->bind_param('s', $domain_param);
$domainStmt->execute();
$domainRow = $domainStmt->get_result()->fetch_assoc();
$domainStmt->close();

// If domain exists in table but is not active, reject the request
if ($domainRow && !$domainRow['is_active']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Domain is deactivated']);
    exit;
}

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

// Fetch offers via redirect_rules
// Priority: partner-specific > domain-specific > global
// First try partner-specific rules
$offers = [];

// Get internal partner ID from partners table (if exists)
$pStmt = $conn->prepare("SELECT id FROM partners WHERE aff_id = ? AND is_active = 1 LIMIT 1");
$pStmt->bind_param('s', $partner_pub);
$pStmt->execute();
$partnerRow = $pStmt->get_result()->fetch_assoc();
$pStmt->close();

$internalPartnerId = $partnerRow ? $partnerRow['id'] : null;

// Try partner-specific rules first
if ($internalPartnerId) {
    $rStmt = $conn->prepare("
        SELECT o.id, o.offer_name as name, o.clickbank_hoplink as destination_hoplink, r.priority as weight
        FROM redirect_rules r
        JOIN offers o ON r.offer_id = o.id
        WHERE r.rule_type = 'partner' 
          AND r.partner_id = ? 
          AND r.is_paused = 0 
          AND o.is_active = 1
        ORDER BY r.priority ASC
    ");
    $rStmt->bind_param('i', $internalPartnerId);
    $rStmt->execute();
    $offers = $rStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $rStmt->close();
}

// Fallback to global rules if no partner-specific rules
if (empty($offers)) {
    $gStmt = $conn->prepare("
        SELECT o.id, o.offer_name as name, o.clickbank_hoplink as destination_hoplink, r.priority as weight
        FROM redirect_rules r
        JOIN offers o ON r.offer_id = o.id
        WHERE r.rule_type = 'global' 
          AND r.is_paused = 0 
          AND o.is_active = 1
        ORDER BY r.priority ASC
    ");
    $gStmt->execute();
    $offers = $gStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $gStmt->close();
}

// Transform offers to creatives format for backward compatibility
$creatives = array_map(function($offer) {
    return [
        'id' => $offer['id'],
        'name' => $offer['name'],
        'type' => 'popup',
        'destination_hoplink' => $offer['destination_hoplink'],
        'weight' => max(1, 101 - intval($offer['weight'])) // Invert priority (lower priority number = higher weight)
    ];
}, $offers);

// Basic config
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
