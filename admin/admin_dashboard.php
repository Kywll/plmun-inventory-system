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

    // ================= SUMMARY COUNTS =================
    $totalUsers = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
    $pendingRequests = $conn->query("SELECT COUNT(*) as total FROM requests WHERE status='Pending'")->fetch_assoc()['total'];
    $lowStock = $conn->query("SELECT COUNT(*) as total FROM items WHERE stock <= low_stock_threshold AND is_active=1")->fetch_assoc()['total'];
    $totalReports = $conn->query("SELECT COUNT(*) as total FROM requests")->fetch_assoc()['total'];

    // ================= LATEST REQUEST =================
    $latestRequest = $conn->query("
        SELECT r.request_id, r.status, r.request_date, u.first_name, u.last_name
        FROM requests r
        JOIN users u ON r.user_id = u.user_id
        ORDER BY r.request_date DESC
        LIMIT 1
    ")->fetch_assoc();

    // ================= URGENT REQUEST (Pending only) =================
    $urgentRequests = $conn->query("
        SELECT r.request_id, r.request_date, u.first_name, u.last_name
        FROM requests r
        JOIN users u ON r.user_id = u.user_id
        WHERE r.status='Pending'
        ORDER BY r.request_date ASC
        LIMIT 5
    ");
    $urgentList = [];
    while ($row = $urgentRequests->fetch_assoc()) {
        $urgentList[] = $row;
    }

    // ================= TOP REQUESTED ITEMS =================
    $topItems = $conn->query("
        SELECT i.item_name, SUM(ri.quantity) as total_requested
        FROM request_items ri
        JOIN items i ON ri.item_id = i.item_id
        GROUP BY ri.item_id
        ORDER BY total_requested DESC
        LIMIT 5
    ");
    $topRequested = [];
    while ($row = $topItems->fetch_assoc()) {
        $topRequested[] = $row;
    }

    // ================= MONTHLY REQUEST TREND =================
    $monthlyTrend = $conn->query("
        SELECT DATE_FORMAT(request_date, '%Y-%m') as month, COUNT(*) as total
        FROM requests
        GROUP BY month
        ORDER BY month ASC
        LIMIT 6
    ");
    $trendLabels = [];
    $trendTotals = [];
    while ($row = $monthlyTrend->fetch_assoc()) {
        $trendLabels[] = $row['month'];
        $trendTotals[] = $row['total'];
    }

    // ================= ACTIVITY LOGS =================
    $logs = $conn->query("
        SELECT l.action, l.timestamp, u.first_name, u.last_name
        FROM logs l
        JOIN users u ON l.user_id = u.user_id
        ORDER BY l.timestamp DESC
        LIMIT 10
    ");
    $activityLogs = [];
    while ($row = $logs->fetch_assoc()) {
        $activityLogs[] = $row;
    }
    ?>

    <div class="d-flex">
    <?php include_once("../includes/sidebar_admin.php"); ?>

    <main class="flex-grow-1 p-4" style="margin-left: 250px;">
    <h2 class="mb-4 text-success fw-bold">Admin Dashboard</h2>

   <!-- ===== SUMMARY CARDS ===== -->
<div class="container mb-4">
  <div class="row g-3">

    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm h-100" style="border-radius:12px">
        <div class="card-body p-3">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-success fw-bold" style="font-size:13px">Total Users</span>
            <div class="d-flex align-items-center justify-content-center rounded-2" style="width:34px;height:34px;background:#E6F1FB">
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <circle cx="6" cy="5" r="2.5" stroke="#185FA5" stroke-width="1.5"/>
                <path d="M1.5 13.5c0-2.485 2.015-4.5 4.5-4.5s4.5 2.015 4.5 4.5" stroke="#185FA5" stroke-width="1.5" stroke-linecap="round"/>
                <circle cx="11.5" cy="5.5" r="2" stroke="#185FA5" stroke-width="1.3"/>
                <path d="M14 13c0-1.657-1.12-3.07-2.672-3.43" stroke="#185FA5" stroke-width="1.3" stroke-linecap="round"/>
              </svg>
            </div>
          </div>
          <div class="fw-medium lh-1 mb-1" style="font-size:28px"><?php echo $totalUsers; ?></div>
          <div class="text-secondary" style="font-size:12px">Registered accounts</div>
        </div>
      </div>
    </div>

    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm h-100" style="border-radius:12px">
        <div class="card-body p-3">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <span  class="text-success fw-bold" style="font-size:13px">Pending Requests</span>
            <div class="d-flex align-items-center justify-content-center rounded-2" style="width:34px;height:34px;background:#FAEEDA">
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <rect x="2" y="2" width="12" height="12" rx="2" stroke="#BA7517" stroke-width="1.5"/>
                <path d="M8 5v3.5l2 2" stroke="#BA7517" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
          </div>
          <div class="fw-medium lh-1 mb-1" style="font-size:28px"><?php echo $pendingRequests; ?></div>
          <div class="text-secondary" style="font-size:12px">Awaiting review</div>
        </div>
      </div>
    </div>

    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm h-100" style="border-radius:12px">
        <div class="card-body p-3">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <span  class="text-success fw-bold" style="font-size:13px">Low Stock</span>
            <div class="d-flex align-items-center justify-content-center rounded-2" style="width:34px;height:34px;background:#FCEBEB">
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M8 2L2 5v6l6 3 6-3V5L8 2z" stroke="#A32D2D" stroke-width="1.5" stroke-linejoin="round"/>
                <path d="M8 2v9M2 5l6 3 6-3" stroke="#A32D2D" stroke-width="1.5" stroke-linejoin="round"/>
                <path d="M8 10.5v2" stroke="#A32D2D" stroke-width="1.5" stroke-linecap="round"/>
              </svg>
            </div>
          </div>
          <div class="fw-medium lh-1 mb-1" style="font-size:28px"><?php echo $lowStock; ?></div>
          <div class="text-secondary" style="font-size:12px">Items need restocking</div>
        </div>
      </div>
    </div>

    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm h-100" style="border-radius:12px">
        <div class="card-body p-3">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <span  class="text-success fw-bold" style="font-size:13px">Reports</span>
            <div class="d-flex align-items-center justify-content-center rounded-2" style="width:34px;height:34px;background:#E1F5EE">
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <rect x="3" y="1.5" width="10" height="13" rx="1.5" stroke="#0F6E56" stroke-width="1.5"/>
                <path d="M5.5 5.5h5M5.5 8h5M5.5 10.5h3" stroke="#0F6E56" stroke-width="1.3" stroke-linecap="round"/>
              </svg>
            </div>
          </div>
          <div class="fw-medium lh-1 mb-1" style="font-size:28px"><?php echo $totalReports; ?></div>
          <div class="text-secondary" style="font-size:12px">Generated this month</div>
        </div>
      </div>
    </div>

  </div>
</div>

    <!-- ===== LATEST REQUEST ===== -->
    <div class="container mb-4">
        <div class="row g-3">
            <div class="col-md-12">
                <div class="card shadow-sm p-3" style="max-height: 200px; overflow-y: auto;">
                    <h5 class="text-success fw-bold text-start">Latest Request</h5>
                    <table class="table table-sm table-bordered text-center mt-2">
                        <thead class="table-success">
                            <tr>
                                <th>Requested By</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($latestRequest): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($latestRequest['first_name'] . " " . $latestRequest['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($latestRequest['status']); ?></td>
                                    <td><?php echo date("M d, Y", strtotime($latestRequest['request_date'])); ?></td>
                                </tr>
                            <?php else: ?>
                                <tr><td colspan="3">No requests available.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

  <div class="container mb-4">
    <div class="row g-3 align-items-stretch">

        <!-- URGENT REQUESTS -->
        <div class="col-md-6 d-flex">
            <div class="card shadow-sm p-3 flex-fill d-flex flex-column" style="overflow-y: auto;">
                <h5 class="text-success fw-bold text-start">Urgent Requests</h5>
                <table class="table table-sm table-bordered text-center mt-2 mb-0">
                    <thead class="table-success">
                        <tr>
                            <th>Requested By</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($urgentList)): ?>
                            <?php foreach ($urgentList as $urgent): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($urgent['first_name'] . " " . $urgent['last_name']); ?></td>
                                    <td><?php echo date("M d, Y", strtotime($urgent['request_date'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="2">No urgent requests.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TOP REQUESTED ITEMS -->
        <div class="col-md-6 d-flex">
            <div class="card shadow-sm p-3 flex-fill d-flex flex-column" style="overflow-y: auto;">
                <h5 class="text-success fw-bold text-start">Top Requested Items</h5>
                <table class="table table-sm table-bordered text-center mt-2 mb-0">
                    <thead class="table-success">
                        <tr>
                            <th>Item Name</th>
                            <th>Total Requested</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($topRequested)): ?>
                            <?php foreach ($topRequested as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                    <td><?php echo $item['total_requested']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="2">No data available.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- ===== MONTHLY REQUEST TREND & GENERATE REPORT ===== -->
<div class="container mb-4">
    <div class="row g-3 align-items-stretch">

        <!-- MONTHLY REQUEST TREND BAR GRAPH -->
        <div class="col-md-9 d-flex">
            <div class="card shadow-sm p-3 flex-fill d-flex flex-column" style="overflow-y: auto; max-height: 300px;">
                <h5 class="text-success fw-bold text-start">Monthly Request Trend</h5>
                <canvas id="monthlyTrendChart" height="100"></canvas>
            </div>
        </div>

        <!-- GENERATE PDF REPORT -->
        <div class="col-md-3 d-flex">
            <div class="card shadow-sm p-3 flex-fill d-flex flex-column justify-content-center text-center" style="overflow-y: auto; max-height: 300px;">
                <h5 class="text-success fw-bold text-start">Generate PDF Report</h5>
                <a href="admin_reports.php" class="btn btn-success mt-3">View Reports</a>
            </div>
        </div>

    </div>
</div>

    <!-- ===== ACTIVITY LOGS ===== -->
    <div class="container mb-4">
        <div class="row g-3">
            <div class="col-md-12">
                <div class="card shadow-sm p-3" style="max-height: 250px; overflow-y: auto;">
                    <h5 class="text-success fw-bold text-start">Activity Logs</h5>
                    <table class="table table-sm table-bordered text-center mt-2">
                        <thead class="table-success">
                            <tr>
                                <th>User</th>
                                <th>Activity</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($activityLogs)): ?>
                                <?php foreach ($activityLogs as $log): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($log['first_name'] . " " . $log['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($log['action']); ?></td>
                                        <td><?php echo date("M d, Y h:i A", strtotime($log['timestamp'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3">No logs available.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    </main>
    </div>

    <!-- ===== CHART.JS ===== -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('monthlyTrendChart').getContext('2d');
        const monthlyTrendChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($trendLabels); ?>,
                datasets: [{
                    label: 'Requests',
                    data: <?php echo json_encode($trendTotals); ?>,
                    backgroundColor: '#198754',
                    borderColor: '#146c43',
                    borderWidth: 1,
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            color: '#198754'
                        },
                        grid: {
                            color: 'rgba(25, 135, 84, 0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#198754'
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    </script>