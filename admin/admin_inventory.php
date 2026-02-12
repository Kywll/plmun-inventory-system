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

// ================= ADD NEW ITEM =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {

    $item_name = trim($_POST['item_name']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $supplier = trim($_POST['supplier']);
    $quantity = intval($_POST['quantity']);

    if (!empty($item_name) && $quantity >= 0) {

        $stmt = $conn->prepare("
            INSERT INTO items (supplier_name, item_name, category, stock)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("sssi", $supplier, $item_name, $category, $quantity);
        $stmt->execute();
        $item_id = $stmt->insert_id;
        $stmt->close();

        $log = $conn->prepare("
            INSERT INTO logs (user_id, item_id, action, quantity, remarks)
            VALUES (?, ?, 'Item Added', ?, ?)
        ");
        $log->bind_param("iiis", $admin_id, $item_id, $quantity, $description);
        $log->execute();
        $log->close();

        header("Location: admin_inventory.php");
        exit();
    }
}

// ================= DELETE ITEM =================
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    $stmt = $conn->prepare("DELETE FROM items WHERE item_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: admin_inventory.php");
    exit();
}

// ================= DEACTIVATE ITEM =================
if (isset($_GET['deactivate'])) {
    $id = intval($_GET['deactivate']);

    $stmt = $conn->prepare("UPDATE items SET is_active=0 WHERE item_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: admin_inventory.php");
    exit();
}

// ================= UPDATE ITEM =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_item'])) {

    $id = intval($_POST['item_id']);
    $item_name = trim($_POST['item_name']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $supplier = trim($_POST['supplier']);
    $quantity = intval($_POST['quantity']);

    $stmt = $conn->prepare("
        UPDATE items 
        SET supplier_name=?, item_name=?, category=?, stock=?
        WHERE item_id=?
    ");
    $stmt->bind_param("sssii", $supplier, $item_name, $category, $quantity, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: admin_inventory.php");
    exit();
}

// ================= SUMMARY DATA =================
$totalItems = $conn->query("SELECT COUNT(*) as total FROM items")->fetch_assoc()['total'];
$lowStockCount = $conn->query("SELECT COUNT(*) as total FROM items WHERE stock <= low_stock_threshold AND stock > 0")->fetch_assoc()['total'];
$outOfStock = $conn->query("SELECT COUNT(*) as total FROM items WHERE stock = 0")->fetch_assoc()['total'];
$newArrivals = $conn->query("SELECT COUNT(*) as total FROM items WHERE date_added >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['total'];

// ================= LOW STOCK LIST =================
$lowStockItems = $conn->query("SELECT item_name, stock FROM items WHERE stock <= low_stock_threshold AND is_active=1");

// ================= FETCH INVENTORY =================
$items = $conn->query("SELECT * FROM items ORDER BY date_added DESC");

include_once("../includes/header.php");
?>

<div class="d-flex">
<?php include_once("../includes/sidebar_admin.php"); ?>

<main class="flex-grow-1 p-4" style="margin-left: 250px;">
<h2 class="mb-4 text-success fw-bold">Inventory Management</h2>

<?php if ($lowStockItems->num_rows > 0): ?>
<div class="container mb-4">
<div class="alert alert-warning shadow-sm">
<strong>Low Stock Alert!</strong>
<ul class="mb-0">
<?php while ($row = $lowStockItems->fetch_assoc()): ?>
<li><?php echo htmlspecialchars($row['item_name']); ?> – <?php echo $row['stock']; ?> units remaining</li>
<?php endwhile; ?>
</ul>
</div>
</div>
<?php endif; ?>

<div class="container mb-4">
<div class="row g-3">
<div class="col-md-3">
<div class="card shadow-sm p-3 text-center">
<h5 class="text-success fw-bold">Total Items</h5>
<h3><?php echo $totalItems; ?></h3>
</div>
</div>
<div class="col-md-3">
<div class="card shadow-sm p-3 text-center">
<h5 class="text-success fw-bold">Low Stock</h5>
<h3><?php echo $lowStockCount; ?></h3>
</div>
</div>
<div class="col-md-3">
<div class="card shadow-sm p-3 text-center">
<h5 class="text-success fw-bold">Out of Stock</h5>
<h3><?php echo $outOfStock; ?></h3>
</div>
</div>
<div class="col-md-3">
<div class="card shadow-sm p-3 text-center">
<h5 class="text-success fw-bold">New Arrivals</h5>
<h3><?php echo $newArrivals; ?></h3>
</div>
</div>
</div>
</div>

<div class="container mb-4">
<div class="card shadow-sm p-3">
<h5 class="text-success fw-bold mb-3">Inventory List</h5>

<div class="table-responsive">
<table class="table table-bordered table-hover align-middle text-center">
<thead class="table-success">
<tr>
<th>#</th>
<th>Item Name</th>
<th>Category</th>
<th>Supplier</th>
<th>Quantity</th>
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
<?php if ($item['stock'] == 0): ?>
<span class="badge bg-danger">Out of Stock</span>
<?php elseif ($item['stock'] <= $item['low_stock_threshold']): ?>
<span class="badge bg-warning text-dark">Low Stock</span>
<?php else: ?>
<span class="badge bg-success">Active</span>
<?php endif; ?>
</td>
<td>
<a href="?deactivate=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-warning">Deactivate</a>
<a href="?delete=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-danger">Delete</a>
</td>
</tr>
<?php endwhile; ?>

</tbody>
</table>
</div>
</div>
</div>

<div class="container mb-4">
<div class="row g-3">

<div class="col-md-8">
<div class="card shadow-sm p-3">
<h5 class="text-success
