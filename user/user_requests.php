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

// ================= CHECK USER STATUS =================
$statusStmt = $conn->prepare("SELECT status FROM users WHERE user_id = ?");
$statusStmt->bind_param("i", $user_id);
$statusStmt->execute();
$statusResult = $statusStmt->get_result()->fetch_assoc();
$statusStmt->close();

if ($statusResult['status'] !== 'Active') {
    die("Your account is inactive. You cannot submit requests.");
}

$message = "";

// ================= CANCEL REQUEST =================
if (isset($_GET['cancel'])) {
    $request_id = intval($_GET['cancel']);

    $checkStmt = $conn->prepare("SELECT status FROM requests WHERE request_id = ? AND user_id = ?");
    $checkStmt->bind_param("ii", $request_id, $user_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows === 1) {
        $row = $checkResult->fetch_assoc();
        if ($row['status'] === 'Pending') {

            $updateStmt = $conn->prepare("UPDATE requests SET status = 'Cancelled' WHERE request_id = ?");
            $updateStmt->bind_param("i", $request_id);
            $updateStmt->execute();

            $logStmt = $conn->prepare("INSERT INTO logs (request_id, user_id, action, remarks) VALUES (?, ?, 'Request Cancelled', 'User cancelled pending request')");
            $logStmt->bind_param("ii", $request_id, $user_id);
            $logStmt->execute();

            $message = "Request cancelled successfully.";
        }
    }
    $checkStmt->close();
}

// ================= SUBMIT NEW REQUEST =================
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $item_name = trim($_POST['item_name']);
    $urgency = trim($_POST['urgency']);
    $notes = trim($_POST['notes']);

    if (!empty($item_name) && !empty($urgency)) {

        // Find item
        $itemStmt = $conn->prepare("SELECT item_id FROM items WHERE item_name = ? AND is_active = 1");
        $itemStmt->bind_param("s", $item_name);
        $itemStmt->execute();
        $itemResult = $itemStmt->get_result();

        if ($itemResult->num_rows === 1) {

            $item = $itemResult->fetch_assoc();
            $item_id = $item['item_id'];

            $conn->begin_transaction();

            try {

                // Insert request header
                $reqStmt = $conn->prepare("INSERT INTO requests (user_id, status, remarks) VALUES (?, 'Pending', ?)");
                $reqStmt->bind_param("is", $user_id, $notes);
                $reqStmt->execute();
                $request_id = $reqStmt->insert_id;

                // Insert request item
                $reqItemStmt = $conn->prepare("INSERT INTO request_items (request_id, item_id, quantity, status) VALUES (?, ?, 1, 'Pending')");
                $reqItemStmt->bind_param("ii", $request_id, $item_id);
                $reqItemStmt->execute();

                // Log
                $logStmt = $conn->prepare("INSERT INTO logs (request_id, user_id, item_id, action, quantity, remarks) VALUES (?, ?, ?, 'Request Submitted', 1, ?)");
                $logStmt->bind_param("iiis", $request_id, $user_id, $item_id, $urgency);
                $logStmt->execute();

                $conn->commit();
                $message = "Request submitted successfully.";

            } catch (Exception $e) {
                $conn->rollback();
                $message = "Error submitting request.";
            }

        } else {
            $message = "Item not found in inventory.";
        }
        $itemStmt->close();
    }
}

// ================= FETCH USER REQUESTS =================
$requests = [];
$listStmt = $conn->prepare("
    SELECT r.request_id, r.status, r.request_date, i.item_name
    FROM requests r
    JOIN request_items ri ON r.request_id = ri.request_id
    JOIN items i ON ri.item_id = i.item_id
    WHERE r.user_id = ?
    ORDER BY r.request_date DESC
");
$listStmt->bind_param("i", $user_id);
$listStmt->execute();
$listResult = $listStmt->get_result();

while ($row = $listResult->fetch_assoc()) {
    $requests[] = $row;
}
$listStmt->close();

include_once("../includes/header.php");
?>

<div class="d-flex">
<?php include_once("../includes/sidebar_user.php"); ?>

<main class="flex-grow-1 p-4" style="margin-left: 250px;">
<h2 class="mb-4 text-success fw-bold">My Requests</h2>

<?php if (!empty($message)): ?>
<div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<div class="container mb-4">
<div class="card shadow-sm p-3">
<h5 class="text-success fw-bold mb-3">Track Your Requests</h5>
<div class="table-responsive">
<table class="table table-bordered table-hover align-middle text-center">
<thead class="table-success">
<tr>
<th>#</th>
<th>Request</th>
<th>Status</th>
<th>Submitted On</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php if (empty($requests)): ?>
<tr><td colspan="5">No requests found</td></tr>
<?php else: ?>
<?php foreach ($requests as $index => $req): ?>
<tr>
<td><?php echo $index + 1; ?></td>
<td><?php echo htmlspecialchars($req['item_name']); ?></td>
<td><?php echo htmlspecialchars($req['status']); ?></td>
<td><?php echo $req['request_date']; ?></td>
<td>
<?php if ($req['status'] === 'Pending'): ?>
<a href="?cancel=<?php echo $req['request_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Cancel this request?')">Cancel</a>
<?php else: ?>
<button class="btn btn-sm btn-secondary" disabled>Cancel</button>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
</div>

<div class="container mb-4">
<div class="card shadow-sm p-4">
<h5 class="text-success fw-bold mb-3">Submit New Request</h5>
<form method="POST">
<div class="col-md-12 mb-3">
<input type="text" name="item_name" class="form-control" placeholder="Item / Facility Name" required>
</div>

<div class="col-md-12 mb-3">
<select name="urgency" class="form-select" required>
<option value="">Urgency Level</option>
<option value="Low">Low</option>
<option value="Medium">Medium</option>
<option value="High">High</option>
</select>
</div>

<div class="col-md-12 mb-3">
<textarea name="notes" class="form-control" placeholder="Additional Notes (optional)"></textarea>
</div>

<div class="col-md-12">
<button type="submit" class="btn btn-success w-100">Submit Request</button>
</div>
</form>
</div>
</div>

</main>
</div>

<?php include_once("../includes/footer.php"); ?>
