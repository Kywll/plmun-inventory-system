<?php
session_start();
require_once("../includes/db_connect.php");

// ================= SESSION SECURITY =================
if (!isset($_SESSION['user_id']) || $_SESSION['position'] !== 'Staff') {
    header("Location: ../login.php");
    exit();
}

// Auto logout after 15 minutes
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) {
    session_unset();
    session_destroy();
    header("Location: ../login.php");
    exit();
}
$_SESSION['last_activity'] = time();

$user_id = $_SESSION['user_id'];

// ================= REQUEST COUNTS =================
$pending = 0;
$approved = 0;
$declined = 0;

$countStmt = $conn->prepare("
    SELECT status, COUNT(*) as total 
    FROM requests 
    WHERE user_id = ? 
    GROUP BY status
");
$countStmt->bind_param("i", $user_id);
$countStmt->execute();
$countResult = $countStmt->get_result();

while ($row = $countResult->fetch_assoc()) {
    if ($row['status'] === 'Pending') $pending = $row['total'];
    if ($row['status'] === 'Approved') $approved = $row['total'];
    if ($row['status'] === 'Declined') $declined = $row['total'];
}
$countStmt->close();

// ================= RECENT REQUEST =================
$recentRequests = [];
$recentStmt = $conn->prepare("
    SELECT r.request_id, r.status, r.request_date, i.item_name
    FROM requests r
    JOIN request_items ri ON r.request_id = ri.request_id
    JOIN items i ON ri.item_id = i.item_id
    WHERE r.user_id = ?
    ORDER BY r.request_date DESC
    LIMIT 5
");
$recentStmt->bind_param("i", $user_id);
$recentStmt->execute();
$recentResult = $recentStmt->get_result();

while ($row = $recentResult->fetch_assoc()) {
    $recentRequests[] = $row;
}
$recentStmt->close();

// ================= INVENTORY SUMMARY =================
$inventoryItems = [];
$inventoryStmt = $conn->prepare("
    SELECT item_name, stock, low_stock_threshold
    FROM items
    WHERE is_active = 1
    ORDER BY stock ASC
    LIMIT 5
");
$inventoryStmt->execute();
$inventoryResult = $inventoryStmt->get_result();

while ($row = $inventoryResult->fetch_assoc()) {
    $inventoryItems[] = $row;
}
$inventoryStmt->close();

// ================= ACTIVITY LOG =================
$activities = [];
$logStmt = $conn->prepare("
    SELECT action, timestamp 
    FROM logs 
    WHERE user_id = ?
    ORDER BY timestamp DESC
    LIMIT 5
");
$logStmt->bind_param("i", $user_id);
$logStmt->execute();
$logResult = $logStmt->get_result();

while ($row = $logResult->fetch_assoc()) {
    $activities[] = $row;
}
$logStmt->close();

include_once("../includes/header.php");
?>

<div class="d-flex">
    <?php include_once("../includes/sidebar_user.php"); ?>

    <main class="flex-grow-1 p-4" style="margin-left: 250px;">
        <h2 class="mb-4 text-success fw-bold">User Dashboard</h2>

        <!-- SUMMARY CARDS -->
        <div class="container mb-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="card shadow-sm p-3 text-center">
                        <h5 class="text-success fw-bold">My Pending</h5>
                        <h3><?php echo $pending; ?></h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm p-3 text-center">
                        <h5 class="text-success fw-bold">Approved</h5>
                        <h3><?php echo $approved; ?></h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm p-3 text-center">
                        <h5 class="text-success fw-bold">Declined</h5>
                        <h3><?php echo $declined; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- RECENT REQUEST -->
        <div class="container mb-4">
            <div class="row g-3">
                <div class="col-md-12">
                    <div class="card shadow-sm p-3" style="height: 150px">
                        <h5 class="text-success fw-bold text-start">My Recent Request</h5>
                        <table class="table table-sm table-bordered text-center align-middle mt-2">
                            <thead class="table-success">
                                <tr>
                                    <th>Item</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentRequests)): ?>
                                    <tr><td colspan="3">No recent requests</td></tr>
                                <?php else: ?>
                                    <?php foreach ($recentRequests as $req): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($req['item_name']); ?></td>
                                            <td><?php echo htmlspecialchars($req['status']); ?></td>
                                            <td><?php echo date("M d, Y", strtotime($req['request_date'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- AVAILABLE ITEMS + ACTIVITY LOG -->
        <div class="container mb-4">
            <div class="row g-3">

                <!-- AVAILABLE ITEMS -->
                <div class="col-md-6">
                    <div class="card shadow-sm p-3" style="height: 200px;">
                        <h5 class="text-success fw-bold text-start">Available Items</h5>
                        <table class="table table-sm table-hover text-center mt-2">
                            <thead class="table-success">
                                <tr>
                                    <th>Item</th>
                                    <th>Available</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inventoryItems as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                        <td>
                                            <?php
                                            if ($item['stock'] <= 0) {
                                                echo "Out of Stock";
                                            } elseif ($item['stock'] <= $item['low_stock_threshold']) {
                                                echo "Low";
                                            } else {
                                                echo $item['stock'];
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ACTIVITY LOG -->
                <div class="col-md-6">
                    <div class="card shadow-sm p-3" style="height: 200px;">
                        <h5 class="text-success fw-bold text-start">Activity Log</h5>
                        <table class="table table-sm table-bordered text-center mt-2">
                            <thead class="table-success">
                                <tr>
                                    <th>Activity</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($activities)): ?>
                                    <tr><td colspan="2">No activity yet</td></tr>
                                <?php else: ?>
                                    <?php foreach ($activities as $act): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($act['action']); ?></td>
                                            <td><?php echo date("M d, Y h:i A", strtotime($act['timestamp'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </main>
</div>

<?php include_once("../includes/footer.php"); ?>
