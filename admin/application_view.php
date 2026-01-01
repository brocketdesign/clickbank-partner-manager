<?php
require_once '../config.php';
requireLogin();

$conn = getDBConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: applications.php');
    exit;
}

// Fetch application
$stmt = $conn->prepare("SELECT * FROM partner_applications WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$app = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$app) {
    header('Location: applications.php');
    exit;
}

// Handle admin actions
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $message = trim($_POST['message'] ?? '');

    if ($action === 'approve') {
        // Create approved partner
        $public_id = generate_uuid_v4();
        $host = parse_url($app['blog_url'], PHP_URL_HOST) ?: '';
        $allowed_domains = $host;

        // Start transaction to ensure both partner and domain creation succeed together
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO partners_new (partner_id_public, name, email, blog_url, allowed_domains, status, domain_verification_status, approved_at, notes, ip_address) VALUES (?, ?, ?, ?, ?, 'approved', ?, NOW(), ?, ?)");
            $stmt->bind_param('ssssssss', $public_id, $app['name'], $app['email'], $app['blog_url'], $allowed_domains, $app['domain_verification_status'], $app['notes'], $app['ip_address']);
            if (!$stmt->execute()) throw new Exception('partners_new insert failed: ' . $stmt->error);
            $partner_new_id = $stmt->insert_id;
            $stmt->close();

            // Also create canonical partners row (used by admin list)
            $aff_id = $public_id; // store same public id in old partners table
            $pstmt = $conn->prepare("INSERT INTO partners (aff_id, partner_name, is_active, created_at) VALUES (?, ?, 1, NOW())");
            $pstmt->bind_param('ss', $aff_id, $app['name']);
            if (!$pstmt->execute()) throw new Exception('partners insert failed: ' . $pstmt->error);
            $pstmt->close();

            // Ensure domain is present in domains table
            if ($host) {
                $dstmt = $conn->prepare("SELECT id FROM domains WHERE domain_name = ? LIMIT 1");
                $dstmt->bind_param('s', $host);
                $dstmt->execute();
                $dres = $dstmt->get_result()->fetch_assoc();
                $dstmt->close();

                if (!$dres) {
                    $ins = $conn->prepare("INSERT INTO domains (domain_name, is_active) VALUES (?, 1)");
                    $ins->bind_param('s', $host);
                    if (!$ins->execute()) throw new Exception('domains insert failed: ' . $ins->error);
                    $ins->close();
                }
            }

            // Update application status
            $u = $conn->prepare("UPDATE partner_applications SET status = 'approved' WHERE id = ?");
            $u->bind_param('i', $id);
            if (!$u->execute()) throw new Exception('application update failed: ' . $u->error);
            $u->close();

            // Log message
            $m = $conn->prepare("INSERT INTO application_messages (partner_id, message_type, message_text) VALUES (?, 'approve', ?)");
            $m->bind_param('is', $id, $message);
            if (!$m->execute()) throw new Exception('message insert failed: ' . $m->error);
            $m->close();

            $conn->commit();

            // Send email to applicant with snippet
            $snippet = htmlspecialchars('<script src="https://adeasynow.com/snippet.js" data-partner="' . $public_id . '" async></script>');
            $subject = 'Your partner application has been approved';
            $escapedName = htmlspecialchars($app['name']);
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
                            <p style="color:#1e293b;font-size:16px;line-height:1.6;margin:0 0 20px;">Hi <strong>{$escapedName}</strong>,</p>
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
            send_email($app['email'], $subject, $body);

            $success = 'Application approved and partner created.';
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Failed to create partner: ' . $e->getMessage();
        }
    } elseif ($action === 'request_info') {
        if (empty($message)) {
            $error = 'Message is required to request more info.';
        } else {
            $u = $conn->prepare("UPDATE partner_applications SET status = 'info_requested' WHERE id = ?");
            $u->bind_param('i', $id);
            $u->execute();
            $u->close();

            $m = $conn->prepare("INSERT INTO application_messages (partner_id, message_type, message_text) VALUES (?, 'request_info', ?)");
            $m->bind_param('is', $id, $message);
            $m->execute();
            $m->close();

            // Email applicant
            $subject = 'More information requested about your application';
            $escapedName = htmlspecialchars($app['name']);
            $escapedMessage = nl2br(htmlspecialchars($message));
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
                            <p style="color:#1e293b;font-size:16px;line-height:1.6;margin:0 0 20px;">Hi <strong>{$escapedName}</strong>,</p>
                            <p style="color:#475569;font-size:15px;line-height:1.7;margin:0 0 24px;">Thank you for your interest in joining AdeasyNow! To process your application, we need some additional information:</p>
                            <div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:12px;padding:24px;margin:24px 0;">
                                <p style="color:#92400e;margin:0;font-size:15px;line-height:1.7;">{$escapedMessage}</p>
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
            send_email($app['email'], $subject, $body);

            $success = 'Request for more information sent.';
        }
    } elseif ($action === 'reject') {
        if (empty($message)) {
            $error = 'Message is required to reject an application.';
        } else {
            $u = $conn->prepare("UPDATE partner_applications SET status = 'rejected' WHERE id = ?");
            $u->bind_param('i', $id);
            $u->execute();
            $u->close();

            $m = $conn->prepare("INSERT INTO application_messages (partner_id, message_type, message_text) VALUES (?, 'reject', ?)");
            $m->bind_param('is', $id, $message);
            $m->execute();
            $m->close();

            $subject = 'Your partner application has been rejected';
            $escapedName = htmlspecialchars($app['name']);
            $escapedMessage = nl2br(htmlspecialchars($message));
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
                            <p style="color:#1e293b;font-size:16px;line-height:1.6;margin:0 0 20px;">Hi <strong>{$escapedName}</strong>,</p>
                            <p style="color:#475569;font-size:15px;line-height:1.7;margin:0 0 24px;">Thank you for your interest in the AdeasyNow Partner Program. After reviewing your application, we regret to inform you that we are unable to approve it at this time.</p>
                            <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:24px;margin:24px 0;">
                                <h4 style="color:#991b1b;margin:0 0 12px;font-size:14px;">Reason:</h4>
                                <p style="color:#7f1d1d;margin:0;font-size:15px;line-height:1.7;">{$escapedMessage}</p>
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
            send_email($app['email'], $subject, $body);

            $success = 'Application rejected and applicant notified.';
        }
    }

    // Reload application data and messages after action
    $stmt = $conn->prepare("SELECT * FROM partner_applications WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $app = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Load messages
$stmt = $conn->prepare("SELECT * FROM application_messages WHERE partner_id = ? ORDER BY created_at DESC");
$stmt->bind_param('i', $id);
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Sample traffic (simple click count matching host in referer)
$host = parse_url($app['blog_url'], PHP_URL_HOST) ?: '';
$sample_clicks = 0;
if ($host) {
    $q = $conn->prepare("SELECT COUNT(*) as cnt FROM click_logs WHERE referer LIKE ? AND clicked_at > DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $like = '%' . $host . '%';
    $q->bind_param('s', $like);
    $q->execute();
    $r = $q->get_result()->fetch_assoc();
    $sample_clicks = (int)($r['cnt'] ?? 0);
    $q->close();
}

$page_title = 'Application #'.$app['id'];
include 'header.php';
?>

<?php include 'nav.php'; ?>

<div class="container">
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="card">
        <h2>Application Details</h2>
        <table>
            <tr><th>ID</th><td><?php echo $app['id']; ?></td></tr>
            <tr><th>Name</th><td><?php echo htmlspecialchars($app['name']); ?></td></tr>
            <tr><th>Email</th><td><?php echo htmlspecialchars($app['email']); ?></td></tr>
            <tr><th>Blog / URL</th><td><?php echo htmlspecialchars($app['blog_url']); ?></td></tr>
            <tr><th>Traffic estimate</th><td><?php echo htmlspecialchars($app['traffic_estimate']); ?></td></tr>
            <tr><th>Domain verification</th><td><?php echo htmlspecialchars($app['domain_verification_status']); ?></td></tr>
            <tr><th>IP Address</th><td><?php echo htmlspecialchars($app['ip_address']); ?></td></tr>
            <tr><th>Submitted</th><td><?php echo $app['created_at']; ?></td></tr>
            <tr><th>Sample clicks (30d)</th><td><?php echo $sample_clicks; ?></td></tr>
            <tr><th>Notes</th><td><?php echo nl2br(htmlspecialchars($app['notes'])); ?></td></tr>
        </table>

        <h3 style="margin-top:20px">Messages / Admin Log</h3>
        <?php if (count($messages) === 0): ?>
            <p class="muted">No messages yet.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($messages as $m): ?>
                    <li><strong><?php echo htmlspecialchars($m['message_type']); ?></strong> — <?php echo nl2br(htmlspecialchars($m['message_text'])); ?> <em style="color:#7f8c8d">(<?php echo $m['created_at']; ?>)</em></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <div style="display:flex;gap:12px;margin-top:18px">
            <form method="POST" style="flex:1">
                <input type="hidden" name="action" value="approve" />
                <textarea name="message" placeholder="Optional message to applicant (approve note)" style="width:100%;padding:8px"></textarea>
                <div style="margin-top:8px;display:flex;gap:10px">
                    <button class="btn btn-success" type="submit">Approve</button>
                </div>
            </form>

            <form method="POST" style="flex:1">
                <input type="hidden" name="action" value="request_info" />
                <textarea name="message" placeholder="Request information from applicant (required)" style="width:100%;padding:8px"></textarea>
                <div style="margin-top:8px;display:flex;gap:10px">
                    <button class="btn" type="submit">Request Info</button>
                </div>
            </form>

            <form method="POST" style="flex:1">
                <input type="hidden" name="action" value="reject" />
                <textarea name="message" placeholder="Reject reason (required)" style="width:100%;padding:8px"></textarea>
                <div style="margin-top:8px;display:flex;gap:10px">
                    <button class="btn btn-danger" type="submit">Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
