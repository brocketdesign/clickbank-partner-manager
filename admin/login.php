<?php
require_once '../config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username && $password) {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT id, password_hash FROM admin_users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password_hash'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_user_id'] = $row['id'];
                $_SESSION['admin_username'] = $username;
                header('Location: index.php');
                exit;
            }
        }
        
        $error = 'Invalid username or password';
        $stmt->close();
    } else {
        $error = 'Please enter both username and password';
    }
}

$page_title = 'Login';
include 'header.php';
?>

<div class="login-container">
    <div class="login-box">
        <h1>Admin Login</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required autofocus>
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            
            <button type="submit" class="btn" style="width: 100%;">Login</button>
        </form>
        
        <p style="margin-top: 20px; text-align: center; color: #7f8c8d; font-size: 14px;">
            Default credentials: admin / admin123
        </p>
    </div>
</div>

<?php include 'footer.php'; ?>
