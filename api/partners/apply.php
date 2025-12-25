<?php
/**
 * Partner Registration API Endpoint
 * 
 * POST /api/partners/apply
 * 
 * Accepts: name, email, blog_url, traffic, notes (optional), consent, captcha_a, captcha_b, captcha_result
 * 
 * Process:
 * 1. Validate form inputs and CAPTCHA
 * 2. Check for disposable email
 * 3. Apply rate limiting (per IP)
 * 4. Verify domain reachability
 * 5. Create application record with status="pending"
 * 6. Send email notifications (admin + applicant)
 * 
 * Response: JSON with success/message
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once __DIR__ . '/../../config.php';

$conn = getDBConnection();

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get client IP
$ip = getClientIP();

// Extract and sanitize inputs
$name = sanitize($_POST['name'] ?? '');
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$blog_url = sanitize($_POST['blog_url'] ?? '');
$traffic = sanitize($_POST['traffic'] ?? '');
$notes = sanitize($_POST['notes'] ?? '');
$consent = isset($_POST['consent']) && $_POST['consent'] === '1' ? 1 : 0;

$captcha_a = intval($_POST['captcha_a'] ?? 0);
$captcha_b = intval($_POST['captcha_b'] ?? 0);
$captcha_result = intval($_POST['captcha_result'] ?? 0);

// ===== Validation =====

// 1. Check required fields
if ($name === '' || $email === '' || $blog_url === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// 2. Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

// 3. Check T&C consent
if (!$consent) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'You must agree to the Terms & Conditions']);
    exit;
}

// 4. Validate CAPTCHA
if ($captcha_result !== ($captcha_a + $captcha_b)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid CAPTCHA. Please try again.']);
    exit;
}

// ===== Automated Pre-checks =====

// 5. Check for disposable email
if (isDisposableEmail($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please use a valid business email address.']);
    exit;
}

// 6. Check duplicate email
$stmt = $conn->prepare("SELECT id FROM partner_applications WHERE email = ? LIMIT 1");
$stmt->bind_param('s', $email);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existing) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'An application from this email already exists.']);
    exit;
}

// 7. Apply rate limiting
$rate_check = checkRateLimit($ip, 24, 5);
if (!$rate_check['allowed']) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => $rate_check['message']]);
    exit;
}

// 8. Normalize and verify blog URL
if (!preg_match('#^https?://#i', $blog_url)) {
    $blog_url = 'http://' . $blog_url;
}

// Domain verification (async-safe for now, just store result)
$domain_verification_result = verifyDomainReachable($blog_url) ? 'verified' : 'failed';

// 9. Ensure applications table exists (safety check)
$create_sql = "CREATE TABLE IF NOT EXISTS partner_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    blog_url VARCHAR(512) NOT NULL,
    traffic_estimate VARCHAR(64),
    notes TEXT,
    consent TINYINT(1) DEFAULT 0,
    status VARCHAR(32) DEFAULT 'pending',
    domain_verification_status VARCHAR(32) DEFAULT 'unchecked',
    domain_verified TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    INDEX idx_status (status),
    INDEX idx_email (email),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$conn->query($create_sql);

// 10. Insert application record
$stmt = $conn->prepare("INSERT INTO partner_applications 
    (name, email, blog_url, traffic_estimate, notes, consent, status, domain_verification_status, domain_verified, ip_address)
    VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, 1, ?)");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
}

$stmt->bind_param('ssssssis', $name, $email, $blog_url, $traffic, $notes, $consent, $domain_verification_result, $ip);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save application']);
    exit;
}

$app_id = $stmt->insert_id;
$stmt->close();

// ===== Email Notifications =====

// Admin notification
$admin_subject = 'New AdeasyNow Application Received';
$admin_body = <<<HTML
<html>
<body style="font-family: Arial, sans-serif;">
    <h2>New AdeasyNow Application</h2>
    <p>A new AdeasyNow application has been submitted for review:</p>
    <table style="border-collapse: collapse; width: 100%;">
        <tr><td style="border: 1px solid #ddd; padding: 8px;"><strong>Name:</strong></td><td style="border: 1px solid #ddd; padding: 8px;">{$name}</td></tr>
        <tr><td style="border: 1px solid #ddd; padding: 8px;"><strong>Email:</strong></td><td style="border: 1px solid #ddd; padding: 8px;">{$email}</td></tr>
        <tr><td style="border: 1px solid #ddd; padding: 8px;"><strong>Blog/URL:</strong></td><td style="border: 1px solid #ddd; padding: 8px;"><a href="{$blog_url}" target="_blank">{$blog_url}</a></td></tr>
        <tr><td style="border: 1px solid #ddd; padding: 8px;"><strong>Monthly Traffic:</strong></td><td style="border: 1px solid #ddd; padding: 8px;">{$traffic}</td></tr>
        <tr><td style="border: 1px solid #ddd; padding: 8px;"><strong>Domain Verification:</strong></td><td style="border: 1px solid #ddd; padding: 8px;"><strong>{$domain_verification_result}</strong></td></tr>
        <tr><td style="border: 1px solid #ddd; padding: 8px;"><strong>IP Address:</strong></td><td style="border: 1px solid #ddd; padding: 8px;">{$ip}</td></tr>
        <tr><td style="border: 1px solid #ddd; padding: 8px;"><strong>Notes:</strong></td><td style="border: 1px solid #ddd; padding: 8px;">{$notes}</td></tr>
    </table>
        <p><a href="https://adeasynow.com/admin/applications" target="_blank">Review Application</a></p>
</body>
</html>
HTML;

send_email(ADMIN_EMAIL, $admin_subject, $admin_body);

// Applicant confirmation
$applicant_subject = 'We Received Your AdeasyNow Application';
$applicant_body = <<<HTML
<html>
<body style="font-family: Arial, sans-serif;">
    <h2>Application Received</h2>
    <p>Hi {$name},</p>
    <p>Thank you for applying to AdeasyNow! We have received your application and our team will review it shortly. Minimum payout is $25.</p>
    <p><strong>What happens next?</strong><br>
    Our team will verify your domain and business information, then get back to you within 3-5 business days with either an approval, a request for more information, or further details.</p>
    <p><strong>Application Summary:</strong><br>
    <em>Submitted URL:</em> {$blog_url}<br>
    <em>Submitted on:</em> " . date('Y-m-d H:i:s') . "</p>
    <p>If you have any questions, please don't hesitate to reach out.</p>
    <p>Best regards,<br>
    The AdeasyNow Team</p>
</body>
</html>
HTML;

send_email($email, $applicant_subject, $applicant_body);

// Return success
http_response_code(201);
echo json_encode([
    'success' => true,
    'message' => 'Application submitted successfully. Check your email for confirmation.',
    'application_id' => $app_id,
    'domain_verification' => $domain_verification_result
]);
exit;
