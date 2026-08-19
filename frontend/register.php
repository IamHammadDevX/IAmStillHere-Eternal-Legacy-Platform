<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="images/favicon.png">
    <title>Register - IamAlwaysHere</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css?v=2026081922">
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
            <div class="col-md-8 col-lg-6">
                <div class="card auth-card">
                    <div class="card-body p-5">
                        <div class="auth-icon"><i class="bi bi-person-plus-fill"></i></div>
                        <h2 class="text-center mb-2">Create Account</h2>
                        <p class="text-center text-muted mb-4">Start your private digital legacy space.</p>
                        <form id="registerForm">
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="full_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="date_of_birth" class="form-label">Date of Birth (Optional)</label>
                                <input type="date" class="form-control" id="date_of_birth">
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" required minlength="8">
                                <small class="text-muted">Minimum 8 characters</small>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" required>
                            </div>
                            <div class="form-check terms-acceptance mb-3">
                                <input class="form-check-input" type="checkbox" id="terms_accepted" required>
                                <label class="form-check-label" for="terms_accepted">
                                    I ACCEPT the <a href="terms.php" target="_blank" rel="noopener">Terms and Conditions</a>
                                </label>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg" id="register-submit" disabled>Register</button>
                            </div>
                        </form>
                        <div class="text-center mt-3">
                            <p>Already have an account? <a href="login.php">Login here</a></p>
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
        const termsCheckbox = document.getElementById('terms_accepted');
        const registerSubmit = document.getElementById('register-submit');
        termsCheckbox.addEventListener('change', () => {
            registerSubmit.disabled = !termsCheckbox.checked;
        });

        document.getElementById('registerForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const password = document.getElementById('password').value;
            const confirm_password = document.getElementById('confirm_password').value;

            if (!termsCheckbox.checked) {
                showAlert('You must accept the Terms and Conditions to register.', 'warning');
                termsCheckbox.focus();
                return;
            }

            if (password !== confirm_password) {
                showAlert('Passwords do not match', 'danger');
                return;
            }

            if (password.length < 8) {
                showAlert('Password must be at least 8 characters', 'warning');
                return;
            }

            const formData = {
                username: document.getElementById('username').value,
                email: document.getElementById('email').value,
                password: password,
                full_name: document.getElementById('full_name').value,
                date_of_birth: document.getElementById('date_of_birth').value,
                terms_accepted: true
            };

            try {
                showAlert('Sending verification code...', 'info');

                const response = await fetch('/backend/auth/send_verification.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();

                if (data.success) {
                    showAlert('Verification code sent! Redirecting...', 'success');
                    setTimeout(() => {
                        window.location.href = `verify_email.php?email=${encodeURIComponent(data.email)}`;
                    }, 2000);
                } else {
                    showAlert(data.message, 'danger');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('An error occurred. Please try again.', 'danger');
            }
        });
    </script>
</body>

</html>
