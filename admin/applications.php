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

<div class="container animate-fade-in">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 8px; color: var(--primary);">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Partner Applications
            </h2>
            <a href="applications.php" class="btn btn-outline btn-small">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Refresh
            </a>
        </div>

        <?php if (count($applications) === 0): ?>
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3>No applications yet</h3>
                <p>Applications will appear here once partners apply.</p>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
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
                                <td style="font-weight: 500;">#<?php echo $app['id']; ?></td>
                                <td>
                                    <div style="font-weight: 500;"><?php echo htmlspecialchars($app['name']); ?></div>
                                </td>
                                <td>
                                    <a href="mailto:<?php echo htmlspecialchars($app['email']); ?>" style="color: var(--primary); text-decoration: none;">
                                        <?php echo htmlspecialchars($app['email']); ?>
                                    </a>
                                </td>
                                <td>
                                    <a href="<?php echo htmlspecialchars($app['blog_url']); ?>" target="_blank" style="color: var(--gray-600); text-decoration: none; display: flex; align-items: center; gap: 4px;">
                                        <?php echo htmlspecialchars($app['blog_url']); ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </a>
                                </td>
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
                                <td>
                                    <div style="font-size: 13px; color: var(--gray-500);">
                                        <?php echo date('M d, Y', strtotime($app['created_at'])); ?>
                                    </div>
                                    <div style="font-size: 12px; color: var(--gray-400);">
                                        <?php echo date('H:i', strtotime($app['created_at'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <a href="application_view.php?id=<?php echo $app['id']; ?>" class="btn btn-small">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
