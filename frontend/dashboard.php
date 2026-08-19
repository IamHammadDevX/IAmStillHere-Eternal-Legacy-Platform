<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="images/favicon.png">
    <title>Dashboard - IamAlwaysHere</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css?v=2026081906">
</head>

<body class="dashboard-page app-page">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/index.php">
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
                    <li class="nav-item" id="nav-memories"><a href="memorials.php" class="nav-link">Memorials</a></li>
                    <li class="nav-item" id="nav-home">
                        <a class="nav-link" href="/index.php">Home</a>
                    </li>
                    <li class="nav-item" id="nav-dashboard" style="display:none;">
                        <a class="nav-link" href="dashboard.php">My Dashboard</a>
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
                        <a class="nav-link" href="profile.php" id="username-display">Public Profile</a>
                    </li>
                    <li class="nav-item" id="nav-logout" style="display:none;">
                        <a class="nav-link" href="#" onclick="logout()">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="dashboard-shell"><div class="container py-4 py-lg-5">
        <section class="dashboard-hero mb-4">
            <div>
                <span class="dashboard-kicker"><i class="bi bi-stars"></i> Your legacy workspace</span>
                <h2 class="mb-2 mt-3">My Dashboard</h2>
                <p class="text-muted mb-0">Upload memories, organize folders, manage milestones, automations, and your private vault.</p>
            </div>
            </section>

        <div class="row dashboard-feature-grid mb-4">
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
                        <h5 class="mt-2">Requests</h5>
                        <a href="family_requests.php" class="btn btn-sm btn-secondary mt-2">
                            Manage
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
                        <h5 class="mt-2">Events</h5>
                        <button class="btn btn-sm btn-warning mt-2" data-bs-toggle="modal"
                            data-bs-target="#scheduleEventModal">Schedule</button>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <a id="dashboard-tributes-link" class="dashboard-feature-link" href="profile.php#tributes-tab" aria-label="View all tributes">
                    <div class="card text-center h-100"><div class="card-body"><i class="bi bi-people display-4 text-info"></i><h5 class="mt-2">Tributes</h5><span class="btn btn-sm btn-outline-info mt-2">View</span></div></div>
                </a>
            </div>
        </div>


        <section class="card mb-4" id="on-this-day-section">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                    <div>
                        <h5 class="mb-1">News About You</h5>
                        <div class="small text-muted">Memories, posts, milestones, and journey moments from past years.</div>
                    </div>
                    <a class="btn btn-sm btn-outline-secondary" href="profile.php">Open Profile</a>
                </div>
                <div id="on-this-day-container" class="row g-3">
                    <div class="text-muted">Loading On This Day...</div>
                </div>
            </div>
        </section>
        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#memories-tab">Memories</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#timeline-tab">Timeline</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#events-tab">Events</a>
            </li>            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#automations-tab">Automations</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#vault-tab">Vault</a>
            </li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane fade show active" id="memories-tab">
                <div class="card mb-3"><div class="card-body"><div class="d-flex flex-wrap gap-2 justify-content-between align-items-center"><strong>Memory folders</strong><div class="d-flex gap-2"><input id="folder-search" class="form-control form-control-sm" placeholder="Search folders"><button class="btn btn-outline-primary memory-folder-add" id="new-folder-button" type="button"><i class="bi bi-folder-plus" aria-hidden="true"></i><span>New folder</span></button></div></div><div id="memory-folder-breadcrumb" class="small text-muted mt-2">All memories</div><div id="memory-folders" class="d-flex flex-wrap gap-2 mt-2"></div></div></div>
                <div class="row" id="memories-grid"></div>
            </div>
            <div class="tab-pane fade" id="timeline-tab">
                <div class="card shadow-sm mb-3 dashboard-timeline-card">
                    <div class="card-body">
                        <div class="dashboard-section-heading mb-4">
                            <h5 class="mb-1">Timeline</h5>
                            <div class="small text-muted">See your milestones and the updates within each life journey.</div>
                        </div>
                        <div id="timeline-container"></div>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="events-tab">
                <div class="card shadow-sm mb-3 dashboard-events-card">
                    <div class="card-body">
                        <div class="dashboard-section-heading mb-4">
                            <h5 class="mb-1">Events</h5>
                            <div class="small text-muted">View your upcoming and past scheduled events.</div>
                        </div>
                        <div id="events-container"></div>
                    </div>
                </div>
            </div>            <div class="tab-pane fade" id="automations-tab">
                <div class="card shadow-sm"><div class="card-body"><div class="d-flex justify-content-between align-items-center mb-3"><h5 class="mb-0">Event Automations</h5><button class="btn btn-primary btn-sm" type="button" onclick="openAutomationModal()">New automation</button></div><div id="automations-container"></div></div></div>
            </div>
            <div class="tab-pane fade" id="vault-tab">
 <div class="card mb-3 vault-shell"><div class="card-body">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
   <div><h5 class="mb-1">Secure Vault</h5><div class="small text-muted">Keep important documents private and encrypted.</div></div>
   <div class="d-flex gap-2"><button class="btn btn-outline-primary" id="vault-refresh-btn" class="btn btn-sm btn-outline-primary" type="button">Refresh</button><button id="vault-upload-trigger" class="btn btn-primary btn-sm" type="button">Upload Document</button></div>
  </div>
  <div id="vault-status" class="alert d-none" role="alert"></div>
  <div id="vault-reauth-box" class="alert alert-warning"><strong>Vault locked.</strong> Confirm your password to unlock documents.<form id="vault-reauth-form" class="d-flex flex-column flex-md-row gap-2 mt-2"><input id="vault-password" type="password" class="form-control" placeholder="Password" autocomplete="current-password"><button class="btn btn-warning" type="submit">Unlock Vault</button></form></div>
  <div class="vault-toolbar d-flex flex-column flex-md-row justify-content-between gap-2 align-items-md-center mb-3"><div id="vault-breadcrumb" class="small fw-semibold">Vault / All documents</div><div class="small text-muted">Encrypted documents - private access</div></div>
  <div class="row g-3"><div class="col-12"><div class="border rounded p-3 mb-3"><div class="d-flex justify-content-between align-items-center mb-2"><div><h6 class="mb-0">Documents</h6><div class="small text-muted">Upload a document to your secure vault.</div></div></div><form id="vault-upload-form" class="row g-2"><div class="col-md-6"><input id="vault-file" type="file" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.rtf,.odt,.jpg,.jpeg,.png" required></div><div class="col-md-3"><button class="btn btn-primary btn-sm w-100" type="submit">Upload Document</button></div></form></div><div id="vault-document-list" class="row g-3"></div></div></div>
  <details class="mt-4"><summary class="fw-semibold">Access and history</summary><div class="row g-3 mt-1"><div class="col-lg-6"><div class="border rounded p-3"><h6>Legal Counsel Access</h6><div class="small text-muted mb-2">Grant or revoke access for an authorized counsel user.</div><div class="input-group input-group-sm mb-2"><input id="vault-counsel-user-id" type="text" class="form-control" placeholder="Username or name"><button id="vault-grant" class="btn btn-outline-success" type="button">Grant</button><button id="vault-revoke" class="btn btn-outline-danger" type="button">Revoke</button></div><div id="vault-permission-list" class="small text-muted"></div></div></div><div class="col-lg-6"><div class="border rounded p-3"><h6>Audit History</h6><div id="vault-log-list" class="small text-muted"></div></div></div><div class="col-12"><div class="border rounded p-3"><h6>Open a shared vault</h6><div class="small text-muted mb-2">Only use this when counsel has authorized your account.</div><div class="d-flex gap-2"><input id="vault-owner-id" type="number" min="1" class="form-control form-control-sm" placeholder="Vault owner ID"><span id="vault-current-owner-label" class="small text-muted align-self-center"></span></div></div></div></div></details>
 </div></div>
</div></div></div></main>

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
                            <label for="memory-folder" class="form-label">Folder (optional)</label><select class="form-select mb-2" id="memory-folder"><option value="0">No folder</option></select>
                            <label for="memory-date" class="form-label">Memory Date</label>
                            <input type="date" class="form-control" id="memory-date">
                        </div>
                        <div class="mb-3">
                            <label for="memory-privacy" class="form-label">Privacy</label>
                            <select class="form-select" id="memory-privacy">
                                <option value="public">Public</option>
                                <option value="family">Family Only</option>
                                <option value="private">Private</option><option value="friends">Friends</option><option value="specific_people">Specific People</option><option value="release_date">Release on Date</option><option value="release_event">Release on Event</option>
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
                                <option value="private">Private</option><option value="friends">Friends</option><option value="specific_people">Specific People</option><option value="release_date">Release on Date</option><option value="release_event">Release on Event</option>
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
                            <label for="event-media" class="form-label">Photo or video <span class="text-muted small">(optional)</span></label>
                            <input type="file" class="form-control" id="event-media" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime">
                            <div class="form-text">Add one memorable photo or video, up to 25 MB.</div>
                            <div id="event-media-preview" class="event-upload-preview d-none"></div>
                        </div>
                        <div class="mb-3">
                            <label for="event-privacy" class="form-label">Privacy</label>
                            <select class="form-select" id="event-privacy">
                                <option value="public">Public</option>
                                <option value="family">Family Only</option>
                                <option value="private">Private</option><option value="friends">Friends</option><option value="specific_people">Specific People</option><option value="release_date">Release on Date</option><option value="release_event">Release on Event</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-warning" id="event-submit">Schedule Event</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="automationModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Event Automation</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <form id="automationForm"><input type="hidden" id="automation-id">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Title</label><input id="automation-title" class="form-control" maxlength="255" required></div>
                            <div class="col-md-6"><label class="form-label">Status</label><select id="automation-status" class="form-select"><option value="scheduled">Scheduled</option><option value="draft">Draft</option></select></div>
                            <div class="col-12"><label class="form-label">Description / message</label><textarea id="automation-description" class="form-control" rows="3"></textarea></div>
                            <div class="col-md-6"><label class="form-label">Trigger</label><select id="automation-trigger" class="form-select"><option value="specific_datetime">Specific date/time</option><option value="birthday">Birthday</option><option value="anniversary">Anniversary</option><option value="custom_recurring">Custom recurring date</option><option value="linked_milestone_event">Linked milestone/event</option></select></div>
                            <div class="col-md-6 automation-datetime"><label class="form-label">Run at</label><input id="automation-datetime" type="datetime-local" class="form-control"></div>
                            <div class="col-md-3 automation-recurring d-none"><label class="form-label">Month</label><input id="automation-month" type="number" min="1" max="12" class="form-control"></div>
                            <div class="col-md-3 automation-recurring d-none"><label class="form-label">Day</label><input id="automation-day" type="number" min="1" max="31" class="form-control"></div>
                            <div class="col-md-3 automation-linked d-none"><label class="form-label">Linked type</label><select id="automation-linked-type" class="form-select"><option value="event">Event</option><option value="milestone">Milestone</option></select></div>
                            <div class="col-md-3 automation-linked d-none"><label class="form-label">Linked ID</label><input id="automation-linked-id" type="number" min="1" class="form-control"></div>
                            <div class="col-12"><label class="form-label">Actions</label><div class="d-flex flex-wrap gap-3"><label><input type="checkbox" class="automation-action" value="notification" checked> In-app notification</label><label><input type="checkbox" class="automation-action" value="wall_post"> Scheduled wall post</label><label><input type="checkbox" class="automation-action" value="email"> Email/message</label></div></div>
                            <div class="col-12"><div id="automation-error" class="small text-danger"></div></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button><button id="automation-save" class="btn btn-primary" form="automationForm" type="submit">Save automation</button></div>
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

    <footer class="bg-dark text-light py-4 app-footer">
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
            <p class="mb-0 small">&copy; <span id="current-year"></span> <strong>KodeBros.</strong> All rights reserved.
            </p>
        </div>
    </footer>

    <script>
        document.getElementById("current-year").textContent = new Date().getFullYear();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/auth.js"></script>
    
    <script src="js/search.js"></script>
<div class="modal fade" id="editMemoryModal" tabindex="-1"><div class="modal-dialog modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Edit memory</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><form id="editMemoryForm"><input type="hidden" id="edit-memory-id"><label class="form-label">Title</label><input id="edit-memory-title" class="form-control mb-2" maxlength="255" required><label class="form-label">Description</label><textarea id="edit-memory-description" class="form-control mb-2" maxlength="10000"></textarea><label class="form-label">Memory date</label><input id="edit-memory-date" type="date" class="form-control mb-2"><label class="form-label">Folder</label><select id="edit-memory-folder" class="form-select mb-3"><option value="0">No folder</option></select><div id="edit-memory-privacy"></div><div id="edit-memory-error" class="small text-danger mt-2"></div></form></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" form="editMemoryForm" id="edit-memory-save" class="btn btn-primary">Save changes</button></div></div></div></div>
<div class="modal fade" id="editMilestoneModal" tabindex="-1"><div class="modal-dialog modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Edit milestone</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><form id="editMilestoneForm"><input type="hidden" id="edit-milestone-id"><label class="form-label">Title</label><input id="edit-milestone-title" class="form-control mb-2" maxlength="255" required><label class="form-label">Description</label><textarea id="edit-milestone-description" class="form-control mb-2" maxlength="10000"></textarea><label class="form-label">Date</label><input id="edit-milestone-date" type="date" class="form-control mb-2" required><label class="form-label">Category</label><input id="edit-milestone-category" class="form-control mb-3" maxlength="100"><div id="edit-milestone-privacy"></div><div id="edit-milestone-error" class="small text-danger mt-2"></div></form></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" form="editMilestoneForm" id="edit-milestone-save" class="btn btn-primary">Save changes</button></div></div></div></div>
<div class="modal fade" id="editFolderModal" tabindex="-1"><div class="modal-dialog modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Edit folder</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><form id="editFolderForm"><input type="hidden" id="edit-folder-id"><label class="form-label">Name</label><input id="edit-folder-name" class="form-control mb-2" maxlength="150" required><label class="form-label">Description</label><textarea id="edit-folder-description" class="form-control mb-2"></textarea><label class="form-label">Parent folder</label><select id="edit-folder-parent" class="form-select mb-3"><option value="0">No parent</option></select><div id="edit-folder-privacy"></div><div id="edit-folder-error" class="small text-danger mt-2"></div></form></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" form="editFolderForm" id="edit-folder-save" class="btn btn-primary">Save changes</button></div></div></div></div>
    <script src="js/privacy.js"></script><script src="js/dashboard.js?v=2026081822"></script>
</body>

</html>



