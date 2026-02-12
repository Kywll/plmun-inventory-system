<?php
session_start();
require_once("../includes/db_connect.php");

// ================= SESSION SECURITY =================
if (!isset($_SESSION['user_id']) || $_SESSION['position'] !== 'Staff') {
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

$user_id = $_SESSION['user_id'];
$message = "";

// ================= FETCH USER INFO =================
$userQuery = $conn->prepare("SELECT * FROM users WHERE user_id=?");
$userQuery->bind_param("i", $user_id);
$userQuery->execute();
$user = $userQuery->get_result()->fetch_assoc();
$userQuery->close();

// ================= CHANGE PASSWORD =================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if (!empty($current) && !empty($new) && !empty($confirm)) {

        if (!password_verify($current, $user['password'])) {
            $message = "Current password is incorrect.";
        } elseif ($new !== $confirm) {
            $message = "New passwords do not match.";
        } else {

            $hashed = password_hash($new, PASSWORD_DEFAULT);

            $update = $conn->prepare("UPDATE users SET password=? WHERE user_id=?");
            $update->bind_param("si", $hashed, $user_id);
            $update->execute();
            $update->close();

            // Log password change
            $log = $conn->prepare("INSERT INTO logs (user_id, action, remarks) VALUES (?, 'Password Changed', 'User updated password')");
            $log->bind_param("i", $user_id);
            $log->execute();
            $log->close();

            $message = "Password successfully updated.";
        }
    } else {
        $message = "All fields are required.";
    }
}

include_once("../includes/header.php");
?>

<div class="d-flex">
<?php include_once("../includes/sidebar_user.php"); ?>

<main class="flex-grow-1 p-4" style="margin-left: 250px;">
<h2 class="mb-4 text-success fw-bold">Account Settings</h2>

<div class="container">
<div class="row g-4">

<div class="col-md-6">

<div class="card shadow-sm p-4 mb-4">
<h5 class="text-success fw-bold mb-3">Account Information</h5>

<div class="mb-2">
<strong>Name:</strong> <?php echo htmlspecialchars($user['first_name'] . " " . $user['last_name']); ?>
</div>

<div class="mb-2">
<strong>Department:</strong> <?php echo htmlspecialchars($user['department']); ?>
</div>

<div class="mb-2">
<strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?>
</div>

<div class="mb-2">
<strong>Status:</strong>
<span class="badge bg-<?php echo $user['status'] === 'Active' ? 'success' : 'danger'; ?>">
<?php echo htmlspecialchars($user['status']); ?>
</span>
</div>

</div>

<div class="card shadow-sm p-4">
<h5 class="text-success fw-bold mb-3">Notification Preferences</h5>

<div class="form-check mb-2">
<input class="form-check-input" type="checkbox" checked disabled>
<label class="form-check-label">Request status updates</label>
</div>

<div class="form-check mb-2">
<input class="form-check-input" type="checkbox" checked disabled>
<label class="form-check-label">Approval / Decline notifications</label>
</div>

<div class="form-check mb-2">
<input class="form-check-input" type="checkbox" disabled>
<label class="form-check-label">Low-stock item alerts</label>
</div>

<button type="button" class="btn btn-success w-100 mt-3" disabled>
Save Preferences
</button>

</div>
</div>

<div class="col-md-6">
<div class="card shadow-sm p-4">
<h5 class="text-success fw-bold mb-3">Change Password</h5>

<?php if (!empty($message)): ?>
<div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

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

<button type="submit" class="btn btn-success w-100">
Update Password
</button>
</form>

</div>
</div>

</div>
</div>

</main>
</div>

<?php include_once("../includes/footer.php"); ?>
