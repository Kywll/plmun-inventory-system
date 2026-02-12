<?php
session_start();
require_once("../includes/db_connect.php");

// ================= SESSION SECURITY =================
if (!isset($_SESSION['user_id']) || $_SESSION['position'] !== 'Admin') {
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

// ================= FILTER LOGIC =================
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$activityType = $_GET['activity_type'] ?? '';

$query = "
    SELECT logs.*, users.first_name, users.last_name 
    FROM logs
    JOIN users ON logs.user_id = users.user_id
    WHERE 1=1
";

$params = [];
$types = "";

if (!empty($startDate)) {
    $query .= " AND DATE(logs.timestamp) >= ?";
    $params[] = $startDate;
    $types .= "s";
}

if (!empty($endDate)) {
    $query .= " AND DATE(logs.timestamp) <= ?";
    $params[] = $endDate;
    $types .= "s";
}

if (!empty($activityType) && $activityType !== "Activity Type") {
    $query .= " AND logs.action LIKE ?";
    $params[] = "%" . $activityType . "%";
    $types .= "s";
}

$query .= " ORDER BY logs.timestamp DESC";

$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

include_once("../includes/header.php");
?>

<div class="d-flex">

<?php include_once("../includes/sidebar_admin.php"); ?>

<main class="flex-grow-1 p-4" style="margin-left: 250px;">
<h2 class="mb-4 text-success fw-bold">Activity Reports</h2>

<div class="container mb-4">
<div class="row g-3">

<!-- Activity Logs Table -->
<div class="container mb-4">
<div class="card shadow-sm p-3">
<h5 class="text-success fw-bold mb-3">Activity Logs</h5>

<div class="table-responsive">
<table class="table table-bordered table-hover align-middle text-center">
<thead class="table-success">
<tr>
<th>#</th>
<th>User</th>
<th>Activity</th>
<th>Details</th>
<th>Date & Time</th>
</tr>
</thead>
<tbody>

<?php
$count = 1;
while ($row = $result->fetch_assoc()):
?>
<tr>
<td><?php echo $count++; ?></td>
<td><?php echo htmlspecialchars($row['first_name'] . " " . $row['last_name']); ?></td>
<td><?php echo htmlspecialchars($row['action']); ?></td>
<td><?php echo htmlspecialchars($row['remarks'] ?? ''); ?></td>
<td><?php echo $row['timestamp']; ?></td>
</tr>
<?php endwhile; ?>

<?php if ($result->num_rows === 0): ?>
<tr>
<td colspan="5">No activity logs found.</td>
</tr>
<?php endif; ?>

</tbody>
</table>
</div>
</div>
</div>

<!-- Filter Activities -->
<div class="col-md-8">
<div class="card shadow-sm p-3">
<h5 class="text-success fw-bold mb-3">Filter Activities</h5>

<form method="GET" class="row g-3 align-items-center">
<div class="col-md-4">
<input type="date" name="start_date" class="form-control"
value="<?php echo htmlspecialchars($startDate); ?>">
</div>
<div class="col-md-4">
<input type="date" name="end_date" class="form-control"
value="<?php echo htmlspecialchars($endDate); ?>">
</div>
<div class="col-md-4">
<select name="activity_type" class="form-select">
<option selected>Activity Type</option>
<option value="Login">Login</option>
<option value="Edit">Edit / Update</option>
<option value="Approval">Approval</option>
<option value="Deletion">Deletion</option>
<option value="Item">Item</option>
<option value="Request">Request</option>
</select>
</div>
<div class="col-md-12">
<button type="submit" class="btn btn-success w-100">Apply Filter</button>
</div>
</form>

</div>
</div>

<!-- Export Reports -->
<div class="col-md-4">
<div class="card shadow-sm p-3 text-center">
<h5 class="text-success fw-bold mb-3">Export Reports</h5>
<button class="btn btn-danger m-1 w-100">Export PDF</button>
<button class="btn btn-success m-1 w-100">Export Excel</button>
<button class="btn btn-primary m-1 w-100">Export CSV</button>
</div>
</div>

</div>
</div>

</main>
</div>

<?php
include_once("../includes/footer.php");
$stmt->close();
$conn->close();
?>
