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
        <h2 class="mb-4 text-success fw-bold">Settings</h2>

        <!-- Settings Cards Row -->
        <div class="container mb-4">
            <div class="row g-3">
                <!-- Change Password (6 columns) -->
                <div class="col-md-6">
                    <div class="card shadow-sm p-4">
                        <h5 class="text-success fw-bold mb-3">Change Password</h5>
                        <form>
                            <div class="mb-3">
                                <label for="currentPassword" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="currentPassword" placeholder="Enter current password">
                            </div>
                            <div class="mb-3">
                                <label for="newPassword" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="newPassword" placeholder="Enter new password">
                            </div>
                            <div class="mb-3">
                                <label for="confirmPassword" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirmPassword" placeholder="Confirm new password">
                            </div>
                            <button type="submit" class="btn btn-success w-100">Update Password</button>
                        </form>
                    </div>
                </div>

                <!-- Security & Notifications (6 columns) -->
                <div class="col-md-6">
                    <div class="card shadow-sm p-4">
                        <h5 class="text-success fw-bold mb-3">Security & Notifications</h5>
                        <form>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" value="" id="notifyLogin">
                                <label class="form-check-label" for="notifyLogin">
                                    Notify me on new login from unknown device
                                </label>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" value="" id="notifyChanges">
                                <label class="form-check-label" for="notifyChanges">
                                    Notify me when my account settings are changed
                                </label>
                            </div>
                            <button type="submit" class="btn btn-success w-100">Save Settings</button>
                        </form>
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
