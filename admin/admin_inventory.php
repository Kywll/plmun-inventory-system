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

// ================= FILTERS =================
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$status = $_GET['status'] ?? '';

// ================= EXPORT LOGIC =================
if (isset($_GET['export'])) {
    $exportType = $_GET['export'];
    
    $exportQuery = "SELECT * FROM items WHERE 1=1";
    $exportParams = [];
    $exportTypes = "";
    
    if (!empty($search)) {
        $exportQuery .= " AND (item_name LIKE ? OR supplier_name LIKE ?)";
        $searchParam = "%$search%";
        $exportParams[] = $searchParam;
        $exportParams[] = $searchParam;
        $exportTypes .= "ss";
    }
    if (!empty($category) && $category !== "Category") {
        $exportQuery .= " AND category = ?";
        $exportParams[] = $category;
        $exportTypes .= "s";
    }
    if ($status === 'Active') {
        $exportQuery .= " AND is_active = 1 AND stock > low_stock_threshold";
    } elseif ($status === 'Low Stock') {
        $exportQuery .= " AND is_active = 1 AND stock <= low_stock_threshold AND stock > 0";
    } elseif ($status === 'Out of Stock') {
        $exportQuery .= " AND is_active = 1 AND stock = 0";
    } elseif ($status === 'Inactive') {
        $exportQuery .= " AND is_active = 0";
    }

    $exportQuery .= " ORDER BY date_added DESC";
    $stmtExport = $conn->prepare($exportQuery);
    if (!empty($exportParams)) {
        $stmtExport->bind_param($exportTypes, ...$exportParams);
    }
    $stmtExport->execute();
    $exportResult = $stmtExport->get_result();

    if ($exportType === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="inventory_list.csv"');
        $output = fopen("php://output", "w");
        fputcsv($output, ['Item', 'Category', 'Supplier', 'Stock', 'Status']);
        while ($row = $exportResult->fetch_assoc()) {
            $itemStatus = ($row['is_active'] == 0) ? 'Inactive' : (($row['stock'] == 0) ? 'Out of Stock' : (($row['stock'] <= $row['low_stock_threshold']) ? 'Low Stock' : 'Active'));
            fputcsv($output, [$row['item_name'], $row['category'], $row['supplier_name'], $row['stock'], $itemStatus]);
        }
        fclose($output);
        exit();
    } elseif ($exportType === 'excel') {
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=\"inventory_list.xls\"");
        echo "Item\tCategory\tSupplier\tStock\tStatus\n";
        while ($row = $exportResult->fetch_assoc()) {
            $itemStatus = ($row['is_active'] == 0) ? 'Inactive' : (($row['stock'] == 0) ? 'Out of Stock' : (($row['stock'] <= $row['low_stock_threshold']) ? 'Low Stock' : 'Active'));
            echo $row['item_name']."\t".$row['category']."\t".$row['supplier_name']."\t".$row['stock']."\t".$itemStatus."\n";
        }
        exit();
    } elseif ($exportType === 'pdf') {
        require_once('../tcpdf/tcpdf.php');
        class MYPDF extends TCPDF {
            public function Header() {
                $this->SetFont('helvetica', 'B', 16);
                $this->Cell(0, 15, 'PLMun Supply Inventory System', 0, 1, 'C');
                $this->SetFont('helvetica', 'B', 12);
                $this->Cell(0, 10, 'Inventory List Report', 0, 1, 'C');
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
        $pdf->SetTitle('Inventory List Report');
        $pdf->SetMargins(15, 40, 15);
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 10);
        $html = '<p><strong>Generated on:</strong> '.date("Y-m-d H:i:s").'</p>
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color: #dff0d8; font-weight: bold;">
                    <th width="30%">Item</th>
                    <th width="20%">Category</th>
                    <th width="25%">Supplier</th>
                    <th width="10%">Stock</th>
                    <th width="15%">Status</th>
                </tr>
            </thead>
            <tbody>';
        while ($row = $exportResult->fetch_assoc()) {
            $itemStatus = ($row['is_active'] == 0) ? 'Inactive' : (($row['stock'] == 0) ? 'Out of Stock' : (($row['stock'] <= $row['low_stock_threshold']) ? 'Low Stock' : 'Active'));
            $html .= '<tr><td>'.$row['item_name'].'</td><td>'.$row['category'].'</td><td>'.$row['supplier_name'].'</td><td>'.$row['stock'].'</td><td>'.$itemStatus.'</td></tr>';
        }
        $html .= '</tbody></table>';
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('inventory_list.pdf', 'D');
        exit();
    }
}

// ================= ADD ITEM =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {

    $stmt = $conn->prepare("
        INSERT INTO items (supplier_name, item_name, category, stock, low_stock_threshold)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sssii",
        $_POST['supplier'],
        $_POST['item_name'],
        $_POST['category'],
        $_POST['quantity'],
        $_POST['threshold']
    );
    $stmt->execute();
    $stmt->close();

    header("Location: admin_inventory.php");
    exit();
}

// ================= UPDATE ITEM =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_item'])) {
    $item_id = intval($_POST['item_id']);
    $item_name = $_POST['item_name'];
    $category = $_POST['category'];
    $supplier = $_POST['supplier'];
    $stock = intval($_POST['stock']);
    $threshold = intval($_POST['threshold']);
    $status = intval($_POST['status']);

    $stmt = $conn->prepare("
        UPDATE items 
        SET item_name=?, category=?, supplier_name=?, stock=?, low_stock_threshold=?, is_active=? 
        WHERE item_id=?
    ");
    $stmt->bind_param("sssiiii", $item_name, $category, $supplier, $stock, $threshold, $status, $item_id);
    $stmt->execute();
    $stmt->close();

    // Log the update
    $log = $conn->prepare("INSERT INTO logs (user_id, action, remarks) VALUES (?, 'Item Updated', ?)");
    $remarks = "Updated item: $item_name (ID: $item_id)";
    $log->bind_param("is", $admin_id, $remarks);
    $log->execute();
    $log->close();

    header("Location: admin_inventory.php");
    exit();
}

// ================= DELETE ITEM =================
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    // Only allow delete if item is inactive (safety)
    $stmt = $conn->prepare("DELETE FROM items WHERE item_id=? AND is_active=0");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: admin_inventory.php");
    exit();
}

// ================= DEACTIVATE =================
if (isset($_GET['deactivate'])) {
    $id = intval($_GET['deactivate']);

    $conn->query("UPDATE items SET is_active=0 WHERE item_id=$id");

    header("Location: admin_inventory.php");
    exit();
}

// ================= ACTIVATE =================
if (isset($_GET['activate'])) {
    $id = intval($_GET['activate']);

    $conn->query("UPDATE items SET is_active=1 WHERE item_id=$id");

    header("Location: admin_inventory.php");
    exit();
}

// ================= FETCH ALL ITEMS =================
$query = "SELECT * FROM items WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (item_name LIKE ? OR supplier_name LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "ss";
}
if (!empty($category) && $category !== "Category") {
    $query .= " AND category = ?";
    $params[] = $category;
    $types .= "s";
}
if ($status === 'Active') {
    $query .= " AND is_active = 1 AND stock > low_stock_threshold";
} elseif ($status === 'Low Stock') {
    $query .= " AND is_active = 1 AND stock <= low_stock_threshold AND stock > 0";
} elseif ($status === 'Out of Stock') {
    $query .= " AND is_active = 1 AND stock = 0";
} elseif ($status === 'Inactive') {
    $query .= " AND is_active = 0";
}

$query .= " ORDER BY date_added DESC";
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$items = $stmt->get_result();

?>

<div class="d-flex">
<?php include_once("../includes/sidebar_admin.php"); ?>

<main class="flex-grow-1 p-4" style="margin-left: 250px; height: 100vh; overflow-y: auto;">
<h2 class="mb-4 text-success fw-bold">Inventory Management</h2>

<div class="container mb-4">
<div class="card border-1 shadow-sm p-3" style="border-radius:12px;">
    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="text-success fw-bold" style="font-size:15px">Add New Item</span>
        <div class="d-flex align-items-center justify-content-center rounded-2" style="width:34px;height:34px;background:#E6F1FB">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M2 2h12v12H2V2z" stroke="#185FA5" stroke-width="1.5"/>
                <path d="M4 8h8M8 4v8" stroke="#185FA5" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
        </div>
    </div>
<form method="POST" class="row g-3 align-items-center">
<div class="col-md-2">
<input type="text" name="item_name" class="form-control" placeholder="Item Name" required>
</div>
<div class="col-md-2">
<input type="text" name="category" class="form-control" placeholder="Category" required>
</div>
<div class="col-md-2">
<input type="text" name="supplier" class="form-control" placeholder="Supplier" required>
</div>
<div class="col-md-2">
<input type="number" name="quantity" class="form-control" placeholder="Stock" required>
</div>
<div class="col-md-2">
<input type="number" name="threshold" class="form-control" placeholder="Threshold" required value="5">
</div>
<div class="col-md-2">
<button type="submit" name="add_item" class="btn btn-success w-100">Add Item</button>
</div>
</form>
</div>
</div>

<div class="container mb-4">
<div class="card border-1 shadow-sm p-3" style="border-radius:12px;">
    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="text-success fw-bold" style="font-size:15px">Filter Inventory</span>
        <div class="d-flex align-items-center justify-content-center rounded-2" style="width:34px;height:34px;background:#E1F5EE">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M6 2a4 4 0 1 0 0 8 4 4 0 0 0 0-8z" stroke="#0F6E56" stroke-width="1.5"/>
                <path d="M11 11l4 4" stroke="#0F6E56" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
        </div>
    </div>
<form method="GET" class="row g-3 align-items-center">
<div class="col-md-3">
<input type="text" name="search" class="form-control" placeholder="Search item or supplier" value="<?php echo htmlspecialchars($search); ?>">
</div>
<div class="col-md-3">
<select name="category" class="form-select">
<option value="">Category</option>
<?php
$catRes = $conn->query("SELECT DISTINCT category FROM items");
while($cat = $catRes->fetch_assoc()) {
    $selected = ($category == $cat['category']) ? 'selected' : '';
    echo "<option value='".htmlspecialchars($cat['category'])."' $selected>".htmlspecialchars($cat['category'])."</option>";
}
?>
</select>
</div>
<div class="col-md-3">
<select name="status" class="form-select">
<option value="">Status</option>
<option value="Active" <?php echo ($status == 'Active') ? 'selected' : ''; ?>>Active</option>
<option value="Low Stock" <?php echo ($status == 'Low Stock') ? 'selected' : ''; ?>>Low Stock</option>
<option value="Out of Stock" <?php echo ($status == 'Out of Stock') ? 'selected' : ''; ?>>Out of Stock</option>
<option value="Inactive" <?php echo ($status == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
</select>
</div>
<div class="col-md-3">
<button type="submit" class="btn btn-success w-100">Apply Filter</button>
</div>
</form>
</div>
</div>

<!-- TABLE -->
<div class="container mb-4">
<div class="row g-3">
<div class="col-md-9 d-flex">
<div class="card border-1 shadow-sm p-3 flex-fill d-flex flex-column" style="border-radius:12px; height: 450px;">
    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="text-success fw-bold" style="font-size:15px">Inventory List</span>
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
<th>Item</th>
<th>Category</th>
<th>Supplier</th>
<th>Stock</th>
<th>Status</th>
<th>Actions</th>
</tr>
</thead>

<tbody>
<?php $count=1; while($item = $items->fetch_assoc()): ?>
<tr>

<td><?php echo $count++; ?></td>
<td><?php echo htmlspecialchars($item['item_name']); ?></td>
<td><?php echo htmlspecialchars($item['category']); ?></td>
<td><?php echo htmlspecialchars($item['supplier_name']); ?></td>
<td><?php echo $item['stock']; ?></td>

<td>
<?php if ($item['is_active'] == 0): ?>
<span class="badge bg-secondary">Inactive</span>
<?php elseif ($item['stock'] == 0): ?>
<span class="badge bg-danger">Out of Stock</span>
<?php elseif ($item['stock'] <= $item['low_stock_threshold']): ?>
<span class="badge bg-warning text-dark">Low Stock</span>
<?php else: ?>
<span class="badge bg-success">Active</span>
<?php endif; ?>
</td>

<td>

<?php if ($item['is_active'] == 1): ?>
<button class="btn btn-sm btn-primary edit-btn" 
        data-bs-toggle="modal" 
        data-bs-target="#editItemModal"
        data-id="<?php echo $item['item_id']; ?>"
        data-name="<?php echo htmlspecialchars($item['item_name']); ?>"
        data-category="<?php echo htmlspecialchars($item['category']); ?>"
        data-supplier="<?php echo htmlspecialchars($item['supplier_name']); ?>"
        data-stock="<?php echo $item['stock']; ?>"
        data-threshold="<?php echo $item['low_stock_threshold']; ?>"
        data-status="<?php echo $item['is_active']; ?>">
   Edit
</button>
<a href="?deactivate=<?php echo $item['item_id']; ?>" 
   class="btn btn-sm btn-warning"
   onclick="return confirm('Deactivate this item?')">
   Deactivate
</a>
<?php else: ?>
<a href="?activate=<?php echo $item['item_id']; ?>" 
   class="btn btn-sm btn-success"
   onclick="return confirm('Activate this item?')">
   Activate
</a>
<a href="?delete=<?php echo $item['item_id']; ?>" 
   class="btn btn-sm btn-danger"
   onclick="return confirm('Are you sure you want to PERMANENTLY DELETE this item? This action cannot be undone.')">
   Delete
</a>
<?php endif; ?>

</td>

</tr>
<?php endwhile; ?>
<?php if ($items->num_rows === 0): ?>
<tr><td colspan="7">No items found.</td></tr>
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
        <span class="text-success fw-bold" style="font-size:15px">Export Inventory</span>
        <div class="d-flex align-items-center justify-content-center rounded-2" style="width:34px;height:34px;background:#E1F5EE">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M3 2h10v12H3z" stroke="#0F6E56" stroke-width="1.5"/>
                <path d="M3 6h10M3 10h10" stroke="#0F6E56" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
        </div>
    </div>
<div class="px-2">
<a href="?export=pdf&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&status=<?php echo urlencode($status); ?>" class="btn btn-danger w-100 mb-3">Export PDF</a>
<a href="?export=excel&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&status=<?php echo urlencode($status); ?>" class="btn btn-success w-100 mb-3">Export Excel</a>
<a href="?export=csv&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&status=<?php echo urlencode($status); ?>" class="btn btn-primary w-100">Export CSV</a>
</div>
</div>
</div>
</div>
</div>



</main>
</div>

<!-- EDIT ITEM MODAL -->
<div class="modal fade" id="editItemModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="border-radius:12px;">
      <div class="modal-header">
        <h5 class="modal-title text-success fw-bold">Edit Item</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST">
        <div class="modal-body">
          <input type="hidden" name="item_id" id="edit_item_id">
          <div class="mb-3">
            <label class="form-label small fw-bold">Item Name</label>
            <input type="text" name="item_name" id="edit_item_name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">Category</label>
            <input type="text" name="category" id="edit_category" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">Supplier</label>
            <input type="text" name="supplier" id="edit_supplier" class="form-control" required>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label small fw-bold">Stock</label>
              <input type="number" name="stock" id="edit_stock" class="form-control" required>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label small fw-bold">Threshold</label>
              <input type="number" name="threshold" id="edit_threshold" class="form-control" required>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label small fw-bold">Status</label>
              <select name="status" id="edit_status" class="form-select">
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="update_item" class="btn btn-success">Update Item</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editBtns = document.querySelectorAll('.edit-btn');
    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_item_id').value = this.dataset.id;
            document.getElementById('edit_item_name').value = this.dataset.name;
            document.getElementById('edit_category').value = this.dataset.category;
            document.getElementById('edit_supplier').value = this.dataset.supplier;
            document.getElementById('edit_stock').value = this.dataset.stock;
            document.getElementById('edit_threshold').value = this.dataset.threshold;
            document.getElementById('edit_status').value = this.dataset.status;
        });
    });
});
</script>