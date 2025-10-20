<!DOCTYPE html>
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
          <button id="edit-profile-btn" class="btn btn-light ms-auto mb-3" data-bs-toggle="modal"
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
        <ul class="nav nav-tabs mb-4" role="tablist">
          <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#timeline-tab">Timeline</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#memories-tab">Memories</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tributes-tab">Tributes</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#events-tab">Events</a></li>
          <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#family-tab">Family</a></li>
        </ul>

        <div class="tab-content">
          <div class="tab-pane fade show active" id="timeline-tab">
            <div id="timeline-container"></div>
          </div>
          <div class="tab-pane fade" id="memories-tab">
            <div class="row" id="memories-grid"></div>
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

          <div class="tab-pane fade" id="family-tab">
            <div class="card mb-4">
              <div class="card-body">
                <h5 class="mb-3">Family Members</h5>
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
  <script src="js/profile.js"></script>
  <script src="js/family.js"></script>
  <script src="js/search.js"></script>

</body>

</html>