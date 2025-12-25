<?php
// Redirect handler (moved from index.php)
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
    $row = stmt_get_assoc($stmt);
    if ($row) {
        $domain_id = $row['id'];
    }
    $stmt->close();
}

// Step 2: Find partner
if ($aff_id) {
    $stmt = $conn->prepare("SELECT id FROM partners WHERE aff_id = ? AND is_active = 1");
    $stmt->bind_param("s", $aff_id);
    $row = stmt_get_assoc($stmt);
    if ($row) {
        $partner_id = $row['id'];
    }
    $stmt->close();
}

// If explicit 'u' parameter provided (direct destination), prefer it
if (isset($_GET['u'])) {
    $u = trim($_GET['u']);
    $u = urldecode($u);
    if (preg_match('#^https?://#i', $u)) {
        $redirect_url = $u;
    }
}

// If creative id passed (c), try to resolve it directly
$creative_id = isset($_GET['c']) ? (int)$_GET['c'] : null;
if ($creative_id && $partner_id) {
    $cstmt = $conn->prepare("SELECT destination_hoplink FROM creatives WHERE id = ? AND partner_id = ? AND active = 1 LIMIT 1");
    $cstmt->bind_param('ii', $creative_id, $partner_id);
    $crow = stmt_get_assoc($cstmt);
    if ($crow && !empty($crow['destination_hoplink'])) {
        // don't override explicit 'u' param if present
        if (empty($redirect_url)) {
            $redirect_url = $crow['destination_hoplink'];
            $offer_id = null; // not from offers table
            $rule_id = null;
        }
    }
    $cstmt->close();
}

// Step 3: Find matching redirect rule (priority: partner -> domain -> global)
// Try partner-specific rule first
if ($partner_id) {
    $stmt = $conn->prepare(
        "
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
    $row = stmt_get_assoc($stmt);
    if ($row) {
        $rule_id = $row['id'];
        $offer_id = $row['offer_id'];
        $redirect_url = $row['clickbank_hoplink'];
    }
    $stmt->close();
}

// Try domain-specific rule if no partner rule found
if (!$redirect_url && $domain_id) {
    $stmt = $conn->prepare(
        "
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
    $row = stmt_get_assoc($stmt);
    if ($row) {
        $rule_id = $row['id'];
        $offer_id = $row['offer_id'];
        $redirect_url = $row['clickbank_hoplink'];
    }
    $stmt->close();
}

// Try global rule if no specific rule found
if (!$redirect_url) {
    $stmt = $conn->prepare(
        "
        SELECT rr.id, rr.offer_id, o.clickbank_hoplink 
        FROM redirect_rules rr
        JOIN offers o ON rr.offer_id = o.id
        WHERE rr.rule_type = 'global' 
        AND rr.is_paused = 0
        AND o.is_active = 1
        ORDER BY rr.priority ASC
        LIMIT 1
    ");
    $row = stmt_get_assoc($stmt);
    if ($row) {
        $rule_id = $row['id'];
        $offer_id = $row['offer_id'];
        $redirect_url = $row['clickbank_hoplink'];
    }
    $stmt->close();
}

// Step 4: Record click in clicks (for attribution) and click_logs (raw)
$click_id = generate_uuid_v4();
$ip_hash = $ip_address ? hash('sha256', $ip_address) : null;
$ua_hash = $user_agent ? hash('sha256', $user_agent) : null;

// Insert into clicks attribution table
$clickStmt = $conn->prepare("INSERT INTO clicks (partner_id, creative_id, click_id, ip_hash, ua_hash, referrer) VALUES (?, ?, ?, ?, ?, ?)");
if ($clickStmt) {
    $clickStmt->bind_param('iissss', $partner_id, $creative_id, $click_id, $ip_hash, $ua_hash, $referer);
    $clickStmt->execute();
    $clickStmt->close();
}

// Raw logging
$logStmt = $conn->prepare(
    "INSERT INTO click_logs (domain_id, partner_id, offer_id, rule_id, ip_address, user_agent, referer, redirect_url)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);
$logStmt->bind_param("iiiissss", $domain_id, $partner_id, $offer_id, $rule_id, $ip_address, $user_agent, $referer, $redirect_url);
$logStmt->execute();
$logStmt->close();

// Set attribution cookie (30 days) — accessible to client-side for attribution
$cookie_val = json_encode(['click_id' => $click_id, 'aff_id' => $aff_id]);
$secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
setcookie('cb_attribution', $cookie_val, time() + 60 * 60 * 24 * 30, '/', '', $secure, false);

// Step 5: Redirect to resolved hoplink, adding tid and click id
if ($redirect_url) {
    $params = [];
    if ($aff_id) $params['tid'] = $aff_id;
    if ($click_id) $params['cb_click_id'] = $click_id;

    if (!empty($params)) {
        $sep = (strpos($redirect_url, '?') !== false) ? '&' : '?';
        $redirect_url .= $sep . http_build_query($params);
    }

    header('Location: ' . $redirect_url, true, 302);
    exit;
} else {
    http_response_code(404);
    echo "No redirect rule configured";
    exit;
}
