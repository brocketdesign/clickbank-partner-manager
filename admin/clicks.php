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

<div class="container">
    <div class="card">
        <h2>Click Logs</h2>
        
        <!-- Filters -->
        <form method="GET" style="background: #f8f9fa; padding: 20px; border-radius: 4px; margin-bottom: 20px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <label style="display: block; margin-bottom: 5px; font-size: 14px;">Domain</label>
                    <select name="filter_domain" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">All Domains</option>
                        <?php foreach ($domains as $domain): ?>
                            <option value="<?php echo $domain['id']; ?>" 
                                    <?php echo $filter_domain == $domain['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($domain['domain_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 5px; font-size: 14px;">Partner</label>
                    <select name="filter_partner" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">All Partners</option>
                        <?php foreach ($partners as $partner): ?>
                            <option value="<?php echo $partner['id']; ?>" 
                                    <?php echo $filter_partner == $partner['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($partner['partner_name'] . ' (' . $partner['aff_id'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 5px; font-size: 14px;">Offer</label>
                    <select name="filter_offer" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">All Offers</option>
                        <?php foreach ($offers as $offer): ?>
                            <option value="<?php echo $offer['id']; ?>" 
                                    <?php echo $filter_offer == $offer['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($offer['offer_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 5px; font-size: 14px;">Date</label>
                    <input type="date" name="filter_date" value="<?php echo htmlspecialchars($filter_date); ?>" 
                           style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
            </div>
            
            <div style="margin-top: 15px; display: flex; gap: 10px;">
                <button type="submit" class="btn btn-small">Apply Filters</button>
                <a href="clicks.php" class="btn btn-small">Clear Filters</a>
            </div>
        </form>
        
        <div style="margin-bottom: 15px; color: #7f8c8d;">
            Showing <?php echo number_format($total_count); ?> total clicks
        </div>
        
        <?php if (count($clicks) > 0): ?>
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
                            <td><?php echo date('Y-m-d H:i:s', strtotime($click['clicked_at'])); ?></td>
                            <td><?php echo htmlspecialchars($click['domain_name'] ?? 'N/A'); ?></td>
                            <td>
                                <?php if ($click['aff_id']): ?>
                                    <div><?php echo htmlspecialchars($click['partner_name']); ?></div>
                                    <small style="color: #7f8c8d;">
                                        <code><?php echo htmlspecialchars($click['aff_id']); ?></code>
                                    </small>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($click['offer_name'] ?? 'N/A'); ?></td>
                            <td><small><?php echo htmlspecialchars($click['rule_name'] ?? 'N/A'); ?></small></td>
                            <td><?php echo htmlspecialchars($click['ip_address']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div style="margin-top: 20px; display: flex; justify-content: center; gap: 10px; align-items: center;">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&filter_domain=<?php echo $filter_domain; ?>&filter_partner=<?php echo $filter_partner; ?>&filter_offer=<?php echo $filter_offer; ?>&filter_date=<?php echo $filter_date; ?>" 
                           class="btn btn-small">« Previous</a>
                    <?php endif; ?>
                    
                    <span>Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&filter_domain=<?php echo $filter_domain; ?>&filter_partner=<?php echo $filter_partner; ?>&filter_offer=<?php echo $filter_offer; ?>&filter_date=<?php echo $filter_date; ?>" 
                           class="btn btn-small">Next »</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p style="color: #7f8c8d; text-align: center; padding: 40px 0;">
                No clicks found matching your filters.
            </p>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
