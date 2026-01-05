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

<div class="container animate-fade-in">
    <!-- Welcome Message -->
    <div class="page-header">
        <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>! 👋</h2>
        <p>Here's what's happening with your affiliate network today.</p>
    </div>
    
    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Clicks</h3>
            <div class="stat-value"><?php echo number_format($stats['total_clicks']); ?></div>
            <div class="stat-change">All time</div>
        </div>
        <div class="stat-card">
            <h3>Clicks Today</h3>
            <div class="stat-value"><?php echo number_format($stats['clicks_today']); ?></div>
            <div class="stat-change">Today's activity</div>
        </div>
        <div class="stat-card">
            <h3>Active Domains</h3>
            <div class="stat-value"><?php echo number_format($stats['active_domains']); ?></div>
            <div class="stat-change">Currently tracking</div>
        </div>
        <div class="stat-card">
            <h3>Active Partners</h3>
            <div class="stat-value"><?php echo number_format($stats['active_partners']); ?></div>
            <div class="stat-change">In network</div>
        </div>
        <div class="stat-card">
            <h3>Active Offers</h3>
            <div class="stat-value"><?php echo number_format($stats['active_offers']); ?></div>
            <div class="stat-change">Running campaigns</div>
        </div>
        <div class="stat-card">
            <h3>Active Rules</h3>
            <div class="stat-value"><?php echo number_format($stats['active_rules']); ?></div>
            <div class="stat-change">Redirect rules</div>
        </div>
    </div>
    
    <!-- Click Trends Chart -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 8px; color: var(--primary);">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Click Trends
            </h2>
            <span style="font-size: 13px; color: var(--gray-500);">Last 7 days</span>
        </div>
        <div class="chart-container">
            <?php if (count($trends) > 0): ?>
                <?php 
                $max_count = max(array_column($trends, 'count'));
                $max_count = $max_count > 0 ? $max_count : 1;
                ?>
                <?php foreach ($trends as $trend): ?>
                    <?php $height = ($trend['count'] / $max_count) * 150; ?>
                    <div class="chart-bar">
                        <div class="chart-bar-fill" style="height: <?php echo max($height, 4); ?>px;"></div>
                        <div class="chart-bar-label">
                            <?php echo date('m/d', strtotime($trend['date'])); ?>
                        </div>
                        <div class="chart-bar-value">
                            <?php echo number_format($trend['count']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state" style="width: 100%;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <h3>No data yet</h3>
                    <p>Click data will appear here once tracking begins</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Recent Clicks Table -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 8px; color: var(--primary);">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Recent Clicks
            </h2>
            <a href="clicks.php" class="btn btn-outline btn-small">View All</a>
        </div>
        <?php if (count($recent_clicks) > 0): ?>
            <div class="table-wrapper">
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
                                <td>
                                    <div style="font-weight: 500;"><?php echo date('H:i:s', strtotime($click['clicked_at'])); ?></div>
                                    <div style="font-size: 12px; color: var(--gray-500);"><?php echo date('Y-m-d', strtotime($click['clicked_at'])); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($click['domain_name'] ?? 'N/A'); ?></td>
                                <td><code><?php echo htmlspecialchars($click['aff_id'] ?? 'N/A'); ?></code></td>
                                <td><?php echo htmlspecialchars($click['offer_name'] ?? 'N/A'); ?></td>
                                <td style="font-family: monospace; font-size: 13px; color: var(--gray-500);"><?php echo htmlspecialchars($click['ip_address']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                </svg>
                <h3>No clicks recorded yet</h3>
                <p>Clicks will appear here once your redirect rules are active</p>
                <a href="rules.php" class="btn">Set Up Rules</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
