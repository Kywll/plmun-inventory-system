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
        <h2 class="mb-4 text-success fw-bold">My Requests</h2>


        <!-- Request Tracking Table -->
        <div class="container mb-4">
            <div class="card shadow-sm p-3">
                <h5 class="text-success fw-bold mb-3">Track Your Requests</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle text-center">
                        <thead class="table-success">
                            <tr>
                                <th>#</th>
                                <th>Request</th>
                                <th>Urgency</th>
                                <th>Status</th>
                                <th>Submitted On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>Projector</td>
                                <td>High</td>
                                <td><span class="badge bg-warning text-dark">Pending</span></td>
                                <td>2026-02-02</td>
                                <td>
                                    <button class="btn btn-sm btn-danger" 
                                        onclick="return confirm('Are you sure you want to cancel this request?')">
                                        Cancel
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>Lab Equipment</td>
                                <td>Medium</td>
                                <td><span class="badge bg-success">Approved</span></td>
                                <td>2026-01-30</td>
                                <td>
                                    <button class="btn btn-sm btn-secondary" disabled>Cancel</button>
                                </td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>Library Room Booking</td>
                                <td>Low</td>
                                <td><span class="badge bg-danger">Declined</span></td>
                                <td>2026-01-28</td>
                                <td>
                                    <button class="btn btn-sm btn-secondary" disabled>Cancel</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        
        <!-- Submit New Request Card -->
        <div class="container mb-4">
            <div class="card shadow-sm p-4">
                <h5 class="text-success fw-bold mb-3">Submit New Request</h5>
                <form class="row g-3">
                    <div class="col-md-6">
                        <input type="text" class="form-control" placeholder="Item / Facility Name" required>
                    </div>
                    <div class="col-md-6">
                        <select class="form-select" required>
                            <option selected>Urgency Level</option>
                            <option value="Low">Low</option>
                            <option value="Medium">Medium</option>
                            <option value="High">High</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <textarea class="form-control" placeholder="Additional Notes (optional)"></textarea>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Attach File (optional)</label>
                        <input type="file" class="form-control">
                    </div>
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-success w-100">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>

    </main>
</div>

<?php
// Include footer
include_once("../includes/footer.php");
?>
