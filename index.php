<?php
// Main redirect handler - traffic entry point
require_once __DIR__ . '/config.php';

$conn = getDBConnection();

// Get parameters from URL
$domain = $_SERVER['HTTP_HOST'] ?? '';
$aff_id = $_GET['aff_id'] ?? '';
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$ip_address = getClientIP();

// Variables for tracking
$domain_id = null;
$partner_id = null;
$offer_id = null;
$rule_id = null;
$redirect_url = null;

// Step 1: Find domain
if ($domain) {
    $stmt = $conn->prepare("SELECT id FROM domains WHERE domain_name = ? AND is_active = 1");
    $stmt->bind_param("s", $domain);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $domain_id = $row['id'];
    }
    $stmt->close();
}

// Step 2: Find partner
if ($aff_id) {
    $stmt = $conn->prepare("SELECT id FROM partners WHERE aff_id = ? AND is_active = 1");
    $stmt->bind_param("s", $aff_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $partner_id = $row['id'];
    }
    $stmt->close();
}

// Step 3: Find matching redirect rule (priority: partner -> domain -> global)
// Try partner-specific rule first
if ($partner_id) {
    $stmt = $conn->prepare("
        SELECT rr.id, rr.offer_id, o.clickbank_hoplink 
        FROM redirect_rules rr
        JOIN offers o ON rr.offer_id = o.id
        WHERE rr.rule_type = 'partner' 
        AND rr.partner_id = ? 
        AND rr.is_paused = 0
        AND o.is_active = 1
        ORDER BY rr.priority ASC
        LIMIT 1
    ");
    $stmt->bind_param("i", $partner_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $rule_id = $row['id'];
        $offer_id = $row['offer_id'];
        $redirect_url = $row['clickbank_hoplink'];
    }
    $stmt->close();
}

// Try domain-specific rule if no partner rule found
if (!$redirect_url && $domain_id) {
    $stmt = $conn->prepare("
        SELECT rr.id, rr.offer_id, o.clickbank_hoplink 
        FROM redirect_rules rr
        JOIN offers o ON rr.offer_id = o.id
        WHERE rr.rule_type = 'domain' 
        AND rr.domain_id = ? 
        AND rr.is_paused = 0
        AND o.is_active = 1
        ORDER BY rr.priority ASC
        LIMIT 1
    ");
    $stmt->bind_param("i", $domain_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $rule_id = $row['id'];
        $offer_id = $row['offer_id'];
        $redirect_url = $row['clickbank_hoplink'];
    }
    $stmt->close();
}

// Try global rule if no specific rule found
if (!$redirect_url) {
    $stmt = $conn->prepare("
        SELECT rr.id, rr.offer_id, o.clickbank_hoplink 
        FROM redirect_rules rr
        JOIN offers o ON rr.offer_id = o.id
        WHERE rr.rule_type = 'global' 
        AND rr.is_paused = 0
        AND o.is_active = 1
        ORDER BY rr.priority ASC
        LIMIT 1
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $rule_id = $row['id'];
        $offer_id = $row['offer_id'];
        $redirect_url = $row['clickbank_hoplink'];
    }
    $stmt->close();
}

// Step 4: Log the click
$stmt = $conn->prepare("
    INSERT INTO click_logs (domain_id, partner_id, offer_id, rule_id, ip_address, user_agent, referer, redirect_url)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param("iiiissss", $domain_id, $partner_id, $offer_id, $rule_id, $ip_address, $user_agent, $referer, $redirect_url);
$stmt->execute();
$stmt->close();

// Step 5: Perform server-side redirect
if ($redirect_url) {
    // Add aff_id to hoplink if provided
    if ($aff_id) {
        $separator = (strpos($redirect_url, '?') !== false) ? '&' : '?';
        $redirect_url .= $separator . 'tid=' . urlencode($aff_id);
    }
    
    header('Location: ' . $redirect_url, true, 302);
    exit;
} else {
    // No redirect rule found - show error
    http_response_code(404);
    echo "No redirect rule configured";
    exit;
}
