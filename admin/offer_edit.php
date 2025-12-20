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
    $clickbank_hoplink = trim($_POST['clickbank_hoplink'] ?? '');
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
    <div class="card">
        <h2><?php echo $id > 0 ? 'Edit Offer' : 'Add New Offer'; ?></h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Offer Name *</label>
                <input type="text" name="offer_name" 
                       value="<?php echo htmlspecialchars($offer['offer_name'] ?? ''); ?>" 
                       placeholder="My ClickBank Product" required>
            </div>
            
            <div class="form-group">
                <label>ClickBank Vendor *</label>
                <input type="text" name="clickbank_vendor" 
                       value="<?php echo htmlspecialchars($offer['clickbank_vendor'] ?? ''); ?>" 
                       placeholder="vendorname" required>
            </div>
            
            <div class="form-group">
                <label>ClickBank Hoplink URL *</label>
                <input type="url" name="clickbank_hoplink" 
                       value="<?php echo htmlspecialchars($offer['clickbank_hoplink'] ?? ''); ?>" 
                       placeholder="https://hop.clickbank.net/?affiliate=YOUR_ID&vendor=vendorname" required>
                <small style="color: #7f8c8d; display: block; margin-top: 5px;">
                    Full ClickBank hoplink URL. The system will automatically append tracking parameters.
                </small>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_active" 
                           <?php echo (!$offer || $offer['is_active']) ? 'checked' : ''; ?>>
                    Active
                </label>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-success">Save Offer</button>
                <a href="offers.php" class="btn">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>
