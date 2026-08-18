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
  <link rel="stylesheet" href="css/style.css?v=2026081815" />
</head>

<body class="profile-page app-page">

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
          <li class="nav-item" id="nav-memories">
            <a href="memorials.php" class="nav-link">Memorials</a>
          </li>
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

  <!-- Cover Section -->
  <div id="cover-section" class="position-relative"
    style="height:300px;background:linear-gradient(135deg,#9b59b6,#3498db)">
    <img id="cover-image" src="" alt="Cover" class="w-100 h-100" style="object-fit:cover;display:none;" />
    <div class="position-absolute bottom-0 start-0 w-100 p-4"
      style="background:linear-gradient(to top,rgba(0,0,0,0.7),transparent)">
      <div class="container">
        <div class="d-flex align-items-end">
          <img id="profile-image" src="/data/uploads/photos/default-profile.png"
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
  </div>

  <!-- Main -->
  <div class="container mt-4">
    <div class="row profile-main-row">
      <!-- Sidebar -->
      <div class="col-md-4 profile-main-sidebar">
        <div class="card mb-4">
          <div class="card-body">
            <h5 class="card-title">About Me</h5>
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
      <div class="col-md-8 profile-main-content">
        <ul class="nav nav-tabs profile-tabs mb-4 flex-nowrap overflow-auto" role="tablist">
          <li class="nav-item"><a class="nav-link text-nowrap active" data-bs-toggle="tab" href="#posts-tab">Posts</a></li>
          <li class="nav-item"><a class="nav-link text-nowrap" data-bs-toggle="tab" href="#ai-avatar-tab">AI Avatar</a></li>
          <li class="nav-item"><a class="nav-link text-nowrap" data-bs-toggle="tab" href="#autobiography-tab">Autobiography</a></li>
          <li class="nav-item"><a class="nav-link text-nowrap" data-bs-toggle="tab" href="#events-tab">AI Messages</a></li>
          <li class="nav-item"><a class="nav-link text-nowrap" data-bs-toggle="tab" href="#timeline-tab">Timeline</a></li>
          <li class="nav-item"><a class="nav-link text-nowrap" data-bs-toggle="tab" href="#journeys-tab">Journeys</a></li>
          <li class="nav-item"><a class="nav-link text-nowrap" data-bs-toggle="tab" href="#about-tab">About Me</a></li>
          <li class="nav-item"><a class="nav-link text-nowrap" data-bs-toggle="tab" href="#friends-tab">Friends</a></li>
          <li class="nav-item"><a class="nav-link text-nowrap" data-bs-toggle="tab" href="#family-tab">Family</a></li>
          <li class="nav-item"><a class="nav-link text-nowrap" data-bs-toggle="tab" href="#photos-tab">Photos</a></li>
          <li class="nav-item"><a class="nav-link text-nowrap" data-bs-toggle="tab" href="#videos-tab">Videos</a></li>
          <li class="nav-item"><a class="nav-link text-nowrap" data-bs-toggle="tab" href="#memories-tab">Memories</a></li>
          <li class="nav-item"><a class="nav-link text-nowrap" data-bs-toggle="tab" href="#tributes-tab">Tributes</a></li>
        </ul>

        <div class="tab-content">
          <div class="tab-pane fade show active" id="posts-tab">
            <div id="post-composer" class="card mb-4" style="display:none;">
              <div class="card-body">
                <form id="post-form">
                  <div class="post-editor mb-3"><div class="post-editor-toolbar" role="toolbar" aria-label="Post formatting"><button type="button" class="btn btn-sm btn-light" data-post-command="bold" title="Bold"><strong>B</strong></button><button type="button" class="btn btn-sm btn-light" data-post-command="italic" title="Italic"><em>I</em></button><button type="button" class="btn btn-sm btn-light" data-post-command="underline" title="Underline"><u>U</u></button><button type="button" class="btn btn-sm btn-light" data-post-command="insertUnorderedList" title="Bullet list"><i class="bi bi-list-ul"></i></button><button type="button" class="btn btn-sm btn-light" id="post-emoji-button" title="Add emoji">&#x1F60A;</button></div><div id="post-body" class="form-control post-editor-surface" contenteditable="true" role="textbox" aria-multiline="true" data-placeholder="What's on your mind?"></div></div>
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
          <div class="tab-pane fade" id="ai-avatar-tab">
            <div class="card mb-4">
              <div class="card-body">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                  <div class="d-flex align-items-center gap-2">
                    <img id="ai-avatar-photo" src="/data/uploads/photos/default-profile.png" alt="Your profile photo" class="ai-avatar-header-photo">
                    <div>
                      <h5 class="mb-0 feature-heading">AI Avatar <button type="button" class="feature-info" aria-label="About AI Avatar" data-tooltip="The AI Avatar learns only from the memories, milestones, and profile details you have approved as knowledge sources. When someone asks it a question, it answers in this personâ€™s voice, drawing solely from that approved content â€” nothing outside it is ever used.">i</button></h5>
                      <div class="small text-muted">Grounded in approved memories and profile knowledge.</div>
                    </div>
                  </div>
                  <button type="button" id="ai-avatar-delete" class="btn btn-outline-danger btn-sm">Clear</button>
                </div>
                <div class="border rounded p-3 mb-3 bg-white ai-knowledge-panel">
                  <div class="d-flex flex-column flex-md-row justify-content-between gap-2 align-items-md-center mb-3">
                    <div>
                      <strong>Knowledge sources</strong>
                      <div class="small text-muted">Pick what AI Avatar can learn. You stay in control.</div>
                    </div>
                    <button type="button" id="ai-avatar-build" class="btn btn-outline-primary btn-sm">Build selected</button>
                  </div>
                  <div class="d-flex flex-column flex-lg-row gap-2 mb-2">
                    <input id="ai-avatar-source-search" class="form-control form-control-sm" placeholder="Search sources">
                    <div class="btn-group btn-group-sm flex-wrap" id="ai-avatar-source-filters" role="group">
                      <button type="button" class="btn btn-outline-secondary active" data-filter="all">All</button>
                      <button type="button" class="btn btn-outline-secondary" data-filter="profile">Bio</button>
                      <button type="button" class="btn btn-outline-secondary" data-filter="memory">Memories</button>
                      <button type="button" class="btn btn-outline-secondary" data-filter="milestone">Milestones</button>
                      <button type="button" class="btn btn-outline-secondary" data-filter="post">Posts</button>
                      <button type="button" class="btn btn-outline-secondary" data-filter="journey">Journeys</button>
                    </div>
                    <button type="button" id="ai-avatar-select-visible" class="btn btn-outline-secondary btn-sm text-nowrap">Select visible</button>
                  </div>
                  <div id="ai-avatar-source-summary" class="small text-muted mb-2"></div>
                  <div id="ai-avatar-sources" class="small text-muted ai-source-list">Loading sources...</div>
                </div>
                <div id="ai-avatar-messages" class="border rounded p-3 mb-3 bg-light" style="min-height:260px;max-height:460px;overflow:auto;"></div>
                <form id="ai-avatar-form" class="d-flex flex-column flex-md-row gap-2">
                  <input id="ai-avatar-question" class="form-control" maxlength="1200" placeholder="Ask about career, marriage, school life, memories...">
                  <button id="ai-avatar-send" class="btn btn-primary" type="submit">Send</button>
                </form>
                <div id="ai-avatar-status" class="small text-muted mt-2"></div>
              </div>
            </div>
          </div>
          <div class="tab-pane fade" id="autobiography-tab">
            <div class="card mb-4">
              <div class="card-body">
                <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                  <div>
                    <h5 class="mb-1 feature-heading">AI Autobiography <button type="button" class="feature-info" aria-label="About AI Autobiography" data-tooltip="AI reads the memories, milestones, and posts you have approved, then drafts each chapter in a natural, first-person narrative. Nothing is added to your story until you review and approve it â€” you can edit, regenerate, or leave any chapter untouched.">i</button></h5>
                    <div class="small text-muted">Build a structured life story from approved AI knowledge sources.</div>
                  </div>
                  <div class="d-flex flex-wrap gap-2">
                    <button type="button" id="autobio-generate" class="btn btn-primary btn-sm">Generate</button>
                    <button type="button" id="autobio-save" class="btn btn-outline-primary btn-sm">Save draft</button>
                    <button type="button" id="autobio-publish" class="btn btn-outline-success btn-sm">Publish</button>
                  </div>
                </div>
                <input id="autobio-title" class="form-control mb-3" maxlength="180" value="My Life Story" aria-label="Autobiography title">
                <div id="autobio-status" class="small text-muted mb-3"></div>
                <div id="autobio-sections" class="autobio-sections mb-4">
                  <div class="text-muted text-center py-4">Generate your autobiography after building AI knowledge.</div>
                </div>
                <div class="d-flex flex-column flex-md-row justify-content-between gap-1 align-items-md-center mb-2">
                  <h6 class="mb-0 feature-heading">Life Timeline / Pictograph <button type="button" class="feature-info" aria-label="About Life Timeline" data-tooltip="Organizes important life milestones by date and expandable progress updates.">i</button></h6>
                  <span class="small text-muted">Existing dated memories, milestones, posts, and journeys</span>
                </div>
                <div id="autobio-timeline" class="autobio-timeline"></div>
                <div id="autobio-timeline-pagination" class="autobio-timeline-pagination" aria-label="Life timeline pages"></div>
              </div>
            </div>
          </div>
          <div class="tab-pane fade" id="about-tab">
            <div class="card about-bio-card mb-4"><div class="card-body"><span class="about-eyebrow"><i class="bi bi-person-vcard"></i> Profile</span><h5>About</h5><p id="profile-about-tab-bio" class="mb-0 text-muted">No bio available.</p></div></div>
            <section class="about-life-journal" aria-labelledby="about-life-journal-title">
              <div class="about-journal-heading"><div><span class="about-eyebrow"><i class="bi bi-newspaper"></i>Journals</span><h5 id="about-life-journal-title">Memories, milestones & events</h5><p>Highlights from this person's story. Open any card to see the original item.</p></div></div>
              <div id="about-life-journal" class="about-life-journal-list"><div class="about-journal-loading">Loading life highlights...</div></div>
              <div id="about-life-journal-pagination" class="about-life-journal-pagination" aria-label="Life journal pages"></div>
            </section>
          </div>
          <div class="tab-pane fade" id="journeys-tab">
            <div class="card mb-4 journeys-overview-card"><div class="card-body"><div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-2"><div><h5 class="mb-1 feature-heading">Shared Journeys <button type="button" class="feature-info" aria-label="About Shared Journeys" data-tooltip="Build a life story together from approved memories, milestones, and event notes.">i</button></h5><p class="small text-muted mb-0">Build a life story together with approved memories, milestones, and event notes.</p></div><button id="journey-create-btn" class="btn btn-primary btn-sm" type="button">New Journey</button></div><div id="journeys-container" class="row g-3 mt-1"></div></div></div><div id="journey-detail" class="card mb-4 d-none"><div class="card-body"></div></div>
          </div>
          <div class="tab-pane fade" id="photos-tab">
            <div id="photos-container"></div>
          </div>
          <div class="tab-pane fade" id="videos-tab">
            <div id="videos-container"></div>
          </div>
          <div class="tab-pane fade" id="timeline-tab">
            <div class="card mb-4"><div class="card-body"><div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3"><div><h5 class="mb-1 feature-heading">Timeline <button type="button" class="feature-info" aria-label="About Timeline" data-tooltip="View milestones chronologically with expandable child progress updates.">i</button></h5><p class="small text-muted mb-0">See personal milestones alongside notable world events from the same years.</p></div></div><div id="timeline-container"></div><div id="world-events-status" class="small text-muted mt-2"></div></div></div>
          </div>
          <div class="tab-pane fade" id="memories-tab">
            <div id="memories-container"><div class="row" id="memories-grid"></div></div>
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
            <div class="card mb-4" id="personalized-messages-card">
              <div class="card-body">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
                  <div><h5 class="mb-1 feature-heading">Personalized AI Messages <button type="button" class="feature-info" aria-label="About Personalized AI Messages" data-tooltip="Draft a message yourself or have AI write it for you, choose an email or post, then set the date it should go out. AI Messages holds it securely and delivers it automatically on that date â€” you can edit or cancel any message anytime before it sends.">i</button></h5><div class="small text-muted">Generate owner-approved future messages for birthdays, weddings, graduations, and milestones.</div></div>
                  <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#personalized-message-form-wrap">New message</button>
                </div>
                <div class="collapse show" id="personalized-message-form-wrap">
                  <form id="personalized-message-form" class="row g-2 mb-3">
                    <input type="hidden" id="pm-id">
                    <div class="col-md-4"><label class="form-label small">Recipient</label><select id="pm-recipient-user" class="form-select"><option value="">External email / wall post</option></select></div>
                    <div class="col-md-4"><label class="form-label small">Recipient email <span class="text-danger" aria-hidden="true">*</span></label><input id="pm-recipient-email" type="email" class="form-control" placeholder="email@example.com"></div>
                    <div class="col-md-4"><label class="form-label small">Recipient name</label><input id="pm-recipient-name" class="form-control" placeholder="Name"></div>
                    <div class="col-md-3"><label class="form-label small">Relationship</label><input id="pm-relationship" class="form-control" placeholder="Daughter, friend..."></div>
                    <div class="col-md-3"><label class="form-label small">Event <span class="text-danger" aria-hidden="true">*</span></label><select id="pm-event-type" class="form-select"><option value="birthday">Birthday</option><option value="graduation">Graduation</option><option value="wedding">Wedding</option><option value="anniversary">Anniversary</option><option value="new_job">New Job</option><option value="new_baby">New Baby</option><option value="custom">Custom</option></select></div>
                    <div class="col-md-3"><label class="form-label small">Trigger date</label><input id="pm-trigger-at" type="datetime-local" class="form-control"></div>
                    <div class="col-md-3"><label class="form-label small">Delivery</label><select id="pm-delivery" class="form-select"><option value="notification">Platform notification</option><option value="email">Email</option><option value="wall_post">Wall post</option></select></div>
                    <div class="col-md-4"><label class="form-label small">Tone/style</label><input id="pm-tone" class="form-control" value="Warm and sincere"></div>
                    <div class="col-md-8"><label class="form-label small">Optional instructions</label><input id="pm-instructions" class="form-control" placeholder="Mention pride, keep it short..."></div>
                    <div class="col-12"><label class="form-label small">Draft message</label><textarea id="pm-message" class="form-control" rows="6" placeholder="Generate a draft first, then edit before scheduling."></textarea></div>
                    <div class="col-12 d-flex flex-wrap gap-2 align-items-center"><button id="pm-generate" class="btn btn-primary" type="button">Generate</button><button id="pm-save" class="btn btn-outline-primary" type="button">Save</button><button id="pm-schedule" class="btn btn-outline-success" type="button">Schedule</button><div class="dropdown"><button class="btn btn-outline-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="More message actions"><i class="bi bi-three-dots"></i></button><ul class="dropdown-menu"><li><button id="pm-cancel" class="dropdown-item text-danger" type="button">Cancel</button></li></ul></div></div>
                  </form>
                </div>
                <div id="pm-status" class="small text-muted mb-2"></div>
                <div id="personalized-messages-list" class="row g-2"></div>
              </div>
            </div>
            <div class="card mb-4" id="gifts-card">
              <div class="card-body">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                  <div>
                    <span class="badge rounded-pill text-bg-light border mb-2"><i class="bi bi-gift me-1"></i> External gift partner</span>
                    <h5 class="mb-1">Gifts for Special Occasions</h5>
                    <div class="small text-muted">Browse birthday, anniversary, wedding, and celebration gifts on Phoolwala. Purchase and checkout happen securely on Phoolwala.</div>
                  </div>
                  <a id="phoolwala-gift-link" class="btn btn-primary" href="https://www.phoolwala.com" target="_blank" rel="noopener noreferrer external">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Browse Gifts on Phoolwala
                  </a>
                </div>
                <div class="alert alert-light border small text-muted mt-3 mb-0">
                  IAmStillHere does not process gift payments, store customer/payment details, sync products, or track Phoolwala orders.
                </div>
              </div>
            </div>
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
                <div class="d-flex justify-content-between align-items-center mb-3 gap-2">
                  <h5 class="mb-0">Friends</h5>
                  <div class="d-flex gap-2"><button id="friends-add-button" class="btn btn-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#friends-add-panel">Add Friend</button><button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#friend-requests-panel">Requests</button></div>
                </div>
                <div class="collapse mb-3" id="friends-add-panel"><form id="friends-search-form" class="d-flex flex-column flex-sm-row gap-2"><input id="friends-search-input" class="form-control" type="search" minlength="2" placeholder="Search by username or email" autocomplete="off"><button class="btn btn-primary" type="submit">Search</button></form><div id="friends-search-results" class="mt-3" aria-live="polite"></div></div>
                <div class="collapse mb-3" id="friend-requests-panel"><div id="friend-requests-container"></div></div>
                <div class="member-list-search mb-3"><i class="bi bi-search" aria-hidden="true"></i><input id="friends-list-search" class="form-control" type="search" placeholder="Search your friends by name or username" autocomplete="off"><span id="friends-list-count" class="small text-muted"></span></div>
                <div id="friends-list" class="row g-3"></div><div id="people-you-may-know" class="mt-4"></div>
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
                <div class="member-list-search mb-3"><i class="bi bi-search" aria-hidden="true"></i><input id="family-list-search" class="form-control" type="search" placeholder="Search family members by name or relationship" autocomplete="off"><span id="family-list-count" class="small text-muted"></span></div>
                <div id="family-list" class="row g-3">
                </div>
              </div>
            </div>

            <div class="card p-3 mb-4" id="add-family-form">
              <div class="d-flex justify-content-between align-items-center gap-2"><div><h5 class="mb-1">Family Members</h5><p class="small text-muted mb-0">Search by username or email, then send a family request.</p></div><button id="family-add-toggle" class="btn btn-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#family-add-panel">Add member</button></div>
              <div id="family-add-panel" class="collapse mt-3"><form id="family-search-form" class="row g-2"><div class="col-md-7"><input type="search" id="family-search-input" class="form-control" placeholder="Search username or email" minlength="2" autocomplete="off"></div><div class="col-md-3"><input type="text" id="relationship" class="form-control" placeholder="Relationship (e.g. Sister)" required></div><div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Search</button></div></form><div id="family-search-results" class="mt-3" aria-live="polite"></div></div>
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
            <button type="submit" class="btn btn-primary" id="profile-save-btn">Save Changes</button><div id="profile-save-status" class="small mt-2" role="status" aria-live="polite"></div>
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

  <footer class="bg-dark text-light py-4 app-footer">
    <div class="container text-center">
      <div class="mb-2">
        <!-- Social Links -->
        <a href="https://github.com/IamHammadDevX" target="_blank" class="text-light mx-2" title="GitHub">
          <i class="bi bi-github fs-4"></i>
        </a>
        <a href="https://www.iamhammaddevx.app/" target="_blank" class="text-light mx-2" title="Portfolio">
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
  <script src="js/auth.js?v=2026081123"></script>
  <script src="js/privacy.js"></script><script src="js/profile.js?v=2026081805"></script>
  <script src="js/posts.js?v=2026081801"></script>
  <script src="js/ai_avatar.js?v=2026081601"></script>
  <script src="js/ai_autobiography.js?v=2026081601"></script>
  <script src="js/personalized_messages.js?v=2026081801"></script>
  <script src="js/gifts.js?v=2026081121"></script>
  <script id="ai-avatar-fallback-init">window.addEventListener("load",function(){setTimeout(function(){if(window.loadAiAvatarSources){window.loadAiAvatarSources();}},500);});</script>
  <script src="js/journeys.js?v=2026081601"></script>
  <script src="js/friends.js?v=2026081601"></script>
  <script src="js/family.js?v=2026081601"></script>
  <script src="js/search.js"></script>

<div class="modal fade" id="journeyModal" tabindex="-1"><div class="modal-dialog modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Shared journey</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><form id="journeyForm"><input type="hidden" id="journey-id"><label class="form-label">Title</label><input id="journey-title" class="form-control mb-2" maxlength="180" required><label class="form-label">Description</label><textarea id="journey-description" class="form-control mb-2" maxlength="5000"></textarea><label class="form-label">Journey cover <span class="text-muted small">(optional photo or video)</span></label><input id="journey-cover-media" type="file" class="form-control mb-2" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime"><div class="row"><div class="col-md-6"><label class="form-label">Start date</label><input id="journey-start" type="date" class="form-control mb-2"></div><div class="col-md-6"><label class="form-label">End date</label><input id="journey-end" type="date" class="form-control mb-2"></div></div><label class="form-label">Status</label><select id="journey-status" class="form-select mb-3"><option value="draft">Draft</option><option value="published">Published</option><option value="archived">Archived</option></select><div id="journey-privacy"></div><div id="journey-error" class="small text-danger mt-2"></div></form></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" form="journeyForm" class="btn btn-primary" id="journey-save">Save</button></div></div></div></div>
<div class="modal fade" id="journeyInviteModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Invite participant</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><input id="journey-invite-search" class="form-control mb-2" placeholder="Search friends/family"><div id="journey-invite-results" class="list-group small"></div></div></div></div></div>
<div class="modal fade" id="journeyItemModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Add journey item</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><select id="journey-item-select" class="form-select mb-2"></select><div class="text-center text-muted my-2">or create a new note or media contribution</div><input id="journey-item-title" class="form-control mb-2" placeholder="Title"><textarea id="journey-item-description" class="form-control mb-2" placeholder="Description"></textarea><input id="journey-item-date" type="date" class="form-control mb-2"><label class="form-label small">Photo or video <span class="text-muted">(optional)</span></label><input id="journey-item-media" type="file" class="form-control mb-2" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime"><div class="small text-muted">Adding media creates a shared contribution. Participant uploads are sent to the owner for approval.</div><div id="journey-item-error" class="small text-danger mt-2"></div></div><div class="modal-footer"><button id="journey-item-save" class="btn btn-primary" type="button">Add item</button></div></div></div></div>
<div class="modal fade" id="postEditModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Edit post</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><textarea id="post-edit-body" class="form-control mb-3" maxlength="5000"></textarea><div id="post-edit-privacy"></div><div id="post-edit-error" class="small text-danger mt-2"></div></div><div class="modal-footer"><button type="button" class="btn btn-primary" id="post-edit-save">Save</button></div></div></div></div>
<div class="modal fade" id="profileEditMemoryModal" tabindex="-1"><div class="modal-dialog modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Edit memory</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><form id="profileEditMemoryForm"><input type="hidden" id="profile-edit-memory-id"><label class="form-label">Title</label><input id="profile-edit-memory-title" class="form-control mb-2" maxlength="255" required><label class="form-label">Description</label><textarea id="profile-edit-memory-description" class="form-control mb-2" maxlength="10000"></textarea><label class="form-label">Memory date</label><input id="profile-edit-memory-date" type="date" class="form-control mb-2"><label class="form-label">Folder</label><select id="profile-edit-memory-folder" class="form-select mb-3"><option value="0">No folder</option></select><div id="profile-edit-memory-privacy"></div><div id="profile-edit-memory-error" class="small text-danger mt-2"></div></form></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" form="profileEditMemoryForm" id="profile-edit-memory-save" class="btn btn-primary">Save changes</button></div></div></div></div>
</body>

</html>


