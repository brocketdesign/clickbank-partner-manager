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

<div class="container">
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">
            <?php
            if ($_GET['msg'] === 'added') echo 'Partner added successfully!';
            elseif ($_GET['msg'] === 'updated') echo 'Partner updated successfully!';
            elseif ($_GET['msg'] === 'deleted') echo 'Partner deleted successfully!';
            elseif ($_GET['msg'] === 'toggled') echo 'Partner status updated!';
            ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0;">Partners</h2>
            <a href="partner_edit.php" class="btn btn-success">+ Add Partner</a>
        </div>
        
        <?php if (count($partners) > 0): ?>
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
                            <td><?php echo $partner['id']; ?></td>
                            <td><code><?php echo htmlspecialchars($partner['aff_id']); ?></code></td>
                            <td><?php echo htmlspecialchars($partner['partner_name']); ?></td>
                            <td>
                                <?php if ($partner['is_active']): ?>
                                    <span class="badge badge-active">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-inactive">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('Y-m-d H:i', strtotime($partner['created_at'])); ?></td>
                            <td>
                                <div class="actions">
                                    <a href="partner_edit.php?id=<?php echo $partner['id']; ?>" class="btn btn-small">Edit</a>
                                    <a href="partners.php?toggle=<?php echo $partner['id']; ?>" 
                                       class="btn btn-small btn-success"
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
        <?php else: ?>
            <p style="color: #7f8c8d; text-align: center; padding: 40px 0;">
                No partners found. Add your first partner to get started!
            </p>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
