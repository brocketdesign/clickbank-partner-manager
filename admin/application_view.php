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
            $body = "<p>Hi " . htmlspecialchars($app['name']) . ",</p>\n<p>Your application has been approved. Below is your snippet (use on your site):</p>\n<pre style=\"background:#f8f9fa;padding:10px;border-radius:4px\">{$snippet}</pre>\n<p>Keep your partner id safe. If you need any help, reply to this email.</p>";
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
            $body = "<p>Hi " . htmlspecialchars($app['name']) . ",</p>\n<p>We need more information to process your application:</p>\n<blockquote>" . nl2br(htmlspecialchars($message)) . "</blockquote>\n<p>Please reply with the requested details.</p>";
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
            $body = "<p>Hi " . htmlspecialchars($app['name']) . ",</p>\n<p>We are sorry to inform you that your application has been rejected for the following reason:</p>\n<blockquote>" . nl2br(htmlspecialchars($message)) . "</blockquote>\n<p>If you believe this is a mistake, you may reply to this email.</p>";
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
