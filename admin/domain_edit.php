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
    
    // Clean domain name
    $domain_name = preg_replace('/^https?:\/\//', '', $domain_name);
    $domain_name = rtrim($domain_name, '/');
    
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
                $error = 'Domain already exists';
                $stmt->close();
            }
        }
    }
}

$page_title = $id > 0 ? 'Edit Domain' : 'Add Domain';
include 'header.php';
?>

<?php include 'nav.php'; ?>

<div class="container animate-fade-in">
    <div style="max-width: 600px;">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 8px; color: var(--primary);">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                    </svg>
                    <?php echo $id > 0 ? 'Edit Domain' : 'Add New Domain'; ?>
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
                    <label for="domain_name">Domain Name *</label>
                    <input type="text" id="domain_name" name="domain_name" 
                           value="<?php echo htmlspecialchars($domain['domain_name'] ?? ''); ?>" 
                           placeholder="e.g., example.com" required>
                    <small>Enter the domain without http:// or https://</small>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" name="is_active" 
                               style="width: 18px; height: 18px; accent-color: var(--primary);"
                               <?php echo (!$domain || $domain['is_active']) ? 'checked' : ''; ?>>
                        <span>Active</span>
                    </label>
                    <small>Inactive domains won't be tracked</small>
                </div>
                
                <div style="display: flex; gap: 12px; padding-top: 16px; border-top: 1px solid var(--gray-100); margin-top: 8px;">
                    <button type="submit" class="btn btn-success">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        Save Domain
                    </button>
                    <a href="domains.php" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
