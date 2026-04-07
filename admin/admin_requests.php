<?php
session_start();
require_once("../includes/db_connect.php");
require_once("../includes/email_helper.php");

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

// ================= HANDLE APPROVE =================
if (isset($_GET['approve'])) {

    $request_item_id = intval($_GET['approve']);

    $conn->begin_transaction();

    $stmt = $conn->prepare("
        SELECT ri.request_id, ri.item_id, ri.quantity, u.email, u.first_name, u.notify_approval, i.item_name
        FROM request_items ri
        JOIN requests r ON ri.request_id = r.request_id
        JOIN users u ON r.user_id = u.user_id
        JOIN items i ON ri.item_id = i.item_id
        WHERE ri.request_item_id=?
    ");
    $stmt->bind_param("i", $request_item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();

    if ($data) {

        $update = $conn->prepare("
            UPDATE request_items 
            SET status='Fulfilled' 
            WHERE request_item_id=?
        ");
        $update->bind_param("i", $request_item_id);
        $update->execute();
        $update->close();

        $updateHeader = $conn->prepare("
            UPDATE requests 
            SET status='Approved' 
            WHERE request_id=?
        ");
        $updateHeader->bind_param("i", $data['request_id']);
        $updateHeader->execute();
        $updateHeader->close();

        // Send Email Notification
        if ($data['notify_approval'] == 1 && !empty($data['email'])) {
            $subject = "Your Request for " . $data['item_name'] . " has been Approved!";
            $action_message = "Your request has been accepted. You can now proceed to the <strong>Inventory and Facility Room</strong> to pick up your item.";
            $email_body = get_status_email_template($data['first_name'], $data['item_name'], 'Approved', $action_message);
            send_notification_email($data['email'], $subject, $email_body);
        }

        $log = $conn->prepare("
            INSERT INTO logs (user_id, request_id, item_id, action, quantity, remarks)
            VALUES (?, ?, ?, 'Request Approved', ?, 'Approved by Admin')
        ");
        $log->bind_param("iiii",
            $admin_id,
            $data['request_id'],
            $data['item_id'],
            $data['quantity']
        );
        $log->execute();
        $log->close();

        $conn->commit();
    }

    header("Location: admin_requests.php");
    exit();
}

// ================= HANDLE COMPLETE =================
if (isset($_GET['complete'])) {

    $request_item_id = intval($_GET['complete']);

    $conn->begin_transaction();

    // Fetch details and current stock
    $stmt = $conn->prepare("
        SELECT ri.request_id, ri.item_id, ri.quantity, i.stock, i.item_name, u.email, u.first_name, u.notify_approval
        FROM request_items ri
        JOIN items i ON ri.item_id = i.item_id
        JOIN requests r ON ri.request_id = r.request_id
        JOIN users u ON r.user_id = u.user_id
        WHERE ri.request_item_id=?
    ");
    $stmt->bind_param("i", $request_item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();

    if ($data) {
        if ($data['stock'] >= $data['quantity']) {
            // 1. Update statuses to Completed
            $updateItem = $conn->prepare("UPDATE request_items SET status='Completed' WHERE request_item_id=?");
            $updateItem->bind_param("i", $request_item_id);
            $updateItem->execute();
            $updateItem->close();

            $updateHeader = $conn->prepare("UPDATE requests SET status='Completed' WHERE request_id=?");
            $updateHeader->bind_param("i", $data['request_id']);
            $updateHeader->execute();
            $updateHeader->close();

            // 2. Deduct stock
            $deduct = $conn->prepare("UPDATE items SET stock = stock - ? WHERE item_id=?");
            $deduct->bind_param("ii", $data['quantity'], $data['item_id']);
            $deduct->execute();
            $deduct->close();

            // Send Email Notification
            if ($data['notify_approval'] == 1 && !empty($data['email'])) {
                $subject = "Your Request for " . $data['item_name'] . " is now Completed";
                $action_message = "Your item has been successfully issued. The transaction is now closed. Thank you!";
                $email_body = get_status_email_template($data['first_name'], $data['item_name'], 'Completed', $action_message);
                send_notification_email($data['email'], $subject, $email_body);
            }

            // 3. Log activity
            $log = $conn->prepare("
                INSERT INTO logs (user_id, request_id, item_id, action, quantity, remarks)
                VALUES (?, ?, ?, 'Request Completed', ?, ?)
            ");
            $remarks = "Item '" . $data['item_name'] . "' issued. Stock deducted.";
            $log->bind_param("iiiis",
                $admin_id,
                $data['request_id'],
                $data['item_id'],
                $data['quantity'],
                $remarks
            );
            $log->execute();
            $log->close();

            $conn->commit();
            $_SESSION['msg'] = "<div class='alert alert-success shadow-sm' style='border-radius:12px;'>Request finalized. Stock deducted.</div>";
        } else {
            $conn->rollback();
            $_SESSION['msg'] = "<div class='alert alert-danger shadow-sm' style='border-radius:12px;'>Insufficient stock to complete this request.</div>";
        }
    }

    header("Location: admin_requests.php");
    exit();
}

// ================= HANDLE DECLINE =================
if (isset($_GET['decline'])) {

    $request_item_id = intval($_GET['decline']);

    $stmt = $conn->prepare("
        SELECT ri.request_id, ri.item_id, ri.quantity, u.email, u.first_name, u.notify_approval, i.item_name
        FROM request_items ri
        JOIN requests r ON ri.request_id = r.request_id
        JOIN users u ON r.user_id = u.user_id
        JOIN items i ON ri.item_id = i.item_id
        WHERE ri.request_item_id=?
    ");
    $stmt->bind_param("i", $request_item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();

    if ($data) {

        $update = $conn->prepare("
            UPDATE request_items 
            SET status='Denied' 
            WHERE request_item_id=?
        ");
        $update->bind_param("i", $request_item_id);
        $update->execute();
        $update->close();

        $updateHeader = $conn->prepare("
            UPDATE requests 
            SET status='Declined' 
            WHERE request_id=?
        ");
        $updateHeader->bind_param("i", $data['request_id']);
        $updateHeader->execute();
        $updateHeader->close();

        // Send Email Notification
        if ($data['notify_approval'] == 1 && !empty($data['email'])) {
            $subject = "Your Request for " . $data['item_name'] . " has been Declined";
            $action_message = "We regret to inform you that your request has been declined. Please contact the administrator for more details.";
            $email_body = get_status_email_template($data['first_name'], $data['item_name'], 'Declined', $action_message);
            send_notification_email($data['email'], $subject, $email_body);
        }

        $log = $conn->prepare("
            INSERT INTO logs (user_id, request_id, item_id, action, quantity, remarks)
            VALUES (?, ?, ?, 'Request Declined', ?, 'Declined by Admin')
        ");
        $log->bind_param("iiii",
            $admin_id,
            $data['request_id'],
            $data['item_id'],
            $data['quantity']
        );
        $log->execute();
        $log->close();
    }

    header("Location: admin_requests.php");
    exit();
}

// ================= FILTERS =================
$statusFilter = $_GET['status'] ?? '';
$departmentFilter = $_GET['department'] ?? '';
$dateFilter = $_GET['date'] ?? '';

// Fetch unique departments for the dropdown
$deptsResult = $conn->query("SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != '' ORDER BY department ASC");
$departments = [];
while ($row = $deptsResult->fetch_assoc()) {
    $departments[] = $row['department'];
}

$query = "
SELECT 
    request_items.request_item_id,
    users.first_name,
    users.last_name,
    users.department,
    items.item_name,
    request_items.quantity,
    requests.status as request_status,
    request_items.status as item_status,
    requests.request_date,
    items.stock
FROM request_items
JOIN requests ON request_items.request_id = requests.request_id
JOIN users ON requests.user_id = users.user_id
JOIN items ON request_items.item_id = items.item_id
WHERE 1=1
";

$params = [];
$types = "";

if (!empty($statusFilter) && $statusFilter !== "Status") {
    $query .= " AND requests.status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

if (!empty($departmentFilter) && $departmentFilter !== "Department") {
    $query .= " AND users.department = ?";
    $params[] = $departmentFilter;
    $types .= "s";
}

if (!empty($dateFilter)) {
    $query .= " AND DATE(requests.request_date) = ?";
    $params[] = $dateFilter;
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
<h2 class="mb-4 text-success fw-bold">Request Management</h2>

<?php 
if(isset($_SESSION['msg'])) {
    echo $_SESSION['msg'];
    unset($_SESSION['msg']);
}
?>

<div class="container mb-4">
<div class="card border-1 shadow-sm p-3" style="border-radius:12px;">
    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="text-success fw-bold" style="font-size:15px">Filter Requests</span>
        <div class="d-flex align-items-center justify-content-center rounded-2" style="width:34px;height:34px;background:#E1F5EE">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M6 2a4 4 0 1 0 0 8 4 4 0 0 0 0-8z" stroke="#0F6E56" stroke-width="1.5"/>
                <path d="M11 11l4 4" stroke="#0F6E56" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
        </div>
    </div>
<form method="GET" class="row g-3 align-items-center">
<div class="col-md-3">
<select name="status" class="form-select">
<option value="">Status</option>
<option value="Pending" <?php if($statusFilter == 'Pending') echo 'selected'; ?>>Pending</option>
<option value="Approved" <?php if($statusFilter == 'Approved') echo 'selected'; ?>>Approved</option>
<option value="Completed" <?php if($statusFilter == 'Completed') echo 'selected'; ?>>Completed</option>
<option value="Declined" <?php if($statusFilter == 'Declined') echo 'selected'; ?>>Declined</option>
</select>
</div>
<div class="col-md-3">
<select name="department" class="form-select">
<option value="">Department</option>
<?php foreach ($departments as $dept): ?>
<option value="<?php echo htmlspecialchars($dept); ?>" <?php if($departmentFilter == $dept) echo 'selected'; ?>>
    <?php echo htmlspecialchars($dept); ?>
</option>
<?php endforeach; ?>
</select>
</div>
<div class="col-md-3">
<input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($dateFilter); ?>">
</div>
<div class="col-md-3">
<button type="submit" class="btn btn-success w-100">Apply Filter</button>
</div>
</form>
</div>
</div>

<div class="container mb-4">
<div class="card border-1 shadow-sm p-3" style="border-radius:12px;">
    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="text-success fw-bold" style="font-size:15px">Incoming Requests</span>
        <div class="d-flex align-items-center justify-content-center rounded-2" style="width:34px;height:34px;background:#E1F5EE">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <rect x="3" y="1.5" width="10" height="13" rx="1.5" stroke="#0F6E56" stroke-width="1.5"/>
                <path d="M5.5 5.5h5M5.5 8h5M5.5 10.5h3" stroke="#0F6E56" stroke-width="1.3" stroke-linecap="round"/>
            </svg>
        </div>
    </div>

<div style="max-height: 400px; overflow-y: auto;">
<table class="table table-sm table-bordered table-hover align-middle text-center mb-0">
<thead class="table-success" style="position: sticky; top: 0; z-index: 1;">
<tr>
<th>#</th>
<th>Requested By</th>
<th>Item</th>
<th>Quantity</th>
<th>Department</th>
<th>Status</th>
<th>Stock Check</th>
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
<td><?php echo htmlspecialchars($row['first_name']." ".$row['last_name']); ?></td>
<td><?php echo htmlspecialchars($row['item_name']); ?></td>
<td><?php echo $row['quantity']; ?></td>
<td><?php echo htmlspecialchars($row['department']); ?></td>

<td>
<?php 
$status = $row['request_status'];
if ($status === 'Pending'): ?>
<span class="badge bg-warning text-dark">Pending</span>
<?php elseif ($status === 'Approved'): ?>
<span class="badge bg-success">Approved</span>
<?php elseif ($status === 'Completed'): ?>
<span class="badge bg-primary">Completed</span>
<?php elseif ($status === 'Declined'): ?>
<span class="badge bg-danger">Declined</span>
<?php endif; ?>
</td>

<td>
<?php if ($row['stock'] >= $row['quantity']): ?>
<span class="badge bg-success">In Stock</span>
<?php else: ?>
<span class="badge bg-danger">Low Stock</span>
<?php endif; ?>
</td>

<td>
<?php if ($status === 'Pending'): ?>
<a href="?approve=<?php echo $row['request_item_id']; ?>" class="btn btn-sm btn-success">Approve</a>
<a href="?decline=<?php echo $row['request_item_id']; ?>" class="btn btn-sm btn-danger">Decline</a>
<?php elseif ($status === 'Approved'): ?>
<a href="?complete=<?php echo $row['request_item_id']; ?>" class="btn btn-sm btn-primary" onclick="return confirm('Mark this request as Completed? Stock will be deducted.')">Complete</a>
<?php elseif ($status === 'Completed'): ?>
<span class="badge bg-primary">Completed</span>
<?php else: ?>
—
<?php endif; ?>
</td>
</tr>
<?php endwhile; ?>

<?php if ($result->num_rows === 0): ?>
<tr><td colspan="8">No requests found.</td></tr>
<?php endif; ?>

</tbody>
</table>
</div>
</div>
</div>



</main>
</div>

<?php
$stmt->close();
$conn->close();
?>
