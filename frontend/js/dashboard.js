let currentUserId = null;
let loggedInUser = null;
let csrfToken = null;
let currentMemoryFolderId = 0;
let memoryPrivacyWidget = null;
let milestonePrivacyWidget = null;
let milestoneCache = [];
let editMilestonePrivacyWidget = null;

async function init() {
    const response = await fetch('/backend/auth/check_session.php');
    const data = await response.json();

    if (!data.logged_in) {
        window.location.href = 'login.php';
        return;
    }
    loggedInUser = data.user;
    const tributesLink = document.getElementById('dashboard-tributes-link'); if (tributesLink) tributesLink.href = `profile.php?user_id=${encodeURIComponent(data.user.id)}#tributes-tab`;

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
    loadAutomations();
    loadRequestCount();
    loadOnThisDay();
}

async function loadCsrfToken() {
    try {
        const response = await fetch('/backend/auth/csrf_token.php');
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
        ? `/data/uploads/photos/${encodeURIComponent(photo)}`
        : '/frontend/images/default-profile.png';
}

function safeUploadPathSegment(value) {
    return encodeURIComponent(value == null ? '' : String(value));
}

async function loadTributeCount(userId) {
    try {
        const response = await fetch(`/backend/tributes/get_count.php?user_id=${userId}`);
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

const DASHBOARD_PAGE_SIZE = 10;
let memoryPage = 1;
let timelinePage = 1;
let eventsPage = 1;
let automationsPage = 1;

function appendDashboardPager(container, page, totalItems, onPageChange) {
    const totalPages = Math.max(1, Math.ceil(totalItems / DASHBOARD_PAGE_SIZE));
    if (totalPages <= 1) return;
    const pager = document.createElement('nav');
    pager.className = 'dashboard-pager d-flex flex-wrap justify-content-center align-items-center gap-2 mt-3';
    pager.setAttribute('aria-label', 'Pagination');
    const previous = document.createElement('button');
    previous.type = 'button'; previous.className = 'btn btn-outline-secondary btn-sm'; previous.textContent = 'Previous'; previous.disabled = page <= 1;
    previous.onclick = () => onPageChange(page - 1);
    const label = document.createElement('span');
    label.className = 'small text-muted px-1';
    label.textContent = `Page ${page} of ${totalPages}`;
    const next = document.createElement('button');
    next.type = 'button'; next.className = 'btn btn-outline-secondary btn-sm'; next.textContent = 'Next'; next.disabled = page >= totalPages;
    next.onclick = () => onPageChange(page + 1);
    pager.append(previous, label, next);
    container.appendChild(pager);
}

async function loadMemories(page = memoryPage) {
    memoryPage = Math.max(1, Number(page) || 1);
    try {
        const response = await fetch(`/backend/memories/list.php?user_id=${currentUserId}${currentMemoryFolderId ? '&folder_id=' + currentMemoryFolderId : ''}&_=${Date.now()}`);
        const data = await response.json();

        const grid = document.getElementById('memories-grid');
        grid.innerHTML = '';

        window.lastLoadedMemories = data.memories || [];
        if (data.success && data.memories.length > 0) {
            const memoryStart = (memoryPage - 1) * DASHBOARD_PAGE_SIZE;
            data.memories.slice(memoryStart, memoryStart + DASHBOARD_PAGE_SIZE).forEach((memory, index) => {
                const col = document.createElement('div');
                col.className = 'col-md-4 mb-4 memory-drag-item';
                col.draggable = true;
                col.dataset.memoryId = String(memory.id);
                col.title = 'Drag this memory onto a folder to move it';

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
                    filePath = `/data/uploads/photos/${memory.file_path}`;
                    downloadButton = `<a href="${filePath}" download="${memory.title}" class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i> Download</a>`;

                    mediaHtml = `
                        <img src="${filePath}" 
                            alt="${memory.title}" 
                            class="memory-image">
                    `;
                } else if (isVideo) {
                    filePath = `/data/uploads/videos/${safeUploadPathSegment(memory.file_path)}`;
                    downloadButton = `<a href="${filePath}" download="${memory.title}" class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i> Download</a>`;
                    const posterPath = memory.video_thumbnail_path
                        ? `/data/uploads/${String(memory.video_thumbnail_path).split('/').map(safeUploadPathSegment).join('/')}`
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
                    filePath = `/data/uploads/audio/${memory.file_path}`;
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
                    filePath = `/data/uploads/documents/${memory.file_path}`;
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
                
                const memoryActionsMenu = `<div class="dropdown memory-actions-menu"><button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Memory actions"><i class="bi bi-three-dots-vertical"></i></button><ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="${filePath}" download="${escapeHtml(memory.title)}" aria-label="Download memory" title="Download"><i class="bi bi-download"></i></a></li><li><button class="dropdown-item" type="button" onclick="editMemory(${memory.id})" aria-label="Edit memory" title="Edit"><i class="bi bi-pencil"></i></button></li><li><button class="dropdown-item" type="button" onclick="moveMemory(${memory.id})" aria-label="Move memory to folder" title="Move to folder"><i class="bi bi-folder2-open"></i></button></li><li><button class="dropdown-item text-danger" type="button" onclick="deleteMemory(${memory.id})" aria-label="Delete memory" title="Delete"><i class="bi bi-trash"></i></button></li></ul></div>`;

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
                                ${memoryActionsMenu}
                                
                                
                            </div>
                            <div class="memory-comments mt-3" data-memory-comments="${memory.id}">
                                <div class="small text-muted">Loading comments...</div>
                            </div>
                        </div>
                    </div>
                `;
                col.addEventListener('dragstart', event => { event.dataTransfer.effectAllowed = 'move'; event.dataTransfer.setData('text/plain', String(memory.id)); col.classList.add('is-dragging'); });
                col.addEventListener('dragend', () => col.classList.remove('is-dragging'));
                grid.appendChild(col);
                loadMemoryComments(memory.id);
            });
            appendDashboardPager(grid, memoryPage, data.memories.length, nextPage => loadMemories(nextPage));
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
        const response = await fetch(`/backend/memories/comments/list.php?memory_id=${memoryId}&page=${page}&limit=20`);
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
        const response = await fetch('/backend/memories/comments/create.php', {
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
        const response = await fetch('/backend/memories/comments/update.php', {
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
        const response = await fetch('/backend/memories/comments/delete.php', {
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
        const response = await fetch('/backend/memories/delete.php', {
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

async function loadTimeline(page = timelinePage) {
    timelinePage = Math.max(1, Number(page) || 1);
    try {
        const response = await fetch(`/backend/milestones/list.php?user_id=${currentUserId}`);
        const data = await response.json();

        const container = document.getElementById('timeline-container');
        container.innerHTML = '';

        milestoneCache = data.milestones || [];
        if (data.success && data.milestones.length > 0) {
            const milestoneStart = (timelinePage - 1) * DASHBOARD_PAGE_SIZE;
            data.milestones.slice(milestoneStart, milestoneStart + DASHBOARD_PAGE_SIZE).forEach((milestone, index) => {
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
                            
                            
                        </div>
                    </div>
                `;

                container.appendChild(item);
            });
            appendDashboardPager(container, timelinePage, data.milestones.length, nextPage => loadTimeline(nextPage));
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
        const response = await fetch('/backend/milestones/delete.php', {
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
async function loadEvents(page = eventsPage) {
    eventsPage = Math.max(1, Number(page) || 1);
    try {
        const response = await fetch(`/backend/events/list.php?user_id=${currentUserId}`);
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

        const eventStart = (eventsPage - 1) * DASHBOARD_PAGE_SIZE;
        const pageEvents = data.events.slice(eventStart, eventStart + DASHBOARD_PAGE_SIZE);
        // Separate the current page into upcoming and past events.
        const now = new Date();
        const upcomingEvents = pageEvents.filter(e => new Date(e.scheduled_date) >= now);
        const pastEvents = pageEvents.filter(e => new Date(e.scheduled_date) < now);

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
        appendDashboardPager(container, eventsPage, data.events.length, nextPage => loadEvents(nextPage));

    } catch (error) {
        console.error('Error loading events:', error);
        document.getElementById('events-container').innerHTML =
            '<div class="alert alert-danger">Error loading events</div>';
    }
}

// Create Event Card
function createEventCard(event, isPast) {
    const card = document.createElement('article');
    card.id = `event-${Number(event.id)}`;
    card.className = `card mb-3 event-display-card ${event.media_url ? 'has-media' : ''} ${isPast ? 'is-past' : 'is-upcoming'}`;
    const eventDate = new Date(event.scheduled_date);
    const formattedDate = eventDate.toLocaleDateString(undefined, {weekday:'long',year:'numeric',month:'long',day:'numeric'});
    const formattedTime = eventDate.toLocaleTimeString(undefined, {hour:'2-digit',minute:'2-digit'});
    const eventTypes = {birthday:{icon:'bi-cake2',color:'text-danger'},anniversary:{icon:'bi-heart',color:'text-danger'},memorial:{icon:'bi-flower1',color:'text-info'},remembrance:{icon:'bi-star',color:'text-warning'},celebration:{icon:'bi-balloon',color:'text-success'},other:{icon:'bi-calendar-event',color:'text-secondary'}};
    const typeInfo = eventTypes[event.event_type] || eventTypes.other;
    const privacyBadges = {public:'bg-success',family:'bg-warning text-dark',friends:'bg-primary',private:'bg-secondary',specific_people:'bg-info text-dark',release_date:'bg-dark',release_event:'bg-dark'};
    const canDelete = loggedInUser && (Number(loggedInUser.id) === Number(currentUserId) || loggedInUser.role === 'admin');
    const media = event.media_url ? (event.media_type === 'video'
        ? `<div class="event-card-media"><video controls preload="metadata"><source src="${escapeHtml(event.media_url)}" type="${escapeHtml(event.media_mime || 'video/mp4')}">Your browser cannot play this video.</video></div>`
        : `<div class="event-card-media"><img src="${escapeHtml(event.media_url)}" alt="${escapeHtml(event.title || 'Event photo')}" loading="lazy"></div>`) : '';
    card.innerHTML = `${media}<div class="card-body event-card-body"><div class="event-card-main"><div class="event-card-title-row"><span class="event-card-icon"><i class="bi ${typeInfo.icon} ${typeInfo.color}"></i></span><h5>${escapeHtml(event.title || 'Untitled event')}</h5><span class="badge ${privacyBadges[event.privacy_level] || 'bg-secondary'}">${escapeHtml(event.privacy_level || 'private')}</span>${isPast ? '<span class="badge bg-secondary">Past</span>' : '<span class="badge bg-info text-dark">Upcoming</span>'}</div><p class="event-card-date"><i class="bi bi-calendar3"></i> ${escapeHtml(formattedDate)} at ${escapeHtml(formattedTime)}</p>${event.message ? `<p class="event-card-message">${escapeHtml(event.message)}</p>` : ''}</div></div>`;
    return card;
}

// Delete Event Function
async function deleteEvent(eventId) {
    if (!confirm('Are you sure you want to delete this event?')) {
        return;
    }

    try {
        const response = await fetch('/backend/events/delete.php', {
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
        const response = await fetch(`/backend/family/pending_requests.php?user_id=${currentUserId}`);
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
    const response = await fetch('/backend/auth/check_session.php');
    const data = await response.json();

    if (data.logged_in) {
        const userId = data.user.id;
        loadTributeCount(userId);
    }
});

document.getElementById('memoryForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.currentTarget;
    const submit = form.querySelector('button[type="submit"]');
    if (form.dataset.uploading === '1') return;
    form.dataset.uploading = '1';
    const originalText = submit?.innerHTML || 'Upload Memory';
    if (submit) { submit.disabled = true; submit.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Uploading...'; }

    const formData = new FormData();
    formData.append('title', document.getElementById('memory-title').value);
    formData.append('description', document.getElementById('memory-description').value);
    formData.append('memory_date', document.getElementById('memory-date').value);
    formData.append('privacy_level', memoryPrivacyWidget?.getRule().visibility_type || document.getElementById('memory-privacy').value);
    if (currentMemoryFolderId) { formData.append('folder_id', currentMemoryFolderId); formData.append('privacy_override', '0'); }
    formData.append('file', document.getElementById('memory-file').files[0]);

    try {
        const response = await fetch('/backend/memories/upload.php', { method: 'POST', body: formData });
        const data = await response.json();
        if (data.success) {
            try { if (memoryPrivacyWidget) await savePrivacyRule(csrfToken, 'memory', data.memory_id, memoryPrivacyWidget.getRule()); } catch (privacyError) { showAlert('Memory uploaded, but privacy settings were not saved: ' + privacyError.message, 'warning'); return; }
            showAlert('Memory uploaded successfully!', 'success');
            form.reset();
            bootstrap.Modal.getInstance(document.getElementById('uploadMemoryModal')).hide();
            loadMemories();
        } else showAlert(data.message, 'danger');
    } catch (error) { showAlert('Upload failed. Please try again.', 'danger'); }
    finally {
        form.dataset.uploading = '0';
        if (submit) { submit.disabled = false; submit.innerHTML = originalText; }
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
        const response = await fetch('/backend/milestones/create.php', {
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

const eventMediaInput = document.getElementById('event-media');
eventMediaInput?.addEventListener('change', () => {
    const preview = document.getElementById('event-media-preview');
    const file = eventMediaInput.files?.[0];
    preview.replaceChildren();
    if (!file) { preview.classList.add('d-none'); return; }
    const url = URL.createObjectURL(file);
    const media = file.type.startsWith('video/') ? document.createElement('video') : document.createElement('img');
    media.src = url; media.className = 'event-upload-preview-media';
    if (media.tagName === 'VIDEO') media.controls = true;
    media.addEventListener('load', () => URL.revokeObjectURL(url), {once:true});
    media.addEventListener('loadedmetadata', () => URL.revokeObjectURL(url), {once:true});
    preview.appendChild(media); preview.classList.remove('d-none');
});

document.getElementById('eventForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    const submit = document.getElementById('event-submit');
    const original = submit.innerHTML;
    submit.disabled = true; submit.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Scheduling...';
    const form = new FormData();
    form.append('title', document.getElementById('event-title').value.trim());
    form.append('message', document.getElementById('event-message').value.trim());
    form.append('scheduled_date', document.getElementById('event-date').value);
    form.append('privacy_level', document.getElementById('event-privacy').value);
    form.append('csrf_token', csrfToken || '');
    const media = eventMediaInput?.files?.[0]; if (media) form.append('media', media);
    try {
        const response = await fetch('/backend/events/create.php', {method:'POST',headers:{'X-CSRF-Token':csrfToken || ''},body:form});
        const text = await response.text();
        let data; try { data = JSON.parse(text); } catch (_) { throw new Error(`Server returned an invalid response (${response.status}).`); }
        if (!response.ok || !data.success) throw new Error(data.message || 'Unable to schedule event.');
        showAlert(data.message || 'Event scheduled successfully!', 'success');
        document.getElementById('eventForm').reset();
        document.getElementById('event-media-preview')?.classList.add('d-none');
        bootstrap.Modal.getInstance(document.getElementById('scheduleEventModal'))?.hide();
        eventsPage = 1; loadEvents(1);
    } catch (error) { showAlert(error.message || 'Failed to schedule event.', 'danger'); }
    finally { submit.disabled = false; submit.innerHTML = original; }
});

document.addEventListener('DOMContentLoaded', init);

async function loadMemoryFolders(search = '') {
    const box = document.getElementById('memory-folders'); if (!box) return;
    const response = await fetch(`/backend/memories/folders/list.php?user_id=${currentUserId}&search=${encodeURIComponent(search)}&_=${Date.now()}`);
    const data = await response.json(); box.innerHTML = '';
    const folders = data.success ? (data.data.folders || []) : []; window.lastMemoryFolders = folders;
    const activeFolder = folders.find(folder => Number(folder.id) === Number(currentMemoryFolderId));
    const breadcrumb = document.getElementById('memory-folder-breadcrumb'); if (breadcrumb) breadcrumb.textContent = activeFolder ? activeFolder.name : 'All memories';
    if (currentMemoryFolderId && !activeFolder) { currentMemoryFolderId = 0; localStorage.removeItem('memoryFolder_' + currentUserId); }
    const all = document.createElement('button'); all.type='button'; all.className=`folder-tree-all ${currentMemoryFolderId===0?'is-selected':''}`; all.innerHTML='<i class="bi bi-collection me-2"></i><span>All memories</span>'; all.onclick=()=>{memoryPage=1;currentMemoryFolderId=0;localStorage.removeItem('memoryFolder_'+currentUserId);if(breadcrumb)breadcrumb.textContent='All memories';loadMemories();loadMemoryFolders(search);}; box.appendChild(all);
    if (!folders.length) { const empty=document.createElement('div'); empty.className='folder-tree-empty'; empty.innerHTML='<i class="bi bi-folder2-open"></i><div>No folders yet.</div><small>Create a folder to organize your memories.</small>'; box.appendChild(empty); }
    const byParent = new Map(); folders.forEach(f=>{const key=Number(f.parent_folder_id||0);if(!byParent.has(key))byParent.set(key,[]);byParent.get(key).push(f);});
    const renderLevel = (parentId, host, depth=0) => (byParent.get(Number(parentId))||[]).forEach(folder => {
        const row=document.createElement('div'); row.className=`folder-tree-node ${currentMemoryFolderId===Number(folder.id)?'is-selected':''}`; row.dataset.folderId=folder.id; row.style.setProperty('--folder-depth',depth);
        row.addEventListener('dragover', event => { event.preventDefault(); event.dataTransfer.dropEffect = 'move'; row.classList.add('is-drop-target'); });
        row.addEventListener('dragleave', event => { if (!row.contains(event.relatedTarget)) row.classList.remove('is-drop-target'); });
        row.addEventListener('drop', async event => { event.preventDefault(); row.classList.remove('is-drop-target'); const memoryId=Number(event.dataTransfer.getData('text/plain')); if(memoryId>0) await moveMemoryToFolder(memoryId, Number(folder.id)); });
        const children=byParent.get(Number(folder.id))||[]; const hasChildren=children.length>0; const toggle=document.createElement('button'); toggle.type='button'; toggle.className='folder-chevron'; toggle.innerHTML=hasChildren?'<i class="bi bi-chevron-right"></i>':'<span></span>'; toggle.setAttribute('aria-label',hasChildren?'Expand folder':'No subfolders');
        const content=document.createElement('button'); content.type='button'; content.className='folder-tree-main'; content.innerHTML=`<i class="bi bi-folder-fill folder-tree-icon"></i><span class="folder-tree-name"></span><span class="folder-tree-count">${Number(folder.memory_count||0)}</span>`; content.querySelector('.folder-tree-name').textContent=folder.name; content.title=folder.name;
        content.onclick=()=>{memoryPage=1;currentMemoryFolderId=Number(folder.id);localStorage.setItem('memoryFolder_'+currentUserId,currentMemoryFolderId);if(breadcrumb)breadcrumb.textContent=folder.name;loadMemories();loadMemoryFolders(search);};
        const manage=document.createElement('div'); manage.className='dropdown folder-tree-manage'; const menu=document.createElement('button'); menu.type='button'; menu.className='btn btn-sm btn-link text-muted'; menu.dataset.bsToggle='dropdown'; menu.setAttribute('aria-label',`Manage ${folder.name}`); menu.innerHTML='<i class="bi bi-three-dots-vertical"></i>'; const list=document.createElement('ul'); list.className='dropdown-menu dropdown-menu-end'; [['Edit',()=>editFolder(folder)],['+ Child',()=>createChildFolder(folder)],['Delete',()=>deleteFolder(folder)]].forEach(([label,fn],i)=>{const li=document.createElement('li'),item=document.createElement('button');item.type='button';item.className=`dropdown-item ${i===2?'text-danger':''}`;item.textContent=label;item.onclick=fn;li.appendChild(item);list.appendChild(li);}); manage.append(menu,list); row.append(toggle,content,manage); host.appendChild(row);
        const childHost=document.createElement('div'); childHost.className='folder-tree-children'; childHost.hidden=true; row.parentNode?.appendChild(childHost); if(hasChildren){toggle.onclick=()=>{childHost.hidden=!childHost.hidden;toggle.innerHTML=childHost.hidden?'<i class="bi bi-chevron-right"></i>':'<i class="bi bi-chevron-down"></i>';};renderLevel(folder.id,childHost,depth+1);}
    });
    renderLevel(0,box);
    const select=document.getElementById('memory-folder'); if(select){select.innerHTML='<option value="0">No folder</option>';folders.forEach(folder=>{const option=document.createElement('option');option.value=folder.id;option.textContent=folder.name;select.appendChild(option);});}
}

document.getElementById('folder-search')?.addEventListener('input', event => loadMemoryFolders(event.target.value));
document.getElementById('new-folder-button')?.addEventListener('click', async () => {
    const name = prompt('Folder name');
    if (!name) return;
    const privacy = prompt('Privacy: public, family, friends, or private', 'private');
    const response = await fetch('/backend/memories/folders/create.php', { method: 'POST', headers: {'Content-Type':'application/json','X-CSRF-Token':csrfToken}, body: JSON.stringify({name, privacy_level: privacy || 'private'}) });
    const data = await response.json();
    if (data.success) loadMemoryFolders(); else vaultStatus(data.message || 'Unable to create folder', 'danger');
});
async function folderPost(endpoint, payload) {
    const response = await fetch(`/backend/memories/folders/${endpoint}.php`, {method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrfToken},body:JSON.stringify(payload)});
    return response.json();
}
async function renameFolder(folder) { const name=prompt('New folder name',folder.name); if(!name)return; const data=await folderPost('update',{folder_id:folder.id,name}); if(data.success)loadMemoryFolders();else showAlert(data.message||'Unable to rename folder','danger'); }
async function createChildFolder(parent) { const name=prompt('Child folder name'); if(!name)return; const data=await folderPost('create',{name,parent_folder_id:parent.id,privacy_level:parent.privacy_level}); if(data.success)loadMemoryFolders();else showAlert(data.message||'Unable to create child folder','danger'); }
async function deleteFolder(folder) { if(!confirm(`Delete empty folder "${folder.name}"?`))return; const data=await folderPost('delete',{folder_id:folder.id}); if(data.success){if(currentMemoryFolderId===folder.id){currentMemoryFolderId=0;loadMemories();}loadMemoryFolders();}else showAlert(data.message||'Folder must be empty','danger'); }
async function moveMemoryToFolder(memoryId, folderId) {
    const data = await folderPost('move_memory', { memory_id: Number(memoryId), folder_id: Number(folderId) || 0 });
    if (data.success) {
        showAlert('Memory moved successfully', 'success');
        loadMemories();
        loadMemoryFolders();
    } else {
        showAlert(data.message || 'Unable to move memory', 'danger');
    }
}

async function moveMemory(memoryId) {
    const folders = await fetch(`/backend/memories/folders/list.php?user_id=${currentUserId}&_=${Date.now()}`).then(r => r.json());
    const available = folders.data?.folders || [];
    const options = available.map(f => `${f.id}: ${f.name}`).join('\n');
    const choice = prompt(`Choose a folder ID. Enter 0 to remove this memory from its folder.\n${options}`, '0');
    if (choice === null) return;
    const input = choice.trim();
    const selected = available.find(f => String(f.id) === input || String(f.name).toLowerCase() === input.toLowerCase());
    await moveMemoryToFolder(memoryId, input === '0' ? 0 : (selected ? Number(selected.id) : parseInt(input, 10) || 0));
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
    try { const rule=editMemoryPrivacyWidget.getRule(); const response=await fetch('/backend/memories/update.php',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrfToken},body:JSON.stringify({memory_id:Number(document.getElementById('edit-memory-id').value),title:document.getElementById('edit-memory-title').value.trim(),description:document.getElementById('edit-memory-description').value,memory_date:document.getElementById('edit-memory-date').value,folder_id:Number(document.getElementById('edit-memory-folder').value),privacy_level:rule.visibility_type})}); const data=await response.json(); if(!data.success)throw new Error(data.message||'Unable to update memory.'); try { await savePrivacyRule(csrfToken,'memory',data.data.memory_id,rule); } catch (privacyError) { error.textContent='Memory updated, but privacy settings were not saved: '+privacyError.message; return; } bootstrap.Modal.getInstance(document.getElementById('editMemoryModal')).hide(); loadMemories(); loadMemoryFolders(); } catch(e){ error.textContent=e.message; } finally { save.disabled=false; }
});
async function editMilestone(milestoneId){const m=milestoneCache.find(x=>Number(x.id)===Number(milestoneId));if(!m)return;document.getElementById('edit-milestone-id').value=m.id;document.getElementById('edit-milestone-title').value=m.title||'';document.getElementById('edit-milestone-description').value=m.description||'';document.getElementById('edit-milestone-date').value=m.milestone_date||'';document.getElementById('edit-milestone-category').value=m.category||'';if(!editMilestonePrivacyWidget){editMilestonePrivacyWidget=privacyComponent('milestone-edit',currentUserId);document.getElementById('edit-milestone-privacy').appendChild(editMilestonePrivacyWidget);}editMilestonePrivacyWidget.querySelector('.privacy-type').value=m.privacy_level||'public';await editMilestonePrivacyWidget.loadRule('milestone',m.id);bootstrap.Modal.getOrCreateInstance(document.getElementById('editMilestoneModal')).show();}
document.getElementById('editMilestoneForm')?.addEventListener('submit',async e=>{e.preventDefault();const error=document.getElementById('edit-milestone-error');const save=document.getElementById('edit-milestone-save');error.textContent='';save.disabled=true;try{const rule=editMilestonePrivacyWidget.getRule();const response=await fetch('/backend/milestones/update.php',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrfToken},body:JSON.stringify({milestone_id:Number(document.getElementById('edit-milestone-id').value),title:document.getElementById('edit-milestone-title').value.trim(),description:document.getElementById('edit-milestone-description').value,milestone_date:document.getElementById('edit-milestone-date').value,category:document.getElementById('edit-milestone-category').value.trim(),privacy_level:rule.visibility_type})});const data=await response.json();if(!data.success)throw new Error(data.message||'Unable to update milestone');await savePrivacyRule(csrfToken,'milestone',data.data.milestone_id,rule);bootstrap.Modal.getInstance(document.getElementById('editMilestoneModal')).hide();loadTimeline();}catch(err){error.textContent=err.message;}finally{save.disabled=false;}});
let editFolderPrivacyWidget = null;
async function editFolder(folder){try{document.getElementById('edit-folder-id').value=folder.id;document.getElementById('edit-folder-name').value=folder.name||'';document.getElementById('edit-folder-description').value=folder.description||'';const parent=document.getElementById('edit-folder-parent');parent.innerHTML='<option value="0">No parent</option>';window.lastMemoryFolders.filter(x=>Number(x.id)!==Number(folder.id)).forEach(x=>{const o=document.createElement('option');o.value=x.id;o.textContent=x.name;parent.appendChild(o);});parent.value=folder.parent_folder_id||0;if(!editFolderPrivacyWidget){editFolderPrivacyWidget=privacyComponent('folder-edit',currentUserId);document.getElementById('edit-folder-privacy').appendChild(editFolderPrivacyWidget);}editFolderPrivacyWidget.querySelector('.privacy-type').value=folder.privacy_level||'private';bootstrap.Modal.getOrCreateInstance(document.getElementById('editFolderModal')).show();await editFolderPrivacyWidget.loadRule('memory_folder',folder.id);}catch(error){showAlert(error.message||'Unable to open folder editor','danger');}}
document.getElementById('editFolderForm')?.addEventListener('submit',async e=>{e.preventDefault();const error=document.getElementById('edit-folder-error');const save=document.getElementById('edit-folder-save');error.textContent='';save.disabled=true;try{const rule=editFolderPrivacyWidget.getRule();const advanced=['specific_people','release_date','release_event'].includes(rule.visibility_type);const legacy=['public','family','friends','private'].includes(rule.visibility_type)?rule.visibility_type:'private';const data=await folderPost('update',{folder_id:Number(document.getElementById('edit-folder-id').value),name:document.getElementById('edit-folder-name').value.trim(),description:document.getElementById('edit-folder-description').value,parent_folder_id:Number(document.getElementById('edit-folder-parent').value),privacy_level:legacy});if(!data.success)throw new Error(data.message||'Unable to update folder');try{await savePrivacyRule(csrfToken,'memory_folder',Number(document.getElementById('edit-folder-id').value),rule);}catch(privacyError){error.textContent='Folder updated, but privacy settings were not saved: '+privacyError.message;return;}bootstrap.Modal.getInstance(document.getElementById('editFolderModal')).hide();loadMemoryFolders();if(currentMemoryFolderId)loadMemories();}catch(err){error.textContent=err.message;}finally{save.disabled=false;}});
async function loadOnThisDay(page = 1) {
    const container = document.getElementById('on-this-day-container');
    if (!container) return;
    container.innerHTML = '<div class="text-muted">Loading On This Day...</div>';
    try {
        const response = await fetch(`/backend/on_this_day/list.php?page=${page}&limit=8`);
        const data = await response.json();
        if (!data.success) {
            container.innerHTML = `<div class="alert alert-danger">${escapeHtml(data.message || 'Unable to load On This Day.')}</div>`;
            return;
        }
        renderOnThisDay(data.data.items || [], data.data.pagination || {});
    } catch (error) {
        container.innerHTML = '<div class="alert alert-danger">Unable to load On This Day.</div>';
    }
}

function renderOnThisDay(items, pagination) {
    const container = document.getElementById('on-this-day-container');
    container.innerHTML = '';
    if (!items.length) {
        const empty = document.createElement('div');
        empty.className = 'col-12 text-muted';
        empty.textContent = 'Nothing from past years on this date yet.';
        container.appendChild(empty);
        return;
    }
    items.forEach(item => container.appendChild(createOnThisDayCard(item)));
    if ((pagination.total_pages || 0) > 1) {
        const controls = document.createElement('div');
        controls.className = 'col-12 d-flex justify-content-between align-items-center mt-2';
        const prev = document.createElement('button');
        prev.className = 'btn btn-sm btn-outline-secondary';
        prev.textContent = 'Previous';
        prev.disabled = Number(pagination.current_page || 1) <= 1;
        prev.onclick = () => loadOnThisDay(Number(pagination.current_page) - 1);
        const next = document.createElement('button');
        next.className = 'btn btn-sm btn-outline-secondary';
        next.textContent = 'Next';
        next.disabled = Number(pagination.current_page || 1) >= Number(pagination.total_pages || 1);
        next.onclick = () => loadOnThisDay(Number(pagination.current_page) + 1);
        const label = document.createElement('span');
        label.className = 'small text-muted';
        label.textContent = `Page ${pagination.current_page} of ${pagination.total_pages}`;
        controls.append(prev, label, next);
        container.appendChild(controls);
    }
}

function createOnThisDayCard(item) {
    const col = document.createElement('div');
    col.className = 'col-md-6 col-xl-3';
    const link = document.createElement('a');
    link.className = 'card h-100 text-decoration-none text-body on-this-day-card';
    link.href = item.url || 'profile.php';
    if (item.thumbnail_url) {
        const img = document.createElement('img');
        img.className = 'card-img-top';
        img.alt = '';
        img.src = item.thumbnail_url;
        img.style.height = '130px';
        img.style.objectFit = 'cover';
        link.appendChild(img);
    }
    const body = document.createElement('div');
    body.className = 'card-body';
    const meta = document.createElement('div');
    meta.className = 'small text-muted mb-1 text-capitalize';
    meta.textContent = `${item.source_type} - ${item.years_ago} years ago`;
    const title = document.createElement('h6');
    title.className = 'mb-1';
    title.textContent = item.title || 'Untitled';
    const preview = document.createElement('p');
    preview.className = 'small text-muted mb-2';
    preview.textContent = item.preview || '';
    const date = document.createElement('div');
    date.className = 'small fw-semibold';
    date.textContent = item.original_date;
    body.append(meta, title, preview, date);
    link.appendChild(body);
    col.appendChild(link);
    return col;
}
let vaultCurrentFolderId = 0;
let vaultOwnerId = null;
let vaultIsVerified = false;
function vaultStatus(message, type = 'success') { const box = document.getElementById('vault-status'); if (box) { box.className = 'alert alert-' + type; box.textContent = message; setTimeout(() => { box.className = 'alert d-none'; box.textContent = ''; }, 4500); } if (typeof showAlert === 'function') showAlert(message, type); }

function initVaultFeature() {
    document.getElementById('vault-refresh-btn')?.addEventListener('click', () => loadVault());
    document.getElementById('vault-reauth-form')?.addEventListener('submit', vaultReauth);
    document.getElementById('vault-upload-form')?.addEventListener('submit', vaultUpload); document.getElementById('vault-upload-trigger')?.addEventListener('click', () => { if (!vaultIsVerified) return vaultStatus('Unlock Vault first.', 'warning'); document.getElementById('vault-file')?.click(); });
    
    document.getElementById('vault-grant')?.addEventListener('click', () => vaultPermission('grant'));
    document.getElementById('vault-revoke')?.addEventListener('click', () => vaultPermission('revoke'));
    loadVault();
}

async function vaultJson(endpoint, payload) {
    const response = await fetch(`/backend/vault/${endpoint}.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
        body: JSON.stringify({ ...payload, csrf_token: csrfToken })
    });
    return response.json();
}

async function loadVault() {
    const docs = document.getElementById('vault-document-list');
    if (!docs || !currentUserId) return;
    docs.innerHTML = '<div class="text-muted">Loading vault...</div>';
    try {
        const ownerInput = Number(document.getElementById('vault-owner-id')?.value || 0);
        const ownerParam = ownerInput > 0 ? `&owner_id=${ownerInput}` : '';
        const response = await fetch(`/backend/vault/list.php?folder_id=${vaultCurrentFolderId}${ownerParam}`);
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Unable to load vault.');
        vaultOwnerId = data.data.owner_id;
        const ownerLabel = document.getElementById('vault-current-owner-label');
        if (ownerLabel) ownerLabel.textContent = ownerInput > 0 ? 'Viewing Vault Owner ID: ' + vaultOwnerId : 'Your Vault ID: ' + vaultOwnerId;
        vaultIsVerified = Boolean(data.data.vault_verified);
        document.getElementById('vault-reauth-box').style.display = vaultIsVerified ? 'none' : '';
        const path = data.data.folder_path || []; const crumb = document.getElementById('vault-breadcrumb'); if (crumb) crumb.textContent = 'Vault / ' + (path.length ? path.map(item => item.name).join(' / ') : 'All documents');
        window.vaultFolderPath = data.data.folder_path || []; renderVaultFolders(data.data.folders || []);
        renderVaultDocuments(data.data.documents || []);
        renderVaultPermissions(data.data.permissions || []);
        setVaultLockedState(vaultIsVerified, data.data.vault_verified_until);
        loadVaultLogs();
    } catch (error) {
        docs.innerHTML = `<div class="alert alert-danger">${escapeHtml(error.message)}</div>`;
    }
}


function setVaultLockedState(isVerified, verifiedUntil) {
    const sensitive = [
        'vault-file',
        'vault-counsel-user-id', 'vault-grant', 'vault-revoke', 'vault-upload-trigger'
    ];
    sensitive.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.disabled = !isVerified;
    });
    document.querySelectorAll('#vault-document-list button, #vault-document-list a').forEach(el => {
        el.classList.toggle('disabled', !isVerified);
        if ('disabled' in el) el.disabled = !isVerified;
        if (!isVerified && el.tagName === 'A') el.addEventListener('click', vaultBlockLockedClick, { once: true });
    });
    const uploadButton = document.querySelector('#vault-upload-form button[type="submit"]');
    if (uploadButton) uploadButton.disabled = !isVerified;
}

function vaultBlockLockedClick(event) {
    if (!vaultIsVerified) {
        event.preventDefault();
        vaultStatus('Unlock Vault first.', 'warning');
    }
}
function renderVaultFolders(folders) {
    const box=document.getElementById('vault-folder-list'); if(!box)return; box.innerHTML='';
    if(vaultCurrentFolderId){const back=document.createElement('button');back.className='list-group-item list-group-item-action';back.textContent='â† Back';back.onclick=()=>{const path=window.vaultFolderPath||[];vaultCurrentFolderId=path.length?(path[path.length-1].parent_folder_id||0):0;loadVault();};box.appendChild(back);}
    const all=document.createElement('button');all.className=`list-group-item list-group-item-action ${vaultCurrentFolderId===0?'active':''}`;all.textContent='All documents';all.onclick=()=>{vaultCurrentFolderId=0;loadVault();};box.appendChild(all);
    folders.forEach(folder=>{const row=document.createElement('div');row.className='list-group-item d-flex justify-content-between align-items-center gap-2';const open=document.createElement('button');open.className='btn btn-sm btn-link text-start flex-grow-1';open.textContent='ðŸ“ '+folder.name;open.onclick=()=>{vaultCurrentFolderId=folder.id;loadVault();};const del=document.createElement('button');del.className='btn btn-sm btn-outline-danger';del.textContent='Delete';del.onclick=()=>vaultDeleteFolder(folder.id);row.append(open,del);box.appendChild(row);});
}
function renderVaultDocuments(documents) {
    const box = document.getElementById('vault-document-list');
    box.innerHTML = '';
    if (!documents.length) { box.innerHTML = '<div class="col-12 text-muted">No vault documents here.</div>'; return; }
    documents.forEach(doc => {
        const col = document.createElement('div'); col.className = 'col-md-6';
        const card = document.createElement('div'); card.className = 'card h-100';
        const body = document.createElement('div'); body.className = 'card-body';
        const title = document.createElement('h6'); title.textContent = doc.display_name || doc.original_filename;
        const meta = document.createElement('div'); meta.className = 'small text-muted mb-2'; meta.textContent = `${doc.mime_type} - ${Math.ceil(doc.file_size / 1024)} KB`;
        const hash = document.createElement('div'); hash.className = 'small text-muted text-truncate mb-2'; hash.title = doc.sha256; hash.textContent = `SHA-256 ${doc.sha256}`;
        const actions = document.createElement('div'); actions.className = 'dropdown vault-document-actions text-end';
        const toggle = document.createElement('button'); toggle.className = 'btn btn-sm btn-outline-secondary'; toggle.type = 'button'; toggle.dataset.bsToggle = 'dropdown'; toggle.setAttribute('aria-label', 'Document actions'); toggle.innerHTML = '<i class="bi bi-three-dots-vertical"></i>';
        const menu = document.createElement('ul'); menu.className = 'dropdown-menu dropdown-menu-end';
        const addAction = (icon, label, handler, danger = false) => { const li=document.createElement('li'); const item=document.createElement('button'); item.type='button'; item.className=`dropdown-item ${danger?'text-danger':''}`; item.title=label; item.setAttribute('aria-label',label); item.innerHTML=`<i class="bi ${icon}"></i>`; item.onclick=handler; li.appendChild(item); menu.appendChild(li); };
        addAction('bi-download', 'Download', () => vaultDownload(doc)); addAction('bi-pencil', 'Rename', () => vaultRename(doc)); addAction('bi-trash', 'Delete', () => vaultDeleteDocument(doc.id), true);
        actions.append(toggle, menu); body.append(title, meta, hash, actions); card.appendChild(body); col.appendChild(card); box.appendChild(col);
    });
}function renderVaultPermissions(permissions) {
    const box = document.getElementById('vault-permission-list');
    if (!box) return;
    box.innerHTML = permissions.length ? '' : 'No legal counsel users authorized.';
    permissions.forEach(p => {
        const row = document.createElement('div');
        row.textContent = `${p.full_name || p.username} (#${p.authorized_user_id}) - ${p.status}`;
        box.appendChild(row);
    });
}

async function loadVaultLogs() {
    const box = document.getElementById('vault-log-list');
    if (!box) return;
    try {
        const ownerInput = Number(document.getElementById('vault-owner-id')?.value || 0);
        const ownerParam = ownerInput > 0 ? `?owner_id=${ownerInput}` : '';
        const response = await fetch(`/backend/vault/logs.php${ownerParam}`);
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Unable to load logs.');
        box.innerHTML = '';
        (data.data.logs || []).slice(0, 8).forEach(log => {
            const row = document.createElement('div');
            row.textContent = `${log.created_at} - ${log.action}`;
            box.appendChild(row);
        });
        if (!box.children.length) box.textContent = 'No audit logs yet.';
    } catch (error) {
        box.textContent = 'Unable to load logs.';
    }
}

async function vaultReauth(event) {
    event.preventDefault();
    const password = document.getElementById('vault-password').value;
    const data = await vaultJson('reauth', { password });
    if (data.success) { document.getElementById('vault-password').value = ''; vaultStatus('Vault unlocked.', 'success'); loadVault(); }
    else vaultStatus(data.message || 'Unable to unlock vault', 'danger');
}

async function vaultUpload(event) {
    event.preventDefault();
    const fileInput = document.getElementById('vault-file');
    
    const uploadButton = document.querySelector('#vault-upload-form button[type="submit"]');
    const file = fileInput?.files?.[0];
    if (!file) { vaultStatus('Choose a file first.', 'danger'); return; }
    if (!vaultIsVerified) { vaultStatus('Unlock Vault first.', 'warning'); return; }

    const displayName = file.name;
    const form = new FormData();
    form.append('csrf_token', csrfToken || '');
    form.append('folder_id', '0');
    form.append('display_name', file.name);
    form.append('file', file, file.name);

    if (uploadButton) { uploadButton.disabled = true; uploadButton.textContent = 'Uploading...'; }
    vaultStatus('Uploading document...', 'info');
    try {
        const response = await fetch('/backend/vault/upload.php', {
            method: 'POST',
            body: form,
            headers: { 'X-CSRF-Token': csrfToken || '' }
        });
        const raw = await response.text();
        let data;
        try { const first=raw.indexOf('{'); const last=raw.lastIndexOf('}'); data=JSON.parse(first>=0&&last>first?raw.slice(first,last+1):raw); } catch (_) { throw new Error(`Server returned invalid response (${response.status}).`); }
        if (!response.ok || !data.success) throw new Error(data.message || `Upload failed (${response.status}).`);
        document.getElementById('vault-upload-form').reset();
        vaultStatus(`Uploaded: ${displayName}`, 'success');
        await loadVault();
    } catch (error) {
        console.error('Vault upload failed:', error);
        vaultStatus(error.message || 'Upload failed.', 'danger');
    } finally {
        if (uploadButton) { uploadButton.disabled = false; uploadButton.textContent = 'Upload'; }
    }
}
async function vaultCreateFolder() {
    if (!vaultIsVerified) return vaultStatus('Unlock Vault first.', 'warning');
    const input=document.getElementById('vault-folder-name'), button=document.getElementById('vault-folder-create'), name=input.value.trim();
    if (!name) return vaultStatus('Enter a folder name first.', 'warning');
    button.disabled=true;button.textContent='Creating...';
    try { const data=await vaultJson('folders',{action:'create',name,parent_folder_id:vaultCurrentFolderId}); if(data.success){input.value='';vaultStatus(`Folder created: ${name}`,'success');await loadVault();}else vaultStatus(data.errors?.name||data.errors?.parent_folder_id||data.message||'Unable to create folder','danger'); }
    finally { button.disabled=!vaultIsVerified;button.textContent='+ Folder'; }
}
async function vaultDeleteFolder(folderId) {
    if (!vaultIsVerified) return vaultStatus('Unlock Vault first.', 'warning');
    if (!confirm('Delete empty vault folder?')) return;
    const data = await vaultJson('folders', { action: 'delete', folder_id: folderId });
    if (data.success) { if (vaultCurrentFolderId === folderId) vaultCurrentFolderId = 0; vaultStatus('Vault folder deleted.', 'success'); loadVault(); }
    else vaultStatus(data.errors?.folder || data.message || 'Cannot delete this folder. Remove its documents and subfolders first.', 'danger');
}

async function vaultDownload(doc) {
    if (!vaultIsVerified) return vaultStatus('Unlock Vault first.', 'warning');
    try {
        let data = await vaultJson('request_download_code', { document_id: doc.id });
        if (!data.success) throw new Error(data.message || 'Unable to send verification code.');
        const code = prompt('Enter the 6-digit code sent to your account email:');
        if (!code) return;
        data = await vaultJson('verify_download', { document_id: doc.id, code: code.trim() });
        if (!data.success) throw new Error(data.message || 'Invalid verification code.');
        const link = document.createElement('a'); link.href = `/backend/vault/download.php?document_id=${doc.id}&download_token=${encodeURIComponent(data.data.download_token)}`; link.download = doc.original_filename || doc.display_name; document.body.appendChild(link); link.click(); link.remove(); vaultStatus('Secure download started.', 'success');
    } catch (error) { vaultStatus(error.message || 'Secure download failed.', 'danger'); }
}
async function vaultRename(doc) {
    if (!vaultIsVerified) return vaultStatus('Unlock Vault first.', 'warning');
    const name = prompt('New vault document name', doc.display_name);
    if (name === null) return;
    const cleanName = name.trim();
    if (!cleanName) return vaultStatus('Document name is required.', 'danger');
    const data = await vaultJson('update', { document_id: doc.id, display_name: cleanName, folder_id: doc.folder_id || 0 });
    if (data.success) { doc.display_name = data.data?.display_name || cleanName; vaultStatus(data.message || 'Vault document renamed.', 'success'); loadVault(); } else vaultStatus(data.message || 'Unable to rename document', 'danger');
}

async function vaultDeleteDocument(id) {
    if (!vaultIsVerified) return vaultStatus('Unlock Vault first.', 'warning');
    if (!confirm('Delete this vault document?')) return;
    const data = await vaultJson('delete', { document_id: id });
    if (data.success) { vaultStatus('Vault document deleted.', 'success'); loadVault(); } else vaultStatus(data.message || 'Unable to delete document', 'danger');
}

async function vaultPermission(action) {
    if (!vaultIsVerified) return vaultStatus('Unlock Vault first.', 'warning');
    const userValue = document.getElementById('vault-counsel-user-id').value.trim();
    if (!userValue) return vaultStatus('Enter legal counsel ID, username, or name.', 'danger');
    const data = await vaultJson('permissions', { action, user_identifier: userValue });
    if (data.success) { vaultStatus(data.message || 'Permission updated.', 'success'); loadVault(); }
    else vaultStatus(data.message || 'Unable to update permission', 'danger');
}

document.addEventListener('DOMContentLoaded', () => setTimeout(initVaultFeature, 500));

const AUTOMATIONS_API = '/backend/automations';
let automationsCache = [];
let automationsPagination = null;
function automationEl(tag, cls='', text=''){const el=document.createElement(tag); if(cls)el.className=cls; if(text)el.textContent=text; return el;}
function automationUtcFromLocal(value){return value ? new Date(value).toISOString() : '';}
function automationLocalInputFromUtc(value){if(!value)return ''; const d=new Date(String(value).replace(' ','T')+'Z'); const pad=n=>String(n).padStart(2,'0'); return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;}
function automationDisplayDate(value){return value ? new Date(String(value).replace(' ','T')+'Z').toLocaleString() : 'none';}
function toggleAutomationFields(){const t=document.getElementById('automation-trigger')?.value; document.querySelectorAll('.automation-datetime').forEach(e=>e.classList.toggle('d-none', !['specific_datetime','linked_milestone_event'].includes(t))); document.querySelectorAll('.automation-recurring').forEach(e=>e.classList.toggle('d-none', !['birthday','anniversary','custom_recurring'].includes(t))); document.querySelectorAll('.automation-linked').forEach(e=>e.classList.toggle('d-none', t!=='linked_milestone_event'));}
async function automationJson(endpoint,payload){const res=await fetch(`${AUTOMATIONS_API}/${endpoint}.php`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrfToken},body:JSON.stringify({...payload,csrf_token:csrfToken})});const text=await res.text();if(!text.trim())throw new Error(`Automation endpoint returned an empty response (HTTP ${res.status}). Check the server error log.`);try{return JSON.parse(text);}catch(e){throw new Error(`Automation endpoint returned invalid JSON (HTTP ${res.status}).`);}}
async function loadAutomations(page=automationsPage){automationsPage=Math.max(1,Number(page)||1);const box=document.getElementById('automations-container'); if(!box||!currentUserId)return; box.innerHTML='<div class="text-muted">Loading automations...</div>'; try{const res=await fetch(`${AUTOMATIONS_API}/list.php?page=${automationsPage}&limit=${DASHBOARD_PAGE_SIZE}`); const data=await res.json(); if(!data.success)throw new Error(data.message||'Unable to load automations'); automationsCache=data.data.automations||[];automationsPagination=data.data.pagination||null;renderAutomations(automationsCache);}catch(e){box.innerHTML=`<div class="alert alert-danger">${escapeHtml(e.message)}</div>`;}}
function renderAutomations(rows){
    const box=document.getElementById('automations-container');
    box.innerHTML='';
    if(!rows.length){box.innerHTML='<div class="text-muted">No automations yet.</div>';return;}
    rows.forEach(a=>{
        const card=automationEl('div','card mb-2');
        const body=automationEl('div','card-body');
        const actions=(a.actions||[]).map(x=>String(x.action_type||'').replace('_',' ')).join(', ');
        const runLabel=a.status==='completed'?'completed':automationDisplayDate(a.next_run_at);
        const trigger=String(a.trigger_type||'').replace('_',' ');
        body.innerHTML=`<div class="d-flex justify-content-between gap-2"><div><h6 class="mb-1">${escapeHtml(a.title)}</h6><div class="small text-muted">${escapeHtml(trigger)} | ${escapeHtml(runLabel)} | actions: ${escapeHtml(actions)}</div>${a.last_error?`<div class="small text-danger">${escapeHtml(a.last_error)}</div>`:''}</div><div class="text-end"><span class="badge bg-secondary">${escapeHtml(a.status)}</span><div class="mt-2 btn-group btn-group-sm"><button class="btn btn-outline-primary" onclick="openAutomationModal(${a.id})">Edit</button><button class="btn btn-outline-danger" onclick="setAutomationStatus(${a.id},'cancel')">Cancel</button><button class="btn btn-outline-success" onclick="setAutomationStatus(${a.id},'enable')">Enable</button></div></div></div>`;
        card.appendChild(body);
        box.appendChild(card);
    });
    if(automationsPagination&&automationsPagination.total_pages>1)appendDashboardPager(box,automationsPage,automationsPagination.total_items,nextPage=>loadAutomations(nextPage));
}
function openAutomationModal(id=null){const a=id?automationsCache.find(x=>Number(x.id)===Number(id)):null; document.getElementById('automation-id').value=a?.id||''; document.getElementById('automation-title').value=a?.title||''; document.getElementById('automation-description').value=a?.description||''; document.getElementById('automation-status').value=['draft','scheduled'].includes(a?.status)?a.status:'scheduled'; document.getElementById('automation-trigger').value=a?.trigger_type||'specific_datetime'; document.getElementById('automation-datetime').value=a?.trigger_datetime?automationLocalInputFromUtc(a.trigger_datetime):automationLocalInputFromUtc(a?.next_run_at); document.getElementById('automation-month').value=a?.recurring_month||''; document.getElementById('automation-day').value=a?.recurring_day||''; document.getElementById('automation-linked-type').value=a?.linked_resource_type||'event'; document.getElementById('automation-linked-id').value=a?.linked_resource_id||''; document.querySelectorAll('.automation-action').forEach(ch=>{ch.checked=!a ? ch.value==='notification' : (a.actions||[]).some(x=>x.action_type===ch.value);}); document.getElementById('automation-error').textContent=''; toggleAutomationFields(); bootstrap.Modal.getOrCreateInstance(document.getElementById('automationModal')).show();}
function collectAutomationPayload(){const actions=[...document.querySelectorAll('.automation-action:checked')].map(ch=>({action_type:ch.value,payload: ch.value==='wall_post'?{body:document.getElementById('automation-description').value,privacy_level:'private'}:{message:document.getElementById('automation-description').value}})); return {automation_id:Number(document.getElementById('automation-id').value||0),title:document.getElementById('automation-title').value.trim(),description:document.getElementById('automation-description').value,trigger_type:document.getElementById('automation-trigger').value,trigger_datetime:automationUtcFromLocal(document.getElementById('automation-datetime').value),recurring_month:Number(document.getElementById('automation-month').value||0),recurring_day:Number(document.getElementById('automation-day').value||0),linked_resource_type:document.getElementById('automation-linked-type').value,linked_resource_id:Number(document.getElementById('automation-linked-id').value||0),status:document.getElementById('automation-status').value,actions};}
async function submitAutomation(e){e.preventDefault(); const err=document.getElementById('automation-error'); const save=document.getElementById('automation-save'); err.textContent=''; save.disabled=true; try{const payload=collectAutomationPayload(); const endpoint=payload.automation_id?'update':'create'; const data=await automationJson(endpoint,payload); if(!data.success)throw new Error(data.message||'Unable to save automation'); bootstrap.Modal.getInstance(document.getElementById('automationModal')).hide(); showAlert(data.message||'Automation saved','success'); loadAutomations();}catch(ex){err.textContent=ex.message;}finally{save.disabled=false;}}
async function setAutomationStatus(id,action){const data=await automationJson('cancel',{automation_id:id,action}); showAlert(data.message||'Done',data.success?'success':'danger'); if(data.success)loadAutomations();}
document.addEventListener('DOMContentLoaded',()=>{document.getElementById('automation-trigger')?.addEventListener('change',toggleAutomationFields);document.getElementById('automationForm')?.addEventListener('submit',submitAutomation);});
