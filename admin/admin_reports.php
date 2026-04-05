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

// ================= EXPORT CSV =================
if (isset($_GET['export']) && $_GET['export'] === 'csv') {

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="request_reports.csv"');

    $output = fopen("php://output", "w");

    fputcsv($output, ['Requested By', 'Item', 'Quantity', 'Department', 'Status', 'Date']);

    $exportQuery = "
        SELECT users.first_name, users.last_name, users.department,
               items.item_name, request_items.quantity,
               requests.status, requests.request_date
        FROM request_items
        JOIN requests ON request_items.request_id = requests.request_id
        JOIN users ON requests.user_id = users.user_id
        JOIN items ON request_items.item_id = items.item_id
        WHERE 1=1
    ";

    $params = [];
    $types = "";

    if (!empty($startDate)) {
        $exportQuery .= " AND DATE(requests.request_date) >= ?";
        $params[] = $startDate;
        $types .= "s";
    }

    if (!empty($endDate)) {
        $exportQuery .= " AND DATE(requests.request_date) <= ?";
        $params[] = $endDate;
        $types .= "s";
    }

    if (!empty($department)) {
        $exportQuery .= " AND users.department LIKE ?";
        $params[] = "%$department%";
        $types .= "s";
    }

    $stmtExport = $conn->prepare($exportQuery);

    if (!empty($params)) {
        $stmtExport->bind_param($types, ...$params);
    }

    $stmtExport->execute();
    $exportResult = $stmtExport->get_result();

    while ($row = $exportResult->fetch_assoc()) {
        fputcsv($output, [
            $row['first_name']." ".$row['last_name'],
            $row['item_name'],
            $row['quantity'],
            $row['department'],
            $row['status'],
            $row['request_date']
        ]);
    }

    fclose($output);
    exit();
}

// ================= EXPORT EXCEL =================
if (isset($_GET['export']) && $_GET['export'] === 'excel') {

    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"request_reports.xls\"");

    echo "Requested By\tItem\tQuantity\tDepartment\tStatus\tDate\n";

    $exportQuery = "
        SELECT users.first_name, users.last_name, users.department,
               items.item_name, request_items.quantity,
               requests.status, requests.request_date
        FROM request_items
        JOIN requests ON request_items.request_id = requests.request_id
        JOIN users ON requests.user_id = users.user_id
        JOIN items ON request_items.item_id = items.item_id
        WHERE 1=1
    ";

    $params = [];
    $types = "";

    if (!empty($startDate)) {
        $exportQuery .= " AND DATE(requests.request_date) >= ?";
        $params[] = $startDate;
        $types .= "s";
    }

    if (!empty($endDate)) {
        $exportQuery .= " AND DATE(requests.request_date) <= ?";
        $params[] = $endDate;
        $types .= "s";
    }

    if (!empty($department)) {
        $exportQuery .= " AND users.department LIKE ?";
        $params[] = "%$department%";
        $types .= "s";
    }

    $stmtExport = $conn->prepare($exportQuery);

    if (!empty($params)) {
        $stmtExport->bind_param($types, ...$params);
    }

    $stmtExport->execute();
    $exportResult = $stmtExport->get_result();

    while ($row = $exportResult->fetch_assoc()) {
        echo $row['first_name']." ".$row['last_name']."\t";
        echo $row['item_name']."\t";
        echo $row['quantity']."\t";
        echo $row['department']."\t";
        echo $row['status']."\t";
        echo $row['request_date']."\n";
    }

    exit();
}

// ================= EXPORT PDF =================
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {

    require_once('../tcpdf/tcpdf.php');

    // Extend TCPDF to create custom Header and Footer
    class MYPDF extends TCPDF {
        public function Header() {
            $this->SetFont('helvetica', 'B', 16);
            $this->Cell(0, 15, 'PLMun Supply Inventory System', 0, 1, 'C');
            $this->SetFont('helvetica', 'B', 12);
            $this->Cell(0, 10, 'Request Report', 0, 1, 'C');
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
    $pdf->SetTitle('Request Report');
    $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    $pdf->SetMargins(15, 40, 15);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 10);

    $exportQuery = "
        SELECT users.first_name, users.last_name, users.department,
               items.item_name, request_items.quantity,
               requests.status, requests.request_date
        FROM request_items
        JOIN requests ON request_items.request_id = requests.request_id
        JOIN users ON requests.user_id = users.user_id
        JOIN items ON request_items.item_id = items.item_id
        WHERE 1=1
    ";

    $params = [];
    $types = "";

    if (!empty($startDate)) {
        $exportQuery .= " AND DATE(requests.request_date) >= ?";
        $params[] = $startDate;
        $types .= "s";
    }

    if (!empty($endDate)) {
        $exportQuery .= " AND DATE(requests.request_date) <= ?";
        $params[] = $endDate;
        $types .= "s";
    }

    if (!empty($department)) {
        $exportQuery .= " AND users.department LIKE ?";
        $params[] = "%$department%";
        $types .= "s";
    }

    $stmtExport = $conn->prepare($exportQuery);
    if (!empty($params)) {
        $stmtExport->bind_param($types, ...$params);
    }
    $stmtExport->execute();
    $exportResult = $stmtExport->get_result();

    $html = '
    <p><strong>Generated on:</strong> '.date("Y-m-d H:i:s").'</p>
    <table border="1" cellpadding="5">
        <thead>
            <tr style="background-color: #dff0d8; font-weight: bold;">
                <th width="20%">Requested By</th>
                <th width="20%">Item</th>
                <th width="10%">Qty</th>
                <th width="20%">Dept</th>
                <th width="15%">Status</th>
                <th width="15%">Date</th>
            </tr>
        </thead>
        <tbody>';

    while ($row = $exportResult->fetch_assoc()) {
        $html .= '<tr>
            <td>'.$row['first_name'].' '.$row['last_name'].'</td>
            <td>'.$row['item_name'].'</td>
            <td>'.$row['quantity'].'</td>
            <td>'.$row['department'].'</td>
            <td>'.$row['status'].'</td>
            <td>'.$row['request_date'].'</td>
        </tr>';
    }

    $html .= '</tbody></table>';

    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output('request_report.pdf', 'D');
    exit();
}

// ================= SUMMARY =================
$totalRequests = $conn->query("SELECT COUNT(*) as total FROM requests")->fetch_assoc()['total'];
$pendingRequests = $conn->query("SELECT COUNT(*) as total FROM requests WHERE status='Pending'")->fetch_assoc()['total'];
$approvedRequests = $conn->query("SELECT COUNT(*) as total FROM requests WHERE status='Approved'")->fetch_assoc()['total'];
$declinedRequests = $conn->query("SELECT COUNT(*) as total FROM requests WHERE status='Declined'")->fetch_assoc()['total'];

// ================= DEPARTMENT SUMMARY =================
$deptSummary = $conn->query("
    SELECT users.department, COUNT(request_items.request_item_id) as total_items
    FROM request_items
    JOIN requests ON request_items.request_id = requests.request_id
    JOIN users ON requests.user_id = users.user_id
    GROUP BY users.department
");

// ================= FILTERED QUERY =================
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

if (!empty($department)) {
    $query .= " AND users.department LIKE ?";
    $params[] = "%$department%";
    $types .= "s";
}

$query .= " ORDER BY requests.request_date DESC";

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
<h2 class="mb-4 text-success fw-bold">Request Reports</h2>



<!-- SUMMARY -->
<div class="container mb-4">
<div class="row g-3">
<div class="col-md-3"><div class="card shadow-sm p-3 text-center "><h5 class="text-success fw-bold">Total</h5><h3><?php echo $totalRequests; ?></h3></div></div>
<div class="col-md-3"><div class="card shadow-sm p-3 text-center"><h5 class="text-success fw-bold">Pending</h5><h3><?php echo $pendingRequests; ?></h3></div></div>
<div class="col-md-3"><div class="card shadow-sm p-3 text-center"><h5 class="text-success fw-bold">Approved</h5><h3><?php echo $approvedRequests; ?></h3></div></div>
<div class="col-md-3"><div class="card shadow-sm p-3 text-center"><h5 class="text-success fw-bold">Declined</h5><h3><?php echo $declinedRequests; ?></h3></div></div>
</div>
</div>

<div class="container mb-4">
<div class="row g-3">

<div class="col-md-8">
<div class="card shadow-sm p-3" style="height: 200px;">
<h5 class="text-success fw-bold mb-3">Filter Reports</h5>
<form method="GET">
<div class="row">
<div class="col-md-4">
<input type="date" name="start_date" value="<?php echo $startDate; ?>" class="form-control mb-2">
</div>
<div class="col-md-4">
<input type="date" name="end_date" value="<?php echo $endDate; ?>" class="form-control mb-2">
</div>
<div class="col-md-4">
<input type="text" name="department" placeholder="Department" value="<?php echo $department; ?>" class="form-control mb-2">
</div>
</div>
<button class="btn btn-success w-100 mt-2">Apply Filter</button>
</form>
</div>
</div>

<div class="col-md-4">
<div class="card shadow-sm p-3 text-center" style="height: 200px;">
<h5 class="text-success fw-bold mb-3">Export Reports</h5>

<a href="admin_reports.php?export=pdf&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>&department=<?php echo urlencode($department); ?>" 
   class="btn btn-danger w-100 mb-2 btn-sm">
Export PDF
</a>

<a href="admin_reports.php?export=excel&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>&department=<?php echo urlencode($department); ?>" 
   class="btn btn-success w-100 mb-2 btn-sm">
Export Excel
</a>

<a href="admin_reports.php?export=csv&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>&department=<?php echo urlencode($department); ?>" 
   class="btn btn-primary w-100 btn-sm">
Export CSV
</a>

</div>
</div>

</div>
</div>


<!-- TABLE -->
<div class="container mb-4">
<div class="card shadow-sm p-3">
<h5>Reports</h5>

<div style="max-height: 400px; overflow-y: auto;">
<table class="table table-bordered text-center align-middle">
<thead class="table-success" style="position: sticky; top: 0; z-index: 1;">
<tr>
<th>#</th>
<th>User</th>
<th>Item</th>
<th>Qty</th>
<th>Dept</th>
<th>Status</th>
<th>Date</th>
</tr>
</thead>
<tbody>

<?php $count=1; while ($row = $result->fetch_assoc()): ?>
<tr>
<td><?php echo $count++; ?></td>
<td><?php echo $row['first_name']." ".$row['last_name']; ?></td>
<td><?php echo $row['item_name']; ?></td>
<td><?php echo $row['quantity']; ?></td>
<td><?php echo $row['department']; ?></td>
<td><?php echo $row['status']; ?></td>
<td><?php echo $row['request_date']; ?></td>
</tr>
<?php endwhile; ?>

<?php if ($result->num_rows === 0): ?>
<tr><td colspan="7">No data found</td></tr>
<?php endif; ?>

</tbody>
</table>
</div>
</div>
</div>

<!-- FILTER + EXPORT -->

</main>
</div>

<?php
$stmt->close();
$conn->close();
?>