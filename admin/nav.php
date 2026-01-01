<?php
// Reusable admin navigation

// Get pending applications count for badge
$pending_count = 0;
if (isset($conn)) {
    $pending_result = $conn->query("SELECT COUNT(*) as cnt FROM partner_applications WHERE status = 'pending'");
    if ($pending_result) {
        $pending_row = $pending_result->fetch_assoc();
        $pending_count = (int)$pending_row['cnt'];
    }
}
?>
<div class="nav">
    <div class="nav-content">
        <h1><img src="/favicon_io/logo.png" alt="AdeasyNow" style="height:26px;margin-right:10px;border-radius:6px;vertical-align:middle"> ClickBank Partner Manager</h1>
        <div class="nav-links">
            <a href="index.php">Dashboard</a>
            <a href="applications.php">Applications<?php if ($pending_count > 0): ?> <span style="background:#e74c3c;color:#fff;font-size:11px;padding:2px 6px;border-radius:10px;margin-left:4px;"><?php echo $pending_count; ?></span><?php endif; ?></a>
            <a href="domains.php">Domains</a>
            <a href="partners.php">Partners</a>
            <a href="offers.php">Offers</a>
            <a href="rules.php">Redirect Rules</a>
            <a href="clicks.php">Click Logs</a>
            <a href="email_test.php">Email Test</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
</div>