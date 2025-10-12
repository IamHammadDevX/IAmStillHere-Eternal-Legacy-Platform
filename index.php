<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAmStillHere - Memorial Social Network</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="frontend/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-heart-fill text-danger"></i> IAmStillHere
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item" id="nav-dashboard" style="display:none;">
                        <a class="nav-link" href="frontend/dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item" id="nav-admin" style="display:none;">
                        <a class="nav-link" href="frontend/admin.php">Admin</a>
                    </li>
                    <li class="nav-item" id="nav-login">
                        <a class="nav-link" href="frontend/login.php">Login</a>
                    </li>
                    <li class="nav-item" id="nav-register">
                        <a class="nav-link" href="frontend/register.php">Register</a>
                    </li>
                    <li class="nav-item" id="nav-profile" style="display:none;">
                        <a class="nav-link" href="#" id="username-display"></a>
                    </li>
                    <li class="nav-item" id="nav-logout" style="display:none;">
                        <a class="nav-link" href="#" onclick="logout()">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="hero-section text-center py-5">
        <div class="container">
            <h1 class="display-4 mb-4">IAmStillHere</h1>
            <p class="lead">A Digital Memorial Platform - Honoring Lives, Preserving Memories</p>
            <p class="text-muted">Create a lasting tribute for your loved ones. Share memories, milestones, and stories that live forever.</p>
            <div class="mt-4">
                <a href="frontend/register.php" class="btn btn-primary btn-lg me-3">Create Memorial</a>
                <a href="frontend/memorials.php" class="btn btn-outline-secondary btn-lg">View Memorials</a>
            </div>
        </div>
    </div>

    <div class="container my-5">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="bi bi-camera-fill display-4 text-primary mb-3"></i>
                        <h5 class="card-title">Share Memories</h5>
                        <p class="card-text">Upload photos, videos, and documents to preserve precious moments forever.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="bi bi-calendar-event display-4 text-success mb-3"></i>
                        <h5 class="card-title">Timeline & Milestones</h5>
                        <p class="card-text">Create a beautiful timeline of life events and important milestones.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="bi bi-chat-heart display-4 text-danger mb-3"></i>
                        <h5 class="card-title">Tributes & Messages</h5>
                        <p class="card-text">Friends and family can leave heartfelt tributes and share their memories.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <i class="bi bi-shield-check display-6 text-info mb-3"></i>
                        <h5 class="card-title">Privacy Control</h5>
                        <p class="card-text">Choose who can view your content - public, family only, or private.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <i class="bi bi-clock-history display-6 text-warning mb-3"></i>
                        <h5 class="card-title">Scheduled Messages</h5>
                        <p class="card-text">Schedule future messages and posts for special occasions and anniversaries.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white text-center py-4 mt-5">
        <div class="container">
            <p>&copy; 2024 IAmStillHere. Honoring memories, celebrating lives.</p>
            <p class="text-muted small">A memorial social networking platform</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="frontend/js/auth.js"></script>
</body>
</html>
