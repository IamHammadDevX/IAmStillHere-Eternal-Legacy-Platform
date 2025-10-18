const urlParams = new URLSearchParams(window.location.search);
const profileUserId = urlParams.get('user_id');
let currentUser = null;

document.addEventListener('DOMContentLoaded', init);

async function init() {
    try {
        const sessionResponse = await fetch('http://localhost/IAmStillHere/backend/auth/check_session.php');
        const sessionData = await sessionResponse.json();

        if (sessionData.logged_in) {
            currentUser = sessionData.user;
            document.getElementById('username-display').textContent = currentUser.full_name;
            document.getElementById('nav-logout').style.display = 'inline-block';
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

async function loadProfile() {
    try {
        const response = await fetch(`http://localhost/IAmStillHere/backend/users/profile.php?user_id=${profileUserId}`);
        const data = await response.json();

        if (!data.success) {
            console.error('Profile not found:', data.message);
            return;
        }

        const profile = data.profile;

        document.getElementById('profile-name').textContent = profile.full_name || "Unknown";
        document.getElementById('profile-bio').textContent = profile.bio || "No bio available.";

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
            alert('✅ Tribute posted successfully!');
            e.target.reset();
            // Optionally refresh tribute list dynamically
            loadTributes();
        } else {
            alert(`❌ ${data.message || 'Failed to post tribute.'}`);
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

// ---------- Load Memories ----------
async function loadMemories() {
    try {
        const response = await fetch(`http://localhost/IAmStillHere/backend/memories/list.php?user_id=${profileUserId}`);
        const data = await response.json();
        const grid = document.getElementById('memories-grid');

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

// ---------- Alert Helper ----------
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} position-fixed bottom-0 end-0 m-3`;
    alertDiv.style.zIndex = 1050;
    alertDiv.textContent = message;
    document.body.appendChild(alertDiv);
    setTimeout(() => alertDiv.remove(), 3000);
}
