<?php
session_start();
require_once("../includes/db_connect.php");

// ================= SESSION SECURITY =================
if (!isset($_SESSION['user_id']) || $_SESSION['position'] !== 'Staff') {
    header("Location: ../login.php");
    exit();
}

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) {
    session_unset();
    session_destroy();
    header("Location: ../login.php");
    exit();
}
$_SESSION['last_activity'] = time();

$user_id = $_SESSION['user_id'];

// ================= FETCH LOG COUNTS =================
$totalActivities = 0;
$requestMade = 0;
$cancellations = 0;

$countQuery = $conn->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN action LIKE '%Requested%' THEN 1 ELSE 0 END) as requests,
        SUM(CASE WHEN action LIKE '%Cancelled%' THEN 1 ELSE 0 END) as cancelled
    FROM logs
    WHERE user_id = ?
");
$countQuery->bind_param("i", $user_id);
$countQuery->execute();
$countResult = $countQuery->get_result()->fetch_assoc();

$totalActivities = $countResult['total'] ?? 0;
$requestMade = $countResult['requests'] ?? 0;
$cancellations = $countResult['cancelled'] ?? 0;

$countQuery->close();

// ================= FETCH USER LOGS =================
$logsQuery = $conn->prepare("
    SELECT *
    FROM logs
    WHERE user_id = ?
    ORDER BY timestamp DESC
");
$logsQuery->bind_param("i", $user_id);
$logsQuery->execute();
$logsResult = $logsQuery->get_result();

$logs = [];
while ($row = $logsResult->fetch_assoc()) {
    $logs[] = $row;
}
$logsQuery->close();

include_once("../includes/header.php");
?>

<div class="d-flex">
<?php include_once("../includes/sidebar_user.php"); ?>

<main class="flex-grow-1 p-4" style="margin-left: 250px;">
<h2 class="mb-4 text-success fw-bold">My Activity Logs</h2>

<div class="container mb-4">
<div class="row g-3">

<div class="col-md-4">
<div class="card shadow-sm p-3 text-center">
<h5 class="text-success fw-bold">Total Activites</h5>
<h3><?php echo $totalActivities; ?></h3>
</div>
</div>

<div class="col-md-4">
<div class="card shadow-sm p-3 text-center">
<h5 class="text-success fw-bold">Request Made</h5>
<h3><?php echo $requestMade; ?></h3>
</div>
</div>

<div class="col-md-4">
<div class="card shadow-sm p-3 text-center">
<h5 class="text-success fw-bold">Cancellations</h5>
<h3><?php echo $cancellations; ?></h3>
</div>
</div>

</div>
</div>

<div class="container">
<div class="card shadow-sm p-3">
<h5 class="text-success fw-bold mb-3">Activity History</h5>

<div class="table-responsive">
<table class="table table-bordered table-hover text-center align-middle">
<thead class="table-success">
<tr>
<th>#</th>
<th>Activity</th>
<th>Description</th>
<th>Status</th>
<th>Date & Time</th>
</tr>
</thead>
<tbody>

<?php if (empty($logs)): ?>
<tr><td colspan="5">No activity found.</td></tr>
<?php else: ?>
<?php foreach ($logs as $index => $log): ?>
<tr>
<td><?php echo $index + 1; ?></td>
<td><?php echo htmlspecialchars($log['action']); ?></td>
<td><?php echo htmlspecialchars($log['remarks']); ?></td>
<td>
<?php
if (strpos($log['action'], 'Approved') !== false) {
    echo '<span class="badge bg-success">Approved</span>';
} elseif (strpos($log['action'], 'Cancelled') !== false) {
    echo '<span class="badge bg-danger">Cancelled</span>';
} elseif (strpos($log['action'], 'Requested') !== false) {
    echo '<span class="badge bg-warning text-dark">Pending</span>';
} else {
    echo '<span class="badge bg-info">Info</span>';
}
?>
</td>
<td><?php echo date("M d, Y - h:i A", strtotime($log['timestamp'])); ?></td>
</tr>
<?php endforeach; ?>
<?php endif; ?>

</tbody>
</table>
</div>

</div>
</div>

</main>
</div>

<?php include_once("../includes/footer.php"); ?>
