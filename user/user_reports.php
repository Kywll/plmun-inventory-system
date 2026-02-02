<?php
session_start();
include_once("../includes/header.php");
?>

<div class="d-flex">

    <!-- SIDEBAR -->
    <?php include_once("../includes/sidebar_user.php"); ?>

    <!-- MAIN CONTENT -->
    <main class="flex-grow-1 p-4" style="margin-left: 250px;">
        <h2 class="mb-4 text-success fw-bold">My Reports</h2>

         <!-- Container with 4 cards in one row -->
        <div class="container mb-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="card shadow-sm p-3 text-center">
                        <h5 class="text-success fw-bold">Total Request</h5>
                        <h3>#</h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm p-3 text-center">
                        <h5 class="text-success fw-bold">Pending</h5>
                        <h3>#</h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm p-3 text-center">
                        <h5 class="text-success fw-bold">Approved</h5>
                        <h3>#</h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm p-3 text-center">
                        <h5 class="text-success fw-bold">Declined</h5>
                        <h3>#</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Row 1: Request Reports + Inventory Summary -->
        <div class="container mb-4">
            <div class="row g-3">

                <!-- Request Reports (8 cols) -->
                <div class="col-md-12">
                    <div class="card shadow-sm p-3">
                        <h5 class="text-success fw-bold mb-3">Request Reports</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover text-center align-middle">
                                <thead class="table-success">
                                    <tr>
                                        <th>#</th>
                                        <th>Item</th>
                                        <th>Urgency</th>
                                        <th>Status</th>
                                        <th>Date Requested</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td>Projector</td>
                                        <td>High</td>
                                        <td><span class="badge bg-success">Approved</span></td>
                                        <td>Aug 12, 2026</td>
                                    </tr>
                                    <tr>
                                        <td>2</td>
                                        <td>Chairs (x2)</td>
                                        <td>Medium</td>
                                        <td><span class="badge bg-warning text-dark">Pending</span></td>
                                        <td>Aug 15, 2026</td>
                                    </tr>
                                    <tr>
                                        <td>3</td>
                                        <td>Whiteboard</td>
                                        <td>Low</td>
                                        <td><span class="badge bg-danger">Declined</span></td>
                                        <td>Aug 10, 2026</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    

    </main>
</div>

<?php
include_once("../includes/footer.php");
?>
