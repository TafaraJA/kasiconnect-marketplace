<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

requireRole('admin');

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_id  = $_POST['report_id']  ?? 0;
    $new_status = $_POST['new_status'] ?? '';

    if (ctype_digit((string)$report_id) && in_array($new_status, ['resolved', 'dismissed', 'open'])) {
        $pdo->prepare("UPDATE reports SET status = ? WHERE report_id = ?")
            ->execute([$new_status, $report_id]);
    }
    header('Location: admin_reports.php');
    exit;
}

// Filter by status (default: open)
$filter = $_GET['status'] ?? 'open';
if (!in_array($filter, ['open', 'resolved', 'dismissed', 'all'])) {
    $filter = 'open';
}

$sql = "SELECT r.*, u.full_name AS reporter_name
        FROM reports r
        JOIN users u ON r.reporter_id = u.user_id";

if ($filter !== 'all') {
    $sql .= " WHERE r.status = ?";
    $stmt = $pdo->prepare($sql . " ORDER BY r.created_at DESC");
    $stmt->execute([$filter]);
} else {
    $stmt = $pdo->query($sql . " ORDER BY r.created_at DESC");
}
$reports = $stmt->fetchAll();

// Counts for filter tabs
$counts = [];
foreach (['open', 'resolved', 'dismissed'] as $s) {
    $c = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE status = ?");
    $c->execute([$s]);
    $counts[$s] = $c->fetchColumn();
}

$page_title = 'Reports';
require_once '../includes/header.php';
?>

<div class="page-header">
    <h1>Reports</h1>
    <a href="admin_dashboard.php" class="link-action">← Dashboard</a>
</div>

<div class="filter-tabs">
    <a href="?status=open"      class="filter-tab <?= $filter === 'open'      ? 'active' : '' ?>">Open (<?= $counts['open'] ?>)</a>
    <a href="?status=resolved"  class="filter-tab <?= $filter === 'resolved'  ? 'active' : '' ?>">Resolved (<?= $counts['resolved'] ?>)</a>
    <a href="?status=dismissed" class="filter-tab <?= $filter === 'dismissed' ? 'active' : '' ?>">Dismissed (<?= $counts['dismissed'] ?>)</a>
    <a href="?status=all"       class="filter-tab <?= $filter === 'all'       ? 'active' : '' ?>">All</a>
</div>

<?php if (empty($reports)): ?>
    <div class="empty-state">
        <p>No reports in this category.</p>
    </div>
<?php else: ?>
    <div class="orders-table-wrapper">
        <table class="orders-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Reporter</th>
                    <th>Target</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $report): ?>
                    <tr>
                        <td>#<?= (int)$report['report_id'] ?></td>
                        <td><?= date('d M Y', strtotime($report['created_at'])) ?></td>
                        <td><?= htmlspecialchars($report['reporter_name']) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($report['target_type']) ?></strong>
                            #<?= (int)$report['target_id'] ?>
                            <?php if ($report['target_type'] === 'product'): ?>
                                <br><a href="product.php?id=<?= $report['target_id'] ?>" target="_blank">View</a>
                            <?php endif; ?>
                        </td>
                        <td style="max-width: 300px;"><?= nl2br(htmlspecialchars($report['reason'])) ?></td>
                        <td>
                            <span class="status-badge status-<?= htmlspecialchars($report['status']) ?>">
                                <?= htmlspecialchars($report['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($report['status'] === 'open'): ?>
                                <form method="POST" style="display:flex; gap:4px; flex-wrap:wrap;">
                                    <input type="hidden" name="report_id" value="<?= $report['report_id'] ?>">
                                    <button type="submit" name="new_status" value="resolved"  class="link-action btn-small btn-confirm">Resolve</button>
                                    <button type="submit" name="new_status" value="dismissed" class="link-action btn-small btn-reject">Dismiss</button>
                                </form>
                            <?php else: ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="report_id" value="<?= $report['report_id'] ?>">
                                    <button type="submit" name="new_status" value="open" class="link-action btn-small">Reopen</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>