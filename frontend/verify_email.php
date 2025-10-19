<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="images/favicon.png">
    <title>Verify Email - IamAlwaysHere</title>
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
                    <div class="card-body p-5 text-center">
                        <i class="bi bi-envelope-check display-1 text-primary mb-4"></i>
                        <h2 class="mb-3">Verify Your Email</h2>
                        <p class="text-muted mb-4">We've sent a 6-digit code to <strong id="user-email"></strong></p>
                        <p class="text-muted small">Check your spam folder if not in inbox.</p>
                        
                        <form id="verifyForm">
                            <div class="mb-4">
                                <label for="verification_code" class="form-label">Enter Verification Code</label>
                                <input type="text" 
                                       class="form-control form-control-lg text-center" 
                                       id="verification_code" 
                                       maxlength="6" 
                                       placeholder="000000"
                                       pattern="[0-9]{6}"
                                       style="font-size: 24px; letter-spacing: 10px; font-weight: bold;"
                                       required>
                                <small class="text-muted">Code expires in <span id="timer">15:00</span></small>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-check-circle"></i> Verify & Create Account
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="resendBtn">
                                    <i class="bi bi-arrow-clockwise"></i> Resend Code
                                </button>
                            </div>
                        </form>
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
        // Get email from URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        const userEmail = urlParams.get('email');
        
        if (!userEmail) {
            window.location.href = 'register.php';
        }
        
        document.getElementById('user-email').textContent = userEmail;

        // Timer countdown (15 minutes)
        let timeLeft = 15 * 60; // 15 minutes in seconds
        
        function updateTimer() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            document.getElementById('timer').textContent = 
                `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            
            if (timeLeft > 0) {
                timeLeft--;
            } else {
                showAlert('Verification code expired. Please request a new one.', 'warning');
            }
        }
        
        setInterval(updateTimer, 1000);

        // Auto-format code input
        document.getElementById('verification_code').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Handle verification
        document.getElementById('verifyForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const code = document.getElementById('verification_code').value;

            if (code.length !== 6) {
                showAlert('Please enter a 6-digit code', 'warning');
                return;
            }

            try {
                const response = await fetch('http://localhost/IAmStillHere/backend/auth/verify_code.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        email: userEmail,
                        code: code
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showAlert('Account verified successfully! Redirecting...', 'success');
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 2000);
                } else {
                    showAlert(data.message, 'danger');
                }
            } catch (error) {
                console.log(error.message)
                showAlert('An error occurred. Please try again.', 'danger');
            }
        });

        // Handle resend
        document.getElementById('resendBtn').addEventListener('click', async () => {
            showAlert('Resending code...', 'info');
            window.location.href = `register.php?resend=${userEmail}`;
        });
    </script>
</body>

</html>