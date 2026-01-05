<?php
require_once '../config.php';
requireLogin();

$conn = getDBConnection();

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM partners WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header('Location: partners.php?msg=deleted');
    exit;
}

// Handle toggle active
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $conn->prepare("UPDATE partners SET is_active = NOT is_active WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header('Location: partners.php?msg=toggled');
    exit;
}

// Get all partners
$result = $conn->query("SELECT * FROM partners ORDER BY created_at DESC");
$partners = [];
while ($row = $result->fetch_assoc()) {
    $partners[] = $row;
}

$page_title = 'Partners';
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
            if ($_GET['msg'] === 'added') echo 'Partner added successfully!';
            elseif ($_GET['msg'] === 'updated') echo 'Partner updated successfully!';
            elseif ($_GET['msg'] === 'deleted') echo 'Partner deleted successfully!';
            elseif ($_GET['msg'] === 'toggled') echo 'Partner status updated!';
            ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 8px; color: var(--primary);">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Partners
            </h2>
            <a href="partner_edit.php" class="btn btn-success">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Add Partner
            </a>
        </div>
        
        <?php if (count($partners) > 0): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Affiliate ID</th>
                            <th>Partner Name</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($partners as $partner): ?>
                            <tr>
                                <td style="font-weight: 500;">#<?php echo $partner['id']; ?></td>
                                <td><code><?php echo htmlspecialchars($partner['aff_id']); ?></code></td>
                                <td><?php echo htmlspecialchars($partner['partner_name']); ?></td>
                                <td>
                                    <?php if ($partner['is_active']): ?>
                                        <span class="badge badge-active">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-inactive">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-size: 13px; color: var(--gray-500);">
                                        <?php echo date('M d, Y', strtotime($partner['created_at'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a href="partner_edit.php?id=<?php echo $partner['id']; ?>" class="btn btn-small btn-outline">
                                            Edit
                                        </a>
                                        <a href="partners.php?toggle=<?php echo $partner['id']; ?>" 
                                           class="btn btn-small <?php echo $partner['is_active'] ? 'btn-warning' : 'btn-success'; ?>"
                                           onclick="return confirm('Toggle partner status?')">
                                            <?php echo $partner['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                        </a>
                                        <a href="partners.php?delete=<?php echo $partner['id']; ?>" 
                                           class="btn btn-small btn-danger"
                                           onclick="return confirm('Delete this partner? This will also delete associated redirect rules.')">
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <h3>No partners yet</h3>
                <p>Add your first partner to get started!</p>
                <a href="partner_edit.php" class="btn btn-success">Add Partner</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
