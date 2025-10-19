let currentUserId = null;

async function init() {
    const response = await fetch('http://localhost/IAmStillHere/backend/auth/check_session.php');
    const data = await response.json();

    if (!data.logged_in) {
        window.location.href = 'login.php';
        return;
    }

    currentUserId = data.user.id;

    loadMemories();
    loadTimeline();
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

async function loadTimeline() {
    try {
        const response = await fetch(`http://localhost/IAmStillHere/backend/milestones/list.php?user_id=${currentUserId}`);
        const data = await response.json();

        const container = document.getElementById('timeline-container');
        container.innerHTML = '';

        if (data.success && data.milestones.length > 0) {
            data.milestones.forEach(milestone => {
                const item = document.createElement('div');
                item.className = 'timeline-item';
                item.innerHTML = `
                    <div class="card">
                        <div class="card-body">
                            <h5>${milestone.title}</h5>
                            <p class="text-muted mb-2">${new Date(milestone.milestone_date).toLocaleDateString()}</p>
                            ${milestone.category ? `<span class="badge bg-info">${milestone.category}</span>` : ''}
                            <p class="mt-2">${milestone.description || ''}</p>
                            <small class="text-muted">
                                <span class="badge bg-secondary privacy-badge">${milestone.privacy_level}</span>
                            </small>
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
