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

// ================= FILTER INPUTS =================
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$department = $_GET['department'] ?? '';

// ================= SUMMARY COUNTS =================
$totalRequests = $conn->query("SELECT COUNT(*) as total FROM requests")->fetch_assoc()['total'];
$pendingRequests = $conn->query("SELECT COUNT(*) as total FROM requests WHERE status='Pending'")->fetch_assoc()['total'];
$approvedRequests = $conn->query("SELECT COUNT(*) as total FROM requests WHERE status='Approved'")->fetch_assoc()['total'];
$declinedRequests = $conn->query("SELECT COUNT(*) as total FROM requests WHERE status='Declined'")->fetch_assoc()['total'];

// ================= DEPARTMENT SUMMARY =================
$deptSummaryQuery = "
    SELECT users.department, COUNT(request_items.request_item_id) as total_items
    FROM request_items
    JOIN requests ON request_items.request_id = requests.request_id
    JOIN users ON requests.user_id = users.user_id
    GROUP BY users.department
";
$deptSummary = $conn->query($deptSummaryQuery);

// ================= FILTERED REQUEST REPORT =================
$query = "
    SELECT requests.request_id,
           users.first_name,
           users.last_name,
           users.department,
           request_items.quantity,
           items.item_name,
           requests.status,
           requests.request_date
    FROM request_items
    JOIN requests ON request_items.request_id = requests.request_id
    JOIN users ON requests.user_id = users.user_id
    JOIN items ON request_items.item_id = items.item_id
    WHERE 1=1
";

$params = [];
$types = "";

if (!empty($startDate)) {
    $query .= " AND DATE(requests.request_date) >= ?";
    $params[] = $startDate;
    $types .= "s";
}

if (!empty($endDate)) {
    $query .= " AND DATE(requests.request_date) <= ?";
    $params[] = $endDate;
    $types .= "s";
}

if (!empty($department) && $department !== "Department") {
    $query .= " AND users.department = ?";
    $params[] = $department;
    $types .= "s";
}

$query .= " ORDER BY requests.request_date DESC";

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
<h2 class="mb-4 text-success fw-bold">Request Reports</h2>

<!-- Summary Cards -->
<div class="container mb-4">
<div class="row g-3">
<div class="col-md-3">
<div class="card shadow-sm p-3 text-center">
<h5 class="text-success fw-bold">Total Requests</h5>
<h3><?php echo $totalRequests; ?></h3>
</div>
</div>
<div class="col-md-3">
<div class="card shadow-sm p-3 text-center">
<h5 class="text-success fw-bold">Pending Requests</h5>
<h3><?php echo $pendingRequests; ?></h3>
</div>
</div>
<div class="col-md-3">
<div class="card shadow-sm p-3 text-center">
<h5 class="text-success fw-bold">Approved Requests</h5>
<h3><?php echo $approvedRequests; ?></h3>
</div>
</div>
<div class="col-md-3">
<div class="card shadow-sm p-3 text-center">
<h5 class="text-success fw-bold">Declined Requests</h5>
<h3><?php echo $declinedRequests; ?></h3>
</div>
</div>
</div>
</div>

<!-- Department Summary -->
<div class="container mb-4">
<div class="row g-3">

<?php while($dept = $deptSummary->fetch_assoc()): ?>
<div class="col-md-3">
<div class="card shadow-sm p-3 text-center">
<h5 class="text-success fw-bold"><?php echo htmlspecialchars($dept['department']); ?></h5>
<p><?php echo $dept['total_items']; ?> items requested</p>
</div>
</div>
<?php endwhile; ?>

</div>
</div>

<!-- Request Reports Table -->
<div class="container mb-4">
<div class="card shadow-sm p-3">
<h5 class="text-success fw-bold mb-3">Request Reports</h5>

<div class="table-responsive">
<table class="table table-bordered table-hover align-middle text-center">
<thead class="table-success">
<tr>
<th>#</th>
<th>Requested By</th>
<th>Item</th>
<th>Quantity</th>
<th>Department</th>
<th>Status</th>
<th>Date</th>
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
<td><?php echo htmlspecialchars($row['item_name']); ?></td>
<td><?php echo $row['quantity']; ?></td>
<td><?php echo htmlspecialchars($row['department']); ?></td>
<td>
<?php if ($row['status'] === 'Approved'): ?>
<span class="badge bg-success">Approved</span>
<?php elseif ($row['status'] === 'Declined'): ?>
<span class="badge bg-danger">Declined</span>
<?php elseif ($row['status'] === 'Pending'): ?>
<span class="badge bg-warning text-dark">Pending</span>
<?php else: ?>
<span class="badge bg-secondary"><?php echo htmlspecialchars($row['status']); ?></span>
<?php endif; ?>
</td>
<td><?php echo $row['request_date']; ?></td>
</tr>
<?php endwhile; ?>

<?php if ($result->num_rows === 0): ?>
<tr><td colspan="7">No request records found.</td></tr>
<?php endif; ?>

</tbody>
</table>
</div>
</div>
</div>

<!-- Filter + Export -->
<div class="container mb-4">
<div class="row g-3">

<div class="col-md-8">
<div class="card shadow-sm p-3">
<h5 class="text-success fw-bold mb-3">Filter Requests</h5>
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
<input type="text" name="department" class="form-control"
placeholder="Department"
value="<?php echo htmlspecialchars($department); ?>">
</div>
<div class="col-md-12">
<button type="submit" class="btn btn-success w-100">Apply Filter</button>
</div>
</form>
</div>
</div>

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
