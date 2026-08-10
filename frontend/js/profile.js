const urlParams = new URLSearchParams(window.location.search);
const profileUserId = urlParams.get('user_id');
let profileMemoriesCache = [];
let profileMemoryFoldersCache = [];
let profileMemoryPrivacyWidget = null;
let currentUser = null;
let csrfToken = null;

document.addEventListener('DOMContentLoaded', init);

async function init() {
    try {
        const sessionResponse = await fetch('http://localhost/IAmStillHere/backend/auth/check_session.php');
        const sessionData = await sessionResponse.json();

        if (sessionData.logged_in) {
            currentUser = sessionData.user;
            document.getElementById('username-display').textContent = currentUser.full_name;
            document.getElementById('nav-logout').style.display = 'inline-block';
            await loadCsrfToken();
        }

        if (!profileUserId) {
            if (sessionData.logged_in) {
                window.location.href = 'profile.php?user_id=' + currentUser.id;
            } else {
                window.location.href = 'memorials.php';
            }
            return;
        }

        await loadProfile();
    } catch (error) {
        console.error('Initialization error:', error);
    }
}

async function loadCsrfToken() {
    try {
        const response = await fetch('http://localhost/IAmStillHere/backend/auth/csrf_token.php');
        const data = await response.json();
        csrfToken = data.success ? data.data.csrf_token : null;
    } catch (error) {
        console.error('Error loading CSRF token:', error);
        csrfToken = null;
    }
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value == null ? '' : String(value);
    return div.innerHTML;
}

function authorPhotoUrl(photo) {
    return photo
        ? `http://localhost/IAmStillHere/data/uploads/photos/${encodeURIComponent(photo)}`
        : 'http://localhost/IAmStillHere/frontend/images/default-profile.png';
}

function safeUploadPathSegment(value) {
    return encodeURIComponent(value == null ? '' : String(value));
}

async function loadProfile() {
    try {
        const response = await fetch(`http://localhost/IAmStillHere/backend/users/profile.php?user_id=${profileUserId}`);
        const data = await response.json();

        if (!data.success) {
            console.error('Profile access failed:', data.message);
            showAlert(data.message || 'Unable to view this profile', 'danger');
            setTimeout(() => {
                window.location.href = currentUser ? 'dashboard.php' : 'login.php';
            }, 1200);
            return;
        }

        const profile = data.profile;

        document.getElementById('profile-name').textContent = profile.full_name || "Unknown";
        document.getElementById('profile-bio').textContent = profile.bio || "No bio available.";
        const aboutTabBio = document.getElementById('profile-about-tab-bio');
        if (aboutTabBio) aboutTabBio.textContent = profile.bio || 'No bio available.';

        const profileImg = document.getElementById('profile-image');
        if (profile.profile_photo) {
            profileImg.src = profile.profile_photo;
        } else {
            profileImg.src = 'http://localhost/IAmStillHere/frontend/images/default-profile.png';
        }

        const coverImg = document.getElementById('cover-image');
        if (profile.cover_photo) {
            coverImg.src = profile.cover_photo;
            coverImg.style.display = "block";
        } else {
            coverImg.style.display = "none";
        }

        const dates = [];
        if (profile.date_of_birth) {
            dates.push('Born: ' + new Date(profile.date_of_birth).toLocaleDateString());
        }
        if (profile.date_of_passing) {
            dates.push('Passed: ' + new Date(profile.date_of_passing).toLocaleDateString());
        }
        document.getElementById('profile-dates').textContent = dates.join(' | ');

        const isOwner = currentUser && currentUser.id == profileUserId;

        if (isOwner) {
            document.getElementById('edit-profile-btn').style.display = 'block';
            document.getElementById('memorial-settings-btn').style.display = 'block';
            document.getElementById('tribute-form').style.display = 'none'; // hide tribute form for self

            document.getElementById('bio-input').value = profile.bio || '';
            document.getElementById('dob-input').value = profile.date_of_birth || '';
            document.getElementById('is-memorial-input').value = profile.is_memorial ? '1' : '0';
            document.getElementById('dop-input').value = profile.date_of_passing || '';

            document.getElementById('memorial-status').textContent = profile.is_memorial
                ? 'Memorial mode is active'
                : 'Memorial mode is inactive';
        } else {
            // View-Only Mode
            document.getElementById('edit-profile-btn').style.display = 'none';
            document.getElementById('memorial-settings-btn').style.display = 'none';
            document.getElementById('tribute-form').style.display = 'block';
            document.querySelectorAll('#profileForm input, #profileForm textarea, #memorialSettingsForm input, #memorialSettingsForm select')
                .forEach(el => el.disabled = true);
        }

        loadTimeline();
        loadMemories();
        loadEvents();
        loadTributes();

    } catch (error) {
        console.error('Error loading profile:', error);
    }
}

// ---------- Profile Update ----------
document.getElementById('profileForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = new FormData();
    const profilePhoto = document.getElementById('profile-photo-upload').files[0];
    const coverPhoto = document.getElementById('cover-photo-upload').files[0];

    if (profilePhoto) formData.append('profile_photo', profilePhoto);
    if (coverPhoto) formData.append('cover_photo', coverPhoto);

    formData.append('bio', document.getElementById('bio-input').value);
    formData.append('date_of_birth', document.getElementById('dob-input').value);

    try {
        const response = await fetch('http://localhost/IAmStillHere/backend/users/update_profile.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showAlert('Profile updated successfully!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('editProfileModal')).hide();

            if (data.user.profile_photo) {
                document.getElementById('profile-image').src = data.user.profile_photo;
            }
            if (data.user.cover_photo) {
                const coverImg = document.getElementById('cover-image');
                coverImg.src = data.user.cover_photo;
                coverImg.style.display = 'block';
            }
            if (data.user.bio) {
                document.getElementById('profile-bio').textContent = data.user.bio;
            }

        } else {
            showAlert(data.message, 'danger');
        }
    } catch (error) {
        console.error('Error updating profile:', error);
        showAlert('Failed to update profile', 'danger');
    }
});

// ---------- Memorial Settings Update ----------
document.getElementById('memorialSettingsForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();

    const settingsData = {
        is_memorial: document.getElementById('is-memorial-input').value === '1',
        date_of_passing: document.getElementById('dop-input').value
    };

    try {
        const response = await fetch('http://localhost/IAmStillHere/backend/users/memorial_settings.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(settingsData)
        });

        const data = await response.json();

        if (data.success) {
            showAlert('Memorial settings saved!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('memorialSettingsModal')).hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message, 'danger');
        }
    } catch (error) {
        console.error('Error saving settings:', error);
        showAlert('Failed to save settings', 'danger');
    }
});

document.getElementById('tributeForm')?.addEventListener('submit', async (e) => {
    e.preventDefault(); // prevent page reload

    const name = document.getElementById('tribute-name').value.trim();
    const email = document.getElementById('tribute-email').value.trim();
    const message = document.getElementById('tribute-message').value.trim();

    // Replace this with the actual memorial user ID (the one whose profile you're viewing)
    const memorialUserId = window.profileUserId || new URLSearchParams(window.location.search).get('user_id');

    if (!memorialUserId) {
        alert('Missing memorial user ID.');
        return;
    }

    if (!name || !message) {
        alert('Please fill in all required fields.');
        return;
    }

    try {
        const response = await fetch('http://localhost/IAmStillHere/backend/tributes/create.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                memorial_user_id: memorialUserId,
                author_name: name,
                author_email: email,
                message: message
            })
        });

        const data = await response.json();

        if (data.success) {
            alert('Tribute posted successfully!');
            e.target.reset();
            // Optionally refresh tribute list dynamically
            loadTributes();
        } else {
            alert(data.message || 'Failed to post tribute.');
        }
    } catch (error) {
        console.error('Error submitting tribute:', error);
        alert('An unexpected error occurred. Please try again.');
    }
});


// ---------- Load Timeline ----------

async function loadTimeline() {
    try {
        const response = await fetch(`http://localhost/IAmStillHere/backend/milestones/list.php?user_id=${profileUserId}`);
        const data = await response.json();

        const container = document.getElementById('timeline-container');
        container.innerHTML = '';

        if (data.success && data.milestones.length > 0) {
            data.milestones.forEach((milestone, index) => {
                const item = document.createElement('div');
                item.className = 'timeline-item';

                const date = new Date(milestone.milestone_date).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });

                const canDelete = loggedInUser && (
                    loggedInUser.id == profileUserId ||
                    loggedInUser.role === 'admin'
                );

                item.innerHTML = `
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h5 class="mb-1">
                                    ${milestone.title}
                                    ${milestone.category ? `<span class="badge bg-info ms-2">${milestone.category}</span>` : ''}
                                </h5>
                                <small class="text-muted"><i class="bi bi-calendar"></i> ${date}</small>
                                <p class="text-muted mb-0">${milestone.description || ''}</p>
                                <small class="text-muted">
                                    <span class="badge bg-secondary privacy-badge">${milestone.privacy_level}</span>
                                </small>
                            </div>
                            ${canDelete ? `<button class="btn btn-sm btn-outline-danger" onclick="deleteMilestone(${milestone.id})">
                                <i class="bi bi-trash"></i>
                            </button>` : ''}
                            
                        </div>
                    </div>
                `;


                container.appendChild(item);
            });
        } else {
            container.innerHTML = '<p class="text-muted text-center">No milestones yet. Add your first milestone!</p>';
        }
    } catch (error) {
        console.error('Error loading timeline:', error);
    }
}

async function deleteMilestone(milestoneId) {
    if (!confirm('Are you sure you want to delete this milestone?')) {
        return;
    }

    try {
        const response = await fetch('http://localhost/IAmStillHere/backend/milestones/delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ milestone_id: milestoneId })
        });

        const data = await response.json();

        if (data.success) {
            showAlert('Milestone deleted successfully', 'success');
            loadTimeline(); // Reload timeline
        } else {
            showAlert(data.message, 'danger');
        }
    } catch (error) {
        console.error('Error deleting milestone:', error);
        showAlert('An error occurred. Please try again.', 'danger');
    }
}

// ---------- Load Memories ----------
async function loadMemories() {
    try {
        const response = await fetch(`http://localhost/IAmStillHere/backend/memories/list.php?user_id=${profileUserId}`);
        const data = await response.json();
        const grid = document.getElementById('memories-grid');

        profileMemoriesCache = data.memories || [];
        if (data.success && data.memories.length > 0) {
            grid.innerHTML = '';
            data.memories.forEach(memory => {
                const col = document.createElement('div');
                col.className = 'col-md-6 mb-3';

                let mediaHtml = '';
                const fileName = memory.file_path.toLowerCase();
                const fileType = memory.file_type.toLowerCase();

                // Determine file category
                let isImage = fileType.includes('image') ||
                    ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'tiff'].some(ext => fileName.endsWith('.' + ext));

                let isVideo = fileType.includes('video') ||
                    ['mp4', 'avi', 'mkv', 'mov', '3gp', 'flv', 'wmv', 'webm', 'mpeg', 'mpg'].some(ext => fileName.endsWith('.' + ext));

                let isAudio = fileType.includes('audio') ||
                    ['mp3', 'wav', 'aac', 'ogg', 'flac', 'm4a'].some(ext => fileName.endsWith('.' + ext));

                let filePath = '';
                let downloadButton = '';

                if (isImage) {
                    filePath = `http://localhost/IAmStillHere/data/uploads/photos/${memory.file_path}`;
                    downloadButton = `<a href="${filePath}" download="${memory.title}" class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i> Download</a>`;

                    mediaHtml = `
                        <img src="${filePath}" 
                            alt="${memory.title}" 
                            style="width: 100%; height: 200px; object-fit: cover; border-radius: 10px;">
                    `;
                } else if (isVideo) {
                    filePath = `http://localhost/IAmStillHere/data/uploads/videos/${safeUploadPathSegment(memory.file_path)}`;
                    downloadButton = `<a href="${filePath}" download="${memory.title}" class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i> Download</a>`;
                    const posterPath = memory.video_thumbnail_path
                        ? `http://localhost/IAmStillHere/data/uploads/${String(memory.video_thumbnail_path).split('/').map(safeUploadPathSegment).join('/')}`
                        : '';

                    mediaHtml = `
                        <div class="video-memory-preview">
                            <video controls preload="metadata" ${posterPath ? `poster="${posterPath}"` : ''}>
                                <source src="${filePath}" type="${escapeHtml(memory.file_type)}">
                                <p>
                                    This video format may not be supported. 
                                    <a href="${filePath}" download>Download the file</a> to view it.
                                </p>
                            </video>
                        </div>
                    `;
                } else if (isAudio) {
                    filePath = `http://localhost/IAmStillHere/data/uploads/audio/${memory.file_path}`;
                    downloadButton = `<a href="${filePath}" download="${memory.title}" class="btn btn-sm btn-outline-success"><i class="bi bi-download"></i> Download</a>`;

                    mediaHtml = `
                        <div class="text-center p-4">
                            <i class="bi bi-music-note-beamed display-1 text-success"></i>
                            <p class="mt-2 mb-2"><strong>${memory.title}</strong></p>
                            <audio 
                                controls 
                                preload="metadata"
                                style="width: 100%;"
                            >
                                <source src="${filePath}" type="${memory.file_type}">
                                <p>Audio format not supported. <a href="${filePath}" download>Download the file</a></p>
                            </audio>
                        </div>
                    `;
                } else {
                    // Documents
                    filePath = `http://localhost/IAmStillHere/data/uploads/documents/${memory.file_path}`;
                    downloadButton = `<a href="${filePath}" download="${memory.title}" class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i> Download</a>`;

                    let fileIcon = 'bi-file-earmark-text';
                    if (fileName.endsWith('.pdf')) fileIcon = 'bi-file-earmark-pdf';
                    else if (fileName.endsWith('.doc') || fileName.endsWith('.docx')) fileIcon = 'bi-file-earmark-word';
                    else if (fileName.endsWith('.xls') || fileName.endsWith('.xlsx')) fileIcon = 'bi-file-earmark-excel';
                    else if (fileName.endsWith('.ppt') || fileName.endsWith('.pptx')) fileIcon = 'bi-file-earmark-ppt';

                    mediaHtml = `
                        <div class="text-center p-4">
                            <i class="${fileIcon} display-1 text-primary"></i>
                            <p class="mt-2">
                                <a href="${filePath}" 
                                   target="_blank" 
                                   class="btn btn-outline-primary btn-sm me-2">
                                    <i class="bi bi-eye"></i> View
                                </a>
                            </p>
                        </div>
                    `;
                }

                const canDelete = loggedInUser && (
                    loggedInUser.id == profileUserId ||
                    loggedInUser.role === 'admin'
                );

                col.innerHTML = `
                    <div class="card memory-card">
                        ${mediaHtml}
                        <div class="card-body">
                            <h5 class="card-title">${escapeHtml(memory.title)}</h5>
                            <p class="card-text">${escapeHtml(memory.description || '')}</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <span class="badge bg-secondary privacy-badge">${memory.privacy_level}</span>
                                    ${memory.memory_date ? new Date(memory.memory_date).toLocaleDateString() : ''}
                                </small>
                                ${downloadButton}
                                ${canDelete ? `<button class="btn btn-sm btn-outline-primary ms-1" onclick="editProfileMemory(${memory.id})" title="Edit memory"><i class="bi bi-pencil"></i></button><button class="btn btn-sm btn-outline-danger ms-1" onclick="deleteMemory(${memory.id})">
                                    <i class="bi bi-trash"></i>
                                </button>` : ''}
                                
                            </div>
                            <div class="memory-comments mt-3" data-memory-comments="${memory.id}">
                                <div class="small text-muted">Loading comments...</div>
                            </div>
                        </div>
                    </div>
                `;
                grid.appendChild(col);
                loadMemoryComments(memory.id);
            });
        } else {
            grid.innerHTML = '<p class="text-muted">No memories shared yet.</p>';
        }
    } catch (error) {
        console.error('Error loading memories:', error);
    }
}

async function loadMemoryComments(memoryId, page = 1) {
    const container = document.querySelector(`[data-memory-comments="${memoryId}"]`);
    if (!container) return;

    container.innerHTML = '<div class="small text-muted">Loading comments...</div>';

    try {
        const response = await fetch(`http://localhost/IAmStillHere/backend/memories/comments/list.php?memory_id=${memoryId}&page=${page}&limit=20`);
        const data = await response.json();

        if (!data.success) {
            container.innerHTML = '<div class="small text-danger">Unable to load comments.</div>';
            return;
        }

        renderMemoryComments(container, memoryId, data.data.comments, data.data.pagination);
    } catch (error) {
        console.error('Error loading comments:', error);
        container.innerHTML = '<div class="small text-danger">Unable to load comments.</div>';
    }
}

function renderMemoryComments(container, memoryId, comments, pagination) {
    container.innerHTML = '';

    const title = document.createElement('div');
    title.className = 'small fw-semibold text-muted mb-2';
    title.textContent = `Comments (${pagination.total_items})`;
    container.appendChild(title);

    const list = document.createElement('div');
    list.className = 'memory-comments-list';
    container.appendChild(list);

    if (comments.length === 0) {
        const empty = document.createElement('div');
        empty.className = 'small text-muted mb-2';
        empty.textContent = 'No comments yet.';
        list.appendChild(empty);
    } else {
        comments.forEach(comment => list.appendChild(createCommentElement(comment, memoryId)));
    }

    if (currentUser && csrfToken) {
        const form = document.createElement('form');
        form.className = 'memory-comment-form mt-2';
        form.dataset.memoryId = memoryId;

        const group = document.createElement('div');
        group.className = 'input-group input-group-sm';

        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'form-control';
        input.maxLength = 2000;
        input.placeholder = 'Write a comment...';
        input.required = true;

        const button = document.createElement('button');
        button.className = 'btn btn-primary';
        button.type = 'submit';
        button.textContent = 'Post';

        group.appendChild(input);
        group.appendChild(button);
        form.appendChild(group);
        form.addEventListener('submit', event => submitMemoryComment(event, memoryId, input));
        container.appendChild(form);
    } else {
        const note = document.createElement('div');
        note.className = 'small text-muted';
        note.textContent = 'Log in to comment.';
        container.appendChild(note);
    }
}

function createCommentElement(comment, memoryId) {
    const wrapper = document.createElement('div');
    wrapper.className = 'memory-comment d-flex gap-2 mb-2';
    wrapper.dataset.commentId = comment.id;

    const img = document.createElement('img');
    img.className = 'rounded-circle flex-shrink-0';
    img.src = authorPhotoUrl(comment.author_profile_photo);
    img.alt = '';
    img.style.width = '32px';
    img.style.height = '32px';
    img.style.objectFit = 'cover';

    const body = document.createElement('div');
    body.className = 'flex-grow-1';

    const bubble = document.createElement('div');
    bubble.className = 'memory-comment-bubble';

    const meta = document.createElement('div');
    meta.className = 'd-flex justify-content-between gap-2';

    const author = document.createElement('strong');
    author.className = 'small';
    author.textContent = comment.author_name || 'Deleted user';

    const time = document.createElement('small');
    time.className = 'text-muted';
    time.textContent = new Date(comment.created_at).toLocaleString();

    meta.appendChild(author);
    meta.appendChild(time);

    const text = document.createElement('div');
    text.className = 'small memory-comment-text';
    text.textContent = comment.comment_text;

    bubble.appendChild(meta);
    bubble.appendChild(text);
    body.appendChild(bubble);

    if (comment.can_edit || comment.can_delete) {
        const actions = document.createElement('div');
        actions.className = 'memory-comment-actions small mt-1';

        if (comment.can_edit) {
            const edit = document.createElement('button');
            edit.type = 'button';
            edit.className = 'btn btn-link btn-sm p-0 me-2';
            edit.textContent = 'Edit';
            edit.addEventListener('click', () => editMemoryComment(comment, memoryId));
            actions.appendChild(edit);
        }

        if (comment.can_delete) {
            const del = document.createElement('button');
            del.type = 'button';
            del.className = 'btn btn-link btn-sm p-0 text-danger';
            del.textContent = 'Delete';
            del.addEventListener('click', () => deleteMemoryComment(comment.id, memoryId));
            actions.appendChild(del);
        }

        body.appendChild(actions);
    }

    wrapper.appendChild(img);
    wrapper.appendChild(body);
    return wrapper;
}

async function submitMemoryComment(event, memoryId, input) {
    event.preventDefault();
    const text = input.value.trim();

    if (!text) return;

    try {
        const response = await fetch('http://localhost/IAmStillHere/backend/memories/comments/create.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({ memory_id: memoryId, comment_text: text })
        });
        const data = await response.json();

        if (data.success) {
            input.value = '';
            loadMemoryComments(memoryId);
        } else {
            showAlert(data.message || 'Failed to post comment', 'danger');
        }
    } catch (error) {
        console.error('Error posting comment:', error);
        showAlert('Failed to post comment', 'danger');
    }
}

async function editMemoryComment(comment, memoryId) {
    const updated = prompt('Edit your comment:', comment.comment_text);
    if (updated === null) return;

    try {
        const response = await fetch('http://localhost/IAmStillHere/backend/memories/comments/update.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({ comment_id: comment.id, comment_text: updated })
        });
        const data = await response.json();

        if (data.success) {
            loadMemoryComments(memoryId);
        } else {
            showAlert(data.message || 'Failed to update comment', 'danger');
        }
    } catch (error) {
        console.error('Error updating comment:', error);
        showAlert('Failed to update comment', 'danger');
    }
}

async function deleteMemoryComment(commentId, memoryId) {
    if (!confirm('Delete this comment?')) return;

    try {
        const response = await fetch('http://localhost/IAmStillHere/backend/memories/comments/delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({ comment_id: commentId })
        });
        const data = await response.json();

        if (data.success) {
            loadMemoryComments(memoryId);
        } else {
            showAlert(data.message || 'Failed to delete comment', 'danger');
        }
    } catch (error) {
        console.error('Error deleting comment:', error);
        showAlert('Failed to delete comment', 'danger');
    }
}

async function deleteMemory(memoryId) {
    if (!confirm('Are you sure you want to delete this memory? This action cannot be undone.')) {
        return;
    }

    try {
        const response = await fetch('http://localhost/IAmStillHere/backend/memories/delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ memory_id: memoryId })
        });

        const data = await response.json();

        if (data.success) {
            showAlert('Memory deleted successfully', 'success');
            loadMemories(); // Reload memories
        } else {
            showAlert(data.message, 'danger');
        }
    } catch (error) {
        console.error('Error deleting memory:', error);
        showAlert('An error occurred. Please try again.', 'danger');
    }
}

// ---------- Load Tributes ----------

async function loadTributes() {
    try {
        const response = await fetch(`http://localhost/IAmStillHere/backend/tributes/list.php?memorial_user_id=${profileUserId}`);
        const data = await response.json();
        const container = document.getElementById('tributes-container');

        if (data.success && data.tributes.length > 0) {
            container.innerHTML = '';
            data.tributes.forEach(tribute => {
                const div = document.createElement('div');
                div.className = 'tribute-card card mb-3 shadow-sm';

                // Determine avatar and name
                let avatarUrl, displayName, userBadge;

                if (tribute.author_id && tribute.registered_user_name) {
                    // Registered user
                    avatarUrl = tribute.profile_photo
                        ? `http://localhost/IAmStillHere/data/uploads/photos/${tribute.profile_photo}`
                        : 'http://localhost/IAmStillHere/data/uploads/photos/default-profile.png';
                    displayName = tribute.registered_user_name;
                    userBadge = '<span class="badge bg-primary ms-2" style="font-size: 0.7rem;">Member</span>';
                } else {
                    // Guest user
                    avatarUrl = 'http://localhost/IAmStillHere/data/uploads/photos/default-profile.png';
                    displayName = tribute.author_name || 'Anonymous';
                    userBadge = '<span class="badge bg-secondary ms-2" style="font-size: 0.7rem;">Guest</span>';
                }

                // Check if current user can delete (profile owner, tribute author, or admin)
                const canDelete = loggedInUser && (
                    loggedInUser.id == profileUserId ||
                    loggedInUser.id == tribute.author_id ||
                    loggedInUser.role === 'admin'
                );

                div.innerHTML = `
                    <div class="card-body">
                        <div class="d-flex align-items-start">
                            <img src="${avatarUrl}" 
                                 alt="${displayName}" 
                                 class="rounded-circle me-3" 
                                 style="width: 50px; height: 50px; object-fit: cover; border: 2px solid #dee2e6;">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center mb-2">
                                    <strong class="text-dark">${displayName}</strong>
                                    ${userBadge}
                                    <small class="text-muted ms-auto">${new Date(tribute.created_at).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                })}</small>
                                    ${canDelete ? `
                                        <button class="btn btn-sm btn-outline-danger ms-2" onclick="deleteTribute(${tribute.id})">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    ` : ''}
                                </div>
                                <p class="mb-0 text-secondary">${tribute.message}</p>
                            </div>
                        </div>
                    </div>
                `;
                container.appendChild(div);
            });
        } else {
            container.innerHTML = '<p class="text-muted text-center py-4">No tributes yet. Be the first to leave a tribute.</p>';
        }
    } catch (error) {
        console.error('Error loading tributes:', error);
        document.getElementById('tributes-container').innerHTML =
            '<p class="text-danger text-center py-4">Error loading tributes. Please try again later.</p>';
    }
}

async function deleteTribute(tributeId) {
    if (!confirm('Are you sure you want to delete this tribute?')) {
        return;
    }

    try {
        const response = await fetch('http://localhost/IAmStillHere/backend/tributes/delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ tribute_id: tributeId })
        });

        const data = await response.json();

        if (data.success) {
            showAlert('Tribute deleted successfully', 'success');
            loadTributes(); // Reload tributes
        } else {
            showAlert(data.message, 'danger');
        }
    } catch (error) {
        console.error('Error deleting tribute:', error);
        showAlert('An error occurred. Please try again.', 'danger');
    }
}

// Load Events Function
async function loadEvents() {
    try {
        const response = await fetch(`http://localhost/IAmStillHere/backend/events/list.php?user_id=${profileUserId}`);
        const data = await response.json();

        const container = document.getElementById('events-container');

        if (!data.success) {
            container.innerHTML = '<div class="alert alert-danger">Error loading events</div>';
            return;
        }

        if (data.events.length === 0) {
            container.innerHTML = `
                <div class="text-center py-4">
                    <i class="bi bi-calendar-x display-3 text-muted"></i>
                    <p class="text-muted mt-3">No scheduled events yet. Schedule your first event!</p>
                </div>
            `;
            return;
        }

        container.innerHTML = '';

        // Separate upcoming and past events
        const now = new Date();
        const upcomingEvents = data.events.filter(e => new Date(e.scheduled_date) >= now);
        const pastEvents = data.events.filter(e => new Date(e.scheduled_date) < now);

        // Display upcoming events
        if (upcomingEvents.length > 0) {
            const upcomingSection = document.createElement('div');
            upcomingSection.className = 'mb-4';
            upcomingSection.innerHTML = '<h6 class="text-primary mb-3"><i class="bi bi-clock-history"></i> Upcoming Events</h6>';

            upcomingEvents.forEach(event => {
                upcomingSection.appendChild(createEventCard(event, false));
            });

            container.appendChild(upcomingSection);
        }

        // Display past events
        if (pastEvents.length > 0) {
            const pastSection = document.createElement('div');
            pastSection.innerHTML = '<h6 class="text-muted mb-3"><i class="bi bi-clock"></i> Past Events</h6>';

            pastEvents.forEach(event => {
                pastSection.appendChild(createEventCard(event, true));
            });

            container.appendChild(pastSection);
        }

    } catch (error) {
        console.error('Error loading events:', error);
        document.getElementById('events-container').innerHTML =
            '<div class="alert alert-danger">Error loading events</div>';
    }
}

// Create Event Card
function createEventCard(event, isPast) {
    const card = document.createElement('div');
    card.className = `card mb-3 ${isPast ? 'bg-light' : 'border-info'}`;

    const eventDate = new Date(event.scheduled_date);
    const formattedDate = eventDate.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    const formattedTime = eventDate.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
    });

    // Event type icons and colors
    const eventTypes = {
        'birthday': { icon: 'bi-cake2', color: 'text-danger', label: 'Birthday' },
        'anniversary': { icon: 'bi-heart', color: 'text-danger', label: 'Anniversary' },
        'memorial': { icon: 'bi-flower1', color: 'text-info', label: 'Memorial' },
        'remembrance': { icon: 'bi-star', color: 'text-warning', label: 'Remembrance' },
        'celebration': { icon: 'bi-balloon', color: 'text-success', label: 'Celebration' },
        'other': { icon: 'bi-calendar-event', color: 'text-secondary', label: 'Other' }
    };

    const typeInfo = eventTypes[event.event_type] || eventTypes['other'];

    // Privacy badge
    const privacyBadges = {
        'public': 'bg-success',
        'family': 'bg-warning',
        'private': 'bg-secondary'
    };

    const canDelete = loggedInUser && (
        loggedInUser.id == profileUserId ||
        loggedInUser.role === 'admin'
    );

    card.innerHTML = `
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi ${typeInfo.icon} ${typeInfo.color} fs-4 me-2"></i>
                        <h5 class="mb-0">${event.title}</h5>
                        <span class="badge ${privacyBadges[event.privacy_level]} ms-2">${event.privacy_level}</span>
                        ${isPast ? '<span class="badge bg-secondary ms-2">Past</span>' : ''}
                    </div>
                    <p class="text-muted mb-2">
                        <i class="bi bi-calendar3"></i> ${formattedDate} at ${formattedTime}
                    </p>
                    ${event.message ? `<p class="mb-0 text-secondary">${event.message}</p>` : ''}
                </div>
                ${canDelete ? `<button class="btn btn-sm btn-outline-danger" onclick="deleteEvent(${event.id})">
                    <i class="bi bi-trash"></i>
                </button>` : ''}
                
            </div>
        </div>
    `;

    return card;
}

// Delete Event Function
async function deleteEvent(eventId) {
    if (!confirm('Are you sure you want to delete this event?')) {
        return;
    }

    try {
        const response = await fetch('http://localhost/IAmStillHere/backend/events/delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ event_id: eventId })
        });

        const data = await response.json();

        if (data.success) {
            showAlert('Event deleted successfully', 'success');
            loadEvents(); // Reload events
        } else {
            showAlert(data.message, 'danger');
        }
    } catch (error) {
        console.error('Error deleting event:', error);
        showAlert('An error occurred. Please try again.', 'danger');
    }
}

// ---------- Alert Helper ----------
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} position-fixed bottom-0 end-0 m-3`;
    alertDiv.style.zIndex = 1050;
    alertDiv.textContent = message;
    document.body.appendChild(alertDiv);
    setTimeout(() => alertDiv.remove(), 3000);
}

async function editProfileMemory(memoryId) {
    const memory = profileMemoriesCache.find(item => Number(item.id) === Number(memoryId));
    if (!memory || !currentUser || Number(currentUser.id) !== Number(profileUserId)) return;
    const modal=document.getElementById('profileEditMemoryModal');
    document.getElementById('profile-edit-memory-id').value=memory.id;
    document.getElementById('profile-edit-memory-title').value=memory.title||'';
    document.getElementById('profile-edit-memory-description').value=memory.description||'';
    document.getElementById('profile-edit-memory-date').value=memory.memory_date||'';
    const folder=document.getElementById('profile-edit-memory-folder'); folder.innerHTML='<option value="0">No folder</option>';
    try { const result=await fetch(`http://localhost/IAmStillHere/backend/memories/folders/list.php?user_id=${encodeURIComponent(profileUserId)}`).then(r=>r.json()); profileMemoryFoldersCache=result.data?.folders||[]; profileMemoryFoldersCache.forEach(item=>{const option=document.createElement('option');option.value=item.id;option.textContent=item.name;folder.appendChild(option);}); } catch(e) {}
    folder.value=memory.folder_id||0;
    if(!profileMemoryPrivacyWidget){profileMemoryPrivacyWidget=privacyComponent('profile-memory-edit',Number(profileUserId));document.getElementById('profile-edit-memory-privacy').appendChild(profileMemoryPrivacyWidget);}
    profileMemoryPrivacyWidget.querySelector('.privacy-type').value=memory.privacy_level||'public';
    await profileMemoryPrivacyWidget.loadRule('memory',memory.id);
    bootstrap.Modal.getOrCreateInstance(modal).show();
}
document.getElementById('profileEditMemoryForm')?.addEventListener('submit',async event=>{event.preventDefault();if(!currentUser||Number(currentUser.id)!==Number(profileUserId))return;const error=document.getElementById('profile-edit-memory-error');const save=document.getElementById('profile-edit-memory-save');error.textContent='';save.disabled=true;try{const rule=profileMemoryPrivacyWidget.getRule();const response=await fetch('http://localhost/IAmStillHere/backend/memories/update.php',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrfToken},body:JSON.stringify({memory_id:Number(document.getElementById('profile-edit-memory-id').value),title:document.getElementById('profile-edit-memory-title').value.trim(),description:document.getElementById('profile-edit-memory-description').value,memory_date:document.getElementById('profile-edit-memory-date').value,folder_id:Number(document.getElementById('profile-edit-memory-folder').value),privacy_level:rule.visibility_type})});const data=await response.json();if(!data.success)throw new Error(data.message||'Unable to update memory.');await savePrivacyRule(csrfToken,'memory',data.data.memory_id,rule);bootstrap.Modal.getInstance(document.getElementById('profileEditMemoryModal')).hide();loadMemories();}catch(e){error.textContent=e.message;}finally{save.disabled=false;}});
// Preserve the selected profile tab across refreshes.
document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelectorAll('[data-bs-toggle="tab"][href^="#"]');
    const saved = window.location.hash;
    if (saved) {
        const target = document.querySelector(`[data-bs-toggle="tab"][href="${CSS.escape(saved)}"]`);
        if (target && window.bootstrap) bootstrap.Tab.getOrCreateInstance(target).show();
    }
    tabs.forEach(tab => tab.addEventListener('shown.bs.tab', event => {
        const href = event.target.getAttribute('href');
        if (href) history.replaceState(null, '', `${window.location.pathname}${window.location.search}${href}`);
    }));
});

// Responsive profile tabs: keep visible tabs, move overflow to More dropdown.
function initResponsiveProfileTabs() {
    const tabs = document.querySelector('.profile-tabs');
    if (!tabs || tabs.dataset.responsiveReady === '1') return;
    tabs.dataset.responsiveReady = '1';

    const originalItems = Array.from(tabs.querySelectorAll(':scope > li.nav-item'));
    const moreItem = document.createElement('li');
    moreItem.className = 'nav-item dropdown profile-tabs-more';
    moreItem.innerHTML = `
        <button class="nav-link dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="More profile sections">
            <i class="bi bi-three-dots"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end profile-tabs-more-menu"></ul>
    `;
    const moreMenu = moreItem.querySelector('.profile-tabs-more-menu');
    tabs.appendChild(moreItem);

    function moveAllBack() {
        originalItems.forEach((item) => {
            const link = item.querySelector('a.nav-link, a.dropdown-item');
            if (link) {
                link.classList.remove('dropdown-item');
                link.classList.add('nav-link');
            }
            tabs.insertBefore(item, moreItem);
        });
        moreMenu.textContent = '';
    }

    function moveToMenu(item) {
        const link = item.querySelector('a.nav-link, a.dropdown-item');
        if (link) {
            link.classList.remove('nav-link');
            link.classList.add('dropdown-item');
        }
        moreMenu.insertBefore(item, moreMenu.firstChild);
    }

    function fitTabs() {
        moveAllBack();
        moreItem.style.display = 'none';
        const available = tabs.clientWidth;
        if (!available) return;

        moreItem.style.display = 'block';
        let guard = 0;
        while (tabs.scrollWidth > available + 2 && originalItems.length > guard) {
            const visibleItems = originalItems.filter((item) => item.parentElement === tabs);
            if (visibleItems.length <= 3) break;
            moveToMenu(visibleItems[visibleItems.length - 1]);
            guard++;
        }
        moreItem.style.display = moreMenu.children.length ? 'block' : 'none';
    }

    moreMenu.addEventListener('click', (event) => {
        const link = event.target.closest('[data-bs-toggle="tab"]');
        if (!link) return;
        const dropdown = bootstrap.Dropdown.getOrCreateInstance(moreItem.querySelector('[data-bs-toggle="dropdown"]'));
        dropdown.hide();
    });

    window.addEventListener('resize', () => window.requestAnimationFrame(fitTabs));
    window.addEventListener('load', fitTabs);
    setTimeout(fitTabs, 100);
}

document.addEventListener('DOMContentLoaded', initResponsiveProfileTabs);

// Keep profile tab highlight correct, including tabs inside More dropdown.
function syncProfileTabActiveState() {
    const tabs = document.querySelector('.profile-tabs');
    if (!tabs) return;
    const moreItem = tabs.querySelector('.profile-tabs-more');
    const moreButton = moreItem?.querySelector('button.nav-link');
    const activePane = document.querySelector('.tab-pane.active.show, .tab-pane.active');
    const activeHref = activePane ? `#${activePane.id}` : (window.location.hash || '#posts-tab');

    tabs.querySelectorAll('a[data-bs-toggle="tab"]').forEach((link) => {
        const isActive = link.getAttribute('href') === activeHref;
        link.classList.toggle('active', isActive);
        link.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });

    if (moreButton && moreItem) {
        const activeInMore = !!moreItem.querySelector(`.dropdown-menu a[href="${activeHref}"]`);
        moreButton.classList.toggle('active', activeInMore);
        moreButton.classList.toggle('profile-more-has-active', activeInMore);
    }
}

document.addEventListener('shown.bs.tab', (event) => {
    if (event.target.closest('.profile-tabs')) {
        setTimeout(syncProfileTabActiveState, 0);
    }
});
window.addEventListener('resize', () => setTimeout(syncProfileTabActiveState, 80));
window.addEventListener('load', () => setTimeout(syncProfileTabActiveState, 250));
document.addEventListener('DOMContentLoaded', () => setTimeout(syncProfileTabActiveState, 250));

// Hard fallback: immediately highlight clicked profile tab, visible or inside More.
function setProfileActiveTabByHref(activeHref) {
    const tabs = document.querySelector('.profile-tabs');
    if (!tabs || !activeHref) return;
    const moreItem = tabs.querySelector('.profile-tabs-more');
    const moreButton = moreItem?.querySelector('button.nav-link');

    tabs.querySelectorAll('a[data-bs-toggle="tab"]').forEach((link) => {
        const isActive = link.getAttribute('href') === activeHref;
        link.classList.toggle('active', isActive);
        link.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });

    if (moreButton && moreItem) {
        const activeInMore = !!moreItem.querySelector(`.dropdown-menu a[href="${activeHref}"]`);
        moreButton.classList.toggle('active', activeInMore);
        moreButton.classList.toggle('profile-more-has-active', activeInMore);
    }
}

document.addEventListener('click', (event) => {
    const link = event.target.closest('.profile-tabs a[data-bs-toggle="tab"]');
    if (!link) return;
    const href = link.getAttribute('href');
    setProfileActiveTabByHref(href);
    setTimeout(() => setProfileActiveTabByHref(href), 80);
});
