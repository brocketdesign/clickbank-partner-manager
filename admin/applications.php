<?php
require_once '../config.php';
requireLogin();

$conn = getDBConnection();

// Basic listing of partner applications
$stmt = $conn->prepare("SELECT id, name, email, blog_url, traffic_estimate, status, domain_verification_status, created_at FROM partner_applications ORDER BY created_at DESC");
$stmt->execute();
$res = $stmt->get_result();
$applications = [];
while ($row = $res->fetch_assoc()) {
    $applications[] = $row;
}
$stmt->close();

$page_title = 'Applications';
include 'header.php';
?>

<?php include 'nav.php'; ?>

<div class="container">
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h2 style="margin:0">Partner Applications</h2>
            <a href="applications.php" class="btn">Refresh</a>
        </div>

        <?php if (count($applications) === 0): ?>
            <p style="color: #7f8c8d; text-align: center; padding: 40px 0;">No applications found.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Blog / Domain</th>
                        <th>Verification</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app): ?>
                        <tr>
                            <td><?php echo $app['id']; ?></td>
                            <td><?php echo htmlspecialchars($app['name']); ?></td>
                            <td><?php echo htmlspecialchars($app['email']); ?></td>
                            <td><?php echo htmlspecialchars($app['blog_url']); ?></td>
                            <td>
                                <?php 
                                $ver_status = $app['domain_verification_status'];
                                $ver_class = 'badge-inactive';
                                if ($ver_status === 'verified') $ver_class = 'badge-active';
                                elseif ($ver_status === 'pending' || $ver_status === 'unchecked') $ver_class = 'badge-paused';
                                ?>
                                <span class="badge <?php echo $ver_class; ?>"><?php echo htmlspecialchars(ucfirst($ver_status)); ?></span>
                            </td>
                            <td>
                                <?php 
                                $status = $app['status'];
                                $status_class = 'badge-paused';
                                if ($status === 'approved') $status_class = 'badge-active';
                                elseif ($status === 'rejected') $status_class = 'badge-inactive';
                                ?>
                                <span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars(ucfirst($status)); ?></span>
                            </td>
                            <td><?php echo date('Y-m-d H:i', strtotime($app['created_at'])); ?></td>
                            <td>
                                <a href="application_view.php?id=<?php echo $app['id']; ?>" class="btn btn-small">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
