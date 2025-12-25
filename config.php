<?php
// Database configuration
// Enable error reporting for development (remove or disable in production)
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('DB_HOST', 'localhost');
define('DB_USER', 'u114685281_cb_manager');
define('DB_PASS', 'dikgyr-nicby9-xosqoD');
define('DB_NAME', 'u114685281_cb_manager');

// Create database connection
function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
    }
    
    return $conn;
}

// Helper to execute a prepared statement and return the first row as an associative array.
// Works whether or not mysqli_stmt::get_result() is available.
function stmt_get_assoc($stmt) {
    if (!$stmt) return false;
    $stmt->execute();

    if (method_exists($stmt, 'get_result')) {
        $res = $stmt->get_result();
        if ($res === false) return false;
        $row = $res->fetch_assoc();
        return $row ?: false;
    }

    $meta = $stmt->result_metadata();
    if (!$meta) return false;
    $fields = [];
    $row = [];
    while ($field = $meta->fetch_field()) {
        $fields[] = &$row[$field->name];
    }
    $meta->free();

    if (empty($fields)) return false;
    call_user_func_array([$stmt, 'bind_result'], $fields);

    if ($stmt->fetch()) {
        $out = [];
        foreach ($row as $k => $v) $out[$k] = $v;
        return $out;
    }
    return false;
}

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in (for admin pages)
function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Redirect to login if not authenticated
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /admin/login.php');
        exit;
    }
}

// Sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Get client IP address
function getClientIP() {
    $ip = '';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

// ----------------------
// Email & helper config
// ----------------------
// Set your admin email for application notifications
define('ADMIN_EMAIL', 'contact@adeasynow.com');
// Default From address for outgoing emails
define('MAIL_FROM', 'contact@adeasynow.com');

// SMTP settings (PHPMailer integration)
// Configure these with your email provider (SendGrid, Mailgun, Postmark, etc.)
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'localhost');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
define('SMTP_ENABLED', !empty(SMTP_USER) && !empty(SMTP_PASS));

/**
 * Send email using PHPMailer with SMTP fallback to PHP mail()
 */
function send_email($to, $subject, $html_body) {
    if (SMTP_ENABLED && function_exists('PHPMailer\PHPMailer\PHPMailer')) {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;
            
            $mail->setFrom(MAIL_FROM);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $html_body;
            
            return $mail->send();
        } catch (Exception $e) {
            error_log('PHPMailer error: ' . $e->getMessage());
            // Fall through to PHP mail()
        }
    }
    
    // Fallback: PHP mail()
    $from = MAIL_FROM;
    $headers  = "From: " . $from . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    return mail($to, $subject, $html_body, $headers);
}

// Simple domain reachability check (returns true on 200/301/302)
function verifyDomainReachable($url) {
    $url = trim($url);
    if ($url === '') return false;
    if (!preg_match('#^https?://#i', $url)) {
        $url = 'http://' . $url;
    }
    $headers = @get_headers($url);
    if ($headers && isset($headers[0]) && preg_match('#200|301|302#', $headers[0])) {
        return true;
    }
    return false;
}

/**
 * Detect disposable/temporary email domains using heuristics
 * Returns true if email appears to be disposable
 */
function isDisposableEmail($email) {
    $domain = strtolower(trim(substr(strrchr($email, '@'), 1)));
    
    // List of known disposable email domains
    $disposable_domains = [
        'tempmail.com', 'temp-mail.org', 'guerrillamail.com', 'mailinator.com',
        '10minutemail.com', 'maildrop.cc', 'yopmail.com', 'trash-mail.com',
        'throwaway.email', 'mytrashmail.com', 'sharklasers.com', 'spam4.me',
        'temp-mail.io', 'temporary-mail.net', 'fakeinbox.com', 'mail.tm'
    ];
    
    if (in_array($domain, $disposable_domains)) {
        return true;
    }
    
    // Heuristic: domains with "temp", "trash", "fake", "disposable" in name
    if (preg_match('/(temp|trash|fake|disposable|spam|drop|guerrilla)/i', $domain)) {
        return true;
    }
    
    return false;
}

/**
 * Basic rate limiting per IP: check if too many applications from this IP in last N hours
 * Returns array: ['allowed' => bool, 'message' => string]
 */
function checkRateLimit($ip, $hours = 24, $max_attempts = 5) {
    $conn = getDBConnection();
    
    // Query applications from this IP in the last N hours
    $cutoff_time = date('Y-m-d H:i:s', time() - ($hours * 3600));
    
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM partner_applications WHERE ip_address = ? AND created_at > ?");
    if (!$stmt) {
        // If query fails, allow (safe to proceed)
        return ['allowed' => true, 'message' => ''];
    }
    
    $stmt->bind_param('ss', $ip, $cutoff_time);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    $count = $row['cnt'] ?? 0;
    if ($count >= $max_attempts) {
        return ['allowed' => false, 'message' => "Too many applications from this IP. Please try again later."];
    }
    
    return ['allowed' => true, 'message' => ''];
}

/**
 * Generate a UUID v4 for public partner IDs
 */
function generate_uuid_v4() {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// Generate a public partner id (aff_id)
function generate_aff_id() {
    try {
        return bin2hex(random_bytes(8));
    } catch (Exception $e) {
        return substr(md5(uniqid('', true)), 0, 16);
    }
}
