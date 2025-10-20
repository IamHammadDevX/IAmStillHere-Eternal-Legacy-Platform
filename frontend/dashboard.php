<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="images/favicon.png">
    <title>Dashboard - IamAlwaysHere</title>
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
                        <a href="memorials.php" class="nav-link">Memorials</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="http://localhost/IAmStillHere/index.php">Home</a>
                    </li>
                    <li class="nav-item" id="nav-dashboard" style="display:none;">
                        <a class="nav-link" href="#">Dashboard</a>
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
        <h2 class="mb-4">My Dashboard</h2>

        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="bi bi-images display-4 text-primary"></i>
                        <h5 class="mt-2">Memories</h5>
                        <button class="btn btn-sm btn-primary mt-2" data-bs-toggle="modal"
                            data-bs-target="#uploadMemoryModal">Upload</button>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="bi bi-person-plus display-4 text-purple mb-3"></i>
                        <h5 class="mt-2">Family Requests</h5>
                        <a href="family_requests.php" class="btn btn-sm btn-secondary mt-2">
                            View Requests
                            <span class="badge bg-danger ms-1" id="request-count-badge" style="display:none;">0</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="bi bi-calendar-event display-4 text-success"></i>
                        <h5 class="mt-2">Milestones</h5>
                        <button class="btn btn-sm btn-success mt-2" data-bs-toggle="modal"
                            data-bs-target="#addMilestoneModal">Add</button>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="bi bi-clock-history display-4 text-warning"></i>
                        <h5 class="mt-2">Scheduled Events</h5>
                        <button class="btn btn-sm btn-warning mt-2" data-bs-toggle="modal"
                            data-bs-target="#scheduleEventModal">Schedule</button>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="bi bi-people display-4 text-info"></i>
                        <h5 class="mt-2">Tributes</h5>
                        <p class="mb-0" id="tribute-count">0</p>
                    </div>
                </div>
            </div>
        </div>

        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#memories-tab">Memories</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#timeline-tab">Timeline</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#events-tab">Events</a>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="memories-tab">
                <div class="row" id="memories-grid"></div>
            </div>
            <div class="tab-pane fade" id="timeline-tab">
                <div id="timeline-container"></div>
            </div>
            <div class="tab-pane fade" id="events-tab">
                <div id="events-container"></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="uploadMemoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Memory</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="memoryForm">
                        <div class="mb-3">
                            <label for="memory-title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="memory-title" required>
                        </div>
                        <div class="mb-3">
                            <label for="memory-description" class="form-label">Description</label>
                            <textarea class="form-control" id="memory-description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="memory-file" class="form-label">File</label>
                            <input type="file" class="form-control" id="memory-file" required
                                accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.rtf,.odt,.avi,.mkv,.mov,.3gp,.flv,.wmv,.mp3,.wav,.aac,.ogg,.flac,.m4a">
                            <small class="text-muted">
                                Supported: Images, Videos (MP4, 3GP, etc.), Audio (MP3, WAV, etc.),
                                Documents (PDF, Word, Excel, PowerPoint)
                            </small>
                        </div>
                        <div class="mb-3">
                            <label for="memory-date" class="form-label">Memory Date</label>
                            <input type="date" class="form-control" id="memory-date">
                        </div>
                        <div class="mb-3">
                            <label for="memory-privacy" class="form-label">Privacy</label>
                            <select class="form-select" id="memory-privacy">
                                <option value="public">Public</option>
                                <option value="family">Family Only</option>
                                <option value="private">Private</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addMilestoneModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Milestone</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="milestoneForm">
                        <div class="mb-3">
                            <label for="milestone-title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="milestone-title" required>
                        </div>
                        <div class="mb-3">
                            <label for="milestone-description" class="form-label">Description</label>
                            <textarea class="form-control" id="milestone-description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="milestone-date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="milestone-date" required>
                        </div>
                        <div class="mb-3">
                            <label for="milestone-category" class="form-label">Category</label>
                            <input type="text" class="form-control" id="milestone-category"
                                placeholder="e.g., Birth, Education, Career">
                        </div>
                        <div class="mb-3">
                            <label for="milestone-privacy" class="form-label">Privacy</label>
                            <select class="form-select" id="milestone-privacy">
                                <option value="public">Public</option>
                                <option value="family">Family Only</option>
                                <option value="private">Private</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success">Add Milestone</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="scheduleEventModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Schedule Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="eventForm">
                        <div class="mb-3">
                            <label for="event-title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="event-title" required>
                        </div>
                        <div class="mb-3">
                            <label for="event-message" class="form-label">Message</label>
                            <textarea class="form-control" id="event-message" rows="4" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="event-date" class="form-label">Scheduled Date & Time</label>
                            <input type="datetime-local" class="form-control" id="event-date" required>
                        </div>
                        <div class="mb-3">
                            <label for="event-privacy" class="form-label">Privacy</label>
                            <select class="form-select" id="event-privacy">
                                <option value="public">Public</option>
                                <option value="family">Family Only</option>
                                <option value="private">Private</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-warning">Schedule</button>
                    </form>
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
                <a href="https://thisishammaddevx.netlify.app" target="_blank" class="text-light mx-2"
                    title="Portfolio">
                    <i class="bi bi-globe fs-4"></i>
                </a>
            </div>

            <!-- Copyright -->
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
    <script src="js/dashboard.js"></script>
    <script src="js/search.js"></script>
</body>

</html>