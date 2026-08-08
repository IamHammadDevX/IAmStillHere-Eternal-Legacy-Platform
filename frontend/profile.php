<?php
require_once __DIR__ . '/../config/config.php';

if (!is_logged_in()) {
  header('Location: login.php');
  exit;
}
?><!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" type="image/png" href="images/favicon.png">
  <title>Memorial Profile - IamAlwaysHere</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
  <link rel="stylesheet" href="css/style.css" />
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
          <a href="memorials.php" class="nav-link">Memorials</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="http://localhost/IAmStillHere/index.php">Home</a>
          </li>
          <li class="nav-item" id="nav-dashboard" style="display:none;">
            <a class="nav-link" href="dashboard.php">Dashboard</a>
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

  <!-- Cover Section -->
  <div id="cover-section" class="position-relative"
    style="height:300px;background:linear-gradient(135deg,#9b59b6,#3498db)">
    <img id="cover-image" src="" alt="Cover" class="w-100 h-100" style="object-fit:cover;display:none;" />
    <div class="position-absolute bottom-0 start-0 w-100 p-4"
      style="background:linear-gradient(to top,rgba(0,0,0,0.7),transparent)">
      <div class="container">
        <div class="d-flex align-items-end">
          <img id="profile-image" src="http://localhost/IAmStillHere/data/uploads/photos/default-profile.png"
            class="profile-photo" alt="Profile" />
          <div class="ms-3 text-white">
            <h2 id="profile-name">Loading...</h2>
            <p class="mb-0" id="profile-dates"></p>
          </div>
          <div class="ms-auto mb-3 d-flex gap-2 align-items-center"><div id="friend-action-area"></div><button id="edit-profile-btn" class="btn btn-light" data-bs-toggle="modal"
            data-bs-target="#editProfileModal">
            <i class="bi bi-pencil"></i> Edit Profile
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Main -->
  <div class="container mt-4">
    <div class="row">
      <!-- Sidebar -->
      <div class="col-md-4">
        <div class="card mb-4">
          <div class="card-body">
            <h5 class="card-title">About</h5>
            <p id="profile-bio" class="card-text">No bio available.</p>
          </div>
        </div>

        <div class="card mb-4">
          <div class="card-body">
            <h5 class="card-title">Memorial Settings</h5>
            <button id="memorial-settings-btn" class="btn btn-primary w-100" data-bs-toggle="modal"
              data-bs-target="#memorialSettingsModal">
              <i class="bi bi-gear"></i> Configure Memorial
            </button>
            <p id="memorial-status" class="text-muted small mt-2"></p>
          </div>
        </div>
      </div>

      <!-- Main content -->
      <div class="col-md-8">
        <ul class="nav nav-tabs mb-4 flex-nowrap overflow-auto" role="tablist">
          <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#posts-tab">Posts</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#about-tab">About</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#friends-tab">Friends</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#journeys-tab">Journeys</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#family-tab">Family</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#photos-tab">Photos</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#videos-tab">Videos</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#timeline-tab">Timeline</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tributes-tab">Tributes</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#events-tab">Events</a></li>
        </ul>

        <div class="tab-content">
          <div class="tab-pane fade show active" id="posts-tab">
            <div id="post-composer" class="card mb-4" style="display:none;">
              <div class="card-body">
                <form id="post-form">
                  <textarea id="post-body" class="form-control mb-3" rows="3" maxlength="5000" placeholder="What's on your mind?"></textarea>
                  <div class="d-flex flex-column flex-md-row gap-2 align-items-md-center">
                    <select id="post-privacy" class="form-select form-select-sm" style="max-width: 150px;">
                      <option value="public">Public</option>
                      <option value="family">Family</option>
                      <option value="private">Private</option><option value="friends">Friends</option><option value="specific_people">Specific People</option><option value="release_date">Release on Date</option><option value="release_event">Release on Event</option>
                    </select>
                    <input type="file" id="post-media" class="form-control form-control-sm" accept="image/*,video/*">
                    <button class="btn btn-primary btn-sm ms-md-auto" type="submit">Post</button>
                  </div>
                </form>
              </div>
            </div>
            <div id="posts-container"></div>
          </div>
          <div class="tab-pane fade" id="about-tab">
            <div class="card"><div class="card-body"><h5>About</h5><p id="profile-about-tab-bio" class="mb-0 text-muted">No bio available.</p></div></div>
          </div>
          <div class="tab-pane fade" id="journeys-tab">
            <div class="card mb-4"><div class="card-body"><div class="d-flex justify-content-between align-items-center mb-3"><h5 class="mb-0">Shared Journeys</h5><button id="journey-create-btn" class="btn btn-primary btn-sm" type="button">New Journey</button></div><div id="journeys-container" class="row g-3"></div></div></div><div id="journey-detail" class="card mb-4 d-none"><div class="card-body"></div></div>
          </div>
          <div class="tab-pane fade" id="photos-tab">
            <div id="photos-container" class="row g-3"><p class="text-muted">Photos from posts will appear here.</p></div>
          </div>
          <div class="tab-pane fade" id="videos-tab">
            <div id="videos-container" class="row g-3"><p class="text-muted">Videos from posts will appear here.</p></div>
          </div>
          <div class="tab-pane fade" id="timeline-tab">
            <div id="timeline-container"></div>
          </div>
          <div class="tab-pane fade" id="memories-tab">
            <div class="row" id="memories-grid"></div>
          </div>
          <div class="tab-pane fade" id="tributes-tab">
            <div id="tribute-form" style="display:none;">
              <div class="card mb-4">
                <div class="card-body">
                  <h5>Leave a Tribute</h5>
                  <form id="tributeForm">
                    <div class="mb-3">
                      <input type="text" class="form-control" id="tribute-name" placeholder="Your Name" required />
                    </div>
                    <div class="mb-3">
                      <input type="email" class="form-control" id="tribute-email" placeholder="Your Email (optional)" />
                    </div>
                    <div class="mb-3">
                      <textarea class="form-control" id="tribute-message" rows="4" placeholder="Share your memories..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Post Tribute</button>
                  </form>
                </div>
              </div>
            </div>
            <div id="tributes-container"></div>
          </div>
          <div class="tab-pane fade" id="events-tab">
            <div id="events-container"></div>
          </div>
          <div class="tab-pane fade" id="tributes-tab">
            <div id="tribute-form" style="display:none;">
              <div class="card mb-4">
                <div class="card-body">
                  <h5>Leave a Tribute</h5>
                  <form id="tributeForm">
                    <div class="mb-3">
                      <input type="text" class="form-control" id="tribute-name" placeholder="Your Name" required />
                    </div>
                    <div class="mb-3">
                      <input type="email" class="form-control" id="tribute-email" placeholder="Your Email (optional)" />
                    </div>
                    <div class="mb-3">
                      <textarea class="form-control" id="tribute-message" rows="4" placeholder="Share your memories..."
                        required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Post Tribute</button>
                  </form>
                </div>
              </div>
            </div>
            <div id="tributes-container"></div>
          </div>

          <div class="tab-pane fade" id="friends-tab">
            <div class="card mb-4">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h5 class="mb-0">Friends</h5>
                  <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#friend-requests-panel">Requests</button>
                </div>
                <div class="collapse mb-3" id="friend-requests-panel"><div id="friend-requests-container"></div></div>
                <div id="friends-list" class="row g-3"></div>
              </div>
            </div>
          </div>

          <div class="tab-pane fade" id="family-tab">
            <div class="card mb-4">
              <div class="card-body">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
                  <h5 class="mb-0">Family Members</h5>
                  <div class="btn-group btn-group-sm" role="group" aria-label="Family view toggle">
                    <button type="button" class="btn btn-outline-secondary active" id="family-grid-view-btn" data-family-view="grid">
                      <i class="bi bi-grid-3x3-gap"></i> Grid
                    </button>
                    <button type="button" class="btn btn-outline-secondary" id="family-list-view-btn" data-family-view="list">
                      <i class="bi bi-list-ul"></i> List
                    </button>
                    <button type="button" class="btn btn-outline-secondary" id="family-tree-view-btn" data-family-view="tree">
                      <i class="bi bi-diagram-3"></i> Tree
                    </button>
                  </div>
                </div>
                <div id="family-list" class="row g-3">
                </div>
              </div>
            </div>

            <div class="card p-3 mb-4" id="add-family-form">
              <h5>Add Family Member</h5>
              <div class="mb-3">
                <label for="familyEmail" class="form-label">Family Member Email</label>
                <input type="email" id="familyEmail" class="form-control" placeholder="Enter family member's email">
              </div>
              <div class="mb-3">
                <label for="relationship" class="form-label">Relationship</label>
                <input type="text" id="relationship" class="form-control" placeholder="e.g., Father, Sister, Friend">
              </div>
              <button class="btn btn-primary" id="btn-add-family">Add Member</button>
            </div>
          </div>
          <!-- END FAMILY TAB CONTENT -->

        </div>
      </div>
    </div>
  </div>

  <!-- Edit Profile Modal -->
  <div class="modal fade" id="editProfileModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Profile</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="profileForm">
            <div class="mb-3">
              <label class="form-label">Profile Photo</label>
              <input type="file" class="form-control" id="profile-photo-upload" accept="image/*" />
            </div>
            <div class="mb-3">
              <label class="form-label">Cover Photo</label>
              <input type="file" class="form-control" id="cover-photo-upload" accept="image/*" />
            </div>
            <div class="mb-3">
              <label class="form-label">Bio</label>
              <textarea class="form-control" id="bio-input" rows="4" placeholder="Tell your story..."></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label">Date of Birth</label>
              <input type="date" class="form-control" id="dob-input" />
            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Memorial Settings Modal -->
  <div class="modal fade" id="memorialSettingsModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Memorial Settings</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="memorialSettingsForm">
            <div class="alert alert-info">
              <i class="bi bi-info-circle"></i> Configure how your memorial page appears.
            </div>
            <div class="mb-3">
              <label class="form-label">Enable Memorial Mode</label>
              <select class="form-select" id="is-memorial-input">
                <option value="0">No - I'm still here</option>
                <option value="1">Yes - Make this a memorial page</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Date of Passing</label>
              <input type="date" class="form-control" id="dop-input" />
            </div>
            <div class="mb-3">
              <label class="form-label">Who can post tributes?</label>
              <select class="form-select" id="tribute-permission-input">
                <option value="public">Everyone</option>
                <option value="family">Family only</option>
                <option value="none">No one</option>
              </select>
            </div>
            <button type="submit" class="btn btn-primary">Save Settings</button>
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
        <a href="https://thisishammaddevx.netlify.app" target="_blank" class="text-light mx-2" title="Portfolio">
          <i class="bi bi-globe fs-4"></i>
        </a>
      </div>

      <!-- Copyright -->
      <p class="mb-0 small">        &copy; <span id="current-year"></span> <strong>KodeBros.</strong> All rights reserved.
      </p>
    </div>
  </footer>

  <script>
    document.getElementById("current-year").textContent = new Date().getFullYear();
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/auth.js"></script>
  <script src="js/privacy.js"></script><script src="js/profile.js"></script>
  <script src="js/posts.js"></script>
  <script src="js/journeys.js"></script>
  <script src="js/friends.js"></script>
  <script src="js/family.js"></script>
  <script src="js/search.js"></script>

<div class="modal fade" id="journeyModal" tabindex="-1"><div class="modal-dialog modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Shared journey</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><form id="journeyForm"><input type="hidden" id="journey-id"><label class="form-label">Title</label><input id="journey-title" class="form-control mb-2" maxlength="180" required><label class="form-label">Description</label><textarea id="journey-description" class="form-control mb-2" maxlength="5000"></textarea><div class="row"><div class="col-md-6"><label class="form-label">Start date</label><input id="journey-start" type="date" class="form-control mb-2"></div><div class="col-md-6"><label class="form-label">End date</label><input id="journey-end" type="date" class="form-control mb-2"></div></div><label class="form-label">Status</label><select id="journey-status" class="form-select mb-3"><option value="draft">Draft</option><option value="published">Published</option><option value="archived">Archived</option></select><div id="journey-privacy"></div><div id="journey-error" class="small text-danger mt-2"></div></form></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" form="journeyForm" class="btn btn-primary" id="journey-save">Save</button></div></div></div></div>
<div class="modal fade" id="journeyInviteModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Invite participant</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><input id="journey-invite-search" class="form-control mb-2" placeholder="Search friends/family"><div id="journey-invite-results" class="list-group small"></div></div></div></div></div>
<div class="modal fade" id="journeyItemModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Add journey item</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><select id="journey-item-select" class="form-select mb-2"></select><div class="text-center text-muted my-2">or add event note</div><input id="journey-item-title" class="form-control mb-2" placeholder="Event title"><textarea id="journey-item-description" class="form-control mb-2" placeholder="Description"></textarea><input id="journey-item-date" type="date" class="form-control mb-2"><div id="journey-item-error" class="small text-danger"></div></div><div class="modal-footer"><button id="journey-item-save" class="btn btn-primary" type="button">Add</button></div></div></div></div>
<div class="modal fade" id="postEditModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Edit post</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><textarea id="post-edit-body" class="form-control mb-3" maxlength="5000"></textarea><div id="post-edit-privacy"></div><div id="post-edit-error" class="small text-danger mt-2"></div></div><div class="modal-footer"><button type="button" class="btn btn-primary" id="post-edit-save">Save</button></div></div></div></div>
<div class="modal fade" id="profileEditMemoryModal" tabindex="-1"><div class="modal-dialog modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Edit memory</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><form id="profileEditMemoryForm"><input type="hidden" id="profile-edit-memory-id"><label class="form-label">Title</label><input id="profile-edit-memory-title" class="form-control mb-2" maxlength="255" required><label class="form-label">Description</label><textarea id="profile-edit-memory-description" class="form-control mb-2" maxlength="10000"></textarea><label class="form-label">Memory date</label><input id="profile-edit-memory-date" type="date" class="form-control mb-2"><label class="form-label">Folder</label><select id="profile-edit-memory-folder" class="form-select mb-3"><option value="0">No folder</option></select><div id="profile-edit-memory-privacy"></div><div id="profile-edit-memory-error" class="small text-danger mt-2"></div></form></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" form="profileEditMemoryForm" id="profile-edit-memory-save" class="btn btn-primary">Save changes</button></div></div></div></div>
</body>

</html>
