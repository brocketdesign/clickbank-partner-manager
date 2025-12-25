<?php
require_once '../config.php';
requireLogin();

$conn = getDBConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$rule = null;
$error = '';

// Load existing rule
if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM redirect_rules WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $rule = $result->fetch_assoc();
    $stmt->close();
    
    if (!$rule) {
        header('Location: rules.php');
        exit;
    }
}

// Get domains for dropdown
$domains = [];
$result = $conn->query("SELECT id, domain_name FROM domains WHERE is_active = 1 ORDER BY domain_name");
while ($row = $result->fetch_assoc()) {
    $domains[] = $row;
}

// Get partners for dropdown
$partners = [];
$result = $conn->query("SELECT id, aff_id, partner_name FROM partners WHERE is_active = 1 ORDER BY partner_name");
while ($row = $result->fetch_assoc()) {
    $partners[] = $row;
}

// Get offers for dropdown
$offers = [];
$result = $conn->query("SELECT id, offer_name FROM offers WHERE is_active = 1 ORDER BY offer_name");
while ($row = $result->fetch_assoc()) {
    $offers[] = $row;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rule_name = sanitize($_POST['rule_name'] ?? '');
    $rule_type = $_POST['rule_type'] ?? 'global';
    $domain_id = !empty($_POST['domain_id']) ? (int)$_POST['domain_id'] : null;
    $partner_id = !empty($_POST['partner_id']) ? (int)$_POST['partner_id'] : null;
    $offer_id = !empty($_POST['offer_id']) ? (int)$_POST['offer_id'] : null;
    $priority = !empty($_POST['priority']) ? (int)$_POST['priority'] : 100;
    $is_paused = isset($_POST['is_paused']) ? 1 : 0;
    
    if (empty($rule_name)) {
        $error = 'Rule name is required';
    } elseif (!$offer_id) {
        $error = 'Please select an offer';
    } elseif ($rule_type === 'domain' && !$domain_id) {
        $error = 'Please select a domain for domain-specific rules';
    } elseif ($rule_type === 'partner' && !$partner_id) {
        $error = 'Please select a partner for partner-specific rules';
    } else {
        // Set nulls for non-applicable fields
        if ($rule_type === 'global') {
            $domain_id = null;
            $partner_id = null;
        } elseif ($rule_type === 'domain') {
            $partner_id = null;
        } elseif ($rule_type === 'partner') {
            $domain_id = null;
        }
        
        if ($id > 0) {
            // Update
            $stmt = $conn->prepare("UPDATE redirect_rules SET rule_name = ?, rule_type = ?, domain_id = ?, partner_id = ?, offer_id = ?, priority = ?, is_paused = ? WHERE id = ?");
            $stmt->bind_param("ssiiiiii", $rule_name, $rule_type, $domain_id, $partner_id, $offer_id, $priority, $is_paused, $id);
            $stmt->execute();
            $stmt->close();
            header('Location: rules.php?msg=updated');
            exit;
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO redirect_rules (rule_name, rule_type, domain_id, partner_id, offer_id, priority, is_paused) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiiiii", $rule_name, $rule_type, $domain_id, $partner_id, $offer_id, $priority, $is_paused);
            $stmt->execute();
            $stmt->close();
            header('Location: rules.php?msg=added');
            exit;
        }
    }
}

$page_title = $id > 0 ? 'Edit Rule' : 'Add Rule';
include 'header.php';
?>

<?php include 'nav.php'; ?>

<div class="container">
    <div class="card">
        <h2><?php echo $id > 0 ? 'Edit Redirect Rule' : 'Add New Redirect Rule'; ?></h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" id="ruleForm">
            <div class="form-group">
                <label>Rule Name *</label>
                <input type="text" name="rule_name" 
                       value="<?php echo htmlspecialchars($rule['rule_name'] ?? ''); ?>" 
                       placeholder="My Redirect Rule" required>
            </div>
            
            <div class="form-group">
                <label>Rule Type *</label>
                <select name="rule_type" id="rule_type" required onchange="updateRuleTypeFields()">
                    <option value="global" <?php echo (!$rule || $rule['rule_type'] === 'global') ? 'selected' : ''; ?>>
                        Global (all traffic)
                    </option>
                    <option value="domain" <?php echo ($rule && $rule['rule_type'] === 'domain') ? 'selected' : ''; ?>>
                        Domain-specific
                    </option>
                    <option value="partner" <?php echo ($rule && $rule['rule_type'] === 'partner') ? 'selected' : ''; ?>>
                        Partner-specific
                    </option>
                </select>
            </div>
            
            <div class="form-group" id="domain_field" style="display: none;">
                <label>Domain *</label>
                <select name="domain_id">
                    <option value="">Select Domain</option>
                    <?php foreach ($domains as $domain): ?>
                        <option value="<?php echo $domain['id']; ?>" 
                                <?php echo ($rule && $rule['domain_id'] == $domain['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($domain['domain_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" id="partner_field" style="display: none;">
                <label>Partner *</label>
                <select name="partner_id">
                    <option value="">Select Partner</option>
                    <?php foreach ($partners as $partner): ?>
                        <option value="<?php echo $partner['id']; ?>" 
                                <?php echo ($rule && $rule['partner_id'] == $partner['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($partner['partner_name'] . ' (' . $partner['aff_id'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>ClickBank Offer *</label>
                <select name="offer_id" required>
                    <option value="">Select Offer</option>
                    <?php foreach ($offers as $offer): ?>
                        <option value="<?php echo $offer['id']; ?>" 
                                <?php echo ($rule && $rule['offer_id'] == $offer['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($offer['offer_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Priority</label>
                <input type="number" name="priority" 
                       value="<?php echo htmlspecialchars($rule['priority'] ?? 100); ?>" 
                       min="1" max="999">
                <small style="color: #7f8c8d; display: block; margin-top: 5px;">
                    Lower numbers = higher priority (default: 100)
                </small>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_paused" 
                           <?php echo ($rule && $rule['is_paused']) ? 'checked' : ''; ?>>
                    Paused (rule won't be used for redirects)
                </label>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-success">Save Rule</button>
                <a href="rules.php" class="btn">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
function updateRuleTypeFields() {
    const ruleType = document.getElementById('rule_type').value;
    const domainField = document.getElementById('domain_field');
    const partnerField = document.getElementById('partner_field');
    
    domainField.style.display = ruleType === 'domain' ? 'block' : 'none';
    partnerField.style.display = ruleType === 'partner' ? 'block' : 'none';
}

// Initialize on page load
updateRuleTypeFields();
</script>

<?php include 'footer.php'; ?>
