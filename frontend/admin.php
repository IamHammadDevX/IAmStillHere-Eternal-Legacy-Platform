<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="images/favicon.png">
    <title>Admin Dashboard - IamAlwaysHere</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css?v=2026081922">
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
                    <li class="nav-item" id="nav-memories"><a href="memorials.php" class="nav-link">Memorials</a></li>
                    <li class="nav-item" id="nav-home">
                        <a class="nav-link" href="/index.php">Home</a>
                    </li>
                    <li class="nav-item" id="nav-dashboard" style="display:none;">
                        <a class="nav-link" href="dashboard.php">My Dashboard</a>
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

        <ul class="nav admin-tabs" role="tablist" aria-label="Admin sections">
            <li class="nav-item"><button class="admin-tab-button active" type="button" data-admin-target="#overview-tab" aria-selected="true">Overview</button></li>
            <li class="nav-item"><button class="admin-tab-button" type="button" data-admin-target="#users-tab" aria-selected="false">Users</button></li>
            <li class="nav-item"><button class="admin-tab-button" type="button" data-admin-target="#content-tab" aria-selected="false">Content</button></li>
            <li class="nav-item"><button class="admin-tab-button" type="button" data-admin-target="#journeys-tab" aria-selected="false">Journeys</button></li>
            <li class="nav-item"><button class="admin-tab-button" type="button" data-admin-target="#automations-tab" aria-selected="false">Automations</button></li>
            <li class="nav-item"><button class="admin-tab-button" type="button" data-admin-target="#ai-tab" aria-selected="false">AI</button></li>
            <li class="nav-item"><button class="admin-tab-button" type="button" data-admin-target="#system-tab" aria-selected="false">System</button></li>
            <li class="nav-item"><button class="admin-tab-button" type="button" data-admin-target="#activity-tab" aria-selected="false">Activity</button></li>
        </ul>

        <div id="admin-alert"></div>
        <div class="admin-content">
            <div class="admin-panel" id="overview-tab">
                <div id="admin-overview-cards" class="row g-3 mb-4"></div>
                <div class="row g-3">
                    <div class="col-lg-6"><div class="card h-100"><div class="card-header">Failed Jobs</div><div class="card-body" id="admin-failed-jobs">Loading...</div></div></div>
                    <div class="col-lg-6"><div class="card h-100"><div class="card-header">Recent System Activity</div><div class="card-body" id="admin-recent-activity">Loading...</div></div></div>
                </div>
            </div>
            <div class="admin-panel" id="users-tab" hidden>
                <div class="card"><div class="card-header d-flex flex-column flex-md-row gap-2 justify-content-between"><h5 class="mb-0">Users</h5><div class="d-flex gap-2"><input id="admin-user-search" class="form-control form-control-sm" placeholder="Search users"><select id="admin-user-status" class="form-select form-select-sm"><option value="">All</option><option value="active">Active</option><option value="suspended">Suspended</option></select><button class="btn btn-sm btn-outline-secondary" id="admin-user-refresh">Refresh</button></div></div><div class="card-body"><div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>ID</th><th>User</th><th>Email</th><th>Role</th><th>Status</th><th>Last Login</th><th>Memory Used</th><th>AI Tokens Used</th><th>Joined</th><th>Actions</th></tr></thead><tbody id="users-table-body"><tr><td colspan="10">Loading...</td></tr></tbody></table></div><div id="users-pagination" class="admin-pagination mt-3"></div></div></div></div>
            </div>
            <div class="admin-panel" id="content-tab" hidden><div class="card"><div class="card-header">Content / Moderation</div><div class="card-body" id="admin-content-status">Loading...</div></div></div>
            <div class="admin-panel" id="journeys-tab" hidden><div class="card"><div class="card-header">Journeys</div><div class="card-body" id="admin-journey-status">Loading...</div></div></div>
            <div class="admin-panel" id="automations-tab" hidden><div class="card"><div class="card-header">Automations</div><div class="card-body" id="admin-automation-status">Loading...</div></div></div>
            <div class="admin-panel" id="ai-tab" hidden><div class="card"><div class="card-header">AI Usage / Status</div><div class="card-body" id="admin-ai-status">Loading...</div></div></div>
            <div class="admin-panel" id="system-tab" hidden><div class="card"><div class="card-header">System / Integration Health</div><div class="card-body" id="admin-system-status">Loading...</div></div></div>
            <div class="admin-panel" id="activity-tab" hidden><div class="card"><div class="card-header d-flex justify-content-between"><h5 class="mb-0">Activity / Audit Logs</h5><button class="btn btn-sm btn-outline-secondary" onclick="loadAdminActivity()">Refresh</button></div><div class="card-body"><div class="table-responsive"><table class="table table-striped"><tbody id="activity-log-body"><tr><td>Loading...</td></tr></tbody></table></div><div id="activity-pagination" class="admin-pagination mt-3"></div></div></div></div></div>
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

            <!-- Copyright -->
            <p class="mb-0 small">
                &copy; <span id="current-year"></span> <strong>SV mobile teleshoppe pvt. ltd.</strong> All rights reserved.
            </p>
        </div>
    </footer>

    <script>
        document.getElementById("current-year").textContent = new Date().getFullYear();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/auth.js"></script>
    <script src="js/search.js"></script>

        <script src="js/admin_dashboard.js?v=2026081919"></script>
</body>

</html>

