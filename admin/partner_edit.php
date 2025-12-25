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

<div class="container">
    <div class="card">
        <h2><?php echo $id > 0 ? 'Edit Partner' : 'Add New Partner'; ?></h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Affiliate ID *</label>
                <input type="text" name="aff_id" 
                       value="<?php echo htmlspecialchars($partner['aff_id'] ?? ''); ?>" 
                       placeholder="partner123" required>
            </div>
            
            <div class="form-group">
                <label>Partner Name *</label>
                <input type="text" name="partner_name" 
                       value="<?php echo htmlspecialchars($partner['partner_name'] ?? ''); ?>" 
                       placeholder="Partner Company Name" required>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_active" 
                           <?php echo (!$partner || $partner['is_active']) ? 'checked' : ''; ?>>
                    Active
                </label>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-success">Save Partner</button>
                <a href="partners.php" class="btn">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>
