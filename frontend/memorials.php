<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Memorials - IamAlwaysHere</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="http://localhost/IAmStillHere/index.php">
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
                    <li>
                        <a href="#" class="nav-link">Memorials</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="http://localhost/IAmStillHere/index.php">Home</a>
                    </li>
                    <li class="nav-item" id="nav-dashboard" style="display:none;">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item" id="nav-admin" style="display:none;">
                        <a class="nav-link" href="admin.php">Admin</a>
                    </li>
                    <li class="nav-item" id="nav-login">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                    <li class="nav-item" id="nav-register">
                        <a class="nav-link" href="register.php">Register</a>
                    </li>
                    <li class="nav-item" id="nav-profile" style="display:none;">
                        <a class="nav-link" href="profile.php" id="username-display"></a>
                    </li>
                    <li class="nav-item" id="nav-logout" style="display:none;">
                        <a class="nav-link" href="#" onclick="logout()">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="mb-4">Memorial Pages</h2>
        <p class="text-muted">Honoring the lives and memories of loved ones</p>

        <div class="row" id="memorials-grid">
            <div class="col-12 text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    </div>
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
                <a href="https://thisishammaddevx.netlify.app" target="_blank" class="text-light mx-2" title="Portfolio">
                    <i class="bi bi-globe fs-4"></i>
                </a>
            </div>

            <!-- Copyright -->
            <p class="mb-0 small">
                © <span id="current-year"></span> <strong>KodeBros.</strong> All rights reserved.
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/auth.js"></script>
    <script src="js/search.js"></script>
    <script>

        async function loadMemorials() {
            try {
                const response = await fetch('http://localhost/IAmStillHere/backend/memorials/list.php');
                const data = await response.json();

                const grid = document.getElementById('memorials-grid');

                if (data.success && data.memorials.length > 0) {
                    grid.innerHTML = '';

                    data.memorials.forEach(memorial => {
                        const col = document.createElement('div');
                        col.className = 'col-md-4 mb-4';

                        // Build the correct photo paths
                        const profilePhoto = memorial.profile_photo
                            ? `http://localhost/IAmStillHere/data/uploads/photos/${memorial.profile_photo}`
                            : 'http://localhost/IAmStillHere/frontend/images/default-profile.png';

                        const coverPhoto = memorial.cover_photo
                            ? `http://localhost/IAmStillHere/data/uploads/photos/${memorial.cover_photo}`
                            : null;

                        col.innerHTML = `
                    <div class="card h-100 shadow-sm">
                        ${coverPhoto ? `<img src="${coverPhoto}" class="card-img-top" alt="Cover Photo" style="height: 150px; object-fit: cover;">` : ''}
                        <div class="card-body text-center">
                            <img src="${profilePhoto}" class="profile-photo mb-3 rounded-circle border" 
                                 alt="${memorial.full_name}" 
                                 style="width: 100px; height: 100px; object-fit: cover;">
                            <h5 class="card-title">${memorial.full_name}</h5>
                            ${memorial.date_of_birth ? `<p class="text-muted small mb-0">Born: ${new Date(memorial.date_of_birth).toLocaleDateString()}</p>` : ''}
                            ${memorial.date_of_passing ? `<p class="text-muted small">Passed: ${new Date(memorial.date_of_passing).toLocaleDateString()}</p>` : ''}
                            ${memorial.bio ? `<p class="card-text">${memorial.bio.substring(0, 100)}${memorial.bio.length > 100 ? '...' : ''}</p>` : ''}
                            <a href="profile.php?user_id=${memorial.id}" class="btn btn-primary btn-sm">View Memorial</a>
                        </div>
                    </div>
                `;

                        grid.appendChild(col);
                    });
                } else {
                    grid.innerHTML = `
                <div class="col-12">
                    <p class="text-center text-muted">No public memorials available yet.</p>
                </div>`;
                }
            } catch (error) {
                console.error('Error loading memorials:', error);
                document.getElementById('memorials-grid').innerHTML = `
            <div class="col-12">
                <p class="text-center text-danger">Error loading memorials.</p>
            </div>`;
            }
        }

        document.addEventListener('DOMContentLoaded', loadMemorials);
    </script>
</body>

</html>