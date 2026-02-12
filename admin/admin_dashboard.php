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

$admin_id = $_SESSION['user_id'];

// ================= SUMMARY COUNTS =================
$totalUsers = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
$pendingRequests = $conn->query("SELECT COUNT(*) as total FROM requests WHERE status='Pending'")->fetch_assoc()['total'];
$lowStock = $conn->query("SELECT COUNT(*) as total FROM items WHERE stock <= low_stock_threshold AND is_active=1")->fetch_assoc()['total'];
$totalReports = $conn->query("SELECT COUNT(*) as total FROM requests")->fetch_assoc()['total'];

// ================= LATEST REQUEST =================
$latestRequest = $conn->query("
    SELECT r.request_id, r.status, r.request_date, u.first_name, u.last_name
    FROM requests r
    JOIN users u ON r.user_id = u.user_id
    ORDER BY r.request_date DESC
    LIMIT 1
")->fetch_assoc();

// ================= URGENT REQUEST (Pending only) =================
$urgentRequest = $conn->query("
    SELECT r.request_id, r.request_date, u.first_name, u.last_name
    FROM requests r
    JOIN users u ON r.user_id = u.user_id
    WHERE r.status='Pending'
    ORDER BY r.request_date ASC
    LIMIT 1
")->fetch_assoc();

// ================= TOP REQUESTED ITEMS =================
$topItems = $conn->query("
    SELECT i.item_name, SUM(ri.quantity) as total_requested
    FROM request_items ri
    JOIN items i ON ri.item_id = i.item_id
    GROUP BY ri.item_id
    ORDER BY total_requested DESC
    LIMIT 3
");

$topRequested = [];
while ($row = $topItems->fetch_assoc()) {
    $topRequested[] = $row;
}

// ================= MONTHLY REQUEST TREND =================
$monthlyTrend = $conn->query("
    SELECT DATE_FORMAT(request_date, '%Y-%m') as month, COUNT(*) as total
    FROM requests
    GROUP BY month
    ORDER BY month DESC
    LIMIT 6
");

$trendData = [];
while ($row = $monthlyTrend->fetch_assoc()) {
    $trendData[] = $row;
}

// ================= ACTIVITY LOGS =================
$logs = $conn->query("
    SELECT l.action, l.timestamp, u.first_name, u.last_name
    FROM logs l
    JOIN users u ON l.user_id = u.user_id
    ORDER BY l.timestamp DESC
    LIMIT 5
");

$activityLogs = [];
while ($row = $logs->fetch_assoc()) {
    $activityLogs[] = $row;
}

include_once("../includes/header.php");
?>

<div class="d-flex">
<?php include_once("../includes/sidebar_admin.php"); ?>

<main class="flex-grow-1 p-4" style="margin-left: 250px;">
<h2 class="mb-4 text-success fw-bold">Admin Dashboard</h2>

<div class="container mb-4">
<div class="row g-3">

<div class="col-md-3">
<div class="card shadow-sm p-3 text-center">
<h5 class="text-success fw-bold">Users</h5>
<h3><?php echo $totalUsers; ?></h3>
</div>
</div>

<div class="col-md-3">
<div class="card shadow-sm p-3 text-center">
<h5 class="text-success fw-bold">Pending Request</h5>
<h3><?php echo $pendingRequests; ?></h3>
</div>
</div>

<div class="col-md-3">
<div class="card shadow-sm p-3 text-center">
<h5 class="text-success fw-bold">Low Stack</h5>
<h3><?php echo $lowStock; ?></h3>
</div>
</div>

<div class="col-md-3">
<div class="card shadow-sm p-3 text-center">
<h5 class="text-success fw-bold">Reports</h5>
<h3><?php echo $totalReports; ?></h3>
</div>
</div>

</div>
</div>

<div class="container mb-4">
<div class="row g-3">
<div class="col-md-12">
<div class="card shadow-sm p-3" style="height: 150px">
<h5 class="text-success fw-bold text-start">Latest Request</h5>
<?php if ($latestRequest): ?>
<p class="text-start">
<strong><?php echo htmlspecialchars($latestRequest['first_name']." ".$latestRequest['last_name']); ?></strong>
— <?php echo htmlspecialchars($latestRequest['status']); ?>
<br>
<?php echo date("M d, Y", strtotime($latestRequest['request_date'])); ?>
</p>
<?php else: ?>
<p class="text-start">No requests available.</p>
<?php endif; ?>
</div>
</div>
</div>
</div>

<div class="container mb-4">
<div class="row g-3">

<div class="col-md-6">
<div class="card shadow-sm p-3" style="height: 150px;">
<h5 class="text-success fw-bold text-start">Urgent Request</h5>
<?php if ($urgentRequest): ?>
<p class="text-start">
<strong><?php echo htmlspecialchars($urgentRequest['first_name']." ".$urgentRequest['last_name']); ?></strong>
<br>
<?php echo date("M d, Y", strtotime($urgentRequest['request_date'])); ?>
</p>
<?php else: ?>
<p class="text-start">No urgent requests.</p>
<?php endif; ?>
</div>
</div>

<div class="col-md-6">
<div class="card shadow-sm p-3" style="height: 150px;">
<h5 class="text-success fw-bold text-start">Top Requested Items</h5>
<?php if (!empty($topRequested)): ?>
<ul class="text-start">
<?php foreach ($topRequested as $item): ?>
<li><?php echo htmlspecialchars($item['item_name']); ?> (<?php echo $item['total_requested']; ?>)</li>
<?php endforeach; ?>
</ul>
<?php else: ?>
<p class="text-start">No data available.</p>
<?php endif; ?>
</div>
</div>

</div>
</div>

<div class="container mb-4">
<div class="row g-3">
<div class="col-md-12">
<div class="card shadow-sm p-3" style="height: 150px">
<h5 class="text-success fw-bold text-start">Monthly Request Trend</h5>
<?php if (!empty($trendData)): ?>
<ul class="text-start">
<?php foreach ($trendData as $trend): ?>
<li><?php echo htmlspecialchars($trend['month']); ?> — <?php echo $trend['total']; ?> requests</li>
<?php endforeach; ?>
</ul>
<?php else: ?>
<p class="text-start">No trend data available.</p>
<?php endif; ?>
</div>
</div>
</div>
</div>

<div class="container mb-4">
<div class="row g-3">

<div class="col-md-9">
<div class="card shadow-sm p-3" style="height: 150px;">
<h5 class="text-success fw-bold text-start">Activity Logs</h5>
<?php if (!empty($activityLogs)): ?>
<ul class="text-start">
<?php foreach ($activityLogs as $log): ?>
<li>
<?php echo htmlspecialchars($log['first_name']." ".$log['last_name']); ?>
— <?php echo htmlspecialchars($log['action']); ?>
</li>
<?php endforeach; ?>
</ul>
<?php else: ?>
<p class="text-start">No logs available.</p>
<?php endif; ?>
</div>
</div>

<div class="col-md-3">
<div class="card shadow-sm p-3 text-center" style="height: 150px;">
<h5 class="text-success fw-bold text-start">Generate PDF Report</h5>
<a href="admin_reports.php" class="btn btn-success mt-3">View Reports</a>
</div>
</div>

</div>
</div>

</main>
</div>

<?php include_once("../includes/footer.php"); ?>
