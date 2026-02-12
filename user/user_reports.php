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

// ================= FETCH REPORT COUNTS =================
$total = 0;
$pending = 0;
$approved = 0;
$declined = 0;

$countQuery = $conn->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status='Pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status='Approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status='Declined' THEN 1 ELSE 0 END) as declined
    FROM requests
    WHERE user_id = ?
");
$countQuery->bind_param("i", $user_id);
$countQuery->execute();
$countResult = $countQuery->get_result()->fetch_assoc();

$total = $countResult['total'] ?? 0;
$pending = $countResult['pending'] ?? 0;
$approved = $countResult['approved'] ?? 0;
$declined = $countResult['declined'] ?? 0;

$countQuery->close();

// ================= FETCH USER REQUEST REPORTS =================
$reportQuery = $conn->prepare("
    SELECT r.*, i.item_name, ri.quantity
    FROM requests r
    LEFT JOIN request_items ri ON r.request_id = ri.request_id
    LEFT JOIN items i ON ri.item_id = i.item_id
    WHERE r.user_id = ?
    ORDER BY r.request_date DESC
");
$reportQuery->bind_param("i", $user_id);
$reportQuery->execute();
$reportResult = $reportQuery->get_result();

$reports = [];
while ($row = $reportResult->fetch_assoc()) {
    $reports[] = $row;
}
$reportQuery->close();

include_once("../includes/header.php");
?>

<div class="d-flex">
<?php include_once("../includes/sidebar_user.php"); ?>

<main class="flex-grow-1 p-4" style="margin-left: 250px;">
<h2 class="mb-4 text-success fw-bold">My Reports</h2>

<div class="container mb-4">
<div class="row g-3">

<div class="col-md-3">
<div class="card shadow-sm p-3 text-center">
<h5 class="text-success fw-bold">Total Request</h5>
<h3><?php echo $total; ?></h3>
</div>
</div>

<div class="col-md-3">
<div class="card shadow-sm p-3 text-center">
<h5 class="text-success fw-bold">Pending</h5>
<h3><?php echo $pending; ?></h3>
</div>
</div>

<div class="col-md-3">
<div class="card shadow-sm p-3 text-center">
<h5 class="text-success fw-bold">Approved</h5>
<h3><?php echo $approved; ?></h3>
</div>
</div>

<div class="col-md-3">
<div class="card shadow-sm p-3 text-center">
<h5 class="text-success fw-bold">Declined</h5>
<h3><?php echo $declined; ?></h3>
</div>
</div>

</div>
</div>

<div class="container mb-4">
<div class="row g-3">

<div class="col-md-12">
<div class="card shadow-sm p-3">
<h5 class="text-success fw-bold mb-3">Request Reports</h5>

<div class="table-responsive">
<table class="table table-bordered table-hover text-center align-middle">
<thead class="table-success">
<tr>
<th>#</th>
<th>Item</th>
<th>Quantity</th>
<th>Status</th>
<th>Date Requested</th>
</tr>
</thead>
<tbody>

<?php if (empty($reports)): ?>
<tr><td colspan="5">No records found.</td></tr>
<?php else: ?>
<?php foreach ($reports as $index => $report): ?>
<tr>
<td><?php echo $index + 1; ?></td>
<td><?php echo htmlspecialchars($report['item_name'] ?? 'N/A'); ?></td>
<td><?php echo htmlspecialchars($report['quantity'] ?? 0); ?></td>
<td>
<?php
$status = $report['status'];
if ($status == 'Approved') {
    echo '<span class="badge bg-success">Approved</span>';
} elseif ($status == 'Pending') {
    echo '<span class="badge bg-warning text-dark">Pending</span>';
} elseif ($status == 'Declined') {
    echo '<span class="badge bg-danger">Declined</span>';
} elseif ($status == 'Cancelled') {
    echo '<span class="badge bg-secondary">Cancelled</span>';
} else {
    echo '<span class="badge bg-info">'.htmlspecialchars($status).'</span>';
}
?>
</td>
<td><?php echo date("M d, Y", strtotime($report['request_date'])); ?></td>
</tr>
<?php endforeach; ?>
<?php endif; ?>

</tbody>
</table>
</div>

</div>
</div>

</div>
</div>

</main>
</div>

<?php include_once("../includes/footer.php"); ?>
