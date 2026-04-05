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

// ================= EXPORT LOGIC =================
if (isset($_GET['export'])) {
    $exportType = $_GET['export'];
    
    // Re-run the filtered query for export
    $exportQuery = "
        SELECT logs.*, users.first_name, users.last_name 
        FROM logs
        JOIN users ON logs.user_id = users.user_id
        WHERE 1=1
    ";
    $exportParams = [];
    $exportTypes = "";
    if (!empty($startDate)) {
        $exportQuery .= " AND DATE(logs.timestamp) >= ?";
        $exportParams[] = $startDate;
        $exportTypes .= "s";
    }
    if (!empty($endDate)) {
        $exportQuery .= " AND DATE(logs.timestamp) <= ?";
        $exportParams[] = $endDate;
        $exportTypes .= "s";
    }
    if (!empty($activityType) && $activityType !== "Activity Type") {
        $exportQuery .= " AND logs.action LIKE ?";
        $exportParams[] = "%" . $activityType . "%";
        $exportTypes .= "s";
    }
    $exportQuery .= " ORDER BY logs.timestamp DESC";
    $stmtExport = $conn->prepare($exportQuery);
    if (!empty($exportParams)) {
        $stmtExport->bind_param($exportTypes, ...$exportParams);
    }
    $stmtExport->execute();
    $exportResult = $stmtExport->get_result();

    if ($exportType === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="activity_logs.csv"');
        $output = fopen("php://output", "w");
        fputcsv($output, ['User', 'Activity', 'Details', 'Date & Time']);
        while ($row = $exportResult->fetch_assoc()) {
            fputcsv($output, [
                $row['first_name'] . " " . $row['last_name'],
                $row['action'],
                $row['remarks'],
                $row['timestamp']
            ]);
        }
        fclose($output);
        exit();
    } elseif ($exportType === 'excel') {
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=\"activity_logs.xls\"");
        echo "User\tActivity\tDetails\tDate & Time\n";
        while ($row = $exportResult->fetch_assoc()) {
            echo $row['first_name'] . " " . $row['last_name'] . "\t";
            echo $row['action'] . "\t";
            echo $row['remarks'] . "\t";
            echo $row['timestamp'] . "\n";
        }
        exit();
    } elseif ($exportType === 'pdf') {
        require_once('../tcpdf/tcpdf.php');
        class MYPDF extends TCPDF {
            public function Header() {
                $this->SetFont('helvetica', 'B', 16);
                $this->Cell(0, 15, 'PLMun Supply Inventory System', 0, 1, 'C');
                $this->SetFont('helvetica', 'B', 12);
                $this->Cell(0, 10, 'Activity Logs Report', 0, 1, 'C');
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
        $pdf->SetTitle('Activity Logs Report');
        $pdf->SetMargins(15, 40, 15);
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 10);
        $html = '
        <p><strong>Generated on:</strong> '.date("Y-m-d H:i:s").'</p>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color: #dff0d8; font-weight: bold;">
                    <th width="20%">User</th>
                    <th width="20%">Activity</th>
                    <th width="35%">Details</th>
                    <th width="25%">Date & Time</th>
                </tr>
            </thead>
            <tbody>';
        while ($row = $exportResult->fetch_assoc()) {
            $html .= '<tr>
                <td>'.$row['first_name'].' '.$row['last_name'].'</td>
                <td>'.$row['action'].'</td>
                <td>'.$row['remarks'].'</td>
                <td>'.$row['timestamp'].'</td>
            </tr>';
        }
        $html .= '</tbody></table>';
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('activity_logs.pdf', 'D');
        exit();
    }
}

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

?>

<div class="d-flex">

<?php include_once("../includes/sidebar_admin.php"); ?>

<main class="flex-grow-1 p-4" style="margin-left: 250px; height: 100vh; overflow-y: auto;">
<h2 class="mb-4 text-success fw-bold">Activity Reports</h2>

<div class="container mb-4">
<div class="row g-3">

<!-- Activity Logs Table -->
<div class="col-md-12">
<div class="card shadow-sm p-3">
<h5 class="text-success fw-bold mb-3">Activity Logs</h5>

<div style="max-height: 400px; overflow-y: auto;">
<table class="table table-bordered table-hover align-middle text-center">
<thead class="table-success" style="position: sticky; top: 0; z-index: 1;">
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
<option value="">Activity Type</option>
<option value="Login" <?php if($activityType == 'Login') echo 'selected'; ?>>Login</option>
<option value="Edit" <?php if($activityType == 'Edit') echo 'selected'; ?>>Edit / Update</option>
<option value="Approval" <?php if($activityType == 'Approval') echo 'selected'; ?>>Approval</option>
<option value="Deletion" <?php if($activityType == 'Deletion') echo 'selected'; ?>>Deletion</option>
<option value="Item" <?php if($activityType == 'Item') echo 'selected'; ?>>Item</option>
<option value="Request" <?php if($activityType == 'Request') echo 'selected'; ?>>Request</option>
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
<a href="?export=pdf&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>&activity_type=<?php echo urlencode($activityType); ?>" class="btn btn-danger m-1 w-100">Export PDF</a>
<a href="?export=excel&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>&activity_type=<?php echo urlencode($activityType); ?>" class="btn btn-success m-1 w-100">Export Excel</a>
<a href="?export=csv&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>&activity_type=<?php echo urlencode($activityType); ?>" class="btn btn-primary m-1 w-100">Export CSV</a>
</div>
</div>

</div>
</div>

</main>
</div>

<?php
$stmt->close();
$conn->close();
?>
