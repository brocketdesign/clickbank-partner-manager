<?php
require_once '../config.php';
requireLogin();

$conn = getDBConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$partner = null;
$error = '';

// Load existing partner
if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM partners WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $partner = $result->fetch_assoc();
    $stmt->close();
    
    if (!$partner) {
        header('Location: partners.php');
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aff_id = sanitize($_POST['aff_id'] ?? '');
    $partner_name = sanitize($_POST['partner_name'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($aff_id)) {
        $error = 'Affiliate ID is required';
    } elseif (empty($partner_name)) {
        $error = 'Partner name is required';
    } else {
        if ($id > 0) {
            // Update
            $stmt = $conn->prepare("UPDATE partners SET aff_id = ?, partner_name = ?, is_active = ? WHERE id = ?");
            $stmt->bind_param("ssii", $aff_id, $partner_name, $is_active, $id);
            $stmt->execute();
            $stmt->close();
            header('Location: partners.php?msg=updated');
            exit;
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO partners (aff_id, partner_name, is_active) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $aff_id, $partner_name, $is_active);
            if ($stmt->execute()) {
                $stmt->close();
                header('Location: partners.php?msg=added');
                exit;
            } else {
                $error = 'Affiliate ID already exists';
                $stmt->close();
            }
        }
    }
}

$page_title = $id > 0 ? 'Edit Partner' : 'Add Partner';
include 'header.php';
?>

<?php include 'nav.php'; ?>

<div class="container animate-fade-in">
    <div style="max-width: 600px;">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 8px; color: var(--primary);">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <?php echo $id > 0 ? 'Edit Partner' : 'Add New Partner'; ?>
                </h2>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="aff_id">Affiliate ID *</label>
                    <input type="text" id="aff_id" name="aff_id" 
                           value="<?php echo htmlspecialchars($partner['aff_id'] ?? ''); ?>" 
                           placeholder="e.g., partner123" required>
                    <small>This is the unique identifier used in redirect links</small>
                </div>
                
                <div class="form-group">
                    <label for="partner_name">Partner Name *</label>
                    <input type="text" id="partner_name" name="partner_name" 
                           value="<?php echo htmlspecialchars($partner['partner_name'] ?? ''); ?>" 
                           placeholder="e.g., Acme Marketing Inc." required>
                    <small>Display name for this partner</small>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" name="is_active" 
                               style="width: 18px; height: 18px; accent-color: var(--primary);"
                               <?php echo (!$partner || $partner['is_active']) ? 'checked' : ''; ?>>
                        <span>Active</span>
                    </label>
                    <small>Inactive partners won't receive redirected traffic</small>
                </div>
                
                <div style="display: flex; gap: 12px; padding-top: 16px; border-top: 1px solid var(--gray-100); margin-top: 8px;">
                    <button type="submit" class="btn btn-success">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        Save Partner
                    </button>
                    <a href="partners.php" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
