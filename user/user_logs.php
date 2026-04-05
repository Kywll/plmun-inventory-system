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

// ================= FILTER LOGIC =================
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$activityType = $_GET['activity_type'] ?? '';

// ================= FETCH LOG COUNTS =================
$countQueryStr = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN action LIKE '%Requested%' THEN 1 ELSE 0 END) as requests,
        SUM(CASE WHEN action LIKE '%Cancelled%' THEN 1 ELSE 0 END) as cancelled
    FROM logs
    WHERE user_id = ?
";
if (!empty($startDate)) $countQueryStr .= " AND DATE(timestamp) >= '$startDate'";
if (!empty($endDate)) $countQueryStr .= " AND DATE(timestamp) <= '$endDate'";
if (!empty($activityType)) $countQueryStr .= " AND action LIKE '%$activityType%'";

$countQuery = $conn->prepare($countQueryStr);
$countQuery->bind_param("i", $user_id);
$countQuery->execute();
$countResult = $countQuery->get_result()->fetch_assoc();

$totalActivities = $countResult['total'] ?? 0;
$requestMade = $countResult['requests'] ?? 0;
$cancellations = $countResult['cancelled'] ?? 0;

$countQuery->close();

// ================= FETCH USER LOGS =================
$logsQueryStr = "
    SELECT *
    FROM logs
    WHERE user_id = ?
";
if (!empty($startDate)) $logsQueryStr .= " AND DATE(timestamp) >= ?";
if (!empty($endDate)) $logsQueryStr .= " AND DATE(timestamp) <= ?";
if (!empty($activityType)) $logsQueryStr .= " AND action LIKE ?";

$logsQueryStr .= " ORDER BY timestamp DESC";

$logsQuery = $conn->prepare($logsQueryStr);

$params = [$user_id];
$types = "i";
if (!empty($startDate)) { $params[] = $startDate; $types .= "s"; }
if (!empty($endDate)) { $params[] = $endDate; $types .= "s"; }
if (!empty($activityType)) { $params[] = "%$activityType%"; $types .= "s"; }

$logsQuery->bind_param($types, ...$params);
$logsQuery->execute();
$logsResult = $logsQuery->get_result();

$logs = [];
while ($row = $logsResult->fetch_assoc()) {
    $logs[] = $row;
}
$logsQuery->close();

?>

<div class="d-flex">
<?php include_once("../includes/sidebar_user.php"); ?>

<main class="flex-grow-1 p-4" style="margin-left: 250px; height: 100vh; overflow-y: auto;">
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

<div class="container mb-4">
<div class="card shadow-sm p-3">
<h5 class="text-success fw-bold mb-3">Filter Activities</h5>
<form method="GET" class="row g-3 align-items-center">
<div class="col-md-4">
<input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($startDate); ?>">
</div>
<div class="col-md-4">
<input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($endDate); ?>">
</div>
<div class="col-md-4">
<select name="activity_type" class="form-select">
<option value="">Activity Type</option>
<option value="Login" <?php if($activityType == 'Login') echo 'selected'; ?>>Login</option>
<option value="Requested" <?php if($activityType == 'Requested') echo 'selected'; ?>>Request Submitted</option>
<option value="Cancelled" <?php if($activityType == 'Cancelled') echo 'selected'; ?>>Request Cancelled</option>
<option value="Password" <?php if($activityType == 'Password') echo 'selected'; ?>>Password Change</option>
</select>
</div>
<div class="col-md-12 mt-3">
<button type="submit" class="btn btn-success w-100">Apply Filter</button>
</div>
</form>
</div>
</div>

<div class="container mb-4">
<div class="card shadow-sm p-3">
<h5 class="text-success fw-bold mb-3">Activity History</h5>

<div style="max-height: 400px; overflow-y: auto;">
<table class="table table-bordered table-hover text-center align-middle">
<thead class="table-success" style="position: sticky; top: 0; z-index: 1;">
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

