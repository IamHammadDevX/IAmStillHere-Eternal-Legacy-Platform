<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="images/favicon.png">
    <title>Family Request - IamAlwaysHere</title>
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
            <div class="col-md-8 col-lg-6">
                <div class="card">
                    <div class="card-body p-5 text-center">
                        <div id="loading-state">
                            <div class="spinner-border text-primary mb-3" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p>Processing your request...</p>
                        </div>

                        <div id="result-state" style="display: none;">
                            <i id="result-icon" class="display-1 mb-4"></i>
                            <h2 id="result-title" class="mb-3"></h2>
                            <p id="result-message" class="text-muted mb-4"></p>
                            <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                            <a href="profile.php" class="btn btn-outline-secondary">View Profile</a>
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
    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const requestId = urlParams.get('request_id');
        const action = urlParams.get('action');

        async function processRequest() {
            if (!requestId || !action) {
                showResult('error', 'Invalid Request', 'The link you followed is invalid or expired.');
                return;
            }

            try {
                const response = await fetch('http://localhost/IAmStillHere/backend/family/respond_request.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        request_id: requestId,
                        action: action
                    })
                });

                const data = await response.json();

                if (data.success) {
                    if (action === 'accept') {
                        showResult('success', 'Request Accepted!', data.message || 'You are now family members.');
                    } else {
                        showResult('info', 'Request Declined', data.message || 'The family request has been declined.');
                    }
                } else {
                    showResult('error', 'Error', data.message || 'Unable to process request.');
                }
            } catch (error) {
                console.error('Error:', error);
                showResult('error', 'Error', 'An unexpected error occurred. Please try again.');
            }
        }

        function showResult(type, title, message) {
            document.getElementById('loading-state').style.display = 'none';
            document.getElementById('result-state').style.display = 'block';

            const icon = document.getElementById('result-icon');
            const titleEl = document.getElementById('result-title');
            const messageEl = document.getElementById('result-message');

            if (type === 'success') {
                icon.className = 'bi bi-check-circle-fill text-success display-1 mb-4';
            } else if (type === 'info') {
                icon.className = 'bi bi-info-circle-fill text-info display-1 mb-4';
            } else {
                icon.className = 'bi bi-x-circle-fill text-danger display-1 mb-4';
            }

            titleEl.textContent = title;
            messageEl.textContent = message;
        }

        // Process on page load
        processRequest();
    </script>
</body>

</html>