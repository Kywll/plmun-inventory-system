<?php
session_start();
include_once("../includes/header.php");
?>

<div class="d-flex">

    <!-- SIDEBAR -->
    <?php include_once("../includes/sidebar_user.php"); ?>

    <!-- MAIN CONTENT -->
    <main class="flex-grow-1 p-4" style="margin-left: 250px;">
        <h2 class="mb-4 text-success fw-bold">Account Settings</h2>

        <div class="container">
            <div class="row g-4">


                <!-- RIGHT COLUMN (6) – Preferences & Info -->
                <div class="col-md-6">

                    <!-- Account Information -->
                    <div class="card shadow-sm p-4 mb-4">
                        <h5 class="text-success fw-bold mb-3">Account Information</h5>

                        <div class="mb-2">
                            <strong>Name:</strong> Juan Dela Cruz
                        </div>
                        <div class="mb-2">
                            <strong>Department:</strong> Science Department
                        </div>
                        <div class="mb-2">
                            <strong>Email:</strong> juan.delacruz@plmun.edu.ph
                        </div>
                        <div class="mb-2">
                            <strong>Status:</strong>
                            <span class="badge bg-success">Active</span>
                        </div>
                    </div>

                    <!-- Notification Preferences -->
                    <div class="card shadow-sm p-4">
                        <h5 class="text-success fw-bold mb-3">Notification Preferences</h5>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" checked>
                            <label class="form-check-label">
                                Request status updates
                            </label>
                        </div>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" checked>
                            <label class="form-check-label">
                                Approval / Decline notifications
                            </label>
                        </div>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox">
                            <label class="form-check-label">
                                Low-stock item alerts
                            </label>
                        </div>

                        <button type="button" class="btn btn-success w-100 mt-3">
                            Save Preferences
                        </button>
                    </div>

                </div>
                <!-- LEFT COLUMN (6) – Change Password -->
                <div class="col-md-6">
                    <div class="card shadow-sm p-4">
                        <h5 class="text-success fw-bold mb-3">Change Password</h5>

                        <form>
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" class="form-control" placeholder="Enter current password">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" class="form-control" placeholder="Enter new password">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" placeholder="Confirm new password">
                            </div>

                            <button type="button" class="btn btn-success w-100">
                                Update Password
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>

    </main>
</div>

<?php
include_once("../includes/footer.php");
?>