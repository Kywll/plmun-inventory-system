<?php
session_start();
include_once("../includes/header.php");
?>

<div class="d-flex">

    <!-- SIDEBAR -->
    <?php include_once("../includes/sidebar_user.php"); ?>

    <!-- MAIN CONTENT -->
    <main class="flex-grow-1 p-4" style="margin-left: 250px;">
        <h2 class="mb-4 text-success fw-bold">User Dashboard</h2>

        <!-- SUMMARY CARDS -->
        <div class="container mb-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="card shadow-sm p-3 text-center">
                        <h5 class="text-success fw-bold">My Pending</h5>
                        <h3>2</h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm p-3 text-center">
                        <h5 class="text-success fw-bold">Approved</h5>
                        <h3>5</h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm p-3 text-center">
                        <h5 class="text-success fw-bold">Declined</h5>
                        <h3>1</h3>
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
                                    <th>Urgency</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Projector</td>
                                    <td>High</td>
                                    <td><span class="badge bg-warning text-dark">Pending</span></td>
                                    <td>Aug 18, 2026</td>
                                </tr>
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
                                <tr>
                                    <td>Chairs</td>
                                    <td>120</td>
                                </tr>
                                <tr>
                                    <td>Whiteboard</td>
                                    <td>15</td>
                                </tr>
                                <tr>
                                    <td>Projector</td>
                                    <td>
                                        <span class="badge bg-danger">Low</span>
                                    </td>
                                </tr>
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
                                <tr>
                                    <td>Requested 2 Chairs</td>
                                    <td>Aug 15, 2026</td>
                                </tr>
                                <tr>
                                    <td>Request Approved</td>
                                    <td>Aug 16, 2026</td>
                                </tr>
                                <tr>
                                    <td>Cancelled Whiteboard Request</td>
                                    <td>Aug 14, 2026</td>
                                </tr>
                            </tbody>
                        </table>

                    </div>
                </div>

            </div>
        </div>
    </main>
</div>

<?php
include_once("../includes/footer.php");
?>
