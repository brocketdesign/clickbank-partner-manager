<?php
require_once '../config.php';
requireLogin();

$conn = getDBConnection();

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM domains WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header('Location: domains.php?msg=deleted');
    exit;
}

// Handle toggle active
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $conn->prepare("UPDATE domains SET is_active = NOT is_active WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header('Location: domains.php?msg=toggled');
    exit;
}

// Get all domains
$result = $conn->query("SELECT * FROM domains ORDER BY created_at DESC");
$domains = [];
while ($row = $result->fetch_assoc()) {
    $domains[] = $row;
}

$page_title = 'Domains';
include 'header.php';
?>

<div class="nav">
    <div class="nav-content">
        <h1>ClickBank Partner Manager</h1>
        <div class="nav-links">
            <a href="index.php">Dashboard</a>
            <a href="domains.php" class="active">Domains</a>
            <a href="partners.php">Partners</a>
            <a href="offers.php">Offers</a>
            <a href="rules.php">Redirect Rules</a>
            <a href="clicks.php">Click Logs</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
</div>

<div class="container">
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">
            <?php
            if ($_GET['msg'] === 'added') echo 'Domain added successfully!';
            elseif ($_GET['msg'] === 'updated') echo 'Domain updated successfully!';
            elseif ($_GET['msg'] === 'deleted') echo 'Domain deleted successfully!';
            elseif ($_GET['msg'] === 'toggled') echo 'Domain status updated!';
            ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0;">Domains</h2>
            <a href="domain_edit.php" class="btn btn-success">+ Add Domain</a>
        </div>
        
        <?php if (count($domains) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Domain Name</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($domains as $domain): ?>
                        <tr>
                            <td><?php echo $domain['id']; ?></td>
                            <td><?php echo htmlspecialchars($domain['domain_name']); ?></td>
                            <td>
                                <?php if ($domain['is_active']): ?>
                                    <span class="badge badge-active">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-inactive">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('Y-m-d H:i', strtotime($domain['created_at'])); ?></td>
                            <td>
                                <div class="actions">
                                    <a href="domain_edit.php?id=<?php echo $domain['id']; ?>" class="btn btn-small">Edit</a>
                                    <a href="domains.php?toggle=<?php echo $domain['id']; ?>" 
                                       class="btn btn-small btn-success"
                                       onclick="return confirm('Toggle domain status?')">
                                        <?php echo $domain['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                    </a>
                                    <a href="domains.php?delete=<?php echo $domain['id']; ?>" 
                                       class="btn btn-small btn-danger"
                                       onclick="return confirm('Delete this domain? This will also delete associated redirect rules.')">
                                        Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="color: #7f8c8d; text-align: center; padding: 40px 0;">
                No domains found. Add your first domain to get started!
            </p>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
