<?php
require_once '../config.php';
requireLogin();

$conn = getDBConnection();

// Get statistics
$stats = [];

// Total clicks
$result = $conn->query("SELECT COUNT(*) as count FROM click_logs");
$stats['total_clicks'] = $result->fetch_assoc()['count'];

// Clicks today
$result = $conn->query("SELECT COUNT(*) as count FROM click_logs WHERE DATE(clicked_at) = CURDATE()");
$stats['clicks_today'] = $result->fetch_assoc()['count'];

// Active domains
$result = $conn->query("SELECT COUNT(*) as count FROM domains WHERE is_active = 1");
$stats['active_domains'] = $result->fetch_assoc()['count'];

// Active partners
$result = $conn->query("SELECT COUNT(*) as count FROM partners WHERE is_active = 1");
$stats['active_partners'] = $result->fetch_assoc()['count'];

// Active offers
$result = $conn->query("SELECT COUNT(*) as count FROM offers WHERE is_active = 1");
$stats['active_offers'] = $result->fetch_assoc()['count'];

// Active rules
$result = $conn->query("SELECT COUNT(*) as count FROM redirect_rules WHERE is_paused = 0");
$stats['active_rules'] = $result->fetch_assoc()['count'];

// Recent clicks
$recent_clicks = [];
$result = $conn->query("
    SELECT 
        cl.id,
        cl.clicked_at,
        d.domain_name,
        p.aff_id,
        o.offer_name,
        cl.ip_address
    FROM click_logs cl
    LEFT JOIN domains d ON cl.domain_id = d.id
    LEFT JOIN partners p ON cl.partner_id = p.id
    LEFT JOIN offers o ON cl.offer_id = o.id
    ORDER BY cl.clicked_at DESC
    LIMIT 10
");

while ($row = $result->fetch_assoc()) {
    $recent_clicks[] = $row;
}

// Click trends (last 7 days)
$trends = [];
$result = $conn->query("
    SELECT 
        DATE(clicked_at) as date,
        COUNT(*) as count
    FROM click_logs
    WHERE clicked_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(clicked_at)
    ORDER BY date ASC
");

while ($row = $result->fetch_assoc()) {
    $trends[] = $row;
}

$page_title = 'Dashboard';
include 'header.php';
?>

<?php include 'nav.php'; ?>

<div class="container">
    <h2 style="margin-bottom: 20px;">Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</h2>
    
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Clicks</h3>
            <div class="stat-value"><?php echo number_format($stats['total_clicks']); ?></div>
        </div>
        <div class="stat-card">
            <h3>Clicks Today</h3>
            <div class="stat-value"><?php echo number_format($stats['clicks_today']); ?></div>
        </div>
        <div class="stat-card">
            <h3>Active Domains</h3>
            <div class="stat-value"><?php echo number_format($stats['active_domains']); ?></div>
        </div>
        <div class="stat-card">
            <h3>Active Partners</h3>
            <div class="stat-value"><?php echo number_format($stats['active_partners']); ?></div>
        </div>
        <div class="stat-card">
            <h3>Active Offers</h3>
            <div class="stat-value"><?php echo number_format($stats['active_offers']); ?></div>
        </div>
        <div class="stat-card">
            <h3>Active Rules</h3>
            <div class="stat-value"><?php echo number_format($stats['active_rules']); ?></div>
        </div>
    </div>
    
    <div class="card">
        <h2>Click Trends (Last 7 Days)</h2>
        <div style="height: 200px; display: flex; align-items: flex-end; gap: 10px; padding: 20px 0;">
            <?php if (count($trends) > 0): ?>
                <?php 
                $max_count = max(array_column($trends, 'count'));
                $max_count = $max_count > 0 ? $max_count : 1;
                ?>
                <?php foreach ($trends as $trend): ?>
                    <?php $height = ($trend['count'] / $max_count) * 150; ?>
                    <div style="flex: 1; display: flex; flex-direction: column; align-items: center;">
                        <div style="background: #3498db; width: 100%; height: <?php echo $height; ?>px; border-radius: 4px 4px 0 0;"></div>
                        <div style="margin-top: 10px; font-size: 12px; color: #7f8c8d;">
                            <?php echo date('m/d', strtotime($trend['date'])); ?>
                        </div>
                        <div style="font-size: 14px; font-weight: 600; color: #2c3e50;">
                            <?php echo $trend['count']; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: #7f8c8d;">No click data available</p>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card">
        <h2>Recent Clicks</h2>
        <?php if (count($recent_clicks) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Domain</th>
                        <th>Partner ID</th>
                        <th>Offer</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_clicks as $click): ?>
                        <tr>
                            <td><?php echo date('Y-m-d H:i:s', strtotime($click['clicked_at'])); ?></td>
                            <td><?php echo htmlspecialchars($click['domain_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($click['aff_id'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($click['offer_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($click['ip_address']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="color: #7f8c8d;">No clicks recorded yet</p>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
