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

// ================= FETCH ACTIVE ITEMS FOR DROPDOWN =================
$activeItemsResult = $conn->query("SELECT item_id, item_name, category FROM items WHERE is_active = 1 ORDER BY item_name ASC");
$activeItems = [];
while ($row = $activeItemsResult->fetch_assoc()) {
    $activeItems[] = $row;
}

// ================= SUBMIT NEW REQUEST =================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_request'])) {

    $item_id = intval($_POST['item_id']);
    $quantity = intval($_POST['quantity']);
    $urgency = trim($_POST['urgency']);
    $notes = trim($_POST['notes']);

    if ($item_id > 0 && $quantity > 0 && !empty($urgency)) {

        // Find item to verify it exists and is active
        $itemStmt = $conn->prepare("SELECT item_name, stock FROM items WHERE item_id = ? AND is_active = 1");
        $itemStmt->bind_param("i", $item_id);
        $itemStmt->execute();
        $itemResult = $itemStmt->get_result();

        if ($itemResult->num_rows === 1) {

            $item = $itemResult->fetch_assoc();
            
            // Check if requested quantity is available
            if ($item['stock'] < $quantity) {
                $message = "Insufficient stock available for " . $item['item_name'] . ". Current stock: " . $item['stock'];
            } else {
                $conn->begin_transaction();

                try {
                    // Insert request header
                    $reqStmt = $conn->prepare("INSERT INTO requests (user_id, status, remarks) VALUES (?, 'Pending', ?)");
                    $reqStmt->bind_param("is", $user_id, $notes);
                    $reqStmt->execute();
                    $request_id = $reqStmt->insert_id;

                    // Insert request item
                    $reqItemStmt = $conn->prepare("INSERT INTO request_items (request_id, item_id, quantity, status) VALUES (?, ?, ?, 'Pending')");
                    $reqItemStmt->bind_param("iii", $request_id, $item_id, $quantity);
                    $reqItemStmt->execute();

                    // Log
                    $logStmt = $conn->prepare("INSERT INTO logs (request_id, user_id, item_id, action, quantity, remarks) VALUES (?, ?, ?, 'Request Submitted', ?, ?)");
                    $logStmt->bind_param("iiiis", $request_id, $user_id, $item_id, $quantity, $urgency);
                    $logStmt->execute();

                    $conn->commit();
                    $message = "Request submitted successfully for $quantity " . $item['item_name'] . "(s).";

                } catch (Exception $e) {
                    $conn->rollback();
                    $message = "Error submitting request.";
                }
            }

        } else {
            $message = "Selected item is no longer available.";
        }
        $itemStmt->close();
    } else {
        $message = "Please select an item, valid quantity, and urgency level.";
    }
}

// ================= FETCH USER REQUESTS =================
$statusFilter = $_GET['status'] ?? '';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

$query = "
    SELECT r.request_id, r.status, r.request_date, i.item_name, ri.quantity
    FROM requests r
    JOIN request_items ri ON r.request_id = ri.request_id
    JOIN items i ON ri.item_id = i.item_id
    WHERE r.user_id = ?
";

$params = [$user_id];
$types = "i";

if (!empty($statusFilter)) {
    $query .= " AND r.status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}
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

$listStmt = $conn->prepare($query);
$listStmt->bind_param($types, ...$params);
$listStmt->execute();
$listResult = $listStmt->get_result();

$requests = [];
while ($row = $listResult->fetch_assoc()) {
    $requests[] = $row;
}
$listStmt->close();

?>

<div class="d-flex">
<?php include_once("../includes/sidebar_user.php"); ?>

<main class="flex-grow-1 p-4" style="margin-left: 250px; height: 100vh; overflow-y: auto;">
<h2 class="mb-4 text-success fw-bold">My Requests</h2>

<?php if (!empty($message)): ?>
<div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<div class="container mb-4">
    <div class="row g-3">

        <!-- SUBMIT NEW REQUEST -->
        <div class="col-md-7 d-flex">
            <div class="card border-1 shadow-sm flex-fill" style="border-radius:12px; height:350px;">
                <div class="card-body p-4 d-flex flex-column">

                    <!-- HEADER -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-success fw-bold" style="font-size:15px">Submit New Request</span>
                        <div class="d-flex align-items-center justify-content-center rounded-2" style="width:34px;height:34px;background:#E1F5EE">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <path d="M2 2h12v12H2V2z" stroke="#0F6E56" stroke-width="1.5"/>
                                <path d="M4 8h8M8 4v8" stroke="#0F6E56" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                        </div>
                    </div>

                    <!-- FORM -->
                    <form method="POST" class="flex-fill d-flex flex-column">
                        <div class="row g-2 mb-3">
                            <div class="col-8">
                                <select name="item_id" class="form-select" required>
                                    <option value="">Select Item / Facility</option>
                                    <?php foreach ($activeItems as $item): ?>
                                        <option value="<?php echo $item['item_id']; ?>">
                                            <?php echo htmlspecialchars($item['item_name']); ?> 
                                            (<?php echo htmlspecialchars($item['category']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-4">
                                <input type="number" name="quantity" class="form-control" placeholder="Qty" min="1" required>
                            </div>
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

                        <div class="col-md-12 mt-auto">
                            <button type="submit" name="submit_request" class="btn btn-success w-100">Submit Request</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>

        <!-- FILTER REQUESTS -->
        <div class="col-md-5 d-flex">
            <div class="card border-1 shadow-sm flex-fill" style="border-radius:12px; height:350px;">
                <div class="card-body p-4 d-flex flex-column">

                    <!-- HEADER -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-success fw-bold" style="font-size:15px">Filter Requests</span>
                        <div class="d-flex align-items-center justify-content-center rounded-2" style="width:34px;height:34px;background:#E1F5EE">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <path d="M2 4h12M6 8h4M4 12h8" stroke="#0F6E56" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                        </div>
                    </div>

                    <!-- FORM -->
                    <form method="GET" class="flex-fill d-flex flex-column">
                        <div class="mb-2">
                            <select name="status" class="form-select">
                                <option value="">Status</option>
                                <option value="Pending" <?php if($statusFilter == 'Pending') echo 'selected'; ?>>Pending</option>
                                <option value="Approved" <?php if($statusFilter == 'Approved') echo 'selected'; ?>>Approved</option>
                                <option value="Declined" <?php if($statusFilter == 'Declined') echo 'selected'; ?>>Declined</option>
                                <option value="Cancelled" <?php if($statusFilter == 'Cancelled') echo 'selected'; ?>>Cancelled</option>
                                <option value="Completed" <?php if($statusFilter == 'Completed') echo 'selected'; ?>>Completed</option>
                            </select>
                        </div>

                        <div class="mb-2">
                            <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($startDate); ?>">
                        </div>
                        <div class="mb-2">
                            <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($endDate); ?>">
                        </div>

                        <div class="mt-auto">
                            <button type="submit" class="btn btn-success w-100 mt-4">Apply Filter</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>

    </div>
</div>

<!-- TRACK YOUR REQUESTS -->
<div class="container mb-4">
    <div class="row g-3">

        <div class="col-md-12 d-flex">
            <div class="card border-1 shadow-sm flex-fill" style="border-radius:12px;">
                
                <div class="card-body p-3 d-flex flex-column">

                    <!-- HEADER -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-success fw-bold" style="font-size:15px">Track Your Requests</span>
                        <div class="d-flex align-items-center justify-content-center rounded-2" style="width:34px;height:34px;background:#E1F5EE">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <rect x="3" y="1.5" width="10" height="13" rx="1.5" stroke="#0F6E56" stroke-width="1.5"/>
                                <path d="M5.5 5.5h5M5.5 8h5M5.5 10.5h3" stroke="#0F6E56" stroke-width="1.3" stroke-linecap="round"/>
                            </svg>
                        </div>
                    </div>

                    <!-- TABLE -->
                    <div style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm table-bordered table-hover align-middle text-center mb-0">
                            <thead class="table-success" style="position: sticky; top: 0; z-index: 1;">
                                <tr>
                                    <th>#</th>
                                    <th>Request</th>
                                    <th>Quantity</th>
                                    <th>Status</th>
                                    <th>Submitted On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($requests)): ?>
                                    <tr><td colspan="6">No requests found</td></tr>
                                <?php else: ?>
                                    <?php foreach ($requests as $index => $req): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($req['item_name']); ?></td>
                                            <td class="fw-bold text-success"><?php echo htmlspecialchars($req['quantity']); ?></td>
                                            <td>
                                                <?php
                                                $status = $req['status'];
                                                $badgeClass = 'bg-info';
                                                if ($status === 'Pending') $badgeClass = 'bg-warning text-dark';
                                                elseif ($status === 'Approved') $badgeClass = 'bg-success';
                                                elseif ($status === 'Declined') $badgeClass = 'bg-danger';
                                                elseif ($status === 'Cancelled') $badgeClass = 'bg-secondary';
                                                elseif ($status === 'Completed') $badgeClass = 'bg-primary';
                                                ?>
                                                <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($status); ?></span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($req['request_date'])); ?></td>
                                            <td>
                                                <?php if ($req['status'] === 'Pending'): ?>
                                                    <a href="?cancel=<?php echo $req['request_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Cancel this request?')">Cancel</a>
                                                <?php elseif ($req['status'] === 'Approved'): ?>
                                                    <div class="text-success small fw-bold">Ready for Pickup</div>
                                                    <div class="text-muted" style="font-size:10px;">Proceed to Inventory Room</div>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-secondary" disabled>—</button>
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
        </div>

    </div>
</div>


</main>
</div>
