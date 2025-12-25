<?php
require_once '../config.php';
requireLogin();

$conn = getDBConnection();

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM redirect_rules WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header('Location: rules.php?msg=deleted');
    exit;
}

// Handle toggle pause
if (isset($_GET['toggle_pause']) && is_numeric($_GET['toggle_pause'])) {
    $id = (int)$_GET['toggle_pause'];
    $stmt = $conn->prepare("UPDATE redirect_rules SET is_paused = NOT is_paused WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header('Location: rules.php?msg=toggled');
    exit;
}

// Get all rules with related data
$result = $conn->query("
    SELECT 
        rr.*,
        o.offer_name,
        d.domain_name,
        p.aff_id
    FROM redirect_rules rr
    LEFT JOIN offers o ON rr.offer_id = o.id
    LEFT JOIN domains d ON rr.domain_id = d.id
    LEFT JOIN partners p ON rr.partner_id = p.id
    ORDER BY rr.priority ASC, rr.created_at DESC
");

$rules = [];
while ($row = $result->fetch_assoc()) {
    $rules[] = $row;
}

$page_title = 'Redirect Rules';
include 'header.php';
?>

<?php include 'nav.php'; ?>

<div class="container">
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">
            <?php
            if ($_GET['msg'] === 'added') echo 'Rule added successfully!';
            elseif ($_GET['msg'] === 'updated') echo 'Rule updated successfully!';
            elseif ($_GET['msg'] === 'deleted') echo 'Rule deleted successfully!';
            elseif ($_GET['msg'] === 'toggled') echo 'Rule status updated!';
            ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0;">Redirect Rules</h2>
            <a href="rule_edit.php" class="btn btn-success">+ Add Rule</a>
        </div>
        
        <div style="background: #f8f9fa; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
            <strong>Rule Priority:</strong> Partner-specific → Domain-specific → Global (within each level, lower priority number = higher priority)
        </div>
        
        <?php if (count($rules) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Priority</th>
                        <th>Rule Name</th>
                        <th>Type</th>
                        <th>Scope</th>
                        <th>Offer</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rules as $rule): ?>
                        <tr>
                            <td><?php echo $rule['priority']; ?></td>
                            <td><?php echo htmlspecialchars($rule['rule_name']); ?></td>
                            <td>
                                <span class="badge" style="background: #e3f2fd; color: #1976d2;">
                                    <?php echo strtoupper($rule['rule_type']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($rule['rule_type'] === 'partner'): ?>
                                    Partner: <code><?php echo htmlspecialchars($rule['aff_id']); ?></code>
                                <?php elseif ($rule['rule_type'] === 'domain'): ?>
                                    Domain: <?php echo htmlspecialchars($rule['domain_name']); ?>
                                <?php else: ?>
                                    All Traffic
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($rule['offer_name']); ?></td>
                            <td>
                                <?php if ($rule['is_paused']): ?>
                                    <span class="badge badge-paused">Paused</span>
                                <?php else: ?>
                                    <span class="badge badge-active">Active</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <a href="rule_edit.php?id=<?php echo $rule['id']; ?>" class="btn btn-small">Edit</a>
                                    <a href="rules.php?toggle_pause=<?php echo $rule['id']; ?>" 
                                       class="btn btn-small <?php echo $rule['is_paused'] ? 'btn-success' : ''; ?>"
                                       onclick="return confirm('Toggle rule status?')">
                                        <?php echo $rule['is_paused'] ? 'Resume' : 'Pause'; ?>
                                    </a>
                                    <a href="rules.php?delete=<?php echo $rule['id']; ?>" 
                                       class="btn btn-small btn-danger"
                                       onclick="return confirm('Delete this rule?')">
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
                No redirect rules found. Add your first rule to start routing traffic!
            </p>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
