<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - IAmStillHere</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="bi bi-heart-fill text-danger"></i> IAmStillHere
            </a>
            <div class="ms-auto">
                <span class="text-white me-3">Admin Panel</span>
                <a href="dashboard.php" class="btn btn-outline-light btn-sm me-2">My Dashboard</a>
                <a href="#" class="btn btn-outline-light btn-sm" onclick="logout()">Logout</a>
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
            <div class="tab-pane fade" id="activity-tab">
                <div class="card">
                    <div class="card-header">
                        <h5>Recent Activity</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Activity log feature - tracking user actions</p>
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
                const response = await fetch('/backend/admin/users.php');
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

        async function updateUserStatus(userId, status) {
            try {
                const response = await fetch('/backend/admin/users.php', {
                    method: 'PUT',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ user_id: userId, status: status })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert('User status updated successfully', 'success');
                } else {
                    showAlert(data.message, 'danger');
                }
            } catch (error) {
                showAlert('Failed to update user status', 'danger');
            }
        }

        document.addEventListener('DOMContentLoaded', async () => {
            const response = await fetch('/backend/auth/check_session.php');
            const data = await response.json();
            
            if (!data.logged_in || data.user.role !== 'admin') {
                window.location.href = 'login.php';
                return;
            }
            
            loadUsers();
        });
    </script>
</body>
</html>
