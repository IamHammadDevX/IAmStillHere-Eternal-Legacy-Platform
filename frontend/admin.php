<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - IamAlwaysHere</title>
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
                    <li class="nav-item">
                        <a class="nav-link" href="http://localhost/IAmStillHere/index.php">Home</a>
                    </li>
                    <li class="nav-item" id="nav-dashboard" style="display:none;">
                        <a class="nav-link" href="#">Dashboard</a>
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
        <h2 class="mb-4">Admin Dashboard</h2>

        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#users-tab">Users</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#activity-tab">Activity Log</a>
            </li>
        </ul>

        <div class="tab-content">
            <!-- USERS TAB -->
            <div class="tab-pane fade show active" id="users-tab">
                <div class="card">
                    <div class="card-header">
                        <h5>User Management</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Full Name</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="users-table-body"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ACTIVITY LOG TAB -->
            <div class="tab-pane fade" id="activity-tab">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Activity</h5>
                        <button class="btn btn-sm btn-outline-secondary" onclick="loadActivityLog()">Refresh</button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>Details</th>
                                        <th>IP Address</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody id="activity-log-body">
                                    <tr><td colspan="6" class="text-center text-muted">Loading activity log...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/auth.js"></script>

    <script>
        async function loadUsers() {
            try {
                const response = await fetch('http://localhost/IAmStillHere/backend/admin/users.php');
                const data = await response.json();

                if (data.success) {
                    const tbody = document.getElementById('users-table-body');
                    tbody.innerHTML = '';

                    data.users.forEach(user => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${user.id}</td>
                            <td>${user.username}</td>
                            <td>${user.email}</td>
                            <td>${user.full_name}</td>
                            <td><span class="badge bg-${user.role === 'admin' ? 'danger' : 'primary'}">${user.role}</span></td>
                            <td><span class="badge bg-${user.status === 'active' ? 'success' : 'secondary'}">${user.status}</span></td>
                            <td>${new Date(user.created_at).toLocaleDateString()}</td>
                            <td>
                                ${user.role !== 'admin' ? `
                                    <select class="form-select form-select-sm" onchange="updateUserStatus(${user.id}, this.value)">
                                        <option value="active" ${user.status === 'active' ? 'selected' : ''}>Active</option>
                                        <option value="suspended" ${user.status === 'suspended' ? 'selected' : ''}>Suspended</option>
                                        <option value="deleted" ${user.status === 'deleted' ? 'selected' : ''}>Deleted</option>
                                    </select>
                                ` : '-'}
                            </td>
                        `;
                        tbody.appendChild(row);
                    });
                }
            } catch (error) {
                console.error('Error loading users:', error);
            }
        }

        async function loadActivityLog() {
            try {
                const response = await fetch('http://localhost/IAmStillHere/backend/admin/activity_log.php');
                const data = await response.json();
                const tbody = document.getElementById('activity-log-body');
                tbody.innerHTML = '';

                if (data.success && data.logs.length > 0) {
                    data.logs.forEach(log => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${log.id}</td>
                            <td>${log.username || 'Unknown'}</td>
                            <td>${log.action}</td>
                            <td>${log.details || '-'}</td>
                            <td>${log.ip_address || '-'}</td>
                            <td>${new Date(log.created_at).toLocaleString()}</td>
                        `;
                        tbody.appendChild(row);
                    });
                } else {
                    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted">No activity found</td></tr>`;
                }
            } catch (error) {
                console.error('Error loading activity log:', error);
            }
        }

        async function updateUserStatus(userId, status) {
            try {
                const response = await fetch('http://localhost/IAmStillHere/backend/admin/users.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId, status: status })
                });

                const data = await response.json();

                if (data.success) {
                    showAlert('User status updated successfully', 'success');
                    loadActivityLog(); // Refresh log after update
                } else {
                    showAlert(data.message, 'danger');
                }
            } catch (error) {
                showAlert('Failed to update user status', 'danger');
            }
        }

        document.addEventListener('DOMContentLoaded', async () => {
            const response = await fetch('http://localhost/IAmStillHere/backend/auth/check_session.php');
            const data = await response.json();

            if (!data.logged_in || data.user.role !== 'admin') {
                window.location.href = 'login.php';
                return;
            }

            loadUsers();
            loadActivityLog();
        });
    </script>
</body>
</html>
