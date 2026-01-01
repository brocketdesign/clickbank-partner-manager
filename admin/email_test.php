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
    $name = htmlspecialchars($data['name']);
    $email = htmlspecialchars($data['email']);
    $blog_url = htmlspecialchars($data['blog_url']);
    $message = nl2br(htmlspecialchars($data['message']));
    $submitted_date = date('Y-m-d H:i:s');
    
    switch ($key) {
        case 'applicant_confirmation':
            $subject = 'We Received Your AdeasyNow Application';
            $body = <<<HTML
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
                    <tr>
                        <td style="background:linear-gradient(135deg,#10b981 0%,#059669 100%);padding:40px;text-align:center;">
                            <div style="width:70px;height:70px;background:rgba(255,255,255,0.2);border-radius:50%;margin:0 auto 16px;line-height:70px;font-size:32px;">✓</div>
                            <h1 style="color:#ffffff;margin:0;font-size:26px;font-weight:600;">Application Received!</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:40px;">
                            <p style="color:#1e293b;font-size:16px;line-height:1.6;margin:0 0 20px;">Hi <strong>{$name}</strong>,</p>
                            <p style="color:#475569;font-size:15px;line-height:1.7;margin:0 0 24px;">Thank you for applying to AdeasyNow! We've received your application and our team will review it shortly.</p>
                            <div style="background:linear-gradient(135deg,rgba(16,185,129,0.08) 0%,rgba(5,150,105,0.08) 100%);border-left:4px solid #10b981;border-radius:0 8px 8px 0;padding:20px;margin:24px 0;">
                                <h3 style="color:#059669;margin:0 0 12px;font-size:15px;">💰 Minimum Payout: \$25</h3>
                                <p style="color:#475569;margin:0;font-size:14px;line-height:1.6;">Start earning from day one with our competitive commission structure!</p>
                            </div>
                            <h3 style="color:#1e293b;font-size:16px;margin:28px 0 16px;">📋 What happens next?</h3>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8fafc;border-radius:12px;">
                                <tr><td style="padding:16px 20px;border-bottom:1px solid #e2e8f0;"><table role="presentation" cellspacing="0" cellpadding="0"><tr><td style="width:32px;vertical-align:top;"><span style="display:inline-block;background:#10b981;color:#fff;width:24px;height:24px;border-radius:50%;text-align:center;line-height:24px;font-size:12px;font-weight:bold;">1</span></td><td style="color:#475569;font-size:14px;padding-left:8px;">We verify your domain and business information</td></tr></table></td></tr>
                                <tr><td style="padding:16px 20px;border-bottom:1px solid #e2e8f0;"><table role="presentation" cellspacing="0" cellpadding="0"><tr><td style="width:32px;vertical-align:top;"><span style="display:inline-block;background:#10b981;color:#fff;width:24px;height:24px;border-radius:50%;text-align:center;line-height:24px;font-size:12px;font-weight:bold;">2</span></td><td style="color:#475569;font-size:14px;padding-left:8px;">Our team reviews your application (3-5 business days)</td></tr></table></td></tr>
                                <tr><td style="padding:16px 20px;"><table role="presentation" cellspacing="0" cellpadding="0"><tr><td style="width:32px;vertical-align:top;"><span style="display:inline-block;background:#10b981;color:#fff;width:24px;height:24px;border-radius:50%;text-align:center;line-height:24px;font-size:12px;font-weight:bold;">3</span></td><td style="color:#475569;font-size:14px;padding-left:8px;">You'll receive approval or further instructions via email</td></tr></table></td></tr>
                            </table>
                            <h3 style="color:#1e293b;font-size:16px;margin:28px 0 16px;">📝 Application Summary</h3>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8fafc;border-radius:12px;">
                                <tr><td style="padding:14px 20px;border-bottom:1px solid #e2e8f0;color:#64748b;font-size:13px;width:40%;">Submitted URL</td><td style="padding:14px 20px;border-bottom:1px solid #e2e8f0;color:#10b981;font-weight:500;">{$blog_url}</td></tr>
                                <tr><td style="padding:14px 20px;color:#64748b;font-size:13px;">Submitted On</td><td style="padding:14px 20px;color:#1e293b;">{$submitted_date}</td></tr>
                            </table>
                            <p style="color:#475569;font-size:14px;line-height:1.6;margin:28px 0 0;">If you have any questions, feel free to reply to this email.</p>
                        </td>
                    </tr>
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
            return ['subject' => $subject, 'body' => $body];
        case 'admin_notification':
            $subject = 'New AdeasyNow Application Received';
            $body = <<<HTML
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
                    <tr>
                        <td style="background:linear-gradient(135deg,#10b981 0%,#059669 100%);padding:32px 40px;text-align:center;">
                            <h1 style="color:#ffffff;margin:0;font-size:24px;font-weight:600;">📋 New Application</h1>
                            <p style="color:rgba(255,255,255,0.9);margin:8px 0 0;font-size:14px;">A new partner application requires your review</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px 40px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8fafc;border-radius:12px;overflow:hidden;">
                                <tr><td style="padding:16px 20px;border-bottom:1px solid #e2e8f0;"><strong style="color:#64748b;font-size:13px;">NAME</strong></td><td style="padding:16px 20px;border-bottom:1px solid #e2e8f0;color:#1e293b;font-weight:500;">{$name}</td></tr>
                                <tr><td style="padding:16px 20px;border-bottom:1px solid #e2e8f0;"><strong style="color:#64748b;font-size:13px;">EMAIL</strong></td><td style="padding:16px 20px;border-bottom:1px solid #e2e8f0;color:#1e293b;">{$email}</td></tr>
                                <tr><td style="padding:16px 20px;border-bottom:1px solid #e2e8f0;"><strong style="color:#64748b;font-size:13px;">BLOG/URL</strong></td><td style="padding:16px 20px;border-bottom:1px solid #e2e8f0;"><a href="{$blog_url}" target="_blank" style="color:#10b981;text-decoration:none;">{$blog_url}</a></td></tr>
                                <tr><td style="padding:16px 20px;"><strong style="color:#64748b;font-size:13px;">NOTES</strong></td><td style="padding:16px 20px;color:#1e293b;">{$message}</td></tr>
                            </table>
                            <div style="text-align:center;margin-top:32px;">
                                <a href="https://adeasynow.com/admin/applications" target="_blank" style="display:inline-block;background:linear-gradient(135deg,#10b981 0%,#059669 100%);color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:8px;font-weight:600;font-size:14px;box-shadow:0 4px 14px rgba(16,185,129,0.4);">Review Application →</a>
                            </div>
                        </td>
                    </tr>
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
            return ['subject' => $subject, 'body' => $body];
        case 'approve':
            $public_id = bin2hex(random_bytes(8));
            $subject = 'Your partner application has been approved';
            $snippet = htmlspecialchars('<script src="https://adeasynow.com/snippet.js" data-partner="' . $public_id . '" async></script>');
            $body = <<<HTML
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
                    <tr>
                        <td style="background:linear-gradient(135deg,#10b981 0%,#059669 100%);padding:40px;text-align:center;">
                            <div style="width:70px;height:70px;background:rgba(255,255,255,0.2);border-radius:50%;margin:0 auto 16px;line-height:70px;font-size:32px;">🎉</div>
                            <h1 style="color:#ffffff;margin:0;font-size:26px;font-weight:600;">You're Approved!</h1>
                            <p style="color:rgba(255,255,255,0.9);margin:12px 0 0;font-size:15px;">Welcome to the AdeasyNow Partner Program</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:40px;">
                            <p style="color:#1e293b;font-size:16px;line-height:1.6;margin:0 0 20px;">Hi <strong>{$name}</strong>,</p>
                            <p style="color:#475569;font-size:15px;line-height:1.7;margin:0 0 24px;">Great news! Your application has been approved. You can now start earning commissions by adding the snippet below to your website.</p>
                            <div style="background:#f8fafc;border-radius:12px;padding:24px;margin:24px 0;">
                                <h3 style="color:#1e293b;margin:0 0 16px;font-size:14px;">📋 Your Integration Snippet</h3>
                                <div style="background:#1e293b;border-radius:8px;padding:16px;overflow-x:auto;">
                                    <code style="color:#10b981;font-family:Monaco,Consolas,monospace;font-size:13px;white-space:pre-wrap;word-break:break-all;">{$snippet}</code>
                                </div>
                                <p style="color:#64748b;font-size:12px;margin:12px 0 0;">Add this code just before the closing &lt;/body&gt; tag on your website.</p>
                            </div>
                            <div style="background:linear-gradient(135deg,rgba(16,185,129,0.08) 0%,rgba(5,150,105,0.08) 100%);border-left:4px solid #10b981;border-radius:0 8px 8px 0;padding:16px 20px;margin:24px 0;">
                                <p style="color:#059669;margin:0;font-size:14px;">🔒 <strong>Keep your partner ID safe!</strong> This unique identifier is tied to your account and earnings.</p>
                            </div>
                            <p style="color:#475569;font-size:14px;line-height:1.6;margin:24px 0 0;">Need help with integration? Simply reply to this email and our team will assist you.</p>
                        </td>
                    </tr>
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
            return ['subject' => $subject, 'body' => $body];
        case 'request_info':
            $subject = 'More information requested about your application';
            $body = <<<HTML
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
                    <tr>
                        <td style="background:linear-gradient(135deg,#f59e0b 0%,#d97706 100%);padding:40px;text-align:center;">
                            <div style="width:70px;height:70px;background:rgba(255,255,255,0.2);border-radius:50%;margin:0 auto 16px;line-height:70px;font-size:32px;">📝</div>
                            <h1 style="color:#ffffff;margin:0;font-size:26px;font-weight:600;">Additional Info Needed</h1>
                            <p style="color:rgba(255,255,255,0.9);margin:12px 0 0;font-size:15px;">We need a bit more information to continue</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:40px;">
                            <p style="color:#1e293b;font-size:16px;line-height:1.6;margin:0 0 20px;">Hi <strong>{$name}</strong>,</p>
                            <p style="color:#475569;font-size:15px;line-height:1.7;margin:0 0 24px;">Thank you for your interest in joining AdeasyNow! To process your application, we need some additional information:</p>
                            <div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:12px;padding:24px;margin:24px 0;">
                                <p style="color:#92400e;margin:0;font-size:15px;line-height:1.7;">{$message}</p>
                            </div>
                            <p style="color:#475569;font-size:14px;line-height:1.6;margin:24px 0 0;">Please reply to this email with the requested details, and we'll continue processing your application right away.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f8fafc;padding:24px 40px;text-align:center;border-top:1px solid #e2e8f0;">
                            <p style="color:#1e293b;font-weight:600;margin:0 0 4px;font-size:14px;">The AdeasyNow Team</p>
                            <p style="color:#94a3b8;font-size:12px;margin:0;">We're here to help</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
            return ['subject' => $subject, 'body' => $body];
        case 'reject':
            $subject = 'Your partner application has been rejected';
            $body = <<<HTML
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
                    <tr>
                        <td style="background:linear-gradient(135deg,#64748b 0%,#475569 100%);padding:40px;text-align:center;">
                            <div style="width:70px;height:70px;background:rgba(255,255,255,0.2);border-radius:50%;margin:0 auto 16px;line-height:70px;font-size:32px;">📋</div>
                            <h1 style="color:#ffffff;margin:0;font-size:26px;font-weight:600;">Application Update</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:40px;">
                            <p style="color:#1e293b;font-size:16px;line-height:1.6;margin:0 0 20px;">Hi <strong>{$name}</strong>,</p>
                            <p style="color:#475569;font-size:15px;line-height:1.7;margin:0 0 24px;">Thank you for your interest in the AdeasyNow Partner Program. After reviewing your application, we regret to inform you that we are unable to approve it at this time.</p>
                            <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:24px;margin:24px 0;">
                                <h4 style="color:#991b1b;margin:0 0 12px;font-size:14px;">Reason:</h4>
                                <p style="color:#7f1d1d;margin:0;font-size:15px;line-height:1.7;">{$message}</p>
                            </div>
                            <p style="color:#475569;font-size:14px;line-height:1.6;margin:24px 0 0;">If you believe this decision was made in error, or if you'd like to provide additional information, please don't hesitate to reply to this email.</p>
                            <div style="background:linear-gradient(135deg,rgba(16,185,129,0.08) 0%,rgba(5,150,105,0.08) 100%);border-left:4px solid #10b981;border-radius:0 8px 8px 0;padding:16px 20px;margin:24px 0;">
                                <p style="color:#059669;margin:0;font-size:14px;">💡 <strong>Tip:</strong> You may reapply after addressing the concerns mentioned above.</p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f8fafc;padding:24px 40px;text-align:center;border-top:1px solid #e2e8f0;">
                            <p style="color:#1e293b;font-weight:600;margin:0 0 4px;font-size:14px;">The AdeasyNow Team</p>
                            <p style="color:#94a3b8;font-size:12px;margin:0;">We appreciate your understanding</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
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
