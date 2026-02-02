<?php
session_start();

// Include header
include_once("../includes/header.php");
?>

<div class="d-flex">

    <!-- SIDEBAR -->
    <?php include_once("../includes/sidebar_admin.php"); ?>

    <!-- MAIN CONTENT -->
    <main class="flex-grow-1 p-4" style="margin-left: 250px;">
        <h2 class="mb-4 text-success fw-bold">Request Reports</h2>

        <!-- Reports Summary Cards -->
        <div class="container mb-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="card shadow-sm p-3 text-center">
                        <h5 class="text-success fw-bold">Total Requests</h5>
                        <h3>#</h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm p-3 text-center">
                        <h5 class="text-success fw-bold">Pending Requests</h5>
                        <h3>#</h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm p-3 text-center">
                        <h5 class="text-success fw-bold">Approved Requests</h5>
                        <h3>#</h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm p-3 text-center">
                        <h5 class="text-success fw-bold">Declined Requests</h5>
                        <h3>#</h3>
                    </div>
                </div>
            </div>
        </div>

             <!-- Department Summary Reports -->
        <div class="container mb-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="card shadow-sm p-3 text-center">
                        <h5 class="text-success fw-bold">Science Dept.</h5>
                        <p>20 items requested</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm p-3 text-center">
                        <h5 class="text-success fw-bold">English Dept.</h5>
                        <p>Total Usage: ₱5,000</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm p-3 text-center">
                        <h5 class="text-success fw-bold">Math Dept.</h5>
                        <p>15 items requested</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm p-3 text-center">
                        <h5 class="text-success fw-bold">IT Dept.</h5>
                        <p>10 items requested</p>
                    </div>
                </div>
            </div>
        </div>

           <!-- Request Reports Table -->
        <div class="container mb-4">
            <div class="card shadow-sm p-3">
                <h5 class="text-success fw-bold mb-3">Request Reports</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle text-center">
                        <thead class="table-success">
                            <tr>
                                <th>#</th>
                                <th>Requested By</th>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>Department</th>
                                <th>Urgency</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>John Doe</td>
                                <td>Item A</td>
                                <td>10</td>
                                <td>Science</td>
                                <td>High</td>
                                <td><span class="badge bg-success">Approved</span></td>
                                <td>2026-02-02</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>Jane Smith</td>
                                <td>Item B</td>
                                <td>5</td>
                                <td>English</td>
                                <td>Medium</td>
                                <td><span class="badge bg-danger">Declined</span></td>
                                <td>2026-02-01</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Filter Reports + Export Reports Side by Side -->
        <div class="container mb-4">
            <div class="row g-3">
                <!-- Filter Reports (8 columns) -->
                <div class="col-md-8">
                    <div class="card shadow-sm p-3">
                        <h5 class="text-success fw-bold mb-3">Filter Requests</h5>
                        <form class="row g-3 align-items-center">
                            <div class="col-md-4">
                                <input type="date" class="form-control" placeholder="Start Date">
                            </div>
                            <div class="col-md-4">
                                <input type="date" class="form-control" placeholder="End Date">
                            </div>
                            <div class="col-md-4">
                                <select class="form-select">
                                    <option selected>Department</option>
                                    <option value="Science">Science</option>
                                    <option value="English">English</option>
                                    <option value="Math">Math</option>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Export Reports (4 columns) -->
                <div class="col-md-4">
                    <div class="card shadow-sm p-3 text-center">
                        <h5 class="text-success fw-bold mb-3">Export Reports</h5>
                        <button class="btn btn-danger m-1 w-100">Export PDF</button>
                        <button class="btn btn-success m-1 w-100">Export Excel</button>
                        <button class="btn btn-primary m-1 w-100">Export CSV</button>
                    </div>
                </div>
            </div>
        </div>

     

    </main>
</div>

<?php
// Include footer
include_once("../includes/footer.php");
?>
