<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="images/favicon.png">
    <title>Admin Dashboard - IamAlwaysHere</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css?v=2026081701">
</head>

<body class="admin-page">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/index.php">
                <i class="bi bi-heart-fill text-danger"></i> IamAlwaysHere
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item" id="nav-search">
                        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#searchModal">
                            <i class="bi bi-search"></i>
                        </a>
                    </li>
                    <li class="nav-item" id="nav-memories"><a href="memorials.php" class="nav-link">Memories</a></li>
                    <li class="nav-item" id="nav-home">
                        <a class="nav-link" href="/index.php">Home</a>
                    </li>
                    <li class="nav-item" id="nav-dashboard" style="display:none;">
                        <a class="nav-link" href="dashboard.php">My dashboard</a>
                    </li>
                    <li class="nav-item" id="nav-admin" style="display:none;">
                        <a class="nav-link" href="#">Admin</a>
                    </li>
                    <li class="nav-item" id="nav-login">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                    <li class="nav-item" id="nav-register">
                        <a class="nav-link" href="register.php">Register</a>
                    </li>
                    <li class="nav-item" id="nav-profile" style="display:none;">
                        <a class="nav-link" href="profile.php" id="username-display">Public Profile</a>
                    </li>
                    <li class="nav-item" id="nav-logout" style="display:none;">
                        <a class="nav-link" href="#" onclick="logout()">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="admin-shell"><div class="container py-4 py-lg-5">
        <div class="admin-hero mb-4">
            <div>
                <span class="admin-kicker"><i class="bi bi-speedometer2"></i> Control center</span>
                <h2 class="mb-2 mt-3">Admin Dashboard</h2>
                <p class="text-muted mb-0">Monitor users, content, AI, automation jobs, Vault metadata, and system health.</p>
            </div>
            <div class="admin-hero-icon"><i class="bi bi-shield-check"></i></div>
        </div>

        <ul class="nav nav-tabs mb-4 flex-nowrap overflow-auto" role="tablist">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#overview-tab">Overview</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#users-tab">Users</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#content-tab">Content</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#journeys-tab">Journeys</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#automations-tab">Automations</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#ai-tab">AI</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#system-tab">System</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#activity-tab">Activity</a></li>
        </ul>

        <div id="admin-alert"></div>
        <div class="tab-content">
            <div class="tab-pane fade show active" id="overview-tab">
                <div id="admin-overview-cards" class="row g-3 mb-4"></div>
                <div class="row g-3">
                    <div class="col-lg-6"><div class="card h-100"><div class="card-header">Failed Jobs</div><div class="card-body" id="admin-failed-jobs">Loading...</div></div></div>
                    <div class="col-lg-6"><div class="card h-100"><div class="card-header">Recent System Activity</div><div class="card-body" id="admin-recent-activity">Loading...</div></div></div>
                </div>
            </div>
            <div class="tab-pane fade" id="users-tab">
                <div class="card"><div class="card-header d-flex flex-column flex-md-row gap-2 justify-content-between"><h5 class="mb-0">Users</h5><div class="d-flex gap-2"><input id="admin-user-search" class="form-control form-control-sm" placeholder="Search users"><select id="admin-user-status" class="form-select form-select-sm"><option value="">All</option><option value="active">Active</option><option value="suspended">Suspended</option></select><button class="btn btn-sm btn-outline-secondary" id="admin-user-refresh">Refresh</button></div></div><div class="card-body"><div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>ID</th><th>User</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead><tbody id="users-table-body"><tr><td colspan="7">Loading...</td></tr></tbody></table></div></div></div>
            </div>
            <div class="tab-pane fade" id="content-tab"><div class="card"><div class="card-header">Content / Moderation</div><div class="card-body" id="admin-content-status">Loading...</div></div></div>
            <div class="tab-pane fade" id="journeys-tab"><div class="card"><div class="card-header">Journeys</div><div class="card-body" id="admin-journey-status">Loading...</div></div></div>
            <div class="tab-pane fade" id="automations-tab"><div class="card"><div class="card-header">Automations</div><div class="card-body" id="admin-automation-status">Loading...</div></div></div>
            <div class="tab-pane fade" id="ai-tab"><div class="card"><div class="card-header">AI Usage / Status</div><div class="card-body" id="admin-ai-status">Loading...</div></div></div>
            <div class="tab-pane fade" id="system-tab"><div class="card"><div class="card-header">System / Integration Health</div><div class="card-body" id="admin-system-status">Loading...</div></div></div>
            <div class="tab-pane fade" id="activity-tab"><div class="card"><div class="card-header d-flex justify-content-between"><h5 class="mb-0">Activity / Audit Logs</h5><button class="btn btn-sm btn-outline-secondary" onclick="loadAdminOverview()">Refresh</button></div><div class="card-body"><div class="table-responsive"><table class="table table-striped"><tbody id="activity-log-body"><tr><td>Loading...</td></tr></tbody></table></div></div></div></div>
        </div></main>
    <!-- Search Users Modal -->
    <div class="modal fade" id="searchModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Search Users</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <input type="text" class="form-control" id="search-input"
                            placeholder="Search by name, username, or email...">
                    </div>
                    <div id="search-results" class="list-group">
                        <p class="text-muted text-center">Enter a search term to find users</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container text-center">
            <div class="mb-2">
                <!-- Social Links -->
                <a href="https://github.com/IamHammadDevX" target="_blank" class="text-light mx-2" title="GitHub">
                    <i class="bi bi-github fs-4"></i>
                </a>
                <a href="https://www.iamhammaddevx.app/" target="_blank" class="text-light mx-2"
                    title="Portfolio">
                    <i class="bi bi-globe fs-4"></i>
                </a>
            </div>

            <!-- Copyright -->
            <p class="mb-0 small">
                &copy; <span id="current-year"></span> <strong>KodeBros.</strong> All rights reserved.
            </p>
        </div>
    </footer>

    <script>
        document.getElementById("current-year").textContent = new Date().getFullYear();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/auth.js"></script>
    <script src="js/search.js"></script>

        <script src="js/admin_dashboard.js?v=2026081102"></script>
</body>

</html>
