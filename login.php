<?php
session_start();
require_once("includes/db_connect.php");

$error = "";

// If already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['position'] === 'Admin') {
        header("Location: admin/admin_dashboard.php");
    } else {
        header("Location: user/user_dashboard.php");
    }
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {

        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            // Check if account locked
            if ($user['account_locked'] == 1) {
                $error = "Account is locked. Please contact administrator.";
            }
            // Check if inactive
            elseif ($user['status'] !== 'Active') {
                $error = "Account is inactive.";
            }
            // Verify password
            elseif (password_verify($password, $user['password'])) {

                // Reset failed attempts
                $resetStmt = $conn->prepare("UPDATE users SET failed_attempts = 0 WHERE user_id = ?");
                $resetStmt->bind_param("i", $user['user_id']);
                $resetStmt->execute();

                // Set session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['position'] = $user['position'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['department'] = $user['department'];
                $_SESSION['last_activity'] = time();

                // Log login
                $logStmt = $conn->prepare("INSERT INTO logs (user_id, action, remarks) VALUES (?, 'Login', 'User logged in')");
                $logStmt->bind_param("i", $user['user_id']);
                $logStmt->execute();

                // Redirect based on role
                if ($user['position'] === 'Admin') {
                    header("Location: admin/admin_dashboard.php");
                } else {
                    header("Location: user/user_dashboard.php");
                }
                exit();

            } else {

                // Wrong password — increment failed attempts
                $failedAttempts = $user['failed_attempts'] + 1;

                if ($failedAttempts >= 5) {
                    $lockStmt = $conn->prepare("UPDATE users SET failed_attempts = ?, account_locked = 1 WHERE user_id = ?");
                    $lockStmt->bind_param("ii", $failedAttempts, $user['user_id']);
                    $lockStmt->execute();
                    $error = "Account locked after multiple failed attempts.";
                } else {
                    $updateStmt = $conn->prepare("UPDATE users SET failed_attempts = ? WHERE user_id = ?");
                    $updateStmt->bind_param("ii", $failedAttempts, $user['user_id']);
                    $updateStmt->execute();
                    $error = "Invalid email or password.";
                }
            }

        } else {
            $error = "Invalid email or password.";
        }

        $stmt->close();
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100..900&display=swap" rel="stylesheet">
  <style>
    * { font-family: 'Montserrat', sans-serif; }
  </style>
</head>

<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-light bg-light px-4">
  <div class="d-flex align-items-center">
    <div class="rounded-circle bg-success me-2" style="width:40px; height:40px;"></div>
    <span class="navbar-brand mb-0 h1 text-success fw-bold">PLMun</span>
  </div>
</nav>

<div class="container d-flex justify-content-center align-items-center flex-grow-1">
  <div class="card shadow p-4" style="max-width: 400px; width: 100%;">
    <div class="card-body">
      <h3 class="card-title text-center text-success fw-bold mb-4">Sign In</h3>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger text-center"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="mb-3">
          <label class="form-label">Email address</label>
          <input type="email" name="email" class="form-control" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>

        <div class="d-grid">
          <button type="submit" class="btn btn-success">Sign In</button>
        </div>

        <p class="text-center mt-3">
          Back to <a href="index.php" class="text-success">Home Page</a>
        </p>
      </form>
    </div>
  </div>
</div>

<footer class="bg-success text-white text-center py-3 mt-auto">
  © 2026 PLMun. All Rights Reserved.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
