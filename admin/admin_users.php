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
        <h2 class="mb-4 text-success fw-bold">User Management</h2>

        

        <!-- User Summary Cards -->
        <div class="container mb-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="card shadow-sm p-3 text-center">
                        <h5 class="text-success fw-bold">Total Users</h5>
                        <h3>#</h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm p-3 text-center">
                        <h5 class="text-success fw-bold">Active Users</h5>
                        <h3>#</h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm p-3 text-center">
                        <h5 class="text-success fw-bold">Inactive Users</h5>
                        <h3>#</h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm p-3 text-center">
                        <h5 class="text-success fw-bold">Recently Updated</h5>
                        <h3>#</h3>
                    </div>
                </div>
            </div>
        </div>

          <!-- User Status Table -->
        <div class="container mb-4">
            <div class="card shadow-sm p-3">
                <h5 class="text-success fw-bold mb-3">User Status List</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle text-center">
                        <thead class="table-success">
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>John Doe</td>
                                <td>Science</td>
                                <td>john.doe@example.com</td>
                                <td><span class="badge bg-success">Active</span></td>
                                <td>2026-02-02 08:30</td>
                                <td>
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#updateStatusModal">Update Status</button>
                                </td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>Jane Smith</td>
                                <td>English</td>
                                <td>jane.smith@example.com</td>
                                <td><span class="badge bg-danger">Inactive</span></td>
                                <td>2026-02-01 10:15</td>
                                <td>
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#updateStatusModal">Update Status</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Filter/Search + Import SQL Side by Side -->
        <div class="container mb-4">
            <div class="row g-3">
                <!-- Filter/Search (8 columns) -->
                <div class="col-md-8">
                    <div class="card shadow-sm p-3">
                        <h5 class="text-success fw-bold mb-3">Filter & Search Users</h5>
                        <form class="row g-3 align-items-center">
                            <div class="col-md-4">
                                <input type="text" class="form-control" placeholder="Search by Name">
                            </div>
                            <div class="col-md-4">
                                <select class="form-select">
                                    <option selected>Department</option>
                                    <option value="Science">Science</option>
                                    <option value="English">English</option>
                                    <option value="Math">Math</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select class="form-select">
                                    <option selected>Status</option>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Import/Update SQL (4 columns) -->
                <div class="col-md-4">
                    <div class="card shadow-sm p-3 text-center">
                        <h5 class="text-success fw-bold mb-3">Import / Update Users</h5>
                        <input type="file" class="form-control mb-2">
                        <button class="btn btn-danger w-100 mb-1">Upload SQL</button>
                        <button class="btn btn-success w-100">Notify Users</button>
                    </div>
                </div>
            </div>
        </div>

      

    </main>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="updateStatusModalLabel">Update User Status</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form class="row g-3">
            <div class="col-md-12">
                <label class="form-label">User Name</label>
                <input type="text" class="form-control" value="John Doe" readonly>
            </div>
            <div class="col-md-12">
                <label class="form-label">Department</label>
                <input type="text" class="form-control" value="Science" readonly>
            </div>
            <div class="col-md-12">
                <label class="form-label">Status</label>
                <select class="form-select">
                    <option value="Active" selected>Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>
            <div class="col-md-12">
                <button type="submit" class="btn btn-success w-100">Update Status</button>
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
