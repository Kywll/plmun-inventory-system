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

    // prevent self-deactivation (optional safety)
    if ($user_id == $_SESSION['user_id']) {
        $message = "<div class='alert alert-danger'>You cannot change your own status.</div>";
    } else {

        $stmt = $conn->prepare("UPDATE users SET status=? WHERE user_id=?");
        $stmt->bind_param("si", $new_status, $user_id);
        $stmt->execute();
        $stmt->close();

        // log
        $log = $conn->prepare("
            INSERT INTO logs (user_id, action, remarks)
            VALUES (?, 'User Status Updated', CONCAT('Updated user ID: ', ?))
        ");
        $log->bind_param("ii", $_SESSION['user_id'], $user_id);
        $log->execute();
        $log->close();

        $message = "<div class='alert alert-success'>User status updated successfully.</div>";
    }
}

// ================= EXPORT CSV =================
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="user_list.csv"');
    $output = fopen("php://output", "w");
    fputcsv($output, ['Name', 'Department', 'Email', 'Status', 'Date Created']);
    $res = $conn->query("SELECT first_name, last_name, department, email, status, date_created FROM users ORDER BY user_id DESC");
    while ($row = $res->fetch_assoc()) {
        fputcsv($output, [
            $row['first_name']." ".$row['last_name'],
            $row['department'],
            $row['email'],
            $row['status'],
            $row['date_created']
        ]);
    }
    fclose($output);
    exit();
}

// ================= EXPORT EXCEL =================
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"user_list.xls\"");
    echo "Name\tDepartment\tEmail\tStatus\tDate Created\n";
    $res = $conn->query("SELECT first_name, last_name, department, email, status, date_created FROM users ORDER BY user_id DESC");
    while ($row = $res->fetch_assoc()) {
        echo $row['first_name']." ".$row['last_name']."\t";
        echo $row['department']."\t";
        echo $row['email']."\t";
        echo $row['status']."\t";
        echo $row['date_created']."\n";
    }
    exit();
}

// ================= EXPORT PDF =================
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {

    require_once('../tcpdf/tcpdf.php');

    class MYPDF extends TCPDF {
        public function Header() {
            $this->SetFont('helvetica', 'B', 16);
            $this->Cell(0, 15, 'PLMun Supply Inventory System', 0, 1, 'C');
            $this->SetFont('helvetica', 'B', 12);
            $this->Cell(0, 10, 'User List Report', 0, 1, 'C');
            $this->Ln(5);
        }
        public function Footer() {
            $this->SetY(-15);
            $this->SetFont('helvetica', 'I', 8);
            $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, 0, 'C');
        }
    }

    $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetTitle('User List Report');
    $pdf->SetMargins(15, 40, 15);
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 10);

    $res = $conn->query("SELECT first_name, last_name, department, email, status, date_created FROM users ORDER BY user_id DESC");

    $html = '
    <p><strong>Generated on:</strong> '.date("Y-m-d H:i:s").'</p>
    <table border="1" cellpadding="5">
        <thead>
            <tr style="background-color: #dff0d8; font-weight: bold;">
                <th width="20%">Name</th>
                <th width="20%">Department</th>
                <th width="30%">Email</th>
                <th width="10%">Status</th>
                <th width="20%">Date Created</th>
            </tr>
        </thead>
        <tbody>';

    while ($row = $res->fetch_assoc()) {
        $html .= '<tr>
            <td>'.$row['first_name'].' '.$row['last_name'].'</td>
            <td>'.$row['department'].'</td>
            <td>'.$row['email'].'</td>
            <td>'.$row['status'].'</td>
            <td>'.$row['date_created'].'</td>
        </tr>';
    }

    $html .= '</tbody></table>';

    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output('user_list.pdf', 'D');
    exit();
}

// ================= FETCH USERS =================
$search = $_GET['search'] ?? '';
$deptFilter = $_GET['department'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$query = "SELECT * FROM users WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "sss";
}

if (!empty($deptFilter)) {
    $query .= " AND department = ?";
    $params[] = $deptFilter;
    $types .= "s";
}

if (!empty($statusFilter)) {
    $query .= " AND status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

$query .= " ORDER BY user_id DESC";
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// ================= COUNTS =================
$total_users = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
$active_users = $conn->query("SELECT COUNT(*) as total FROM users WHERE status='Active'")->fetch_assoc()['total'];
$inactive_users = $conn->query("SELECT COUNT(*) as total FROM users WHERE status='Inactive'")->fetch_assoc()['total'];
$recent_users = $conn->query("SELECT COUNT(*) as total FROM users WHERE date_created >= NOW() - INTERVAL 7 DAY")->fetch_assoc()['total'];
?>

<div class="d-flex">
<?php include_once("../includes/sidebar_admin.php"); ?>

<main class="flex-grow-1 p-4" style="margin-left: 250px; height: 100vh; overflow-y: auto;">
<h2 class="mb-4 text-success fw-bold">User Management</h2>

<?php echo $message; ?>

<!-- SUMMARY -->
<div class="container mb-4">
  <div class="row g-3">

    <div class="col-6 col-md-3">
      <div class="card border-1 shadow-sm h-100" style="border-radius:12px">
        <div class="card-body p-3">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-success fw-bold" style="font-size:13px">Total Users</span>
            <div class="d-flex align-items-center justify-content-center rounded-2" style="width:34px;height:34px;background:#E6F1FB">
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <circle cx="6" cy="5" r="2.5" stroke="#185FA5" stroke-width="1.5"/>
                <path d="M1.5 13.5c0-2.485 2.015-4.5 4.5-4.5s4.5 2.015 4.5 4.5" stroke="#185FA5" stroke-width="1.5" stroke-linecap="round"/>
                <circle cx="11.5" cy="5.5" r="2" stroke="#185FA5" stroke-width="1.3"/>
                <path d="M14 13c0-1.657-1.12-3.07-2.672-3.43" stroke="#185FA5" stroke-width="1.3" stroke-linecap="round"/>
              </svg>
            </div>
          </div>
          <div class="fw-medium lh-1 mb-1" style="font-size:28px"><?php echo $total_users; ?></div>
          <div class="text-secondary" style="font-size:12px">Registered accounts</div>
        </div>
      </div>
    </div>

    <div class="col-6 col-md-3">
      <div class="card border-1 shadow-sm h-100" style="border-radius:12px">
        <div class="card-body p-3">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-success fw-bold" style="font-size:13px">Active</span>
            <div class="d-flex align-items-center justify-content-center rounded-2" style="width:34px;height:34px;background:#E1F5EE">
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <circle cx="8" cy="8" r="6" stroke="#0F6E56" stroke-width="1.5"/>
                <path d="M5.5 8.5l2 2 3-4" stroke="#0F6E56" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
          </div>
          <div class="fw-medium lh-1 mb-1" style="font-size:28px"><?php echo $active_users; ?></div>
          <div class="text-secondary" style="font-size:12px">Currently active</div>
        </div>
      </div>
    </div>

    <div class="col-6 col-md-3">
      <div class="card border-1 shadow-sm h-100" style="border-radius:12px">
        <div class="card-body p-3">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-success fw-bold" style="font-size:13px">Inactive</span>
            <div class="d-flex align-items-center justify-content-center rounded-2" style="width:34px;height:34px;background:#FCEBEB">
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <circle cx="8" cy="8" r="6" stroke="#A32D2D" stroke-width="1.5"/>
                <path d="M5.5 5.5l5 5M10.5 5.5l-5 5" stroke="#A32D2D" stroke-width="1.5" stroke-linecap="round"/>
              </svg>
            </div>
          </div>
          <div class="fw-medium lh-1 mb-1" style="font-size:28px"><?php echo $inactive_users; ?></div>
          <div class="text-secondary" style="font-size:12px">No recent activity</div>
        </div>
      </div>
    </div>

    <div class="col-6 col-md-3">
      <div class="card border-1 shadow-sm h-100" style="border-radius:12px">
        <div class="card-body p-3">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-success fw-bold" style="font-size:13px">Recent</span>
            <div class="d-flex align-items-center justify-content-center rounded-2" style="width:34px;height:34px;background:#FAEEDA">
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <circle cx="8" cy="8" r="6" stroke="#BA7517" stroke-width="1.5"/>
                <path d="M8 5v3l2 1.5" stroke="#BA7517" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
          </div>
          <div class="fw-medium lh-1 mb-1" style="font-size:28px"><?php echo $recent_users; ?></div>
          <div class="text-secondary" style="font-size:12px">Joined this month</div>
        </div>
      </div>
    </div>

  </div>
</div>
<!-- FILTER -->
<div class="container mb-4">
<div class="card border-1 shadow-sm p-3" style="border-radius:12px;">
    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="text-success fw-bold" style="font-size:15px">Filter Users</span>
        <div class="d-flex align-items-center justify-content-center rounded-2" style="width:34px;height:34px;background:#E1F5EE">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M6 2a4 4 0 1 0 0 8 4 4 0 0 0 0-8z" stroke="#0F6E56" stroke-width="1.5"/>
                <path d="M11 11l4 4" stroke="#0F6E56" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
        </div>
    </div>
<form method="GET" class="row g-3 align-items-center">
<div class="col-md-4">
<input type="text" name="search" class="form-control" placeholder="Search name or email" value="<?php echo htmlspecialchars($search); ?>">
</div>
<div class="col-md-3">
<select name="department" class="form-select">
<option value="">Department</option>
<?php
$deptRes = $conn->query("SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != '' ORDER BY department ASC");
while($dept = $deptRes->fetch_assoc()) {
    $selected = ($deptFilter == $dept['department']) ? 'selected' : '';
    echo "<option value='".htmlspecialchars($dept['department'])."' $selected>".htmlspecialchars($dept['department'])."</option>";
}
?>
</select>
</div>
<div class="col-md-3">
<select name="status" class="form-select">
<option value="">Status</option>
<option value="Active" <?php echo ($statusFilter == 'Active') ? 'selected' : ''; ?>>Active</option>
<option value="Inactive" <?php echo ($statusFilter == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
</select>
</div>
<div class="col-md-2">
<button type="submit" class="btn btn-success w-100">Apply Filter</button>
</div>
</form>
</div>
</div>


<!-- USER TABLE -->
<div class="container mb-4">
<div class="row g-3">
<div class="col-md-9 d-flex">
<div class="card border-1 shadow-sm p-3 flex-fill d-flex flex-column" style="border-radius:12px; height: 450px;">
    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="text-success fw-bold" style="font-size:15px">User List</span>
        <div class="d-flex align-items-center justify-content-center rounded-2" style="width:34px;height:34px;background:#E1F5EE">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <rect x="3" y="1.5" width="10" height="13" rx="1.5" stroke="#0F6E56" stroke-width="1.5"/>
                <path d="M5.5 5.5h5M5.5 8h5M5.5 10.5h3" stroke="#0F6E56" stroke-width="1.3" stroke-linecap="round"/>
            </svg>
        </div>
    </div>

<div style="max-height: 380px; overflow-y: auto;">
<table class="table table-sm table-bordered table-hover text-center align-middle mb-0">

<thead class="table-success" style="position: sticky; top: 0; z-index: 1;">
<tr>
<th>#</th>
<th>Name</th>
<th>Department</th>
<th>Email</th>
<th>Status</th>
<th>Date</th>
<th>Action</th>
</tr>
</thead>

<tbody>
<?php $count=1; while ($row = $result->fetch_assoc()): ?>
<tr>

<td><?php echo $count++; ?></td>
<td><?php echo $row['first_name']." ".$row['last_name']; ?></td>
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
data-name="<?php echo $row['first_name'].' '.$row['last_name']; ?>"
data-department="<?php echo $row['department']; ?>"
data-status="<?php echo $row['status']; ?>">
Update
</button>
</td>

</tr>
<?php endwhile; ?>
<?php if ($result->num_rows === 0): ?>
<tr><td colspan="7">No users found.</td></tr>
<?php endif; ?>
</tbody>

</table>
</div>

</div>
</div>

<div class="col-md-3 d-flex">
<div class="card border-1 shadow-sm p-3 text-center d-flex flex-column justify-content-center flex-fill" style="border-radius:12px; height: 450px;">
    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <span class="text-success fw-bold" style="font-size:15px">Export Users</span>
        <div class="d-flex align-items-center justify-content-center rounded-2" style="width:34px;height:34px;background:#E1F5EE">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M3 2h10v12H3z" stroke="#0F6E56" stroke-width="1.5"/>
                <path d="M3 6h10M3 10h10" stroke="#0F6E56" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
        </div>
    </div>
<div class="px-2">
<a href="?export=pdf&search=<?php echo urlencode($search); ?>&department=<?php echo urlencode($deptFilter); ?>&status=<?php echo urlencode($statusFilter); ?>" class="btn btn-danger w-100 mb-3">Export PDF</a>
<a href="?export=excel&search=<?php echo urlencode($search); ?>&department=<?php echo urlencode($deptFilter); ?>&status=<?php echo urlencode($statusFilter); ?>" class="btn btn-success w-100 mb-3">Export Excel</a>
<a href="?export=csv&search=<?php echo urlencode($search); ?>&department=<?php echo urlencode($deptFilter); ?>&status=<?php echo urlencode($statusFilter); ?>" class="btn btn-primary w-100">Export CSV</a>
</div>
</div>
</div>
</div>
</div>


</main>
</div>

<!-- MODAL -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">

<div class="modal-header bg-success text-white">
<h5 class="modal-title">Update User Status</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

<form method="POST">

<input type="hidden" name="user_id" id="modal_user_id">

<div class="mb-2">
<label>Name</label>
<input type="text" id="modal_name" class="form-control" readonly>
</div>

<div class="mb-2">
<label>Department</label>
<input type="text" id="modal_department" class="form-control" readonly>
</div>

<div class="mb-3">
<label>Status</label>
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

<!-- BOOTSTRAP JS (IMPORTANT) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<!-- MODAL SCRIPT -->
<script>
document.addEventListener("DOMContentLoaded", function () {

    var modal = document.getElementById('updateStatusModal');

    modal.addEventListener('show.bs.modal', function (event) {

        var button = event.relatedTarget;

        document.getElementById('modal_user_id').value = button.getAttribute('data-id');
        document.getElementById('modal_name').value = button.getAttribute('data-name');
        document.getElementById('modal_department').value = button.getAttribute('data-department');
        document.getElementById('modal_status').value = button.getAttribute('data-status');

    });

});
</script>

<?php $conn->close(); ?>