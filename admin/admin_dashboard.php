<?php
session_start();

// include database if available
// include_once("../includes/db_connect.php");

// Include header
include_once("../includes/header.php");
?>

<!-- WRAPPER: Sidebar + Main Content -->
<div class="d-flex">

    <!-- SIDEBAR -->
    <?php include_once("../includes/sidebar_admin.php"); ?>

    <!-- MAIN CONTENT -->
    <main class="flex-grow-1 p-4" style="margin-left: 250px;">
        <h2 class="mb-4 text-success fw-bold">Admin Dashboard</h2>

        <!-- Container with 4 cards in one row -->
        <div class="container mb-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="card shadow-sm p-3 text-center">
                        <h5 class="text-success fw-bold">Users</h5>
                        <h3>#</h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm p-3 text-center">
                        <h5 class="text-success fw-bold">Pending Request</h5>
                        <h3>#</h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm p-3 text-center">
                        <h5 class="text-success fw-bold">Low Stack</h5>
                        <h3>#</h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm p-3 text-center">
                        <h5 class="text-success fw-bold">Reports</h5>
                        <h3>#</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="container mb-4">
            <div class="row g-3">
                <div class="col-md-12">
                    <div class="card shadow-sm p-3 text-center" style="height: 150px">
                        <h5 class="text-success fw-bold text-start">Latest Request</h5>
                        <!----DATA FOR LATEST REQUEST---->
                    </div>
                </div>
            </div>
        </div>

        <div class="container mb-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card shadow-sm p-3 text-center" style="height: 150px;">
                        <h5 class="text-success fw-bold text-start">Urgent Request</h5>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow-sm p-3 text-center" style="height: 150px;">
                        <h5 class="text-success fw-bold text-start">Top Requested Items</h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="container mb-4">
            <div class="row g-3">
                <div class="col-md-12">
                    <div class="card shadow-sm p-3 text-center" style="height: 150px">
                        <h5 class="text-success fw-bold text-start">Monthly Request Trend</h5>
                        <!----DATA FOR LATEST REQUEST---->
                    </div>
                </div>
            </div>
        </div>

        <div class="container mb-4">
            <div class="row g-3">
                <div class="col-md-9">
                    <div class="card shadow-sm p-3 text-center" style="height: 150px;">
                        <h5 class="text-success fw-bold text-start">Activity Logs</h5>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm p-3 text-center" style="height: 150px;">
                        <h5 class="text-success fw-bold text-start">Generate PDF Report</h5>
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