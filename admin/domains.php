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

<?php include 'nav.php'; ?>

<div class="container animate-fade-in">
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <?php
            if ($_GET['msg'] === 'added') echo 'Domain added successfully!';
            elseif ($_GET['msg'] === 'updated') echo 'Domain updated successfully!';
            elseif ($_GET['msg'] === 'deleted') echo 'Domain deleted successfully!';
            elseif ($_GET['msg'] === 'toggled') echo 'Domain status updated!';
            ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 8px; color: var(--primary);">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                </svg>
                Domains
            </h2>
            <a href="domain_edit.php" class="btn btn-success">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Add Domain
            </a>
        </div>
        
        <?php if (count($domains) > 0): ?>
            <div class="table-wrapper">
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
                                <td style="font-weight: 500;">#<?php echo $domain['id']; ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="var(--gray-400)" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                        </svg>
                                        <span style="font-weight: 500;"><?php echo htmlspecialchars($domain['domain_name']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($domain['is_active']): ?>
                                        <span class="badge badge-active">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-inactive">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-size: 13px; color: var(--gray-500);">
                                        <?php echo date('M d, Y', strtotime($domain['created_at'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a href="domain_edit.php?id=<?php echo $domain['id']; ?>" class="btn btn-small btn-outline">
                                            Edit
                                        </a>
                                        <a href="domains.php?toggle=<?php echo $domain['id']; ?>" 
                                           class="btn btn-small <?php echo $domain['is_active'] ? 'btn-warning' : 'btn-success'; ?>"
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
            </div>
        <?php else: ?>
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                </svg>
                <h3>No domains yet</h3>
                <p>Add your first domain to start tracking!</p>
                <a href="domain_edit.php" class="btn btn-success">Add Domain</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
