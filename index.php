<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Bootstrap demo</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
  
  <style>
    *{
      font-family: 'Montserrat', sans-serif;
    }
  </style>
</head>

<!-- BODY AS FLEX -->

<body class="d-flex flex-column min-vh-100">

  <!-- NAVBAR -->
  <nav class="navbar navbar-light bg-light px-4">
    <div class="d-flex align-items-center">
      <div class="rounded-circle bg-success me-2" style="width:40px; height:40px;"></div>
      <span class="navbar-brand mb-0 h1 text-success fw-bold">PLMun</span>
    </div>
  </nav>

  <!-- MAIN CONTENT -->
  <div class="container d-flex justify-content-between align-items-center flex-grow-1">

    <!-- LEFT -->
    <div class="col-5 p-4">
      <h1 class="text-success fw-bold" style="text-align: justify;">
        PLMUN SUPPLY AND FACILITY INVENTORY MANAGEMENT SYSTEM
      </h1>
      <p style="text-align: justify;">
        PLMUN Supply and Facility Inventory Management System helps track, manage,
        and monitor school supplies and facilities efficiently, ensuring accurate
        records, easy access, and streamlined inventory operations.
      </p>
      <button class="btn btn-success"><a href="login.php" class="text-white text-decoration-none">Login</a></button>
    </div>

    <!-- RIGHT -->
    <div class="col-5 p-4">
      <img class="img-fluid" src="assets/images/undraw_building-a-website_1wrp (1).png">
    </div>

  </div>

  <!-- FOOTER -->
  <footer class="bg-success text-white text-center py-3 mt-auto">
    © 2026 PLMun. All Rights Reserved.
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>