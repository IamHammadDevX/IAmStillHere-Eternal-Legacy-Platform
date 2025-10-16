let currentUserId = null;

async function init() {
    const response = await fetch('http://localhost/IAmStillHere/backend/auth/check_session.php');
    const data = await response.json();
    
    if (!data.logged_in) {
        window.location.href = 'login.php';
        return;
    }
    
    currentUserId = data.user.id;
    document.getElementById('user-name').textContent = data.user.full_name;
    
    loadMemories();
    loadTimeline();
}

async function loadMemories() {
    try {
        const response = await fetch(`http://localhost/IAmStillHere/backend/memories/list.php?user_id=${currentUserId}`);
        const data = await response.json();
        
        const grid = document.getElementById('memories-grid');
        grid.innerHTML = '';
        
        if (data.success && data.memories.length > 0) {
            data.memories.forEach(memory => {
                const col = document.createElement('div');
                col.className = 'col-md-4 mb-4';
                
                let mediaHtml = '';
                if (memory.file_type.includes('image')) {
                    mediaHtml = `<img src="http://localhost/IAmStillHere/data/uploads/photos/${memory.file_path}" alt="${memory.title}" style="width: 100%; height: 200px; object-fit: cover; border-radius: 10px;">`;
                } else if (memory.file_type.includes('video')) {
                    mediaHtml = `<video controls><source src="/data/uploads/videos/${memory.file_path}" type="${memory.file_type}"></video>`;
                } else {
                    mediaHtml = `<div class="p-4 text-center"><i class="bi bi-file-earmark display-1"></i></div>`;
                }
                
                col.innerHTML = `
                    <div class="card memory-card">
                        ${mediaHtml}
                        <div class="card-body">
                            <h5 class="card-title">${memory.title}</h5>
                            <p class="card-text">${memory.description || ''}</p>
                            <small class="text-muted">
                                <span class="badge bg-secondary privacy-badge">${memory.privacy_level}</span>
                                ${memory.memory_date ? new Date(memory.memory_date).toLocaleDateString() : ''}
                            </small>
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
            headers: {'Content-Type': 'application/json'},
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
            headers: {'Content-Type': 'application/json'},
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
