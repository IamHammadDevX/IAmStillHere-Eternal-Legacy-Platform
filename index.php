<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="frontend/images/favicon.png">
    <meta name="description" content="A private, beautiful home for memories, family, and digital legacy."><title>IamAlwaysHere — Keep their story close</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="frontend/css/style.css?v=2026081701">
</head>

<body class="landing-page">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
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
                    <li class="nav-item" id="nav-home">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>                    <li class="nav-item" id="nav-memories"><a class="nav-link" href="frontend/memorials.php">Memories</a></li>
                    <li class="nav-item" id="nav-dashboard" style="display:none;">
                        <a class="nav-link" href="frontend/dashboard.php">My Dashboard</a>
                    </li>
                    <li class="nav-item" id="nav-admin" style="display:none;">
                        <a class="nav-link" href="frontend/admin.php">Admin</a>
                    </li>
                    <li class="nav-item" id="nav-login">
                        <a class="nav-link" href="frontend/login.php">Login</a>
                    </li>
                    <li class="nav-item" id="nav-register">
                        <a class="nav-link" href="frontend/register.php">Register</a>
                    </li>
                    <li class="nav-item" id="nav-profile" style="display:none;">
                        <a class="nav-link" href="frontend/profile.php" id="username-display">Public Profile</a>
                    </li>
                    <li class="nav-item" id="nav-logout" style="display:none;">
                        <a class="nav-link" href="#" onclick="logout()">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="home-hero position-relative overflow-hidden">
        <div class="container position-relative py-5">
            <div class="row align-items-center g-5 py-lg-5">
                <div class="col-lg-7 text-center text-lg-start">
                    <span class="home-kicker">Digital legacy, protected with care</span>
                    <h1 class="display-3 fw-bold mt-3 mb-3">Keep every story close, even when life moves forward.</h1>
                    <p class="lead mb-4">IamAlwaysHere brings memories, family connections, private documents, AI legacy tools, and future messages into one calm memorial platform.</p>
                    <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center justify-content-lg-start">
                        <a href="frontend/register.php" class="btn btn-primary btn-lg px-4"><i class="bi bi-plus-circle me-2"></i>Create Memorial</a>
                        <a href="frontend/memorials.php" class="btn btn-light btn-lg px-4"><i class="bi bi-collection-heart me-2"></i>View Memorials</a>
                    </div>
                    <div class="home-trust-row mt-4">
                        <span><i class="bi bi-shield-lock"></i> Layered privacy</span>
                        <span><i class="bi bi-clock-history"></i> Scheduled legacy</span>
                        <span><i class="bi bi-stars"></i> AI-assisted remembrance</span>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="home-hero-card mx-auto">
                        <div class="home-orbit home-orbit-one"></div>
                        <div class="home-orbit home-orbit-two"></div>
                        <div class="home-memory-preview">
                            <div class="home-avatar"><i class="bi bi-person-heart"></i></div>
                            <div>
                                <h5 class="mb-1">A living timeline</h5>
                                <p class="mb-0 small">Memories, milestones, posts, journeys, tributes, and future messages - organized with dignity.</p>
                            </div>
                        </div>
                        <div class="home-mini-card"><i class="bi bi-camera-fill text-primary"></i><span>Photos & videos</span></div>
                        <div class="home-mini-card"><i class="bi bi-diagram-3-fill text-success"></i><span>Family tree</span></div>
                        <div class="home-mini-card"><i class="bi bi-lock-fill text-warning"></i><span>Secure vault</span></div>
                        <div class="home-mini-card"><i class="bi bi-robot text-info"></i><span>AI avatar</span></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="container home-section py-5">
        <div class="text-center mb-5">
            <span class="home-kicker dark"><i class="bi bi-grid-1x2"></i> Platform features</span>
            <h2 class="fw-bold mt-3">Everything families need to preserve, protect, and revisit a life story.</h2>
            <p class="mx-auto text-muted" style="max-width: 760px;">Designed for memorial profiles, private family archives, future messages, and thoughtful AI experiences  -  without losing control of privacy.</p>
        </div>

        <div class="row g-4 home-feature-grid">
            <div class="col-md-6 col-xl-4"><div class="home-feature-card"><div class="home-feature-icon text-primary"><i class="bi bi-camera-fill"></i></div><h5>Share Memories</h5><p>Upload photos, videos, audio, and documents to preserve precious moments forever.</p></div></div>
            <div class="col-md-6 col-xl-4"><div class="home-feature-card"><div class="home-feature-icon text-success"><i class="bi bi-calendar-event"></i></div><h5>Timeline & Milestones</h5><p>Create a beautiful timeline of life events, achievements, education, work, and family moments.</p></div></div>
            <div class="col-md-6 col-xl-4"><div class="home-feature-card"><div class="home-feature-icon text-danger"><i class="bi bi-chat-heart"></i></div><h5>Tributes & Messages</h5><p>Friends and family can leave heartfelt tributes and share memories around a memorial profile.</p></div></div>
            <div class="col-md-6 col-xl-4"><div class="home-feature-card"><div class="home-feature-icon text-info"><i class="bi bi-shield-check"></i></div><h5>Layered Privacy Control</h5><p>Choose public, family, friends, specific people, private, or release content on a date/event.</p></div></div>
            <div class="col-md-6 col-xl-4"><div class="home-feature-card"><div class="home-feature-icon text-warning"><i class="bi bi-clock-history"></i></div><h5>Scheduled Messages & Posts</h5><p>Prepare future posts and personal messages for birthdays, anniversaries, and life events.</p></div></div>
            <div class="col-md-6 col-xl-4"><div class="home-feature-card"><div class="home-feature-icon text-secondary"><i class="bi bi-folder2-open"></i></div><h5>Albums & Memory Folders</h5><p>Organize memories into folders, nested collections, and privacy-aware family archives.</p></div></div>
            <div class="col-md-6 col-xl-4"><div class="home-feature-card"><div class="home-feature-icon text-success"><i class="bi bi-diagram-3-fill"></i></div><h5>Family Tree & Friends</h5><p>Build visual family relationships and manage friends separately from family connections.</p></div></div>
            <div class="col-md-6 col-xl-4"><div class="home-feature-card"><div class="home-feature-icon text-primary"><i class="bi bi-map-fill"></i></div><h5>Shared Journeys</h5><p>Create shared vacations, weddings, school memories, and life journeys with approval controls.</p></div></div>
            <div class="col-md-6 col-xl-4"><div class="home-feature-card"><div class="home-feature-icon text-dark"><i class="bi bi-safe2-fill"></i></div><h5>Secure Vault</h5><p>Store confidential/legal documents separately with encryption, audit logs, and strict access.</p></div></div>
            <div class="col-md-6 col-xl-4"><div class="home-feature-card"><div class="home-feature-icon text-info"><i class="bi bi-robot"></i></div><h5>AI Avatar & Knowledge Base</h5><p>Let approved memories and profile knowledge power a respectful, grounded AI conversation.</p></div></div>
            <div class="col-md-6 col-xl-4"><div class="home-feature-card"><div class="home-feature-icon text-primary"><i class="bi bi-journal-richtext"></i></div><h5>AI Autobiography</h5><p>Generate, edit, and publish a structured life story with a chronological pictograph timeline.</p></div></div>
            <div class="col-md-6 col-xl-4"><div class="home-feature-card"><div class="home-feature-icon text-danger"><i class="bi bi-bell-fill"></i></div><h5>Notifications & Admin Tools</h5><p>Stay updated on requests, comments, automations, and manage the platform from admin views.</p></div></div>
        </div>
    </section>

    <section class="container pb-5">
        <div class="home-cta-card text-center">
            <h2 class="fw-bold">Create a place where memories stay organized, protected, and alive.</h2>
            <p class="text-muted mx-auto mb-4" style="max-width: 680px;">Start with one memory, one milestone, or one message. The platform grows gently into a complete digital legacy.</p>
            <a href="frontend/register.php" class="btn btn-primary btn-lg me-sm-2 mb-2"><i class="bi bi-heart-fill me-2"></i>Start Your Legacy</a>
            <a href="frontend/memorials.php" class="btn btn-outline-primary btn-lg mb-2">Explore Memorials</a>
        </div>
    </section>
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

    <!-- ===== Footer Start ===== -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container text-center">
            <div class="mb-2">
                <!-- Social Links -->
                <a href="https://github.com/IamHammadDevX" target="_blank" class="text-light mx-2" title="GitHub">
                    <i class="bi bi-github fs-4"></i>
                </a>
                <a href="https://https://www.iamhammaddevx.app//" target="_blank" class="text-light mx-2" title="Portfolio">
                    <i class="bi bi-globe fs-4"></i>
                </a>
            </div>

            <!-- Copyright -->
            <p class="mb-0 small">
                &copy; <span id="current-year"></span> <strong>KodeBros.</strong> All rights reserved.
            </p>
        </div>
    </footer>
    <!-- ===== Footer End ===== -->

    <!-- Script to auto-update year -->
    <script>
        document.getElementById("current-year").textContent = new Date().getFullYear();
    </script>

    <!-- <footer class="bg-dark text-white text-center py-4 mt-5">
        <div class="container">
            <p>&copy; 2024 IamAlwaysHere. Honoring memories, celebrating lives.</p>
            <p class="small">A memorial social networking platform</p>
        </div>
    </footer> -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="frontend/js/auth.js"></script>
    <script src="frontend/js/search.js"></script>
</body>

</html>
