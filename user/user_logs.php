<?php
session_start();
include_once("../includes/header.php");
?>

<div class="d-flex">

    <!-- SIDEBAR -->
    <?php include_once("../includes/sidebar_user.php"); ?>

    <!-- MAIN CONTENT -->
    <main class="flex-grow-1 p-4" style="margin-left: 250px;">
        <h2 class="mb-4 text-success fw-bold">My Activity Logs</h2>

       <!-- Container with 4 cards in one row -->
            <div class="container mb-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card shadow-sm p-3 text-center">
                            <h5 class="text-success fw-bold">Total Activites</h5>
                            <h3>#</h3>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow-sm p-3 text-center">
                            <h5 class="text-success fw-bold">Request Made</h5>
                            <h3>#</h3>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow-sm p-3 text-center">
                            <h5 class="text-success fw-bold">Cancellations</h5>
                            <h3>#</h3>
                        </div>
                    </div>
                </div>
            </div>

        <!-- Activity Logs Table -->
        <div class="container">
            <div class="card shadow-sm p-3">
                <h5 class="text-success fw-bold mb-3">Activity History</h5>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-center align-middle">
                        <thead class="table-success">
                            <tr>
                                <th>#</th>
                                <th>Activity</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Date & Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>Request Submitted</td>
                                <td>Requested 2 Chairs</td>
                                <td><span class="badge bg-warning text-dark">Pending</span></td>
                                <td>Aug 12, 2026 - 10:32 AM</td>
                            </tr>

                            <tr>
                                <td>2</td>
                                <td>Request Approved</td>
                                <td>Projector request approved</td>
                                <td><span class="badge bg-success">Approved</span></td>
                                <td>Aug 13, 2026 - 9:10 AM</td>
                            </tr>

                            <tr>
                                <td>3</td>
                                <td>Request Cancelled</td>
                                <td>Cancelled Whiteboard request</td>
                                <td><span class="badge bg-danger">Cancelled</span></td>
                                <td>Aug 14, 2026 - 2:45 PM</td>
                            </tr>

                            <tr>
                                <td>4</td>
                                <td>Login</td>
                                <td>User logged into the system</td>
                                <td><span class="badge bg-info">Info</span></td>
                                <td>Aug 15, 2026 - 8:01 AM</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

    </main>
</div>

<?php
include_once("../includes/footer.php");
?>
