<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <style>
    * {
      font-family: 'Montserrat', sans-serif;
    }

    .sidebar-link {
      transition: all 0.2s ease-in-out;
    }

    .sidebar-link:hover {
      background-color: rgba(255, 255, 255, 0.2);
    }

    .nav-pills .nav-link.active {
      background-color: rgba(255, 255, 255, 0.2) !important;
      border: none !important;
      font-weight: 600;
      border-left: 4px solid #ffffff;
      /* optional */
      border-radius: 30;
      padding-left: 12px;
    }

    aside{
      position: fixed;
      height: 0;
      bottom: 0;
      left: 0;
      top: 0;
    }
  </style>
</head>

<body>

  <!-- WRAPPER -->
  <div class="d-flex min-vh-100">

    <!-- SIDEBAR -->
    <aside class="bg-success text-white p-3 vh-100 position-fixed" style="width: 250px;">


      <!-- BRAND -->
      <div class="d-flex align-items-center mb-4">
        <div class="rounded-circle bg-white me-2" style="width:40px; height:40px;"></div>
        <h5 class="mb-0 fw-bold">PLMun</h5>
      </div>

      <!-- MENU -->
      <ul class="nav nav-pills flex-column gap-2">
        <li class="nav-item">
          <a href="admin_dashboard.php" class="nav-link text-white sidebar-link <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php') ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2 me-2"></i> Dashboard
          </a>
        </li>

        <li class="nav-item">
          <a href="admin_requests.php" class="nav-link text-white sidebar-link <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_requests.php') ? 'active' : ''; ?>">
            <i class="bi bi-clipboard-check-fill me-2"></i> My Request
          </a>
        </li>

        <li class="nav-item">
          <a href="inventory.php" class="nav-link text-white sidebar-link <?php echo (basename($_SERVER['PHP_SELF']) == 'inventory.php') ? 'active' : ''; ?>">
            <i class="bi bi-box-seam-fill me-2"></i> Inventory
          </a>
        </li>

        <li class="nav-item">
          <a href="reports.php" class="nav-link text-white sidebar-link <?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>">
            <i class="bi bi-file-earmark-bar-graph-fill me-2"></i>My Reports
          </a>
        </li>


        <li class="nav-item">
          <a href="activity_logs.php" class="nav-link text-white sidebar-link <?php echo (basename($_SERVER['PHP_SELF']) == 'activity_logs.php') ? 'active' : ''; ?>">
            <i class="bi bi-clock-history me-2"></i> Activity Logs
          </a>
        </li>

        <li class="nav-item">
          <a href="settings.php" class="nav-link text-white sidebar-link <?php echo (basename($_SERVER['PHP_SELF']) == 'settings.php') ? 'active' : ''; ?>">
            <i class="bi bi-sliders me-2"></i> Settings
          </a>
        </li>

        <li class="nav-item mt-auto">
          <a href="logout.php" class="nav-link text-white sidebar-link  ">
            <i class="bi bi-box-arrow-right me-2"></i> Logout
          </a>
        </li>
      </ul>



    </aside>

  </div>


</body>

</html>