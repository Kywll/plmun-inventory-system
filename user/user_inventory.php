<?php
session_start();

// Include header
include_once("../includes/header.php");
?>

<div class="d-flex">

    <!-- SIDEBAR -->
    <?php include_once("../includes/sidebar_user.php"); ?>

    <!-- MAIN CONTENT -->
    <main class="flex-grow-1 p-4" style="margin-left: 250px;">
        <h2 class="mb-4 text-success fw-bold">Inventory</h2>

       

        <!-- Filter/Search Card -->
        <div class="container mb-4">
            <div class="card shadow-sm p-3">
                <h5 class="text-success fw-bold mb-3">Filter / Search Inventory</h5>
                <form class="row g-3 align-items-center">
                    <div class="col-md-4">
                        <input type="text" class="form-control" placeholder="Search by Item Name">
                    </div>
                    <div class="col-md-4">
                        <select class="form-select">
                            <option selected>Category</option>
                            <option value="Electronics">Electronics</option>
                            <option value="Lab Equipment">Lab Equipment</option>
                            <option value="Books">Books</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select">
                            <option selected>Supplier</option>
                            <option value="Supplier X">Supplier X</option>
                            <option value="Supplier Y">Supplier Y</option>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <!-- Inventory Table -->
        <div class="container mb-4">
            <div class="card shadow-sm p-3">
                <h5 class="text-success fw-bold mb-3">Available Items</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle text-center">
                        <thead class="table-success">
                            <tr>
                                <th>#</th>
                                <th>Item Name</th>
                                <th>Category</th>
                                <th>Supplier</th>
                                <th>Available Quantity</th>
                                <th>Low Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>Item A</td>
                                <td>Electronics</td>
                                <td>Supplier X</td>
                                <td>50</td>
                                <td><span class="badge bg-success">OK</span></td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>Item B</td>
                                <td>Lab Equipment</td>
                                <td>Supplier Y</td>
                                <td>5</td>
                                <td><span class="badge bg-warning text-dark">Low Stock</span></td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>Item C</td>
                                <td>Books</td>
                                <td>Supplier X</td>
                                <td>0</td>
                                <td><span class="badge bg-danger">Out of Stock</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

       
    </main>
</div>

<?php
// Include footer
include_once("../includes/footer.php");
?>
