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

<div class="container animate-fade-in">
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <?php
            if ($_GET['msg'] === 'added') echo 'Rule added successfully!';
            elseif ($_GET['msg'] === 'updated') echo 'Rule updated successfully!';
            elseif ($_GET['msg'] === 'deleted') echo 'Rule deleted successfully!';
            elseif ($_GET['msg'] === 'toggled') echo 'Rule status updated!';
            ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 8px; color: var(--primary);">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                Redirect Rules
            </h2>
            <a href="rule_edit.php" class="btn btn-success">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Add Rule
            </a>
        </div>
        
        <div class="alert alert-info" style="margin-bottom: 24px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
                <strong>Rule Priority:</strong> Partner-specific → Domain-specific → Global (within each level, lower priority number = higher priority)
            </div>
        </div>
        
        <?php if (count($rules) > 0): ?>
            <div class="table-wrapper">
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
                                <td>
                                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; background: var(--gray-100); border-radius: 6px; font-weight: 600; font-size: 13px;">
                                        <?php echo $rule['priority']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="font-weight: 500;"><?php echo htmlspecialchars($rule['rule_name']); ?></div>
                                </td>
                                <td>
                                    <?php
                                    $type_colors = [
                                        'partner' => 'background: #dbeafe; color: #1e40af;',
                                        'domain' => 'background: #fce7f3; color: #9d174d;',
                                        'global' => 'background: #f3e8ff; color: #7c3aed;'
                                    ];
                                    $type_style = $type_colors[$rule['rule_type']] ?? 'background: var(--gray-100); color: var(--gray-600);';
                                    ?>
                                    <span class="badge" style="<?php echo $type_style; ?>">
                                        <?php echo strtoupper($rule['rule_type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($rule['rule_type'] === 'partner'): ?>
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="var(--gray-400)" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            <code><?php echo htmlspecialchars($rule['aff_id']); ?></code>
                                        </div>
                                    <?php elseif ($rule['rule_type'] === 'domain'): ?>
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="var(--gray-400)" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                            </svg>
                                            <?php echo htmlspecialchars($rule['domain_name']); ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: var(--gray-500);">All Traffic</span>
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
                                        <a href="rule_edit.php?id=<?php echo $rule['id']; ?>" class="btn btn-small btn-outline">
                                            Edit
                                        </a>
                                        <a href="rules.php?toggle_pause=<?php echo $rule['id']; ?>" 
                                           class="btn btn-small <?php echo $rule['is_paused'] ? 'btn-success' : 'btn-warning'; ?>"
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
            </div>
        <?php else: ?>
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <h3>No redirect rules yet</h3>
                <p>Create your first rule to start routing traffic!</p>
                <a href="rule_edit.php" class="btn btn-success">Add Rule</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
