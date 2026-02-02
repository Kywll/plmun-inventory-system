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
        <h2 class="mb-4 text-success fw-bold">Inventory Management</h2>

         <!-- Low-Stock Alert -->
        <div class="container mb-4">
            <div class="alert alert-warning shadow-sm" role="alert">
                <strong>Low Stock Alert!</strong> The following items are below minimum stock level:
                <ul class="mb-0">
                    <li>Item B – 5 units remaining</li>
                    <li>Item C – 0 units remaining</li>
                </ul>
            </div>
        </div>

        <!-- Inventory Summary Cards -->
        <div class="container mb-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="card shadow-sm p-3 text-center">
                        <h5 class="text-success fw-bold">Total Items</h5>
                        <h3>#</h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm p-3 text-center">
                        <h5 class="text-success fw-bold">Low Stock</h5>
                        <h3>#</h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm p-3 text-center">
                        <h5 class="text-success fw-bold">Out of Stock</h5>
                        <h3>#</h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm p-3 text-center">
                        <h5 class="text-success fw-bold">New Arrivals</h5>
                        <h3>#</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inventory Table -->
        <div class="container mb-4">
            <div class="card shadow-sm p-3">
                <h5 class="text-success fw-bold mb-3">Inventory List</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle text-center">
                        <thead class="table-success">
                            <tr>
                                <th>#</th>
                                <th>Item Name</th>
                                <th>Description</th>
                                <th>Category</th>
                                <th>Supplier</th>
                                <th>Quantity</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Sample Row -->
                            <tr>
                                <td>1</td>
                                <td>Item A</td>
                                <td>Sample description</td>
                                <td>Category 1</td>
                                <td>Supplier X</td>
                                <td>50</td>
                                <td><span class="badge bg-success">Active</span></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#updateItemModal">Edit</button>
                                    <button class="btn btn-sm btn-warning">Deactivate</button>
                                    <button class="btn btn-sm btn-danger">Delete</button>
                                </td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>Item B</td>
                                <td>Sample description</td>
                                <td>Category 2</td>
                                <td>Supplier Y</td>
                                <td>5</td>
                                <td><span class="badge bg-warning text-dark">Low Stock</span></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#updateItemModal">Edit</button>
                                    <button class="btn btn-sm btn-warning">Deactivate</button>
                                    <button class="btn btn-sm btn-danger">Delete</button>
                                </td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>Item C</td>
                                <td>Sample description</td>
                                <td>Category 3</td>
                                <td>Supplier Z</td>
                                <td>0</td>
                                <td><span class="badge bg-danger">Out of Stock</span></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#updateItemModal">Edit</button>
                                    <button class="btn btn-sm btn-warning">Deactivate</button>
                                    <button class="btn btn-sm btn-danger">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

       <!-- Add New Item + Inventory Reports Side by Side -->
<div class="container mb-4">
    <div class="row g-3">
        <!-- Add New Item (8 columns) -->
        <div class="col-md-8">
            <div class="card shadow-sm p-3">
                <h5 class="text-success fw-bold mb-3">Add New Item</h5>
                <form class="row g-3">
                    <div class="col-md-6">
                        <input type="text" class="form-control" placeholder="Item Name">
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="form-control" placeholder="Description">
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="form-control" placeholder="Category">
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="form-control" placeholder="Supplier">
                    </div>
                    <div class="col-md-4">
                        <input type="number" class="form-control" placeholder="Quantity">
                    </div>
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-success w-100">Add Item</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Inventory Reports (4 columns) -->
        <div class="col-md-4">
            <div class="card shadow-sm p-3 text-center">
                <h5 class="text-success fw-bold mb-3">Inventory Reports</h5>
                <button class="btn btn-danger m-1 w-100">Export PDF</button>
                <button class="btn btn-success m-1 w-100">Export Excel</button>
                <button class="btn btn-primary m-1 w-100">Export CSV</button>
            </div>
        </div>
    </div>
</div>


    </main>
</div>

<!-- Update Item Modal -->
<div class="modal fade" id="updateItemModal" tabindex="-1" aria-labelledby="updateItemModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="updateItemModalLabel">Update Item</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form class="row g-3">
            <div class="col-md-6">
                <input type="text" class="form-control" placeholder="Item Name" value="Item A">
            </div>
            <div class="col-md-6">
                <input type="text" class="form-control" placeholder="Description" value="Sample description">
            </div>
            <div class="col-md-4">
                <input type="text" class="form-control" placeholder="Category" value="Category 1">
            </div>
            <div class="col-md-4">
                <input type="text" class="form-control" placeholder="Supplier" value="Supplier X">
            </div>
            <div class="col-md-4">
                <input type="number" class="form-control" placeholder="Quantity" value="50">
            </div>
            <div class="col-md-12">
                <button type="submit" class="btn btn-success w-100">Update Item</button>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php
// Include footer
include_once("../includes/footer.php");
?>
