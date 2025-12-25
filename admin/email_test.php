<?php
require_once '../config.php';
requireLogin();

// Sample data defaults
$sample = [
    'name' => 'Jane Tester',
    'email' => 'jane@example.com',
    'blog_url' => 'https://example.com',
    'message' => 'This is a test message from admin.',
];

$templates = [
    'applicant_confirmation' => 'Applicant confirmation (received)',
    'admin_notification' => 'Admin notification (new application)',
    'approve' => 'Application approved (send snippet)',
    'request_info' => 'Request more information',
    'reject' => 'Reject application',
    'custom' => 'Custom raw subject/body'
];

$notice = '';
$error = '';
$preview_html = '';
$preview_subject = '';

function build_email($key, $data) {
    switch ($key) {
        case 'applicant_confirmation':
            $subject = 'We Received Your AdeasyNow Application';
            $body = "<html><body><h2>Application Received</h2><p>Hi " . htmlspecialchars($data['name']) . ",</p><p>Thank you for applying to AdeasyNow! We have received your application and our team will review it shortly. Minimum payout is $25.</p><p><strong>What happens next?</strong><br>Our team will verify your domain and business information, then get back to you within 3-5 business days with either an approval, a request for more information, or further details.</p><p><strong>Application Summary:</strong><br><em>Submitted URL:</em> " . htmlspecialchars($data['blog_url']) . "<br><em>Submitted on:</em> " . date('Y-m-d H:i:s') . "</p><p>If you have any questions, please don't hesitate to reach out.</p><p>Best regards,<br>The AdeasyNow Team</p></body></html>";
            return ['subject' => $subject, 'body' => $body];
        case 'admin_notification':
            $subject = 'New AdeasyNow Application Received';
            $body = "<html><body><h2>New AdeasyNow Application</h2><p>A new AdeasyNow application has been submitted for review:</p><table style=\"border-collapse:collapse;width:100%;\"><tr><td style=\"padding:8px;\"><strong>Name:</strong></td><td style=\"padding:8px;\">" . htmlspecialchars($data['name']) . "</td></tr><tr><td style=\"padding:8px;\"><strong>Email:</strong></td><td style=\"padding:8px;\">" . htmlspecialchars($data['email']) . "</td></tr><tr><td style=\"padding:8px;\"><strong>Blog/URL:</strong></td><td style=\"padding:8px;\">" . htmlspecialchars($data['blog_url']) . "</td></tr><tr><td style=\"padding:8px;\"><strong>Notes:</strong></td><td style=\"padding:8px;\">" . nl2br(htmlspecialchars($data['message'])) . "</td></tr></table></body></html>";
            return ['subject' => $subject, 'body' => $body];
        case 'approve':
            $public_id = bin2hex(random_bytes(8));
            $subject = 'Your partner application has been approved';
            $snippet = htmlspecialchars('<script src="https://adeasynow.com/snippet.js" data-partner="' . $public_id . '" async></script>');
            $body = "<html><body><p>Hi " . htmlspecialchars($data['name']) . ",</p><p>Your application has been approved. Below is your snippet (use on your site):</p><pre style=\"background:#f8f9fa;padding:10px;border-radius:4px\">{$snippet}</pre><p>Keep your partner id safe. If you need any help, reply to this email.</p></body></html>";
            return ['subject' => $subject, 'body' => $body];
        case 'request_info':
            $subject = 'More information requested about your application';
            $body = "<html><body><p>Hi " . htmlspecialchars($data['name']) . ",</p><p>We need more information to process your application:</p><blockquote>" . nl2br(htmlspecialchars($data['message'])) . "</blockquote><p>Please reply with the requested details.</p></body></html>";
            return ['subject' => $subject, 'body' => $body];
        case 'reject':
            $subject = 'Your partner application has been rejected';
            $body = "<html><body><p>Hi " . htmlspecialchars($data['name']) . ",</p><p>We are sorry to inform you that your application has been rejected for the following reason:</p><blockquote>" . nl2br(htmlspecialchars($data['message'])) . "</blockquote><p>If you believe this is a mistake, you may reply to this email.</p></body></html>";
            return ['subject' => $subject, 'body' => $body];
        default:
            $subject = $data['subject'] ?? '';
            $body = $data['body'] ?? '';
            return ['subject' => $subject, 'body' => $body];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $to = filter_var(trim($_POST['to'] ?? ''), FILTER_SANITIZE_EMAIL);
    $template = $_POST['template'] ?? 'applicant_confirmation';

    // populate sample overrides
    $sample['name'] = sanitize($_POST['name'] ?? $sample['name']);
    $sample['email'] = sanitize($_POST['email_override'] ?? $sample['email']);
    $sample['blog_url'] = sanitize($_POST['blog_url'] ?? $sample['blog_url']);
    $sample['message'] = sanitize($_POST['message'] ?? $sample['message']);

    if ($action === 'preview') {
        $e = build_email($template, $sample);
        $preview_subject = $e['subject'];
        $preview_html = $e['body'];
    } elseif ($action === 'send') {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid recipient email.';
        } else {
            $payload = $template === 'custom' ? ['subject' => $_POST['subject'] ?? '', 'body' => $_POST['body'] ?? ''] : $sample;
            $e = build_email($template, $payload);
            $sent = send_email($to, $e['subject'], $e['body']);
            if ($sent) {
                $notice = 'Test email sent to ' . htmlspecialchars($to) . '.';
            } else {
                $error = 'Failed to send email. Check server settings / logs.';
            }
            // show preview as well
            $preview_subject = $e['subject'];
            $preview_html = $e['body'];
        }
    }
}

$page_title = 'Email Test Dashboard';
include 'header.php';
?>

<?php include 'nav.php'; ?>

<div class="container">
    <div class="card">
        <h2>Email Test Dashboard</h2>
        <p class="muted">Send test copies of the app's outgoing emails to preview or debug designs. Only accessible to admin users.</p>

        <?php if ($notice): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($notice); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="to">Recipient Email</label>
                <input type="email" id="to" name="to" value="<?php echo htmlspecialchars($sample['email']); ?>" required />
            </div>

            <div class="form-group">
                <label for="template">Template</label>
                <select id="template" name="template" onchange="document.getElementById('custom-fields').style.display = (this.value === 'custom') ? 'block' : 'none';">
                    <?php foreach ($templates as $k => $label): ?>
                        <option value="<?php echo $k; ?>" <?php echo (isset($template) && $template === $k) ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="name">Name (placeholder)</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($sample['name']); ?>" />
            </div>

            <div class="form-group">
                <label for="blog_url">Blog / URL (placeholder)</label>
                <input type="text" id="blog_url" name="blog_url" value="<?php echo htmlspecialchars($sample['blog_url']); ?>" />
            </div>

            <div class="form-group">
                <label for="message">Message (used for request/reject templates)</label>
                <textarea id="message" name="message"><?php echo htmlspecialchars($sample['message']); ?></textarea>
            </div>

            <div id="custom-fields" style="display:none;">
                <div class="form-group">
                    <label for="subject">Custom Subject</label>
                    <input type="text" id="subject" name="subject" />
                </div>
                <div class="form-group">
                    <label for="body">Custom Body (HTML allowed)</label>
                    <textarea id="body" name="body" style="min-height:160px"></textarea>
                </div>
            </div>

            <div style="display:flex;gap:8px">
                <button class="btn" type="submit" name="action" value="preview">Preview</button>
                <button class="btn btn-success" type="submit" name="action" value="send">Send Test Email</button>
            </div>
        </form>

        <?php if ($preview_subject || $preview_html): ?>
            <h3 style="margin-top:20px">Preview</h3>
            <div style="margin-bottom:8px"><strong>Subject:</strong> <?php echo htmlspecialchars($preview_subject); ?></div>
            <div class="card" style="padding:12px;">
                <?php echo $preview_html; ?>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php include 'footer.php'; ?>
