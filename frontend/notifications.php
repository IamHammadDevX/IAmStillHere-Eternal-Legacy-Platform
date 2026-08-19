<?php
require_once __DIR__ . '/../config/config.php';

if (!is_logged_in()) {
  header('Location: login.php');
  exit;
}
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" type="image/png" href="images/favicon.png">
  <title>Notifications - IamAlwaysHere</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
  <link rel="stylesheet" href="css/style.css?v=2026081918" />
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <a class="navbar-brand" href="/index.php"><i class="bi bi-heart-fill text-danger"></i> IamAlwaysHere</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item" id="nav-search"><a class="nav-link" href="memorials.php"><i class="bi bi-search"></i></a></li>
          <li class="nav-item" id="nav-memories"><a class="nav-link" href="memorials.php">Memorials</a></li>
          <li class="nav-item" id="nav-home"><a class="nav-link" href="/index.php">Home</a></li>
          <li class="nav-item" id="nav-dashboard" style="display:none;"><a class="nav-link" href="dashboard.php">My Dashboard</a></li>
          <li class="nav-item" id="nav-admin" style="display:none;"><a class="nav-link" href="admin.php">Admin</a></li>
          <li class="nav-item" id="nav-login"><a class="nav-link" href="login.php">Login</a></li>
          <li class="nav-item" id="nav-register"><a class="nav-link" href="register.php">Register</a></li>
          <li class="nav-item" id="nav-profile" style="display:none;"><a class="nav-link" href="profile.php" id="username-display">Public Profile</a></li>
          <li class="nav-item" id="nav-logout" style="display:none;"><a class="nav-link" href="#" onclick="logout()">Logout</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <main class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h3 mb-0">Notifications</h1>
      <button type="button" class="btn btn-outline-secondary btn-sm" onclick="markAllNotificationsRead()">Mark all read</button>
    </div>
    <div id="notifications-page-list" class="list-group shadow-sm"></div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/auth.js"></script>
</body>
</html>

