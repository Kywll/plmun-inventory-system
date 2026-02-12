<?php
session_start();
require_once("../includes/db_connect.php");

// ================= SECURITY =================
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

$message = "";

// ================= UPDATE STATUS =================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_status'])) {

    $user_id = intval($_POST['user_id']);
    $new_status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE users SET status=? WHERE user_id=?");
    $stmt->bind_param("si", $new_status, $user_id);
    $stmt->execute();
    $stmt->close();

    // Log the update
    $log = $conn->prepare("
        INSERT INTO logs (user_id, action, remarks)
        VALUES (?, 'User Status Updated', CONCAT('Updated user ID: ', ?))
    ");
    $log->bind_param("ii", $_SESSION['user_id'], $user_id);
    $log->execute();
    $log->close();

    $message = "<div class='alert alert-success'>User status updated successfully.</div>";
}

// ================= FILTERING =================
$search = $_GET['search'] ?? '';
$department = $_GET['department'] ?? '';
$status = $_GET['status'] ?? '';

$query = "SELECT * FROM users WHERE 1=1";

if (!empty($search)) {
    $query .= " AND (first_name LIKE '%$search%' OR last_name LIKE '%$search%')";
}
if (!empty($department) && $department !== "Department") {
    $query .= " AND department='$department'";
}
if (!empty($status) && $status !== "Status") {
    $query .= " AND status='$status'";
}

$query .= " ORDER BY user_id DESC";
$result = $conn->query($query);

// ================= SUMMARY CARDS =================
$total_users = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
$active_users = $conn->query("SELECT COUNT(*) as total FROM users WHERE status='Active'")->fetch_assoc()['total'];
$inactive_users = $conn->query("SELECT COUNT(*) as total FROM users WHERE status='Inactive'")->fetch_assoc()['total'];
$recent_users = $conn->query("SELECT COUNT(*) as total FROM users WHERE date_created >= NOW() - INTERVAL 7 DAY")->fetch_assoc()['total'];

include_once("../includes/header.php");
?>

<div class="d-flex">
<?php include_once("../includes/sidebar_admin.php"); ?>

<main class="flex-grow-1 p-4" style="margin-left: 250px;">
<h2 class="mb-4 text-success fw-bold">User Management</h2>

<?php echo $message; ?>

<!-- SUMMARY CARDS -->
<div class="container mb-4">
<div class="row g-3">
<div class="col-md-3">
<div class="card shadow-sm p-3 text-center">
<h5 class="text-success fw-bold">Total Users</h5>
<h3><?php echo $total_users; ?></h3>
</div>
</div>
<div class="col-md-3">
<div class="card shadow-sm p-3 text-center">
<h5 class="text-success fw-bold">Active Users</h5>
<h3><?php echo $active_users; ?></h3>
</div>
</div>
<div class="col-md-3">
<div class="card shadow-sm p-3 text-center">
<h5 class="text-success fw-bold">Inactive Users</h5>
<h3><?php echo $inactive_users; ?></h3>
</div>
</div>
<div class="col-md-3">
<div class="card shadow-sm p-3 text-center">
<h5 class="text-success fw-bold">Recently Updated</h5>
<h3><?php echo $recent_users; ?></h3>
</div>
</div>
</div>
</div>

<!-- USER TABLE -->
<div class="container mb-4">
<div class="card shadow-sm p-3">
<h5 class="text-success fw-bold mb-3">User Status List</h5>
<div class="table-responsive">
<table class="table table-bordered table-hover align-middle text-center">
<thead class="table-success">
<tr>
<th>#</th>
<th>Name</th>
<th>Department</th>
<th>Email</th>
<th>Status</th>
<th>Date Created</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php
$count = 1;
while ($row = $result->fetch_assoc()):
?>
<tr>
<td><?php echo $count++; ?></td>
<td><?php echo $row['first_name'] . " " . $row['last_name']; ?></td>
<td><?php echo $row['department']; ?></td>
<td><?php echo $row['email']; ?></td>
<td>
<?php if ($row['status'] === 'Active'): ?>
<span class="badge bg-success">Active</span>
<?php else: ?>
<span class="badge bg-danger">Inactive</span>
<?php endif; ?>
</td>
<td><?php echo $row['date_created']; ?></td>
<td>
<button class="btn btn-sm btn-primary"
data-bs-toggle="modal"
data-bs-target="#updateStatusModal"
data-id="<?php echo $row['user_id']; ?>"
data-name="<?php echo $row['first_name'] . ' ' . $row['last_name']; ?>"
data-department="<?php echo $row['department']; ?>"
data-status="<?php echo $row['status']; ?>">
Update Status
</button>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</div>
</div>

</main>
</div>

<!-- STATUS MODAL -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
<div class="modal-dialog modal-md">
<div class="modal-content">
<div class="modal-header bg-success text-white">
<h5 class="modal-title">Update User Status</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
<form method="POST">
<input type="hidden" name="user_id" id="modal_user_id">

<div class="col-md-12 mb-2">
<label class="form-label">User Name</label>
<input type="text" class="form-control" id="modal_name" readonly>
</div>

<div class="col-md-12 mb-2">
<label class="form-label">Department</label>
<input type="text" class="form-control" id="modal_department" readonly>
</div>

<div class="col-md-12 mb-3">
<label class="form-label">Status</label>
<select name="status" id="modal_status" class="form-select">
<option value="Active">Active</option>
<option value="Inactive">Inactive</option>
</select>
</div>

<button type="submit" name="update_status" class="btn btn-success w-100">
Update Status
</button>
</form>
</div>
</div>
</div>
</div>

<script>
var modal = document.getElementById('updateStatusModal');
modal.addEventListener('show.bs.modal', function (event) {
var button = event.relatedTarget;
document.getElementById('modal_user_id').value = button.getAttribute('data-id');
document.getElementById('modal_name').value = button.getAttribute('data-name');
document.getElementById('modal_department').value = button.getAttribute('data-department');
document.getElementById('modal_status').value = button.getAttribute('data-status');
});
</script>

<?php
include_once("../includes/footer.php");
$conn->close();
?>
