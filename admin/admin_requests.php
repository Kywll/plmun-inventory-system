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
        <h2 class="mb-4 text-success fw-bold">Request Management</h2>

        <!-- Filters -->
        <div class="container mb-4">
            <div class="card shadow-sm p-3">
                <h5 class="text-success fw-bold mb-3">Filter Requests</h5>
                <form class="row g-3 align-items-center">
                    <div class="col-md-3">
                        <select class="form-select">
                            <option selected>Priority</option>
                            <option value="High">High</option>
                            <option value="Medium">Medium</option>
                            <option value="Low">Low</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select">
                            <option selected>Department</option>
                            <option value="HR">HR</option>
                            <option value="Finance">Finance</option>
                            <option value="IT">IT</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="date" class="form-control" placeholder="Date">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select">
                            <option selected>Status</option>
                            <option value="Pending">Pending</option>
                            <option value="Approved">Approved</option>
                            <option value="Declined">Declined</option>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <!-- Requests Table -->
        <div class="container mb-4">
            <div class="card shadow-sm p-3">
                <h5 class="text-success fw-bold mb-3">Incoming Requests</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle text-center">
                        <thead class="table-success">
                            <tr>
                                <th>#</th>
                                <th>Requested By</th>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>Department</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Stock Check</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>John Doe</td>
                                <td>Item A</td>
                                <td>10</td>
                                <td>IT</td>
                                <td>High</td>
                                <td><span class="badge bg-warning text-dark">Pending</span></td>
                                <td><span class="badge bg-success">In Stock</span></td>
                                <td>
                                    <button class="btn btn-sm btn-success">Approve</button>
                                    <button class="btn btn-sm btn-danger">Decline</button>
                                </td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>Jane Smith</td>
                                <td>Item B</td>
                                <td>60</td>
                                <td>Finance</td>
                                <td>Medium</td>
                                <td><span class="badge bg-warning text-dark">Pending</span></td>
                                <td><span class="badge bg-danger">Low Stock</span></td>
                                <td>
                                    <button class="btn btn-sm btn-success">Approve</button>
                                    <button class="btn btn-sm btn-danger">Decline</button>
                                </td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>Mark Lee</td>
                                <td>Item C</td>
                                <td>5</td>
                                <td>HR</td>
                                <td>Low</td>
                                <td><span class="badge bg-warning text-dark">Pending</span></td>
                                <td><span class="badge bg-success">In Stock</span></td>
                                <td>
                                    <button class="btn btn-sm btn-success">Approve</button>
                                    <button class="btn btn-sm btn-danger">Decline</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Request Logs -->
        <div class="container mb-4">
            <div class="card shadow-sm p-3">
                <h5 class="text-success fw-bold mb-3">Request Logs</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle text-center">
                        <thead class="table-success">
                            <tr>
                                <th>#</th>
                                <th>Requested By</th>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>Status</th>
                                <th>Updated On</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>John Doe</td>
                                <td>Item A</td>
                                <td>10</td>
                                <td>Approved</td>
                                <td>2026-02-02</td>
                                <td>Approved by Admin</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>Jane Smith</td>
                                <td>Item B</td>
                                <td>60</td>
                                <td>Declined</td>
                                <td>2026-02-01</td>
                                <td>Low stock</td>
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
