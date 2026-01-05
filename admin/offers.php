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

<?php include 'nav.php'; ?>

<div class="container animate-fade-in">
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <?php
            if ($_GET['msg'] === 'added') echo 'Offer added successfully!';
            elseif ($_GET['msg'] === 'updated') echo 'Offer updated successfully!';
            elseif ($_GET['msg'] === 'deleted') echo 'Offer deleted successfully!';
            elseif ($_GET['msg'] === 'toggled') echo 'Offer status updated!';
            ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 8px; color: var(--primary);">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
                ClickBank Offers
            </h2>
            <a href="offer_edit.php" class="btn btn-success">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Add Offer
            </a>
        </div>
        
        <?php if (count($offers) > 0): ?>
            <div class="table-wrapper">
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
                                <td style="font-weight: 500;">#<?php echo $offer['id']; ?></td>
                                <td>
                                    <div style="font-weight: 500;"><?php echo htmlspecialchars($offer['offer_name']); ?></div>
                                </td>
                                <td><code><?php echo htmlspecialchars($offer['clickbank_vendor']); ?></code></td>
                                <td style="max-width: 250px;">
                                    <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 12px; color: var(--gray-500);">
                                        <?php echo htmlspecialchars($offer['clickbank_hoplink']); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($offer['is_active']): ?>
                                        <span class="badge badge-active">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-inactive">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-size: 13px; color: var(--gray-500);">
                                        <?php echo date('M d, Y', strtotime($offer['created_at'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a href="offer_edit.php?id=<?php echo $offer['id']; ?>" class="btn btn-small btn-outline">
                                            Edit
                                        </a>
                                        <a href="offers.php?toggle=<?php echo $offer['id']; ?>" 
                                           class="btn btn-small <?php echo $offer['is_active'] ? 'btn-warning' : 'btn-success'; ?>"
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
            </div>
        <?php else: ?>
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
                <h3>No offers yet</h3>
                <p>Add your first ClickBank offer to get started!</p>
                <a href="offer_edit.php" class="btn btn-success">Add Offer</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
