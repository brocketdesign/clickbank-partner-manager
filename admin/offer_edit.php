<?php
require_once '../config.php';
requireLogin();

$conn = getDBConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$offer = null;
$error = '';

// Load existing offer
if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM offers WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $offer = $result->fetch_assoc();
    $stmt->close();
    
    if (!$offer) {
        header('Location: offers.php');
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $offer_name = sanitize($_POST['offer_name'] ?? '');
    $clickbank_vendor = sanitize($_POST['clickbank_vendor'] ?? '');
    $clickbank_hoplink = sanitize($_POST['clickbank_hoplink'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($offer_name)) {
        $error = 'Offer name is required';
    } elseif (empty($clickbank_vendor)) {
        $error = 'ClickBank vendor is required';
    } elseif (empty($clickbank_hoplink)) {
        $error = 'ClickBank hoplink is required';
    } else {
        if ($id > 0) {
            // Update
            $stmt = $conn->prepare("UPDATE offers SET offer_name = ?, clickbank_vendor = ?, clickbank_hoplink = ?, is_active = ? WHERE id = ?");
            $stmt->bind_param("sssii", $offer_name, $clickbank_vendor, $clickbank_hoplink, $is_active, $id);
            $stmt->execute();
            $stmt->close();
            header('Location: offers.php?msg=updated');
            exit;
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO offers (offer_name, clickbank_vendor, clickbank_hoplink, is_active) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $offer_name, $clickbank_vendor, $clickbank_hoplink, $is_active);
            $stmt->execute();
            $stmt->close();
            header('Location: offers.php?msg=added');
            exit;
        }
    }
}

$page_title = $id > 0 ? 'Edit Offer' : 'Add Offer';
include 'header.php';
?>

<?php include 'nav.php'; ?>

<div class="container animate-fade-in">
    <div style="max-width: 700px;">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 8px; color: var(--primary);">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                    <?php echo $id > 0 ? 'Edit Offer' : 'Add New Offer'; ?>
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
                    <label for="offer_name">Offer Name *</label>
                    <input type="text" id="offer_name" name="offer_name" 
                           value="<?php echo htmlspecialchars($offer['offer_name'] ?? ''); ?>" 
                           placeholder="e.g., Weight Loss Guide" required>
                    <small>A descriptive name for this offer</small>
                </div>
                
                <div class="form-group">
                    <label for="clickbank_vendor">ClickBank Vendor *</label>
                    <input type="text" id="clickbank_vendor" name="clickbank_vendor" 
                           value="<?php echo htmlspecialchars($offer['clickbank_vendor'] ?? ''); ?>" 
                           placeholder="e.g., vendorname" required>
                    <small>The ClickBank vendor/nickname for this product</small>
                </div>
                
                <div class="form-group">
                    <label for="clickbank_hoplink">ClickBank Hoplink *</label>
                    <input type="url" id="clickbank_hoplink" name="clickbank_hoplink" 
                           value="<?php echo htmlspecialchars($offer['clickbank_hoplink'] ?? ''); ?>" 
                           placeholder="https://vendor.hop.clickbank.net" required>
                    <small>The full ClickBank hoplink URL (affiliate ID will be appended)</small>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" name="is_active" 
                               style="width: 18px; height: 18px; accent-color: var(--primary);"
                               <?php echo (!$offer || $offer['is_active']) ? 'checked' : ''; ?>>
                        <span>Active</span>
                    </label>
                    <small>Inactive offers won't be used in redirect rules</small>
                </div>
                
                <div style="display: flex; gap: 12px; padding-top: 16px; border-top: 1px solid var(--gray-100); margin-top: 8px;">
                    <button type="submit" class="btn btn-success">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        Save Offer
                    </button>
                    <a href="offers.php" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
