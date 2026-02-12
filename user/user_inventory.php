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

include_once("../includes/header.php");
?>

<div class="d-flex">
<?php include_once("../includes/sidebar_user.php"); ?>

<main class="flex-grow-1 p-4" style="margin-left: 250px;">
<h2 class="mb-4 text-success fw-bold">Inventory</h2>

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
<option>Category</option>
<?php foreach ($categories as $cat): ?>
<option value="<?php echo htmlspecialchars($cat); ?>"
<?php if ($category === $cat) echo "selected"; ?>>
<?php echo htmlspecialchars($cat); ?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="col-md-4">
<select name="supplier" class="form-select">
<option>Supplier</option>
<?php foreach ($suppliers as $sup): ?>
<option value="<?php echo htmlspecialchars($sup); ?>"
<?php if ($supplier === $sup) echo "selected"; ?>>
<?php echo htmlspecialchars($sup); ?>
</option>
<?php endforeach; ?>
</select>
</div>

</form>
</div>
</div>

<div class="container mb-4">
<div class="card shadow-sm p-3">
<h5 class="text-success fw-bold mb-3">Available Items</h5>
<div class="table-responsive">
<table class="table table-bordered table-hover align-middle text-center">
<thead class="table-success">
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

</main>
</div>

<?php include_once("../includes/footer.php"); ?>
