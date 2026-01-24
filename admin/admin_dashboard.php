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
        <h1 class="mb-4">Admin Dashboard</h1>

        <!-- Container with 4 cards in one row -->
        <div class="container">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="card shadow-sm p-3 text-center">
                        <h5>Card 1</h5>
                        <p>Some content here</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm p-3 text-center">
                        <h5>Card 2</h5>
                        <p>Some content here</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm p-3 text-center">
                        <h5>Card 3</h5>
                        <p>Some content here</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm p-3 text-center">
                        <h5>Card 4</h5>
                        <p>Some content here</p>
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
