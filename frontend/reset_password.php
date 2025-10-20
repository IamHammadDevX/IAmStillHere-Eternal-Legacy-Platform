<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="images/favicon.png">
    <title>Reset Password - IamAlwaysHere</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="bi bi-heart-fill text-danger"></i> IamAlwaysHere
            </a>
        </div>
    </nav>

    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="bi bi-shield-lock display-1 text-primary"></i>
                            <h2 class="mt-3">Reset Password</h2>
                            <p class="text-muted">Enter the code sent to your email</p>
                        </div>
                        
                        <!-- Step 1: Verify Code -->
                        <div id="verifyStep">
                            <form id="verifyCodeForm">
                                <div class="mb-4">
                                    <label for="reset_code" class="form-label">Reset Code</label>
                                    <input type="text" 
                                           class="form-control form-control-lg text-center" 
                                           id="reset_code" 
                                           maxlength="6" 
                                           placeholder="000000"
                                           pattern="[0-9]{6}"
                                           style="font-size: 24px; letter-spacing: 10px; font-weight: bold;"
                                           required>
                                    <small class="text-muted">Code expires in <span id="timer">30:00</span></small>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="bi bi-check-circle"></i> Verify Code
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Step 2: Set New Password -->
                        <div id="resetStep" style="display: none;">
                            <form id="resetPasswordForm">
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" required>
                                    <small class="text-muted">Minimum 8 characters</small>
                                </div>
                                <div class="mb-4">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" id="confirm_password" required>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="bi bi-key"></i> Reset Password
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="text-center mt-4">
                            <a href="login.php" class="text-decoration-none">
                                <i class="bi bi-arrow-left"></i> Back to Login
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0 small">
                © <span id="current-year"></span> <strong>KodeBros.</strong> All rights reserved.
            </p>
        </div>
    </footer>

    <script>
        document.getElementById("current-year").textContent = new Date().getFullYear();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/auth.js"></script>
    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const resetToken = urlParams.get('token');
        let verifiedCode = '';

        if (!resetToken) {
            window.location.href = 'forgot_password.php';
        }

        // Timer countdown (30 minutes)
        let timeLeft = 30 * 60;
        
        function updateTimer() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            document.getElementById('timer').textContent = 
                `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            
            if (timeLeft > 0) {
                timeLeft--;
            } else {
                showAlert('Reset code expired. Please request a new one.', 'warning');
            }
        }
        
        setInterval(updateTimer, 1000);

        // Auto-format code input
        document.getElementById('reset_code').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Verify code
        document.getElementById('verifyCodeForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const code = document.getElementById('reset_code').value;

            if (code.length !== 6) {
                showAlert('Please enter a 6-digit code', 'warning');
                return;
            }

            try {
                const response = await fetch('http://localhost/IAmStillHere/backend/auth/verify_reset_code.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        token: resetToken,
                        code: code
                    })
                });

                const data = await response.json();

                if (data.success) {
                    verifiedCode = code;
                    document.getElementById('verifyStep').style.display = 'none';
                    document.getElementById('resetStep').style.display = 'block';
                    showAlert('Code verified! Now set your new password.', 'success');
                } else {
                    showAlert(data.message, 'danger');
                }
            } catch (error) {
                showAlert('An error occurred. Please try again.', 'danger');
            }
        });

        // Reset password
        document.getElementById('resetPasswordForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (newPassword !== confirmPassword) {
                showAlert('Passwords do not match', 'danger');
                return;
            }

            if (newPassword.length < 8) {
                showAlert('Password must be at least 8 characters', 'warning');
                return;
            }

            try {
                const response = await fetch('http://localhost/IAmStillHere/backend/auth/reset_password.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        token: resetToken,
                        code: verifiedCode,
                        new_password: newPassword,
                        confirm_password: confirmPassword
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showAlert('Password reset successfully! Redirecting to login...', 'success');
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 2000);
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