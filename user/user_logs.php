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

<!-- DASHBOARD ACTIVITY CARDS -->
<div class="container mb-4">
    <div class="row g-3 d-flex align-items-stretch">

        <!-- Total Activities -->
        <div class="col-12 col-md-4 d-flex">
            <div class="card border-0 shadow-sm flex-fill" style="border-radius:12px">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-success fw-bold" style="font-size:13px">Total Activities</span>
                        <div class="d-flex align-items-center justify-content-center rounded-2" style="width:34px;height:34px;background:#E6F1FB">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <circle cx="6" cy="5" r="2.5" stroke="#185FA5" stroke-width="1.5"/>
                                <path d="M1.5 13.5c0-2.485 2.015-4.5 4.5-4.5s4.5 2.015 4.5 4.5" stroke="#185FA5" stroke-width="1.5" stroke-linecap="round"/>
                                <circle cx="11.5" cy="5.5" r="2" stroke="#185FA5" stroke-width="1.3"/>
                                <path d="M14 13c0-1.657-1.12-3.07-2.672-3.43" stroke="#185FA5" stroke-width="1.3"/>
                            </svg>
                        </div>
                    </div>
                    <div class="fw-medium lh-1 mb-1" style="font-size:28px"><?php echo $totalActivities; ?></div>
                    <div class="text-secondary" style="font-size:12px">All activities logged</div>
                </div>
            </div>
        </div>

        <!-- Request Made -->
        <div class="col-12 col-md-4 d-flex">
            <div class="card border-0 shadow-sm flex-fill" style="border-radius:12px">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-success fw-bold" style="font-size:13px">Request Made</span>
                        <div class="d-flex align-items-center justify-content-center rounded-2" style="width:34px;height:34px;background:#FAEEDA">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <rect x="2" y="2" width="12" height="12" rx="2" stroke="#BA7517" stroke-width="1.5"/>
                                <path d="M8 5v3.5l2 2" stroke="#BA7517" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </div>
                    <div class="fw-medium lh-1 mb-1" style="font-size:28px"><?php echo $requestMade; ?></div>
                    <div class="text-secondary" style="font-size:12px">Requests submitted</div>
                </div>
            </div>
        </div>

        <!-- Cancellations -->
        <div class="col-12 col-md-4 d-flex">
            <div class="card border-0 shadow-sm flex-fill" style="border-radius:12px">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-success fw-bold" style="font-size:13px">Cancellations</span>
                        <div class="d-flex align-items-center justify-content-center rounded-2" style="width:34px;height:34px;background:#FCEBEB">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <line x1="3" y1="3" x2="13" y2="13" stroke="#A32D2D" stroke-width="1.5" stroke-linecap="round"/>
                                <line x1="13" y1="3" x2="3" y2="13" stroke="#A32D2D" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                        </div>
                    </div>
                    <div class="fw-medium lh-1 mb-1" style="font-size:28px"><?php echo $cancellations; ?></div>
                    <div class="text-secondary" style="font-size:12px">Requests cancelled</div>
                </div>
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

