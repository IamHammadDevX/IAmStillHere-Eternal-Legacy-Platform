const urlParams = new URLSearchParams(window.location.search);
const profileUserId = urlParams.get('user_id');
let currentUser = null;

async function init() {
    const sessionResponse = await fetch('/backend/auth/check_session.php');
    const sessionData = await sessionResponse.json();
    
    if (sessionData.logged_in) {
        currentUser = sessionData.user;
        document.getElementById('user-name').textContent = currentUser.full_name;
        document.getElementById('logout-btn').style.display = 'inline-block';
    }
    
    if (!profileUserId) {
        if (sessionData.logged_in) {
            window.location.href = 'profile.php?user_id=' + currentUser.id;
        } else {
            window.location.href = 'memorials.php';
        }
        return;
    }
    
    loadProfile();
}

async function loadProfile() {
    try {
        const response = await fetch(`/backend/users/profile.php?user_id=${profileUserId}`);
        const data = await response.json();
        
        if (data.success) {
            const profile = data.profile;
            
            document.getElementById('profile-name').textContent = profile.full_name;
            
            if (profile.profile_photo) {
                document.getElementById('profile-image').src = '/data/uploads/photos/' + profile.profile_photo;
            }
            
            if (profile.cover_photo) {
                document.getElementById('cover-image').src = '/data/uploads/photos/' + profile.cover_photo;
                document.getElementById('cover-image').style.display = 'block';
            }
            
            if (profile.bio) {
                document.getElementById('profile-bio').textContent = profile.bio;
            }
            
            let dates = [];
            if (profile.date_of_birth) {
                dates.push('Born: ' + new Date(profile.date_of_birth).toLocaleDateString());
            }
            if (profile.date_of_passing) {
                dates.push('Passed: ' + new Date(profile.date_of_passing).toLocaleDateString());
            }
            document.getElementById('profile-dates').textContent = dates.join(' | ');
            
            if (currentUser && currentUser.id == profileUserId) {
                document.getElementById('edit-profile-btn').style.display = 'block';
                document.getElementById('memorial-settings-btn').style.display = 'block';
                
                document.getElementById('bio-input').value = profile.bio || '';
                document.getElementById('dob-input').value = profile.date_of_birth || '';
                document.getElementById('is-memorial-input').value = profile.is_memorial ? '1' : '0';
                document.getElementById('dop-input').value = profile.date_of_passing || '';
                
                document.getElementById('memorial-status').textContent = profile.is_memorial ? 
                    'Memorial mode is active' : 'Memorial mode is inactive';
            } else {
                document.getElementById('tribute-form').style.display = 'block';
            }
            
            loadTimeline();
            loadMemories();
            loadTributes();
        }
    } catch (error) {
        console.error('Error loading profile:', error);
    }
}

async function loadTimeline() {
    try {
        const response = await fetch(`/backend/milestones/list.php?user_id=${profileUserId}`);
        const data = await response.json();
        
        const container = document.getElementById('timeline-container');
        
        if (data.success && data.milestones.length > 0) {
            container.innerHTML = '';
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
                        </div>
                    </div>
                `;
                container.appendChild(item);
            });
        } else {
            container.innerHTML = '<p class="text-muted">No timeline events yet.</p>';
        }
    } catch (error) {
        console.error('Error loading timeline:', error);
    }
}

async function loadMemories() {
    try {
        const response = await fetch(`/backend/memories/list.php?user_id=${profileUserId}`);
        const data = await response.json();
        
        const grid = document.getElementById('memories-grid');
        
        if (data.success && data.memories.length > 0) {
            grid.innerHTML = '';
            data.memories.forEach(memory => {
                const col = document.createElement('div');
                col.className = 'col-md-6 mb-3';
                
                let mediaHtml = '';
                if (memory.file_type.includes('image')) {
                    mediaHtml = `<img src="/data/uploads/photos/${memory.file_path}" alt="${memory.title}" style="width: 100%; height: 200px; object-fit: cover; border-radius: 10px;">`;
                } else if (memory.file_type.includes('video')) {
                    mediaHtml = `<video controls style="width: 100%; height: 200px; border-radius: 10px;"><source src="/data/uploads/videos/${memory.file_path}"></video>`;
                } else {
                    mediaHtml = `<div class="p-4 text-center bg-light rounded"><i class="bi bi-file-earmark display-1"></i></div>`;
                }
                
                col.innerHTML = `
                    <div class="card">
                        ${mediaHtml}
                        <div class="card-body">
                            <h6>${memory.title}</h6>
                            <p class="small text-muted">${memory.description || ''}</p>
                        </div>
                    </div>
                `;
                grid.appendChild(col);
            });
        } else {
            grid.innerHTML = '<p class="text-muted">No memories shared yet.</p>';
        }
    } catch (error) {
        console.error('Error loading memories:', error);
    }
}

async function loadTributes() {
    try {
        const response = await fetch(`/backend/tributes/list.php?memorial_user_id=${profileUserId}`);
        const data = await response.json();
        
        const container = document.getElementById('tributes-container');
        
        if (data.success && data.tributes.length > 0) {
            container.innerHTML = '';
            data.tributes.forEach(tribute => {
                const div = document.createElement('div');
                div.className = 'tribute-card';
                div.innerHTML = `
                    <strong>${tribute.author_name}</strong>
                    <p class="mb-1">${tribute.message}</p>
                    <small class="text-muted">${new Date(tribute.created_at).toLocaleDateString()}</small>
                `;
                container.appendChild(div);
            });
        } else {
            container.innerHTML = '<p class="text-muted">No tributes yet.</p>';
        }
    } catch (error) {
        console.error('Error loading tributes:', error);
    }
}

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
        const response = await fetch('/backend/users/update_profile.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('Profile updated successfully!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('editProfileModal')).hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message, 'danger');
        }
    } catch (error) {
        showAlert('Failed to update profile', 'danger');
    }
});

document.getElementById('memorialSettingsForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const settingsData = {
        is_memorial: document.getElementById('is-memorial-input').value === '1',
        date_of_passing: document.getElementById('dop-input').value
    };
    
    try {
        const response = await fetch('/backend/users/memorial_settings.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
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
        showAlert('Failed to save settings', 'danger');
    }
});

document.getElementById('tributeForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const tributeData = {
        memorial_user_id: profileUserId,
        author_name: document.getElementById('tribute-name').value,
        author_email: document.getElementById('tribute-email').value,
        message: document.getElementById('tribute-message').value
    };
    
    try {
        const response = await fetch('/backend/tributes/create.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(tributeData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('Tribute posted successfully!', 'success');
            document.getElementById('tributeForm').reset();
            loadTributes();
        } else {
            showAlert(data.message, 'danger');
        }
    } catch (error) {
        showAlert('Failed to post tribute', 'danger');
    }
});

document.addEventListener('DOMContentLoaded', init);
