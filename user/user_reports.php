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

// ================= FILTER INPUTS =================
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

// ================= BASE QUERY =================
$baseQuery = "
    SELECT i.item_name, ri.quantity, r.status, r.request_date
    FROM requests r
    LEFT JOIN request_items ri ON r.request_id = ri.request_id
    LEFT JOIN items i ON ri.item_id = i.item_id
    WHERE r.user_id = ?
";

// ================= EXPORT CSV =================
if (isset($_GET['export']) && $_GET['export'] === 'csv') {

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="my_reports.csv"');

    $output = fopen("php://output", "w");
    fputcsv($output, ['Item', 'Quantity', 'Status', 'Date']);

    $query = $baseQuery;
    $params = [$user_id];
    $types = "i";

    if (!empty($startDate)) {
        $query .= " AND DATE(r.request_date) >= ?";
        $params[] = $startDate;
        $types .= "s";
    }

    if (!empty($endDate)) {
        $query .= " AND DATE(r.request_date) <= ?";
        $params[] = $endDate;
        $types .= "s";
    }

    $stmtExport = $conn->prepare($query);
    $stmtExport->bind_param($types, ...$params);
    $stmtExport->execute();
    $res = $stmtExport->get_result();

    while ($row = $res->fetch_assoc()) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit();
}

// ================= EXPORT EXCEL =================
if (isset($_GET['export']) && $_GET['export'] === 'excel') {

    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"my_reports.xls\"");

    echo "Item\tQuantity\tStatus\tDate\n";

    $query = $baseQuery;
    $params = [$user_id];
    $types = "i";

    if (!empty($startDate)) {
        $query .= " AND DATE(r.request_date) >= ?";
        $params[] = $startDate;
        $types .= "s";
    }

    if (!empty($endDate)) {
        $query .= " AND DATE(r.request_date) <= ?";
        $params[] = $endDate;
        $types .= "s";
    }

    $stmtExport = $conn->prepare($query);
    $stmtExport->bind_param($types, ...$params);
    $stmtExport->execute();
    $res = $stmtExport->get_result();

    while ($row = $res->fetch_assoc()) {
        echo $row['item_name']."\t";
        echo $row['quantity']."\t";
        echo $row['status']."\t";
        echo $row['request_date']."\n";
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
            $this->Cell(0, 10, 'My Request Report', 0, 1, 'C');
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
    $pdf->SetTitle('My Request Report');
    $pdf->SetMargins(15, 40, 15);
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 10);

    $query = $baseQuery;
    $params = [$user_id];
    $types = "i";

    if (!empty($startDate)) {
        $query .= " AND DATE(r.request_date) >= ?";
        $params[] = $startDate;
        $types .= "s";
    }

    if (!empty($endDate)) {
        $query .= " AND DATE(r.request_date) <= ?";
        $params[] = $endDate;
        $types .= "s";
    }

    $stmtExport = $conn->prepare($query);
    $stmtExport->bind_param($types, ...$params);
    $stmtExport->execute();
    $res = $stmtExport->get_result();

    $html = '
    <p><strong>Generated on:</strong> '.date("Y-m-d H:i:s").'</p>
    <table border="1" cellpadding="5">
        <thead>
            <tr style="background-color: #dff0d8; font-weight: bold;">
                <th width="30%">Item</th>
                <th width="20%">Quantity</th>
                <th width="25%">Status</th>
                <th width="25%">Date</th>
            </tr>
        </thead>
        <tbody>';

    while ($row = $res->fetch_assoc()) {
        $html .= '<tr>
            <td>'.$row['item_name'].'</td>
            <td>'.$row['quantity'].'</td>
            <td>'.$row['status'].'</td>
            <td>'.$row['request_date'].'</td>
        </tr>';
    }

    $html .= '</tbody></table>';

    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output('my_reports.pdf', 'D');
    exit();
}

// ================= FETCH COUNTS =================
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

// ================= FETCH REPORTS =================
$query = $baseQuery;
$params = [$user_id];
$types = "i";

if (!empty($startDate)) {
    $query .= " AND DATE(r.request_date) >= ?";
    $params[] = $startDate;
    $types .= "s";
}

if (!empty($endDate)) {
    $query .= " AND DATE(r.request_date) <= ?";
    $params[] = $endDate;
    $types .= "s";
}

$query .= " ORDER BY r.request_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="d-flex">
<?php include_once("../includes/sidebar_user.php"); ?>

<main class="flex-grow-1 p-4" style="margin-left: 250px; height: 100vh; overflow-y: auto;">
<h2 class="mb-4 text-success fw-bold">My Reports</h2>


<!-- DASHBOARD SUMMARY CARDS -->
<div class="container mb-4">
    <div class="row g-3 d-flex align-items-stretch">

        <!-- Total -->
        <div class="col-12 col-md-3 d-flex">
            <div class="card border-1 shadow-sm flex-fill" style="border-radius:12px">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-success fw-bold" style="font-size:13px">Total</span>
                        <div class="d-flex align-items-center justify-content-center rounded-2" style="width:34px;height:34px;background:#E1F5EE">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <circle cx="6" cy="5" r="2.5" stroke="#0F6E56" stroke-width="1.5"/>
                                <path d="M1.5 13.5c0-2.485 2.015-4.5 4.5-4.5s4.5 2.015 4.5 4.5" stroke="#0F6E56" stroke-width="1.5" stroke-linecap="round"/>
                                <circle cx="11.5" cy="5.5" r="2" stroke="#0F6E56" stroke-width="1.3"/>
                                <path d="M14 13c0-1.657-1.12-3.07-2.672-3.43" stroke="#0F6E56" stroke-width="1.3"/>
                            </svg>
                        </div>
                    </div>
                    <div class="fw-medium lh-1 mb-1" style="font-size:28px"><?php echo $total; ?></div>
                    <div class="text-secondary" style="font-size:12px">All requests</div>
                </div>
            </div>
        </div>

        <!-- Pending -->
        <div class="col-12 col-md-3 d-flex">
            <div class="card border-1 shadow-sm flex-fill" style="border-radius:12px">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-success fw-bold" style="font-size:13px">Pending</span>
                        <div class="d-flex align-items-center justify-content-center rounded-2" style="width:34px;height:34px;background:#FAEEDA">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <rect x="2" y="2" width="12" height="12" rx="2" stroke="#BA7517" stroke-width="1.5"/>
                                <path d="M8 5v3.5l2 2" stroke="#BA7517" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </div>
                    <div class="fw-medium lh-1 mb-1" style="font-size:28px"><?php echo $pending; ?></div>
                    <div class="text-secondary" style="font-size:12px">Awaiting review</div>
                </div>
            </div>
        </div>

        <!-- Approved -->
        <div class="col-12 col-md-3 d-flex">
            <div class="card border-1 shadow-sm flex-fill" style="border-radius:12px">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-success fw-bold" style="font-size:13px">Approved</span>
                        <div class="d-flex align-items-center justify-content-center rounded-2" style="width:34px;height:34px;background:#E6F1FB">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <path d="M2 8l4 4 8-8" stroke="#185FA5" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </div>
                    <div class="fw-medium lh-1 mb-1" style="font-size:28px"><?php echo $approved; ?></div>
                    <div class="text-secondary" style="font-size:12px">Requests approved</div>
                </div>
            </div>
        </div>

        <!-- Declined -->
        <div class="col-12 col-md-3 d-flex">
            <div class="card border-1 shadow-sm flex-fill" style="border-radius:12px">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-success fw-bold" style="font-size:13px">Declined</span>
                        <div class="d-flex align-items-center justify-content-center rounded-2" style="width:34px;height:34px;background:#FCEBEB">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <line x1="3" y1="3" x2="13" y2="13" stroke="#A32D2D" stroke-width="1.5" stroke-linecap="round"/>
                                <line x1="13" y1="3" x2="3" y2="13" stroke="#A32D2D" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                        </div>
                    </div>
                    <div class="fw-medium lh-1 mb-1" style="font-size:28px"><?php echo $declined; ?></div>
                    <div class="text-secondary" style="font-size:12px">Requests declined</div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- FILTER + EXPORT -->
<div class="container mb-4">
    <div class="row g-3">

        <div class="col-md-8 d-flex">
            <div class="card border-1 shadow-sm flex-fill" style="border-radius:12px;">
                <div class="card-body p-3">
                    <!-- HEADER -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-success fw-bold" style="font-size:15px">Filter Reports</span>
                        <div class="d-flex align-items-center justify-content-center rounded-2" style="width:34px;height:34px;background:#E1F5EE">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <path d="M6 2a4 4 0 1 0 0 8 4 4 0 0 0 0-8z" stroke="#0F6E56" stroke-width="1.5"/>
                                <path d="M11 11l4 4" stroke="#0F6E56" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                        </div>
                    </div>
                    <form method="GET">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <input type="date" name="start_date" value="<?php echo $startDate; ?>" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <input type="date" name="end_date" value="<?php echo $endDate; ?>" class="form-control">
                            </div>
                        </div>
                        <button class="btn btn-success w-100 mt-3">Apply Filter</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4 d-flex">
            <div class="card border-1 shadow-sm flex-fill text-center d-flex flex-column justify-content-center" style="border-radius:12px;">
                <div class="card-body p-3">
                    <!-- HEADER -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-success fw-bold" style="font-size:15px">Export Reports</span>
                        <div class="d-flex align-items-center justify-content-center rounded-2" style="width:34px;height:34px;background:#E1F5EE">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <path d="M3 2h10v12H3z" stroke="#0F6E56" stroke-width="1.5"/>
                                <path d="M3 6h10M3 10h10" stroke="#0F6E56" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                        </div>
                    </div>
                    <div class="d-flex flex-column">
                        <a href="?export=pdf&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="btn btn-danger w-100 mb-2 btn-sm">Export PDF</a>
                        <a href="?export=excel&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="btn btn-success w-100 mb-2 btn-sm">Export Excel</a>
                        <a href="?export=csv&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="btn btn-primary w-100 btn-sm">Export CSV</a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="container mb-4">
    <div class="card border-1 shadow-sm" style="border-radius:12px;">
        <div class="card-body p-3 d-flex flex-column">
            <!-- HEADER -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-success fw-bold" style="font-size:15px">Request Reports</span>
                <div class="d-flex align-items-center justify-content-center rounded-2" style="width:34px;height:34px;background:#E1F5EE">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                        <rect x="3" y="1.5" width="10" height="13" rx="1.5" stroke="#0F6E56" stroke-width="1.5"/>
                        <path d="M5.5 5.5h5M5.5 8h5M5.5 10.5h3" stroke="#0F6E56" stroke-width="1.3" stroke-linecap="round"/>
                    </svg>
                </div>
            </div>

            <div style="max-height: 400px; overflow-y: auto;">
                <table class="table table-sm table-bordered table-hover text-center align-middle mb-0">
                    <thead class="table-success" style="position: sticky; top: 0; z-index: 1;">
                        <tr>
                            <th>#</th>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $count=1; while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <td><?php echo htmlspecialchars($row['item_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($row['quantity'] ?? 0); ?></td>
                                <td>
                                    <?php
                                    $status = $row['status'] ?? '';
                                    $badgeClass = 'bg-info';
                                    if ($status === 'Pending') $badgeClass = 'bg-warning text-dark';
                                    elseif ($status === 'Approved') $badgeClass = 'bg-success';
                                    elseif ($status === 'Declined') $badgeClass = 'bg-danger';
                                    elseif ($status === 'Cancelled') $badgeClass = 'bg-secondary';
                                    elseif ($status === 'Completed') $badgeClass = 'bg-primary';
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($status); ?></span>
                                </td>
                                <td><?php echo date("M d, Y", strtotime($row['request_date'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                        <?php if ($result->num_rows === 0): ?>
                            <tr><td colspan="5">No data found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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