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
        <h2 class="mb-4 text-success fw-bold">Activity Reports</h2>

        

        <!-- Filter + Export Side by Side -->
        <div class="container mb-4">
            <div class="row g-3">
                

        <!-- Activity Logs Table -->
        <div class="container mb-4">
            <div class="card shadow-sm p-3">
                <h5 class="text-success fw-bold mb-3">Activity Logs</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle text-center">
                        <thead class="table-success">
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>Activity</th>
                                <th>Details</th>
                                <th>Date & Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>Admin</td>
                                <td>Login</td>
                                <td>Logged into the system</td>
                                <td>2026-02-02 08:30</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>Admin</td>
                                <td>Edit</td>
                                <td>Updated Item #5 quantity to 20</td>
                                <td>2026-02-02 09:15</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>Admin</td>
                                <td>Approval</td>
                                <td>Approved Request #12</td>
                                <td>2026-02-02 10:00</td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>Admin</td>
                                <td>Deletion</td>
                                <td>Deleted User #8</td>
                                <td>2026-02-02 10:30</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Filter Activities (8 columns) -->
                <div class="col-md-8">
                    <div class="card shadow-sm p-3">
                        <h5 class="text-success fw-bold mb-3">Filter Activities</h5>
                        <form class="row g-3 align-items-center">
                            <div class="col-md-4">
                                <input type="date" class="form-control" placeholder="Start Date">
                            </div>
                            <div class="col-md-4">
                                <input type="date" class="form-control" placeholder="End Date">
                            </div>
                            <div class="col-md-4">
                                <select class="form-select">
                                    <option selected>Activity Type</option>
                                    <option value="Login">Login</option>
                                    <option value="Edit">Edit / Update</option>
                                    <option value="Approval">Approval</option>
                                    <option value="Deletion">Deletion</option>
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
