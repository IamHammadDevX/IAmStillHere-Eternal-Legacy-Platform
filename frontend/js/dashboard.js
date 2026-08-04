let currentUserId = null;
let loggedInUser = null;
let csrfToken = null;
let currentMemoryFolderId = 0;
let memoryPrivacyWidget = null;
let milestonePrivacyWidget = null;
let milestoneCache = [];
let editMilestonePrivacyWidget = null;

async function init() {
    const response = await fetch('http://localhost/IAmStillHere/backend/auth/check_session.php');
    const data = await response.json();

    if (!data.logged_in) {
        window.location.href = 'login.php';
        return;
    }
    loggedInUser = data.user;

    currentUserId = data.user.id;
    const savedFolder = Number(localStorage.getItem('memoryFolder_' + currentUserId) || 0);
    currentMemoryFolderId = Number.isFinite(savedFolder) && savedFolder > 0 ? savedFolder : 0;
    const memoryPrivacy = document.getElementById('memory-privacy'); if(memoryPrivacy && typeof privacyComponent==='function'){ memoryPrivacy.style.display='none'; memoryPrivacyWidget=privacyComponent('memory',currentUserId); memoryPrivacy.parentElement.appendChild(memoryPrivacyWidget); }
    const milestonePrivacy = document.getElementById('milestone-privacy'); if(milestonePrivacy && typeof privacyComponent==='function'){ milestonePrivacy.style.display='none'; milestonePrivacyWidget=privacyComponent('milestone',currentUserId); milestonePrivacy.parentElement.appendChild(milestonePrivacyWidget); }
    await loadCsrfToken();

    loadMemories();
    loadMemoryFolders();
    loadTimeline();
    loadEvents();
    loadRequestCount();
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

async function loadTributeCount(userId) {
    try {
        const response = await fetch(`http://localhost/IAmStillHere/backend/tributes/get_count.php?user_id=${userId}`);
        const data = await response.json();

        if (data.success) {
            document.getElementById('tribute-count').textContent = data.tribute_count;
        } else {
            document.getElementById('tribute-count').textContent = '0';
        }
    } catch (error) {
        console.error('Error loading tribute count:', error);
        document.getElementById('tribute-count').textContent = '0';
    }
}

async function loadMemories() {
    try {
        const response = await fetch(`http://localhost/IAmStillHere/backend/memories/list.php?user_id=${currentUserId}${currentMemoryFolderId ? '&folder_id=' + currentMemoryFolderId : ''}&_=${Date.now()}`);
        const data = await response.json();

        const grid = document.getElementById('memories-grid');
        grid.innerHTML = '';

        window.lastLoadedMemories = data.memories || [];
        if (data.success && data.memories.length > 0) {
            data.memories.forEach((memory, index) => {
                const col = document.createElement('div');
                col.className = 'col-md-4 mb-4';

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
                    loggedInUser.id == currentUserId ||
                    loggedInUser.role === 'admin'
                );
                console.log('loggedInUser: ', loggedInUser);

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
                                ${canDelete ? `<button class="btn btn-sm btn-outline-primary ms-1" onclick="editMemory(${memory.id})" title="Edit memory"><i class="bi bi-pencil"></i></button><button class="btn btn-sm btn-outline-secondary ms-1" onclick="moveMemory(${memory.id})" title="Move memory"><i class="bi bi-folder2-open"></i></button><button class="btn btn-sm btn-outline-danger ms-1" onclick="deleteMemory(${memory.id})">
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
            grid.innerHTML = '<div class="col-12"><p class="text-muted text-center">No memories yet. Upload your first memory!</p></div>';
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

    if (loggedInUser && csrfToken) {
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

async function loadTimeline() {
    try {
        const response = await fetch(`http://localhost/IAmStillHere/backend/milestones/list.php?user_id=${currentUserId}`);
        const data = await response.json();

        const container = document.getElementById('timeline-container');
        container.innerHTML = '';

        milestoneCache = data.milestones || [];
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
                    loggedInUser.id == currentUserId ||
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
                            ${canDelete ? `<button class="btn btn-sm btn-outline-primary me-1" onclick="editMilestone(${milestone.id})" title="Edit milestone"><i class="bi bi-pencil"></i></button><button class="btn btn-sm btn-outline-danger" onclick="deleteMilestone(${milestone.id})">
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

// Load Events Function
async function loadEvents() {
    try {
        const response = await fetch(`http://localhost/IAmStillHere/backend/events/list.php?user_id=${currentUserId}`);
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
        loggedInUser.id == currentUserId ||
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

async function loadRequestCount() {
    try {
        const response = await fetch(`http://localhost/IAmStillHere/backend/family/pending_requests.php?user_id=${currentUserId}`);
        const data = await response.json();

        if (data.success && data.count > 0) {
            const badge = document.getElementById('request-count-badge');
            if (badge) {
                badge.textContent = data.count;
                badge.style.display = 'inline-block';
            }
        }
    } catch (error) {
        console.error('Error loading request count:', error);
    }
}

document.addEventListener('DOMContentLoaded', async () => {
    const response = await fetch('http://localhost/IAmStillHere/backend/auth/check_session.php');
    const data = await response.json();

    if (data.logged_in) {
        const userId = data.user.id;
        loadTributeCount(userId);
    }
});

document.getElementById('memoryForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = new FormData();
    formData.append('title', document.getElementById('memory-title').value);
    formData.append('description', document.getElementById('memory-description').value);
    formData.append('memory_date', document.getElementById('memory-date').value);
    formData.append('privacy_level', memoryPrivacyWidget?.getRule().visibility_type || document.getElementById('memory-privacy').value);
    if (currentMemoryFolderId) { formData.append('folder_id', currentMemoryFolderId); formData.append('privacy_override', '0'); }
    formData.append('file', document.getElementById('memory-file').files[0]);

    try {
        const response = await fetch('http://localhost/IAmStillHere/backend/memories/upload.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            try { if (memoryPrivacyWidget) await savePrivacyRule(csrfToken, 'memory', data.memory_id, memoryPrivacyWidget.getRule()); } catch (privacyError) { showAlert('Memory uploaded, but privacy settings were not saved: ' + privacyError.message, 'warning'); return; }
            showAlert('Memory uploaded successfully!', 'success');
            document.getElementById('memoryForm').reset();
            bootstrap.Modal.getInstance(document.getElementById('uploadMemoryModal')).hide();
            loadMemories();
        } else {
            showAlert(data.message, 'danger');
        }
    } catch (error) {
        showAlert('Upload failed. Please try again.', 'danger');
    }
});

document.getElementById('milestoneForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const milestoneData = {
        title: document.getElementById('milestone-title').value,
        description: document.getElementById('milestone-description').value,
        milestone_date: document.getElementById('milestone-date').value,
        category: document.getElementById('milestone-category').value,
        privacy_level: milestonePrivacyWidget?.getRule().visibility_type || document.getElementById('milestone-privacy').value
    };

    try {
        const response = await fetch('http://localhost/IAmStillHere/backend/milestones/create.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(milestoneData)
        });

        const data = await response.json();

        if (data.success) {
            try { if (milestonePrivacyWidget && data.milestone_id) await savePrivacyRule(csrfToken, 'milestone', data.milestone_id, milestonePrivacyWidget.getRule()); } catch (privacyError) { showAlert('Milestone created, but privacy settings were not saved: ' + privacyError.message, 'warning'); return; }
            showAlert('Milestone added successfully!', 'success');
            document.getElementById('milestoneForm').reset();
            bootstrap.Modal.getInstance(document.getElementById('addMilestoneModal')).hide();
            loadTimeline();
        } else {
            showAlert(data.message, 'danger');
        }
    } catch (error) {
        showAlert('Failed to add milestone. Please try again.', 'danger');
    }
});

document.getElementById('eventForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const eventData = {
        title: document.getElementById('event-title').value,
        message: document.getElementById('event-message').value,
        scheduled_date: document.getElementById('event-date').value,
        privacy_level: document.getElementById('event-privacy').value
    };

    try {
        const response = await fetch('http://localhost/IAmStillHere/backend/events/create.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(eventData)
        });

        const data = await response.json();

        if (data.success) {
            showAlert('Event scheduled successfully!', 'success');
            document.getElementById('eventForm').reset();
            bootstrap.Modal.getInstance(document.getElementById('scheduleEventModal')).hide();
        } else {
            showAlert(data.message, 'danger');
        }
    } catch (error) {
        showAlert('Failed to schedule event. Please try again.', 'danger');
    }
});

document.addEventListener('DOMContentLoaded', init);

async function loadMemoryFolders(search = '') {
    const box = document.getElementById('memory-folders');
    if (!box) return;
    const response = await fetch(`http://localhost/IAmStillHere/backend/memories/folders/list.php?user_id=${currentUserId}&search=${encodeURIComponent(search)}&_=${Date.now()}`);
    const data = await response.json();
    box.innerHTML = '';
    const folders = data.success ? data.data.folders : [];
    window.lastMemoryFolders = folders;
    const activeFolder = folders.find(folder => Number(folder.id) === Number(currentMemoryFolderId));
    const breadcrumb = document.getElementById('memory-folder-breadcrumb');
    if (breadcrumb) breadcrumb.textContent = activeFolder ? activeFolder.name : 'All memories';
    if (currentMemoryFolderId && !activeFolder) { currentMemoryFolderId = 0; localStorage.removeItem('memoryFolder_' + currentUserId); }
    const all = document.createElement('button');
    all.className = 'btn btn-sm btn-outline-dark'; all.textContent = 'All';
    all.onclick = () => { currentMemoryFolderId = 0; localStorage.removeItem('memoryFolder_' + currentUserId); document.getElementById('memory-folder-breadcrumb').textContent = 'All memories'; loadMemories(); loadMemoryFolders(search); };
    box.appendChild(all);
    folders.forEach(folder => {
        const button = document.createElement('button');
        button.className = `btn btn-sm ${currentMemoryFolderId === folder.id ? 'btn-primary' : 'btn-outline-secondary'}`;
        button.textContent = `${folder.name} (${folder.memory_count})`;
        button.onclick = () => { currentMemoryFolderId = Number(folder.id); localStorage.setItem('memoryFolder_' + currentUserId, currentMemoryFolderId); document.getElementById('memory-folder-breadcrumb').textContent = folder.name; loadMemories(); loadMemoryFolders(search); };
        const renameButton = document.createElement('button'); renameButton.className='btn btn-sm btn-outline-secondary'; renameButton.textContent='Edit'; renameButton.onclick=()=>editFolder(folder);
        const childButton = document.createElement('button'); childButton.className='btn btn-sm btn-outline-secondary'; childButton.textContent='+ Child'; childButton.onclick=()=>createChildFolder(folder);
        const deleteButton = document.createElement('button'); deleteButton.className='btn btn-sm btn-outline-danger'; deleteButton.textContent='Delete'; deleteButton.onclick=()=>deleteFolder(folder);
        box.appendChild(button); box.append(renameButton, childButton, deleteButton);
    });
    const select = document.getElementById('memory-folder');
    if (select) { select.innerHTML = '<option value="0">No folder</option>'; folders.forEach(folder => { const option=document.createElement('option'); option.value=folder.id; option.textContent=folder.name; select.appendChild(option); }); }
}

document.getElementById('folder-search')?.addEventListener('input', event => loadMemoryFolders(event.target.value));
document.getElementById('new-folder-button')?.addEventListener('click', async () => {
    const name = prompt('Folder name');
    if (!name) return;
    const privacy = prompt('Privacy: public, family, friends, or private', 'private');
    const response = await fetch('http://localhost/IAmStillHere/backend/memories/folders/create.php', { method: 'POST', headers: {'Content-Type':'application/json','X-CSRF-Token':csrfToken}, body: JSON.stringify({name, privacy_level: privacy || 'private'}) });
    const data = await response.json();
    if (data.success) loadMemoryFolders(); else showAlert(data.message || 'Unable to create folder', 'danger');
});
async function folderPost(endpoint, payload) {
    const response = await fetch(`http://localhost/IAmStillHere/backend/memories/folders/${endpoint}.php`, {method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrfToken},body:JSON.stringify(payload)});
    return response.json();
}
async function renameFolder(folder) { const name=prompt('New folder name',folder.name); if(!name)return; const data=await folderPost('update',{folder_id:folder.id,name}); if(data.success)loadMemoryFolders();else showAlert(data.message||'Unable to rename folder','danger'); }
async function createChildFolder(parent) { const name=prompt('Child folder name'); if(!name)return; const data=await folderPost('create',{name,parent_folder_id:parent.id,privacy_level:parent.privacy_level}); if(data.success)loadMemoryFolders();else showAlert(data.message||'Unable to create child folder','danger'); }
async function deleteFolder(folder) { if(!confirm(`Delete empty folder "${folder.name}"?`))return; const data=await folderPost('delete',{folder_id:folder.id}); if(data.success){if(currentMemoryFolderId===folder.id){currentMemoryFolderId=0;loadMemories();}loadMemoryFolders();}else showAlert(data.message||'Folder must be empty','danger'); }
async function moveMemory(memoryId) {
    const folders = await fetch(`http://localhost/IAmStillHere/backend/memories/folders/list.php?user_id=${currentUserId}&_=${Date.now()}`).then(r => r.json());
    const available = folders.data?.folders || [];
    const options = available.map(f => `${f.id}: ${f.name}`).join('\\n');
    const choice = prompt(`Enter folder ID or exact folder name. Enter 0 to remove folder.\\n${options}`, '0');
    if (choice === null) return;
    const input = choice.trim();
    const selected = available.find(f => String(f.id) === input || String(f.name).toLowerCase() === input.toLowerCase());
    const folderId = input === '0' ? 0 : (selected ? Number(selected.id) : parseInt(input, 10) || 0);
    const data = await folderPost('move_memory', { memory_id: memoryId, folder_id: folderId });
    if (data.success) { showAlert('Memory moved successfully', 'success'); loadMemories(); loadMemoryFolders(); }
    else showAlert(data.message || 'Unable to move memory', 'danger');
}
let editMemoryPrivacyWidget = null;
async function editMemory(memoryId) {
    const memory = (window.lastLoadedMemories || []).find(item => Number(item.id) === Number(memoryId));
    if (!memory) { showAlert('Memory data unavailable. Refresh and try again.', 'danger'); return; }
    document.getElementById('edit-memory-id').value = memory.id;
    document.getElementById('edit-memory-title').value = memory.title || '';
    document.getElementById('edit-memory-description').value = memory.description || '';
    document.getElementById('edit-memory-date').value = memory.memory_date || '';
    const folder = document.getElementById('edit-memory-folder');
    folder.innerHTML = '<option value="0">No folder</option>';
    (window.lastMemoryFolders || []).forEach(item => { const option=document.createElement('option'); option.value=item.id; option.textContent=item.name; folder.appendChild(option); });
    folder.value = memory.folder_id || 0;
    if (!editMemoryPrivacyWidget) { editMemoryPrivacyWidget = privacyComponent('memory-edit', currentUserId); document.getElementById('edit-memory-privacy').appendChild(editMemoryPrivacyWidget); }
    editMemoryPrivacyWidget.querySelector('.privacy-type').value = memory.privacy_level || 'public';
    await editMemoryPrivacyWidget.loadRule('memory', memory.id);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('editMemoryModal')).show();
}
document.getElementById('editMemoryForm')?.addEventListener('submit', async event => {
    event.preventDefault(); const error=document.getElementById('edit-memory-error'); error.textContent=''; const save=document.getElementById('edit-memory-save'); save.disabled=true;
    try { const rule=editMemoryPrivacyWidget.getRule(); const response=await fetch('http://localhost/IAmStillHere/backend/memories/update.php',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrfToken},body:JSON.stringify({memory_id:Number(document.getElementById('edit-memory-id').value),title:document.getElementById('edit-memory-title').value.trim(),description:document.getElementById('edit-memory-description').value,memory_date:document.getElementById('edit-memory-date').value,folder_id:Number(document.getElementById('edit-memory-folder').value),privacy_level:rule.visibility_type})}); const data=await response.json(); if(!data.success)throw new Error(data.message||'Unable to update memory.'); try { await savePrivacyRule(csrfToken,'memory',data.data.memory_id,rule); } catch (privacyError) { error.textContent='Memory updated, but privacy settings were not saved: '+privacyError.message; return; } bootstrap.Modal.getInstance(document.getElementById('editMemoryModal')).hide(); loadMemories(); loadMemoryFolders(); } catch(e){ error.textContent=e.message; } finally { save.disabled=false; }
});
async function editMilestone(milestoneId){const m=milestoneCache.find(x=>Number(x.id)===Number(milestoneId));if(!m)return;document.getElementById('edit-milestone-id').value=m.id;document.getElementById('edit-milestone-title').value=m.title||'';document.getElementById('edit-milestone-description').value=m.description||'';document.getElementById('edit-milestone-date').value=m.milestone_date||'';document.getElementById('edit-milestone-category').value=m.category||'';if(!editMilestonePrivacyWidget){editMilestonePrivacyWidget=privacyComponent('milestone-edit',currentUserId);document.getElementById('edit-milestone-privacy').appendChild(editMilestonePrivacyWidget);}editMilestonePrivacyWidget.querySelector('.privacy-type').value=m.privacy_level||'public';await editMilestonePrivacyWidget.loadRule('milestone',m.id);bootstrap.Modal.getOrCreateInstance(document.getElementById('editMilestoneModal')).show();}
document.getElementById('editMilestoneForm')?.addEventListener('submit',async e=>{e.preventDefault();const error=document.getElementById('edit-milestone-error');const save=document.getElementById('edit-milestone-save');error.textContent='';save.disabled=true;try{const rule=editMilestonePrivacyWidget.getRule();const response=await fetch('http://localhost/IAmStillHere/backend/milestones/update.php',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrfToken},body:JSON.stringify({milestone_id:Number(document.getElementById('edit-milestone-id').value),title:document.getElementById('edit-milestone-title').value.trim(),description:document.getElementById('edit-milestone-description').value,milestone_date:document.getElementById('edit-milestone-date').value,category:document.getElementById('edit-milestone-category').value.trim(),privacy_level:rule.visibility_type})});const data=await response.json();if(!data.success)throw new Error(data.message||'Unable to update milestone');await savePrivacyRule(csrfToken,'milestone',data.data.milestone_id,rule);bootstrap.Modal.getInstance(document.getElementById('editMilestoneModal')).hide();loadTimeline();}catch(err){error.textContent=err.message;}finally{save.disabled=false;}});
let editFolderPrivacyWidget = null;
async function editFolder(folder){try{document.getElementById('edit-folder-id').value=folder.id;document.getElementById('edit-folder-name').value=folder.name||'';document.getElementById('edit-folder-description').value=folder.description||'';const parent=document.getElementById('edit-folder-parent');parent.innerHTML='<option value="0">No parent</option>';window.lastMemoryFolders.filter(x=>Number(x.id)!==Number(folder.id)).forEach(x=>{const o=document.createElement('option');o.value=x.id;o.textContent=x.name;parent.appendChild(o);});parent.value=folder.parent_folder_id||0;if(!editFolderPrivacyWidget){editFolderPrivacyWidget=privacyComponent('folder-edit',currentUserId);document.getElementById('edit-folder-privacy').appendChild(editFolderPrivacyWidget);}editFolderPrivacyWidget.querySelector('.privacy-type').value=folder.privacy_level||'private';bootstrap.Modal.getOrCreateInstance(document.getElementById('editFolderModal')).show();await editFolderPrivacyWidget.loadRule('memory_folder',folder.id);}catch(error){showAlert(error.message||'Unable to open folder editor','danger');}}
document.getElementById('editFolderForm')?.addEventListener('submit',async e=>{e.preventDefault();const error=document.getElementById('edit-folder-error');const save=document.getElementById('edit-folder-save');error.textContent='';save.disabled=true;try{const rule=editFolderPrivacyWidget.getRule();const advanced=['specific_people','release_date','release_event'].includes(rule.visibility_type);const legacy=['public','family','friends','private'].includes(rule.visibility_type)?rule.visibility_type:'private';const data=await folderPost('update',{folder_id:Number(document.getElementById('edit-folder-id').value),name:document.getElementById('edit-folder-name').value.trim(),description:document.getElementById('edit-folder-description').value,parent_folder_id:Number(document.getElementById('edit-folder-parent').value),privacy_level:legacy});if(!data.success)throw new Error(data.message||'Unable to update folder');try{await savePrivacyRule(csrfToken,'memory_folder',Number(document.getElementById('edit-folder-id').value),rule);}catch(privacyError){error.textContent='Folder updated, but privacy settings were not saved: '+privacyError.message;return;}bootstrap.Modal.getInstance(document.getElementById('editFolderModal')).hide();loadMemoryFolders();if(currentMemoryFolderId)loadMemories();}catch(err){error.textContent=err.message;}finally{save.disabled=false;}});