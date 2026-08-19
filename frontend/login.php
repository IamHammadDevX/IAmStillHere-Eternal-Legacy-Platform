<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="images/favicon.png">
    <title>Login - IamAlwaysHere</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css?v=2026081921">
</head>

<body class="auth-page">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="bi bi-heart-fill text-danger"></i> IamAlwaysHere
            </a>
        </div>
    </nav>

    <main class="auth-shell"><div class="container">
        <div class="row justify-content-center align-items-center min-vh-auth">
            <div class="col-md-6 col-lg-5">
                <div class="card auth-card">
                    <div class="card-body p-5">
                        <div class="auth-icon"><i class="bi bi-heart-fill"></i></div>
                        <h2 class="text-center mb-2">Welcome Back</h2>
                        <p class="text-center text-muted mb-4">Sign in to continue preserving memories.</p>
                        <form id="loginForm">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username or Email</label>
                                <input type="text" class="form-control" id="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Login</button>
                            </div>
                        </form>
                        <div class="text-center mt-3">
                            <a href="forgot_password.php" class="text-decoration-none">
                                Forgot your password?
                            </a>
                        </div>
                        <div class="text-center mt-3">
                            <p>Don't have an account? <a href="register.php">Register here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div></main>

    <footer class="bg-dark text-light py-4 auth-footer">
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
                &copy; <span id="current-year"></span> <strong>SV mobile teleshoppe pvt. ltd.</strong> All rights reserved.
            </p>
        </div>
    </footer>

    <script>
        document.getElementById("current-year").textContent = new Date().getFullYear();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/auth.js"></script>
    <script>
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;

            try {
                const response = await fetch('/backend/auth/login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username, password })
                });

                const data = await response.json();

                if (data.success) {
                    showAlert('Login successful! Redirecting...', 'success');
                    setTimeout(() => {
                        window.location.href = data.user.role === 'admin' ? 'admin.php' : 'dashboard.php';
                    }, 1000);
                } else {
                    showAlert(data.message, 'danger');
                }
            } catch (error) {
                showAlert('An error occurred. Please try again.', 'danger');
            }
        });
    </script>
</body>

</html>
