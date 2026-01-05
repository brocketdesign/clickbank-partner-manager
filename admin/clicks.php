<?php
require_once '../config.php';
requireLogin();

$conn = getDBConnection();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

// Filters
$filter_domain = $_GET['filter_domain'] ?? '';
$filter_partner = $_GET['filter_partner'] ?? '';
$filter_offer = $_GET['filter_offer'] ?? '';
$filter_date = $_GET['filter_date'] ?? '';

// Build WHERE clause
$where_clauses = [];
$params = [];
$types = '';

if (!empty($filter_domain)) {
    $where_clauses[] = "d.id = ?";
    $params[] = (int)$filter_domain;
    $types .= 'i';
}

if (!empty($filter_partner)) {
    $where_clauses[] = "p.id = ?";
    $params[] = (int)$filter_partner;
    $types .= 'i';
}

if (!empty($filter_offer)) {
    $where_clauses[] = "o.id = ?";
    $params[] = (int)$filter_offer;
    $types .= 'i';
}

if (!empty($filter_date)) {
    $where_clauses[] = "DATE(cl.clicked_at) = ?";
    $params[] = $filter_date;
    $types .= 's';
}

$where_sql = '';
if (count($where_clauses) > 0) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
}

// Get total count
$count_sql = "
    SELECT COUNT(*) as total
    FROM click_logs cl
    LEFT JOIN domains d ON cl.domain_id = d.id
    LEFT JOIN partners p ON cl.partner_id = p.id
    LEFT JOIN offers o ON cl.offer_id = o.id
    $where_sql
";

if ($types) {
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $total_count = $count_stmt->get_result()->fetch_assoc()['total'];
    $count_stmt->close();
} else {
    $total_count = $conn->query($count_sql)->fetch_assoc()['total'];
}

$total_pages = ceil($total_count / $per_page);

// Get clicks
$sql = "
    SELECT 
        cl.id,
        cl.clicked_at,
        cl.ip_address,
        cl.redirect_url,
        d.domain_name,
        p.aff_id,
        p.partner_name,
        o.offer_name,
        rr.rule_name
    FROM click_logs cl
    LEFT JOIN domains d ON cl.domain_id = d.id
    LEFT JOIN partners p ON cl.partner_id = p.id
    LEFT JOIN offers o ON cl.offer_id = o.id
    LEFT JOIN redirect_rules rr ON cl.rule_id = rr.id
    $where_sql
    ORDER BY cl.clicked_at DESC
    LIMIT ? OFFSET ?
";

$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$clicks = [];
while ($row = $result->fetch_assoc()) {
    $clicks[] = $row;
}
$stmt->close();

// Get filter options
$domains = [];
$result = $conn->query("SELECT id, domain_name FROM domains ORDER BY domain_name");
while ($row = $result->fetch_assoc()) {
    $domains[] = $row;
}

$partners = [];
$result = $conn->query("SELECT id, aff_id, partner_name FROM partners ORDER BY partner_name");
while ($row = $result->fetch_assoc()) {
    $partners[] = $row;
}

$offers = [];
$result = $conn->query("SELECT id, offer_name FROM offers ORDER BY offer_name");
while ($row = $result->fetch_assoc()) {
    $offers[] = $row;
}

$page_title = 'Click Logs';
include 'header.php';
?>

<?php include 'nav.php'; ?>

<div class="container animate-fade-in">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 8px; color: var(--primary);">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                </svg>
                Click Logs
            </h2>
            <span style="background: var(--gray-100); padding: 6px 14px; border-radius: 50px; font-size: 13px; font-weight: 500; color: var(--gray-600);">
                <?php echo number_format($total_count); ?> total clicks
            </span>
        </div>
        
        <!-- Filters -->
        <form method="GET" style="background: var(--gray-50); padding: 20px; border-radius: var(--border-radius); margin-bottom: 24px; border: 1px solid var(--gray-100);">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 16px;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label style="font-size: 13px;">Domain</label>
                    <select name="filter_domain" style="padding: 10px 12px;">
                        <option value="">All Domains</option>
                        <?php foreach ($domains as $domain): ?>
                            <option value="<?php echo $domain['id']; ?>" 
                                    <?php echo $filter_domain == $domain['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($domain['domain_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label style="font-size: 13px;">Partner</label>
                    <select name="filter_partner" style="padding: 10px 12px;">
                        <option value="">All Partners</option>
                        <?php foreach ($partners as $partner): ?>
                            <option value="<?php echo $partner['id']; ?>"
                                    <?php echo $filter_partner == $partner['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($partner['partner_name'] ?: $partner['aff_id']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label style="font-size: 13px;">Offer</label>
                    <select name="filter_offer" style="padding: 10px 12px;">
                        <option value="">All Offers</option>
                        <?php foreach ($offers as $offer): ?>
                            <option value="<?php echo $offer['id']; ?>"
                                    <?php echo $filter_offer == $offer['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($offer['offer_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label style="font-size: 13px;">Date</label>
                    <input type="date" name="filter_date" value="<?php echo htmlspecialchars($filter_date); ?>" style="padding: 10px 12px;">
                </div>
            </div>
            
            <div style="display: flex; gap: 12px;">
                <button type="submit" class="btn btn-small">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Apply Filters
                </button>
                <a href="clicks.php" class="btn btn-outline btn-small">Clear</a>
            </div>
        </form>
        
        <?php if (count($clicks) > 0): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Domain</th>
                            <th>Partner</th>
                            <th>Offer</th>
                            <th>Rule</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clicks as $click): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 500;"><?php echo date('H:i:s', strtotime($click['clicked_at'])); ?></div>
                                    <div style="font-size: 12px; color: var(--gray-500);"><?php echo date('M d, Y', strtotime($click['clicked_at'])); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($click['domain_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if ($click['aff_id']): ?>
                                        <code><?php echo htmlspecialchars($click['aff_id']); ?></code>
                                        <?php if ($click['partner_name']): ?>
                                            <div style="font-size: 12px; color: var(--gray-500);"><?php echo htmlspecialchars($click['partner_name']); ?></div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: var(--gray-400);">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($click['offer_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if ($click['rule_name']): ?>
                                        <span style="font-size: 13px;"><?php echo htmlspecialchars($click['rule_name']); ?></span>
                                    <?php else: ?>
                                        <span style="color: var(--gray-400);">—</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-family: monospace; font-size: 13px; color: var(--gray-500);">
                                    <?php echo htmlspecialchars($click['ip_address']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div style="display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; padding-top: 24px; border-top: 1px solid var(--gray-100);">
                    <?php
                    $query_params = $_GET;
                    unset($query_params['page']);
                    $base_url = 'clicks.php?' . http_build_query($query_params);
                    ?>
                    
                    <?php if ($page > 1): ?>
                        <a href="<?php echo $base_url . '&page=' . ($page - 1); ?>" class="btn btn-outline btn-small">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                            Previous
                        </a>
                    <?php endif; ?>
                    
                    <span style="padding: 8px 16px; background: var(--gray-100); border-radius: 8px; font-size: 14px; font-weight: 500;">
                        Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                    </span>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="<?php echo $base_url . '&page=' . ($page + 1); ?>" class="btn btn-outline btn-small">
                            Next
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                </svg>
                <h3>No clicks found</h3>
                <p><?php echo (count($where_clauses) > 0) ? 'Try adjusting your filters.' : 'Clicks will appear here once tracking begins.'; ?></p>
                <?php if (count($where_clauses) > 0): ?>
                    <a href="clicks.php" class="btn btn-outline">Clear Filters</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
