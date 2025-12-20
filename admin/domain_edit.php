<?php
require_once '../config.php';
requireLogin();

$conn = getDBConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$domain = null;
$error = '';

// Load existing domain
if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM domains WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $domain = $result->fetch_assoc();
    $stmt->close();
    
    if (!$domain) {
        header('Location: domains.php');
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $domain_name = sanitize($_POST['domain_name'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($domain_name)) {
        $error = 'Domain name is required';
    } else {
        if ($id > 0) {
            // Update
            $stmt = $conn->prepare("UPDATE domains SET domain_name = ?, is_active = ? WHERE id = ?");
            $stmt->bind_param("sii", $domain_name, $is_active, $id);
            $stmt->execute();
            $stmt->close();
            header('Location: domains.php?msg=updated');
            exit;
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO domains (domain_name, is_active) VALUES (?, ?)");
            $stmt->bind_param("si", $domain_name, $is_active);
            if ($stmt->execute()) {
                $stmt->close();
                header('Location: domains.php?msg=added');
                exit;
            } else {
                $error = 'Domain name already exists';
                $stmt->close();
            }
        }
    }
}

$page_title = $id > 0 ? 'Edit Domain' : 'Add Domain';
include 'header.php';
?>

<div class="nav">
    <div class="nav-content">
        <h1>ClickBank Partner Manager</h1>
        <div class="nav-links">
            <a href="index.php">Dashboard</a>
            <a href="domains.php" class="active">Domains</a>
            <a href="partners.php">Partners</a>
            <a href="offers.php">Offers</a>
            <a href="rules.php">Redirect Rules</a>
            <a href="clicks.php">Click Logs</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
</div>

<div class="container">
    <div class="card">
        <h2><?php echo $id > 0 ? 'Edit Domain' : 'Add New Domain'; ?></h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Domain Name *</label>
                <input type="text" name="domain_name" 
                       value="<?php echo htmlspecialchars($domain['domain_name'] ?? ''); ?>" 
                       placeholder="example.com" required>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_active" 
                           <?php echo (!$domain || $domain['is_active']) ? 'checked' : ''; ?>>
                    Active
                </label>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-success">Save Domain</button>
                <a href="domains.php" class="btn">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>
