<?php
session_start();
require_once("../includes/db_connect.php");

// ================= SESSION SECURITY =================
if (!isset($_SESSION['user_id']) || $_SESSION['position'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) {
    session_unset();
    session_destroy();
    header("Location: ../login.php");
    exit();
}
$_SESSION['last_activity'] = time();

$admin_id = $_SESSION['user_id'];
$message = "";

// ================= FETCH ADMIN DATA =================
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id=?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ================= HANDLE PASSWORD UPDATE =================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_password'])) {

    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if (!$admin_data || !password_verify($currentPassword, $admin_data['password'])) {
        $message = "<div class='alert alert-danger'>Current password is incorrect.</div>";
    } elseif ($newPassword !== $confirmPassword) {
        $message = "<div class='alert alert-danger'>New passwords do not match.</div>";
    } elseif (strlen($newPassword) < 8) {
        $message = "<div class='alert alert-danger'>Password must be at least 8 characters long.</div>";
    } else {

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $update = $conn->prepare("UPDATE users SET password=? WHERE user_id=?");
        $update->bind_param("si", $hashedPassword, $admin_id);
        $update->execute();
        $update->close();

        // Insert log
        $log = $conn->prepare("
            INSERT INTO logs (user_id, action, remarks)
            VALUES (?, 'Password Updated', 'Admin changed account password')
        ");
        $log->bind_param("i", $admin_id);
        $log->execute();
        $log->close();

        $message = "<div class='alert alert-success'>Password updated successfully.</div>";
    }
}

// ================= HANDLE NOTIFICATIONS UPDATE =================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['save_notifications'])) {
    $notify_request = isset($_POST['notify_request']) ? 1 : 0;
    $notify_approval = isset($_POST['notify_approval']) ? 1 : 0;
    $notify_lowstock = isset($_POST['notify_lowstock']) ? 1 : 0;

    $update = $conn->prepare("UPDATE users SET notify_request=?, notify_approval=?, notify_lowstock=? WHERE user_id=?");
    $update->bind_param("iiii", $notify_request, $notify_approval, $notify_lowstock, $admin_id);
    
    if ($update->execute()) {
        $message = "<div class='alert alert-success'>Notification preferences updated.</div>";
        // Refresh admin data
        $admin_data['notify_request'] = $notify_request;
        $admin_data['notify_approval'] = $notify_approval;
        $admin_data['notify_lowstock'] = $notify_lowstock;
    } else {
        $message = "<div class='alert alert-danger'>Failed to update preferences.</div>";
    }
    $update->close();
}

?>

<div class="d-flex">

<?php include_once("../includes/sidebar_admin.php"); ?>

<main class="flex-grow-1 p-4" style="margin-left: 250px; height: 100vh; overflow-y: auto;">
<h2 class="mb-4 text-success fw-bold">Settings</h2>

<div class="container mb-4">
<div class="row g-3">

<!-- Change Password -->
<div class="col-md-6">
<div class="card shadow-sm p-4" style="height: 400px;">
<h5 class="text-success fw-bold mb-3">Change Password</h5>

<?php echo $message; ?>

<form method="POST">
<div class="mb-3">
<label class="form-label">Current Password</label>
<input type="password" name="current_password" class="form-control" required>
</div>

<div class="mb-3">
<label class="form-label">New Password</label>
<input type="password" name="new_password" class="form-control" required>
</div>

<div class="mb-3">
<label class="form-label">Confirm New Password</label>
<input type="password" name="confirm_password" class="form-control" required>
</div>

<div class="mt-auto">
<button type="submit" name="update_password" class="btn btn-success w-100">
Update Password
</button>
</div>
</form>
</div>
</div>

<!-- Security & Notifications -->
<div class="col-md-6">
<div class="card shadow-sm p-4 d-flex flex-column" style="height: 400px;">
<h5 class="text-success fw-bold mb-3">Security & Notifications</h5>

<form class="d-flex flex-column h-100">
<div class="mb-4">
<h6 class="fw-bold text-muted border-bottom pb-2 mb-3">Alert Preferences</h6>
<div class="form-check mb-3">
<input class="form-check-input" type="checkbox" id="notifyLogin" checked disabled>
<label class="form-check-label" for="notifyLogin">
Notify me on new login from unknown device
</label>
</div>

<div class="form-check mb-3">
<input class="form-check-input" type="checkbox" id="notifyChanges" checked disabled>
<label class="form-check-label" for="notifyChanges">
Notify me when my account settings are changed
</label>
</div>
</div>

<div class="mt-auto">
<button type="submit" class="btn btn-success w-100" disabled title="Settings are currently locked for Admin">
Save Settings
</button>
</div>
</form>

</div>
</div>

</div>
</div>

</main>
</div>

<?php
$conn->close();
?>
