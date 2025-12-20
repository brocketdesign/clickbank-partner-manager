<?php
require_once '../config.php';
requireLogin();

$conn = getDBConnection();

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM offers WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header('Location: offers.php?msg=deleted');
    exit;
}

// Handle toggle active
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $conn->prepare("UPDATE offers SET is_active = NOT is_active WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header('Location: offers.php?msg=toggled');
    exit;
}

// Get all offers
$result = $conn->query("SELECT * FROM offers ORDER BY created_at DESC");
$offers = [];
while ($row = $result->fetch_assoc()) {
    $offers[] = $row;
}

$page_title = 'Offers';
include 'header.php';
?>

<div class="nav">
    <div class="nav-content">
        <h1>ClickBank Partner Manager</h1>
        <div class="nav-links">
            <a href="index.php">Dashboard</a>
            <a href="domains.php">Domains</a>
            <a href="partners.php">Partners</a>
            <a href="offers.php" class="active">Offers</a>
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
            if ($_GET['msg'] === 'added') echo 'Offer added successfully!';
            elseif ($_GET['msg'] === 'updated') echo 'Offer updated successfully!';
            elseif ($_GET['msg'] === 'deleted') echo 'Offer deleted successfully!';
            elseif ($_GET['msg'] === 'toggled') echo 'Offer status updated!';
            ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0;">ClickBank Offers</h2>
            <a href="offer_edit.php" class="btn btn-success">+ Add Offer</a>
        </div>
        
        <?php if (count($offers) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Offer Name</th>
                        <th>Vendor</th>
                        <th>Hoplink</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($offers as $offer): ?>
                        <tr>
                            <td><?php echo $offer['id']; ?></td>
                            <td><?php echo htmlspecialchars($offer['offer_name']); ?></td>
                            <td><?php echo htmlspecialchars($offer['clickbank_vendor']); ?></td>
                            <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <small><?php echo htmlspecialchars($offer['clickbank_hoplink']); ?></small>
                            </td>
                            <td>
                                <?php if ($offer['is_active']): ?>
                                    <span class="badge badge-active">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-inactive">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('Y-m-d H:i', strtotime($offer['created_at'])); ?></td>
                            <td>
                                <div class="actions">
                                    <a href="offer_edit.php?id=<?php echo $offer['id']; ?>" class="btn btn-small">Edit</a>
                                    <a href="offers.php?toggle=<?php echo $offer['id']; ?>" 
                                       class="btn btn-small btn-success"
                                       onclick="return confirm('Toggle offer status?')">
                                        <?php echo $offer['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                    </a>
                                    <a href="offers.php?delete=<?php echo $offer['id']; ?>" 
                                       class="btn btn-small btn-danger"
                                       onclick="return confirm('Delete this offer? This will also delete associated redirect rules.')">
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
                No offers found. Add your first ClickBank offer to get started!
            </p>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
