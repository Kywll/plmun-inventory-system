<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bootstrap demo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
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
   <!-- MAIN CONTENT: SIGN IN CARD -->
<div class="container d-flex justify-content-center align-items-center flex-grow-1">
  <div class="card shadow p-4" style="max-width: 400px; width: 100%;">
    <div class="card-body">
      <h3 class="card-title text-center text-success fw-bold mb-4">Sign In</h3>
      
      <form>
        <div class="mb-3">
          <label for="email" class="form-label">Email address</label>
          <input type="email" class="form-control" id="email" placeholder="Enter email">
        </div>
        
        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <input type="password" class="form-control" id="password" placeholder="Enter password">
        </div>

        <div class="d-grid">
          <button type="submit" class="btn btn-success">Sign In</button>
        </div>

        <p class="text-center mt-3">
          Back to Home Page <a href="index.php" class="text-success text-decoration-none">Home Page</a>
        </p>
      </form>
    </div>
  </div>
</div>


    <!-- FOOTER -->
    <footer class="bg-success text-white text-center py-3 mt-auto">
      © 2026 PLMun. All Rights Reserved.
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
