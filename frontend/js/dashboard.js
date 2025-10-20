let currentUserId = null;
let loggedInUser = null;

async function init() {
    const response = await fetch('http://localhost/IAmStillHere/backend/auth/check_session.php');
    const data = await response.json();

    if (!data.logged_in) {
        window.location.href = 'login.php';
        return;
    }
    loggedInUser = data.user;

    currentUserId = data.user.id;

    loadMemories();
    loadTimeline();
    loadEvents();
    loadRequestCount();
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
        const response = await fetch(`http://localhost/IAmStillHere/backend/memories/list.php?user_id=${currentUserId}`);
        const data = await response.json();

        const grid = document.getElementById('memories-grid');
        grid.innerHTML = '';

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
                    filePath = `http://localhost/IAmStillHere/data/uploads/videos/${memory.file_path}`;
                    downloadButton = `<a href="${filePath}" download="${memory.title}" class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i> Download</a>`;

                    mediaHtml = `
                        <div style="width: 100%; height: 200px; border-radius: 10px; overflow: hidden;">
                            <video controls style="width: 100%; height: 200px; border-radius: 10px;"><source src="http://localhost/IAmStillHere/data/uploads/videos/${memory.file_path}">
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
                            <h5 class="card-title">${memory.title}</h5>
                            <p class="card-text">${memory.description || ''}</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <span class="badge bg-secondary privacy-badge">${memory.privacy_level}</span>
                                    ${memory.memory_date ? new Date(memory.memory_date).toLocaleDateString() : ''}
                                </small>
                                ${downloadButton}
                                ${canDelete ? `<button class="btn btn-sm btn-outline-danger ms-1" onclick="deleteMemory(${memory.id})">
                                    <i class="bi bi-trash"></i>
                                </button>` : ''}
                                
                            </div>
                        </div>
                    </div>
                `;
                grid.appendChild(col);
            });
        } else {
            grid.innerHTML = '<div class="col-12"><p class="text-muted text-center">No memories yet. Upload your first memory!</p></div>';
        }
    } catch (error) {
        console.error('Error loading memories:', error);
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
    formData.append('privacy_level', document.getElementById('memory-privacy').value);
    formData.append('file', document.getElementById('memory-file').files[0]);

    try {
        const response = await fetch('http://localhost/IAmStillHere/backend/memories/upload.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
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
        privacy_level: document.getElementById('milestone-privacy').value
    };

    try {
        const response = await fetch('http://localhost/IAmStillHere/backend/milestones/create.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(milestoneData)
        });

        const data = await response.json();

        if (data.success) {
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
