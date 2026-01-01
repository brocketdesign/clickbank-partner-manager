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
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background-color:#f7fafc;font-family:'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f7fafc;padding:40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color:#ffffff;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,0.08);overflow:hidden;">
                    <!-- Header -->
                    <tr>
                        <td style="background:linear-gradient(135deg,#10b981 0%,#059669 100%);padding:32px 40px;text-align:center;">
                            <h1 style="color:#ffffff;margin:0;font-size:24px;font-weight:600;">📋 New Application</h1>
                            <p style="color:rgba(255,255,255,0.9);margin:8px 0 0;font-size:14px;">A new partner application requires your review</p>
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td style="padding:32px 40px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8fafc;border-radius:12px;overflow:hidden;">
                                <tr>
                                    <td style="padding:16px 20px;border-bottom:1px solid #e2e8f0;"><strong style="color:#64748b;font-size:13px;">NAME</strong></td>
                                    <td style="padding:16px 20px;border-bottom:1px solid #e2e8f0;color:#1e293b;font-weight:500;">{$name}</td>
                                </tr>
                                <tr>
                                    <td style="padding:16px 20px;border-bottom:1px solid #e2e8f0;"><strong style="color:#64748b;font-size:13px;">EMAIL</strong></td>
                                    <td style="padding:16px 20px;border-bottom:1px solid #e2e8f0;color:#1e293b;">{$email}</td>
                                </tr>
                                <tr>
                                    <td style="padding:16px 20px;border-bottom:1px solid #e2e8f0;"><strong style="color:#64748b;font-size:13px;">BLOG/URL</strong></td>
                                    <td style="padding:16px 20px;border-bottom:1px solid #e2e8f0;"><a href="{$blog_url}" target="_blank" style="color:#10b981;text-decoration:none;">{$blog_url}</a></td>
                                </tr>
                                <tr>
                                    <td style="padding:16px 20px;border-bottom:1px solid #e2e8f0;"><strong style="color:#64748b;font-size:13px;">MONTHLY TRAFFIC</strong></td>
                                    <td style="padding:16px 20px;border-bottom:1px solid #e2e8f0;color:#1e293b;">{$traffic}</td>
                                </tr>
                                <tr>
                                    <td style="padding:16px 20px;border-bottom:1px solid #e2e8f0;"><strong style="color:#64748b;font-size:13px;">DOMAIN STATUS</strong></td>
                                    <td style="padding:16px 20px;border-bottom:1px solid #e2e8f0;"><span style="background:#10b981;color:#fff;padding:4px 10px;border-radius:20px;font-size:12px;font-weight:600;">{$domain_verification_result}</span></td>
                                </tr>
                                <tr>
                                    <td style="padding:16px 20px;border-bottom:1px solid #e2e8f0;"><strong style="color:#64748b;font-size:13px;">IP ADDRESS</strong></td>
                                    <td style="padding:16px 20px;border-bottom:1px solid #e2e8f0;color:#64748b;font-family:monospace;">{$ip}</td>
                                </tr>
                                <tr>
                                    <td style="padding:16px 20px;"><strong style="color:#64748b;font-size:13px;">NOTES</strong></td>
                                    <td style="padding:16px 20px;color:#1e293b;">{$notes}</td>
                                </tr>
                            </table>
                            <!-- CTA Button -->
                            <div style="text-align:center;margin-top:32px;">
                                <a href="https://adeasynow.com/admin/applications" target="_blank" style="display:inline-block;background:linear-gradient(135deg,#10b981 0%,#059669 100%);color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:8px;font-weight:600;font-size:14px;box-shadow:0 4px 14px rgba(16,185,129,0.4);">Review Application →</a>
                            </div>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background:#f8fafc;padding:24px 40px;text-align:center;border-top:1px solid #e2e8f0;">
                            <p style="color:#94a3b8;font-size:12px;margin:0;">This is an automated notification from AdeasyNow Partner System</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

send_email(ADMIN_EMAIL, $admin_subject, $admin_body);

// Applicant confirmation
$submitted_date = date('Y-m-d H:i:s');
$applicant_subject = 'We Received Your AdeasyNow Application';
$applicant_body = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background-color:#f7fafc;font-family:'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f7fafc;padding:40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color:#ffffff;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,0.08);overflow:hidden;">
                    <!-- Header with checkmark -->
                    <tr>
                        <td style="background:linear-gradient(135deg,#10b981 0%,#059669 100%);padding:40px;text-align:center;">
                            <div style="width:70px;height:70px;background:rgba(255,255,255,0.2);border-radius:50%;margin:0 auto 16px;line-height:70px;font-size:32px;">✓</div>
                            <h1 style="color:#ffffff;margin:0;font-size:26px;font-weight:600;">Application Received!</h1>
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td style="padding:40px;">
                            <p style="color:#1e293b;font-size:16px;line-height:1.6;margin:0 0 20px;">Hi <strong>{$name}</strong>,</p>
                            <p style="color:#475569;font-size:15px;line-height:1.7;margin:0 0 24px;">Thank you for applying to AdeasyNow! We've received your application and our team will review it shortly.</p>
                            
                            <!-- Info box -->
                            <div style="background:linear-gradient(135deg,rgba(16,185,129,0.08) 0%,rgba(5,150,105,0.08) 100%);border-left:4px solid #10b981;border-radius:0 8px 8px 0;padding:20px;margin:24px 0;">
                                <h3 style="color:#059669;margin:0 0 12px;font-size:15px;">💰 Minimum Payout: \$25</h3>
                                <p style="color:#475569;margin:0;font-size:14px;line-height:1.6;">Start earning from day one with our competitive commission structure!</p>
                            </div>
                            
                            <!-- What happens next -->
                            <h3 style="color:#1e293b;font-size:16px;margin:28px 0 16px;">📋 What happens next?</h3>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8fafc;border-radius:12px;">
                                <tr>
                                    <td style="padding:16px 20px;border-bottom:1px solid #e2e8f0;">
                                        <table role="presentation" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td style="width:32px;vertical-align:top;"><span style="display:inline-block;background:#10b981;color:#fff;width:24px;height:24px;border-radius:50%;text-align:center;line-height:24px;font-size:12px;font-weight:bold;">1</span></td>
                                                <td style="color:#475569;font-size:14px;padding-left:8px;">We verify your domain and business information</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:16px 20px;border-bottom:1px solid #e2e8f0;">
                                        <table role="presentation" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td style="width:32px;vertical-align:top;"><span style="display:inline-block;background:#10b981;color:#fff;width:24px;height:24px;border-radius:50%;text-align:center;line-height:24px;font-size:12px;font-weight:bold;">2</span></td>
                                                <td style="color:#475569;font-size:14px;padding-left:8px;">Our team reviews your application (3-5 business days)</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:16px 20px;">
                                        <table role="presentation" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td style="width:32px;vertical-align:top;"><span style="display:inline-block;background:#10b981;color:#fff;width:24px;height:24px;border-radius:50%;text-align:center;line-height:24px;font-size:12px;font-weight:bold;">3</span></td>
                                                <td style="color:#475569;font-size:14px;padding-left:8px;">You'll receive approval or further instructions via email</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Application summary -->
                            <h3 style="color:#1e293b;font-size:16px;margin:28px 0 16px;">📝 Application Summary</h3>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8fafc;border-radius:12px;">
                                <tr>
                                    <td style="padding:14px 20px;border-bottom:1px solid #e2e8f0;color:#64748b;font-size:13px;width:40%;">Submitted URL</td>
                                    <td style="padding:14px 20px;border-bottom:1px solid #e2e8f0;color:#10b981;font-weight:500;">{$blog_url}</td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 20px;color:#64748b;font-size:13px;">Submitted On</td>
                                    <td style="padding:14px 20px;color:#1e293b;">{$submitted_date}</td>
                                </tr>
                            </table>
                            
                            <p style="color:#475569;font-size:14px;line-height:1.6;margin:28px 0 0;">If you have any questions, feel free to reply to this email.</p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background:#f8fafc;padding:24px 40px;text-align:center;border-top:1px solid #e2e8f0;">
                            <p style="color:#1e293b;font-weight:600;margin:0 0 4px;font-size:14px;">The AdeasyNow Team</p>
                            <p style="color:#94a3b8;font-size:12px;margin:0;">Empowering partners to succeed</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
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
