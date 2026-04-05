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

// ================= FILTER LOGIC =================
$search = "";
$category = "";
$supplier = "";

$where = "WHERE is_active = 1";
$params = [];
$types = "";

if ($_SERVER["REQUEST_METHOD"] === "GET") {

    if (!empty($_GET['search'])) {
        $search = trim($_GET['search']);
        $where .= " AND item_name LIKE ?";
        $params[] = "%$search%";
        $types .= "s";
    }

    if (!empty($_GET['category']) && $_GET['category'] !== "Category") {
        $category = $_GET['category'];
        $where .= " AND category = ?";
        $params[] = $category;
        $types .= "s";
    }

    if (!empty($_GET['supplier']) && $_GET['supplier'] !== "Supplier") {
        $supplier = $_GET['supplier'];
        $where .= " AND supplier_name = ?";
        $params[] = $supplier;
        $types .= "s";
    }
}

// ================= EXPORT LOGIC =================
if (isset($_GET['export'])) {
    $exportType = $_GET['export'];
    
    // Re-run the filtered query for export
    $exportQuery = "SELECT * FROM items $where ORDER BY item_name ASC";
    $stmtExport = $conn->prepare($exportQuery);
    if (!empty($params)) {
        $stmtExport->bind_param($types, ...$params);
    }
    $stmtExport->execute();
    $exportResult = $stmtExport->get_result();

    if ($exportType === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="user_inventory.csv"');
        $output = fopen("php://output", "w");
        fputcsv($output, ['Item Name', 'Category', 'Supplier', 'Stock', 'Status']);
        while ($row = $exportResult->fetch_assoc()) {
            $status = ($row['stock'] <= 0) ? 'Out of Stock' : (($row['stock'] <= $row['low_stock_threshold']) ? 'Low Stock' : 'OK');
            fputcsv($output, [$row['item_name'], $row['category'], $row['supplier_name'], $row['stock'], $status]);
        }
        fclose($output);
        exit();
    } elseif ($exportType === 'excel') {
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=\"user_inventory.xls\"");
        echo "Item Name\tCategory\tSupplier\tStock\tStatus\n";
        while ($row = $exportResult->fetch_assoc()) {
            $status = ($row['stock'] <= 0) ? 'Out of Stock' : (($row['stock'] <= $row['low_stock_threshold']) ? 'Low Stock' : 'OK');
            echo $row['item_name']."\t".$row['category']."\t".$row['supplier_name']."\t".$row['stock']."\t".$status."\n";
        }
        exit();
    } elseif ($exportType === 'pdf') {
        require_once('../tcpdf/tcpdf.php');
        class MYPDF extends TCPDF {
            public function Header() {
                $this->SetFont('helvetica', 'B', 16);
                $this->Cell(0, 15, 'PLMun Supply Inventory System', 0, 1, 'C');
                $this->SetFont('helvetica', 'B', 12);
                $this->Cell(0, 10, 'Available Inventory Report', 0, 1, 'C');
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
        $pdf->SetTitle('Inventory Report');
        $pdf->SetMargins(15, 40, 15);
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 10);
        $html = '<p><strong>Generated on:</strong> '.date("Y-m-d H:i:s").'</p>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color: #dff0d8; font-weight: bold;">
                    <th width="30%">Item Name</th>
                    <th width="20%">Category</th>
                    <th width="25%">Supplier</th>
                    <th width="10%">Stock</th>
                    <th width="15%">Status</th>
                </tr>
            </thead>
            <tbody>';
        while ($row = $exportResult->fetch_assoc()) {
            $status = ($row['stock'] <= 0) ? 'Out of Stock' : (($row['stock'] <= $row['low_stock_threshold']) ? 'Low Stock' : 'OK');
            $html .= '<tr><td>'.$row['item_name'].'</td><td>'.$row['category'].'</td><td>'.$row['supplier_name'].'</td><td>'.$row['stock'].'</td><td>'.$status.'</td></tr>';
        }
        $html .= '</tbody></table>';
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('user_inventory.pdf', 'D');
        exit();
    }
}

// ================= FETCH INVENTORY =================
$query = "SELECT * FROM items $where ORDER BY item_name ASC";
$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();

// ================= FETCH DISTINCT CATEGORIES =================
$categories = [];
$catResult = $conn->query("SELECT DISTINCT category FROM items WHERE is_active = 1");
while ($row = $catResult->fetch_assoc()) {
    $categories[] = $row['category'];
}

// ================= FETCH DISTINCT SUPPLIERS =================
$suppliers = [];
$supResult = $conn->query("SELECT DISTINCT supplier_name FROM items WHERE is_active = 1");
while ($row = $supResult->fetch_assoc()) {
    $suppliers[] = $row['supplier_name'];
}

?>

<div class="d-flex">
<?php include_once("../includes/sidebar_user.php"); ?>

<main class="flex-grow-1 p-4" style="margin-left: 250px; height: 100vh; overflow-y: auto;">
<h2 class="mb-4 text-success fw-bold">Inventory</h2>

<!-- TABLE -->
<div class="container mb-4">
<div class="row g-3">
<div class="col-md-9">
<div class="card shadow-sm p-3" style="height: 450px;">
<h5 class="text-success fw-bold mb-3">Available Items</h5>
<div style="max-height: 380px; overflow-y: auto;">
<table class="table table-bordered table-hover align-middle text-center">
<thead class="table-success" style="position: sticky; top: 0; z-index: 1;">
<tr>
<th>#</th>
<th>Item Name</th>
<th>Category</th>
<th>Supplier</th>
<th>Available Quantity</th>
<th>Low Stock</th>
</tr>
</thead>
<tbody>

<?php if (empty($items)): ?>
<tr><td colspan="6">No items found</td></tr>
<?php else: ?>
<?php foreach ($items as $index => $item): ?>
<tr>
<td><?php echo $index + 1; ?></td>
<td><?php echo htmlspecialchars($item['item_name']); ?></td>
<td><?php echo htmlspecialchars($item['category']); ?></td>
<td><?php echo htmlspecialchars($item['supplier_name']); ?></td>
<td><?php echo $item['stock']; ?></td>
<td>
<?php
if ($item['stock'] <= 0) {
    echo '<span class="badge bg-danger">Out of Stock</span>';
} elseif ($item['stock'] <= $item['low_stock_threshold']) {
    echo '<span class="badge bg-warning text-dark">Low Stock</span>';
} else {
    echo '<span class="badge bg-success">OK</span>';
}
?>
</td>
</tr>
<?php endforeach; ?>
<?php endif; ?>

</tbody>
</table>
</div>
</div>
</div>

<div class="col-md-3">
<div class="card shadow-sm p-3 text-center d-flex flex-column justify-content-center" style="height: 450px;">
<h5 class="text-success fw-bold mb-4">Export Inventory</h5>
<div class="px-2">
<a href="?export=pdf&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&supplier=<?php echo urlencode($supplier); ?>" class="btn btn-danger m-1 w-100 mb-3">Export PDF</a>
<a href="?export=excel&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&supplier=<?php echo urlencode($supplier); ?>" class="btn btn-success m-1 w-100 mb-3">Export Excel</a>
<a href="?export=csv&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&supplier=<?php echo urlencode($supplier); ?>" class="btn btn-primary m-1 w-100">Export CSV</a>
</div>
</div>
</div>
</div>
</div>

<div class="container mb-4">
<div class="card shadow-sm p-3">
<h5 class="text-success fw-bold mb-3">Filter / Search Inventory</h5>
<form method="GET" class="row g-3 align-items-center">

<div class="col-md-4">
<input type="text" name="search" class="form-control"
placeholder="Search by Item Name"
value="<?php echo htmlspecialchars($search); ?>">
</div>

<div class="col-md-4">
<select name="category" class="form-select">
<option value="">Category</option>
<?php foreach ($categories as $cat): ?>
<option value="<?php echo htmlspecialchars($cat); ?>"
<?php if ($category === $cat) echo "selected"; ?>>
<?php echo htmlspecialchars($cat); ?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="col-md-3">
<select name="supplier" class="form-select">
<option value="">Supplier</option>
<?php foreach ($suppliers as $sup): ?>
<option value="<?php echo htmlspecialchars($sup); ?>"
<?php if ($supplier === $sup) echo "selected"; ?>>
<?php echo htmlspecialchars($sup); ?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="col-md-1">
<button type="submit" class="btn btn-success w-100"><i class="bi bi-search"></i></button>
</div>

</form>
</div>
</div>

</main>
</div>

