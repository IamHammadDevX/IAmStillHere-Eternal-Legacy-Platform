const urlParams = new URLSearchParams(window.location.search);
const profileUserId = urlParams.get('user_id');
let profileMemoriesCache = [];
let profileMemoryFoldersCache = [];
const PROFILE_MEDIA_PAGE_SIZE = 10;
const profileMediaState = { memories: { page: 1, folderId: 0 }, photos: { page: 1, folderId: 0 }, videos: { page: 1, folderId: 0 } };
let profileMemoryPrivacyWidget = null;
let currentUser = null;
let csrfToken = null;

document.addEventListener('DOMContentLoaded', init);

async function init() {
    try {
        const sessionResponse = await fetch('/backend/auth/check_session.php');
        const sessionData = await sessionResponse.json();

        if (sessionData.logged_in) {
            currentUser = sessionData.user;
            window.dispatchEvent(new CustomEvent('profile-session-ready'));
            document.getElementById('username-display').textContent = 'Public Profile';
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

async function loadProfile() {
    try {
        const response = await fetch(`/backend/users/profile.php?user_id=${profileUserId}`);
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
            profileImg.src = '/frontend/images/default-profile.png';
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
    const form = e.currentTarget;
    const saveButton = document.getElementById('profile-save-btn') || form.querySelector('button[type="submit"]');
    const status = document.getElementById('profile-save-status');
    const originalLabel = saveButton?.textContent || 'Save Changes';
    if (saveButton?.disabled) return;

    const setStatus = (message, type = 'muted') => {
        if (status) {
            status.className = `small mt-2 text-${type}`;
            status.textContent = message;
        }
    };

    if (saveButton) {
        saveButton.disabled = true;
        saveButton.textContent = 'Saving...';
    }
    setStatus('Saving changes...', 'muted');

    const formData = new FormData();
    const profilePhoto = document.getElementById('profile-photo-upload')?.files?.[0];
    const coverPhoto = document.getElementById('cover-photo-upload')?.files?.[0];
    if (profilePhoto) formData.append('profile_photo', profilePhoto);
    if (coverPhoto) formData.append('cover_photo', coverPhoto);
    formData.append('bio', document.getElementById('bio-input')?.value || '');
    formData.append('date_of_birth', document.getElementById('dob-input')?.value || '');

    try {
        const response = await fetch('/backend/users/update_profile.php', { method: 'POST', body: formData });
        const raw = await response.text();
        let data;
        try { data = JSON.parse(raw); } catch (_) { throw new Error(`Server returned invalid response (${response.status}).`); }
        if (!response.ok || !data.success) throw new Error(data.message || `Save failed (${response.status}).`);

        showAlert(data.message || 'Profile updated successfully!', 'success');
        setStatus('Saved successfully.', 'success');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('editProfileModal')).hide();
        if (data.user?.profile_photo) document.getElementById('profile-image').src = `${data.user.profile_photo}?v=${Date.now()}`;
        if (data.user?.cover_photo) { const coverImg = document.getElementById('cover-image'); coverImg.src = `${data.user.cover_photo}?v=${Date.now()}`; coverImg.style.display = 'block'; }
        const bio = document.getElementById('profile-bio');
        if (bio) bio.textContent = data.user?.bio || 'No bio available.';
        const about = document.getElementById('profile-about-tab-bio');
        if (about) about.textContent = data.user?.bio || 'No bio available.';
    } catch (error) {
        console.error('Error updating profile:', error);
        setStatus(error.message || 'Failed to update profile.', 'danger');
        showAlert(error.message || 'Failed to update profile.', 'danger');
    } finally {
        if (saveButton) { saveButton.disabled = false; saveButton.textContent = originalLabel; }
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
        const response = await fetch('/backend/users/memorial_settings.php', {
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
        const response = await fetch('/backend/tributes/create.php', {
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


let timelinePersonalItems = [];
let timelineWorldItems = [];
let timelineWorldReady = false;
function timelineYear(value){const match=String(value||'').match(/\d{4}/);return match?Number(match[0]):null;}
function renderWorldEventsTimeline(){const box=document.getElementById('timeline-container');if(!box)return;const view=document.querySelector('[data-timeline-view].active')?.dataset.timelineView||'combined';box.querySelectorAll('.world-timeline-item,.world-events-empty').forEach(e=>e.remove());if(view==='personal')return;if(!timelineWorldReady)return;const events=timelineWorldItems;if(!events.length&&view==='world'){const empty=document.createElement('p');empty.className='world-events-empty text-muted text-center py-4';empty.textContent='No world events match the dates in this timeline.';box.appendChild(empty);return;}events.forEach(e=>{const item=document.createElement('div');item.className='timeline-item world-timeline-item';item.dataset.timelineYear=e.year||0;item.innerHTML=`<div class="timeline-marker world-event-marker"></div><div class="timeline-content world-event-content"><span class="badge bg-info-subtle text-info mb-1">World event</span><h5 class="mb-1">${escapeHtml(e.title)}</h5><small class="text-muted"><i class="bi bi-calendar"></i> ${escapeHtml(e.date||String(e.year))}</small><p class="text-muted mb-0">${escapeHtml(e.description||'')}</p></div>`;box.appendChild(item);});[...box.children].filter(item=>item.classList.contains('timeline-item')).sort((a,b)=>(Number(a.dataset.timelineYear)||0)-(Number(b.dataset.timelineYear)||0)).forEach(item=>box.appendChild(item));}

async function loadWorldEventsForTimeline(milestones){const years=[...new Set((milestones||[]).map(m=>timelineYear(m.milestone_date)).filter(Boolean))];timelineWorldReady=false;const status=document.getElementById('world-events-status');if(status)status.textContent='Loading world events...';try{const r=await fetch(`/backend/world_events/list.php?years=${years.join(',')}`);const d=await r.json();if(!d.success)throw Error(d.message||'Unable to load world events.');timelineWorldItems=d.data.events||[];timelineWorldReady=true;if(status)status.textContent=timelineWorldItems.length?'World events are informational and matched by year.':'No matching world events for these years.';renderWorldEventsTimeline();}catch(e){timelineWorldReady=false;if(status)status.textContent='World events are temporarily unavailable.';}}
function initTimelineWorldToggle(){document.querySelectorAll('[data-timeline-view]').forEach(b=>b.addEventListener('click',()=>{document.querySelectorAll('[data-timeline-view]').forEach(x=>x.classList.toggle('active',x===b));const view=b.dataset.timelineView;document.getElementById('timeline-container')?.classList.toggle('timeline-world-only',view==='world');if(view==='world'){document.getElementById('timeline-container').querySelectorAll('.timeline-item:not(.world-timeline-item)').forEach(e=>e.classList.add('d-none'));}else{document.getElementById('timeline-container').querySelectorAll('.timeline-item:not(.world-timeline-item)').forEach(e=>e.classList.remove('d-none'));}renderWorldEventsTimeline();}));}
document.addEventListener('DOMContentLoaded',initTimelineWorldToggle);


// ---------- About life journal ----------
let aboutJournalPage = 1;
const ABOUT_JOURNAL_PAGE_SIZE = 10;

function initAboutLifeJournal() {
    const tab = document.querySelector('[data-bs-toggle="tab"][href="#about-tab"]');
    tab?.addEventListener('shown.bs.tab', () => loadAboutLifeJournal(aboutJournalPage));
    if (document.querySelector('#about-tab.active')) loadAboutLifeJournal(aboutJournalPage);
}

function aboutJournalIcon(type) {
    return ({memory: 'bi-images', milestone: 'bi-award', event: 'bi-calendar-event'})[type] || 'bi-bookmark-star';
}

function aboutJournalLabel(type) {
    return ({memory: 'Memory', milestone: 'Milestone', event: 'Event'})[type] || 'Life update';
}

function aboutJournalDate(value) {
    const date = value ? new Date(value) : null;
    return date && !Number.isNaN(date.getTime()) ? date.toLocaleDateString(undefined, {year: 'numeric', month: 'short', day: 'numeric'}) : 'Date unavailable';
}

async function loadAboutLifeJournal(page = 1) {
    const list = document.getElementById('about-life-journal');
    const pager = document.getElementById('about-life-journal-pagination');
    if (!list || !profileUserId) return;
    list.innerHTML = '<div class="about-journal-loading"><span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Loading life highlights?</div>';
    if (pager) pager.innerHTML = '';
    try {
        const response = await fetch(`/backend/about/list.php?user_id=${encodeURIComponent(profileUserId)}&page=${page}&limit=${ABOUT_JOURNAL_PAGE_SIZE}`, {cache: 'no-store'});
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Unable to load life highlights.');
        const result = data.data || {};
        const items = Array.isArray(result.items) ? result.items : [];
        const pagination = result.pagination || {};
        aboutJournalPage = Number(pagination.current_page) || 1;
        if (!items.length) {
            list.innerHTML = `<div class="about-journal-empty"><i class="bi bi-journal-text"></i><div><strong>No life highlights to show yet.</strong><span>Memories, milestones, and events shared with you will appear here.</span></div></div>`;
            return;
        }
        list.innerHTML = items.map(item => `<article class="about-journal-card about-journal-${escapeHtml(item.type)}">
            <div class="about-journal-icon"><i class="bi ${aboutJournalIcon(item.type)}"></i></div>
            <div class="about-journal-content"><div class="about-journal-meta"><span>${aboutJournalLabel(item.type)}</span><time>${aboutJournalDate(item.item_date)}</time></div><h6>${escapeHtml(item.title || 'Untitled')}</h6><p>${escapeHtml(item.description || 'No description was added.')}</p></div>
            <button type="button" class="about-journal-open" data-about-type="${escapeHtml(item.type)}" data-about-id="${Number(item.id)}" aria-label="Open ${aboutJournalLabel(item.type)}"><i class="bi bi-arrow-up-right"></i></button>
        </article>`).join('');
        list.querySelectorAll('[data-about-type]').forEach(button => button.addEventListener('click', () => openAboutItem(button.dataset.aboutType, Number(button.dataset.aboutId))));
        renderAboutJournalPagination(pagination);
    } catch (error) {
        list.innerHTML = `<div class="about-journal-empty about-journal-error"><i class="bi bi-exclamation-circle"></i><div><strong>Life highlights could not load.</strong><span>${escapeHtml(error.message || 'Please try again.')}</span></div></div>`;
    }
}

function renderAboutJournalPagination(pagination) {
    const pager = document.getElementById('about-life-journal-pagination');
    if (!pager) return;
    const current = Number(pagination.current_page) || 1;
    const pages = Number(pagination.total_pages) || 1;
    const total = Number(pagination.total_items) || 0;
    if (pages <= 1) { pager.innerHTML = total ? `<span>${total} highlight${total === 1 ? '' : 's'}</span>` : ''; return; }
    pager.innerHTML = `<button type="button" class="btn btn-outline-secondary btn-sm" data-about-page="${current - 1}" ${current === 1 ? 'disabled' : ''}>? Previous</button><span>Showing page ${current} of ${pages}</span><button type="button" class="btn btn-outline-secondary btn-sm" data-about-page="${current + 1}" ${current === pages ? 'disabled' : ''}>Next ?</button>`;
    pager.querySelectorAll('[data-about-page]').forEach(button => button.addEventListener('click', () => {
        loadAboutLifeJournal(Number(button.dataset.aboutPage));
        document.getElementById('about-life-journal')?.scrollIntoView({behavior: 'smooth', block: 'start'});
    }));
}

function openAboutItem(type, id) {
    const targets = {memory: '#memories-tab', milestone: '#timeline-tab', event: '#events-tab'};
    const tabId = targets[type];
    if (!tabId || !id) return;
    const tab = document.querySelector(`[data-bs-toggle="tab"][href="${tabId}"]`);
    bootstrap.Tab.getOrCreateInstance(tab).show();
    const selector = type === 'memory' ? `#memory-card-${id}` : type === 'milestone' ? `#milestone-${id}` : `#event-${id}`;
    setTimeout(() => {
        const item = document.querySelector(selector);
        if (item) { item.classList.add('about-item-focus'); item.scrollIntoView({behavior: 'smooth', block: 'center'}); setTimeout(() => item.classList.remove('about-item-focus'), 1800); }
        else if (type === 'memory') { const url = new URL(window.location.href); url.searchParams.set('focus_memory', id); url.hash = 'memories-tab'; window.location.assign(url.toString()); }
        else showAlert('Open the relevant tab to view this shared item.', 'info');
    }, 180);
}

// ---------- Load Timeline ----------

async function loadTimeline() {
    try {
        const response = await fetch(`/backend/milestones/list.php?user_id=${profileUserId}`), data = await response.json(), container = document.getElementById('timeline-container');
        container.innerHTML = ''; const all = data.milestones || [], children = new Map();
        all.filter(item => Number(item.parent_id) > 0).forEach(item => { if (!children.has(String(item.parent_id))) children.set(String(item.parent_id), []); children.get(String(item.parent_id)).push(item); });
        const parents = all.filter(item => Number(item.parent_id) <= 0), canManage = loggedInUser && (loggedInUser.id == profileUserId || loggedInUser.role === 'admin');
        const dateText = value => { const [year, month, day] = String(value || '').slice(0, 10).split('-').map(Number); return year && month && day ? new Date(year, month - 1, day).toLocaleDateString('en-US', {year:'numeric', month:'long', day:'numeric'}) : ''; };
        const card = (item, list = [], child = false) => `<div class="timeline-item ${child?'timeline-child-item':''}" id="milestone-${item.id}"><div class="timeline-marker"></div><div class="timeline-content timeline-story-card"><div class="timeline-story-heading"><div><h5>${escapeHtml(item.title||'Untitled')} ${item.category?`<span class="badge bg-info ms-2">${escapeHtml(item.category)}</span>`:''}</h5><small class="text-muted"><i class="bi bi-calendar"></i> ${escapeHtml(dateText(item.milestone_date))}</small></div>${canManage?`<button class="btn btn-sm btn-outline-danger" onclick="deleteMilestone(${item.id})" aria-label="Delete timeline item"><i class="bi bi-trash"></i></button>`:''}</div><p class="text-muted mb-2">${escapeHtml(item.description||'')}</p><span class="badge bg-secondary privacy-badge">${escapeHtml(item.privacy_level||'public')}</span>${!child?`<button type="button" class="timeline-expand-btn" data-timeline-toggle="${item.id}" aria-expanded="false"><i class="bi bi-chevron-down"></i><span>${list.length?`${list.length} progress update${list.length===1?'':'s'}`:'Expand journey'}</span></button><div class="timeline-children d-none" data-timeline-children="${item.id}">${list.map(x=>card(x,[],true)).join('')}${canManage?`<button type="button" class="btn btn-sm btn-outline-primary timeline-add-child" data-parent-id="${item.id}"><i class="bi bi-plus-circle me-1"></i>Add progress update</button>`:''}</div>`:''}</div></div>`;
        container.innerHTML = parents.length ? parents.map(x=>card(x,children.get(String(x.id))||[])).join('') : '<p class="text-muted text-center">No milestones yet. Add your first milestone!</p>';
        container.querySelectorAll('[data-timeline-toggle]').forEach(button=>button.addEventListener('click',()=>{const box=container.querySelector(`[data-timeline-children="${button.dataset.timelineToggle}"]`),open=!box.classList.toggle('d-none');button.setAttribute('aria-expanded',String(open));button.querySelector('i').className=open?'bi bi-chevron-up':'bi bi-chevron-down';}));
        container.querySelectorAll('.timeline-add-child').forEach(button=>button.addEventListener('click',()=>addTimelineProgress(button.dataset.parentId)));
        timelinePersonalItems=all; loadWorldEventsForTimeline(all);
    } catch (error) { console.error('Error loading timeline:', error); }
}
function openProgressModal(parentId,onSave){const modal=document.createElement('div');modal.className='modal fade';modal.innerHTML='<div class="modal-dialog modal-dialog-centered"><div class="modal-content timeline-progress-modal"><div class="modal-header"><h5 class="modal-title">Add progress update</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><form><div class="modal-body"><label class="form-label">What did you accomplish?</label><input id="tp-title" class="form-control mb-3" maxlength="255" required><label class="form-label">Tell the story</label><textarea id="tp-desc" class="form-control mb-3" rows="4" maxlength="10000"></textarea><label class="form-label">Date</label><input id="tp-date" class="form-control" type="date" value="'+new Date().toISOString().slice(0,10)+'" required></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit">Add update</button></div></form></div></div>';document.body.appendChild(modal);const instance=bootstrap.Modal.getOrCreateInstance(modal);instance.show();modal.querySelector('form').addEventListener('submit',async e=>{e.preventDefault();await onSave(parentId,{title:modal.querySelector('#tp-title').value.trim(),description:modal.querySelector('#tp-desc').value.trim(),date:modal.querySelector('#tp-date').value});instance.hide();modal.addEventListener('hidden.bs.modal',()=>modal.remove(),{once:true});});}async function addTimelineProgress(parentId){openProgressModal(parentId,async(id,v)=>{if(!v.title||!v.date)return;const r=await fetch('/backend/milestones/create.php',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrfToken},body:JSON.stringify({title:v.title,description:v.description,milestone_date:v.date,category:'Progress',privacy_level:'public',parent_id:Number(id),csrf_token:csrfToken})});const d=await r.json();if(!d.success){showAlert(d.message||'Unable to add progress update.','danger');return;}loadTimeline();});}
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

function profileMemoryKind(memory) {
    const type = String(memory.file_type || '').toLowerCase();
    const name = String(memory.file_path || '').toLowerCase();
    if (type.includes('image') || /\.(jpg|jpeg|png|gif|webp|bmp|svg|tiff)$/.test(name)) return 'photo';
    if (type.includes('video') || /\.(mp4|avi|mkv|mov|3gp|flv|wmv|webm|mpeg|mpg)$/.test(name)) return 'video';
    if (type.includes('audio') || /\.(mp3|wav|aac|ogg|flac|m4a)$/.test(name)) return 'audio';
    return 'document';
}

function profileMemoryMediaUrl(memory) {
    const kind = profileMemoryKind(memory);
    const directory = kind === 'photo' ? 'photos' : kind === 'video' ? 'videos' : kind === 'audio' ? 'audio' : 'documents';
    return `/data/uploads/${directory}/${String(memory.file_path || '').split('/').map(safeUploadPathSegment).join('/')}`;
}

function profileFolderName(folderId) {
    return profileMemoryFoldersCache.find(folder => Number(folder.id) === Number(folderId))?.name || '';
}

function profileMediaItems(type) {
    let items = profileMemoriesCache || [];
    if (type === 'photos') items = items.filter(memory => profileMemoryKind(memory) === 'photo');
    if (type === 'videos') items = items.filter(memory => profileMemoryKind(memory) === 'video');
    const folderId = profileMediaState[type].folderId;
    return folderId ? items.filter(memory => Number(memory.folder_id || 0) === folderId) : items;
}

function renderProfileFolderTree(type) {
    const host = document.querySelector(`[data-profile-folder-tree="${type}"]`);
    if (!host) return;
    const state = profileMediaState[type];
    const allForType = type === 'photos'
        ? profileMemoriesCache.filter(memory => profileMemoryKind(memory) === 'photo')
        : type === 'videos'
            ? profileMemoriesCache.filter(memory => profileMemoryKind(memory) === 'video')
            : profileMemoriesCache;
    const counts = new Map();
    allForType.forEach(memory => counts.set(Number(memory.folder_id || 0), (counts.get(Number(memory.folder_id || 0)) || 0) + 1));
    const byParent = new Map();
    profileMemoryFoldersCache.forEach(folder => {
        const parent = Number(folder.parent_folder_id || 0);
        if (!byParent.has(parent)) byParent.set(parent, []);
        byParent.get(parent).push(folder);
    });
    byParent.forEach(folders => folders.sort((a, b) => String(a.name).localeCompare(String(b.name))));
    const makeButton = (folder, depth) => {
        const id = Number(folder?.id || 0);
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `profile-media-folder ${state.folderId === id ? 'is-selected' : ''}`;
        button.style.setProperty('--profile-folder-depth', depth);
        button.innerHTML = `<i class="bi ${id ? 'bi-folder2' : 'bi-collection'}"></i><span></span><small>${id ? (counts.get(id) || 0) : allForType.length}</small>`;
        button.querySelector('span').textContent = id ? folder.name : `All ${type}`;
        button.addEventListener('click', () => {
            state.folderId = id;
            state.page = 1;
            renderProfileMediaBrowser(type);
        });
        return button;
    };
    host.replaceChildren(makeButton(null, 0));
    const appendLevel = (parent, depth) => (byParent.get(parent) || []).forEach(folder => {
        host.appendChild(makeButton(folder, depth));
        appendLevel(Number(folder.id), depth + 1);
    });
    appendLevel(0, 0);
    if (!profileMemoryFoldersCache.length) {
        const empty = document.createElement('p');
        empty.className = 'profile-media-folder-empty';
        empty.textContent = 'No memory folders are visible.';
        host.appendChild(empty);
    }
}

function profileMediaPagination(type, total, totalPages) {
    if (totalPages <= 1) return '';
    const page = profileMediaState[type].page;
    return `<nav class="profile-media-pagination" aria-label="${type} pages"><button type="button" class="btn btn-outline-primary btn-sm" data-profile-page="${page - 1}" ${page <= 1 ? 'disabled' : ''}><i class="bi bi-chevron-left"></i> Previous</button><span>Page ${page} of ${totalPages} <small>(${total} items)</small></span><button type="button" class="btn btn-outline-primary btn-sm" data-profile-page="${page + 1}" ${page >= totalPages ? 'disabled' : ''}>Next <i class="bi bi-chevron-right"></i></button></nav>`;
}

async function moveProfileMemory(memoryId) {
    try {
        const result = await fetch(`/backend/memories/folders/list.php?user_id=${encodeURIComponent(profileUserId)}&_=${Date.now()}`).then(response => response.json());
        const folders = result.data?.folders || [];
        const options = folders.length ? folders.map(folder => `${folder.id}: ${folder.name}`).join('\n') : 'No folders available.';
        const choice = prompt(`Choose a folder ID. Enter 0 to remove this memory from its folder.\n${options}`, '0');
        if (choice === null) return;
        const selected = folders.find(folder => String(folder.id) === choice.trim() || String(folder.name).toLowerCase() === choice.trim().toLowerCase());
        const response = await fetch('/backend/memories/folders/move_memory.php', { method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken}, body: JSON.stringify({memory_id: Number(memoryId), folder_id: choice.trim() === '0' ? 0 : Number(selected?.id || parseInt(choice, 10) || 0)}) });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Unable to move memory.');
        await loadProfileMemories();
    } catch (error) { showAlert(error.message || 'Unable to move memory.', 'danger'); }
}
function profileMemoryCard(memory) {
    const kind = profileMemoryKind(memory);
    const filePath = profileMemoryMediaUrl(memory);
    let mediaHtml = '';
    if (kind === 'photo') {
        mediaHtml = `<div class="profile-memory-media"><img src="${filePath}" alt="${escapeHtml(memory.title || 'Memory photo')}" class="memory-image" loading="lazy"></div>`;
    } else if (kind === 'video') {
        const poster = memory.video_thumbnail_path ? `/data/uploads/${String(memory.video_thumbnail_path).split('/').map(safeUploadPathSegment).join('/')}` : '';
        mediaHtml = `<div class="profile-memory-media video-memory-preview"><video controls preload="metadata" ${poster ? `poster="${poster}"` : ''}><source src="${filePath}" type="${escapeHtml(memory.file_type || 'video/mp4')}">Your browser cannot play this video.</video></div>`;
    } else if (kind === 'audio') {
        mediaHtml = `<div class="profile-memory-file"><i class="bi bi-music-note-beamed"></i><audio controls preload="metadata"><source src="${filePath}" type="${escapeHtml(memory.file_type || '')}"></audio></div>`;
    } else {
        mediaHtml = `<div class="profile-memory-file"><i class="bi bi-file-earmark-text"></i><a href="${filePath}" target="_blank" class="btn btn-outline-primary btn-sm">View document</a></div>`;
    }
    const canManage = typeof loggedInUser !== 'undefined' && loggedInUser && (Number(loggedInUser.id) === Number(profileUserId) || loggedInUser.role === 'admin');
    const folder = profileFolderName(memory.folder_id);
    return `<div class="col-12 col-xl-6"><article id="memory-card-${Number(memory.id)}" class="card memory-card profile-memory-card h-100">${mediaHtml}<div class="card-body"><div class="profile-media-card-meta"><span class="badge bg-secondary">${escapeHtml(memory.privacy_level || 'private')}</span>${folder ? `<span><i class="bi bi-folder2"></i> ${escapeHtml(folder)}</span>` : '<span>Unfiled</span>'}</div><h5 class="card-title">${escapeHtml(memory.title || 'Untitled memory')}</h5><p class="card-text">${escapeHtml(memory.description || 'No description added.')}</p><div class="profile-memory-actions"><small>${memory.memory_date ? new Date(memory.memory_date).toLocaleDateString() : ''}</small><div class="dropdown memory-actions-menu"><button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Memory actions"><i class="bi bi-three-dots-vertical"></i></button><ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="${filePath}" download><i class="bi bi-download me-2"></i>Download</a></li>${canManage ? `<li><button class="dropdown-item" type="button" onclick="editProfileMemory(${Number(memory.id)})"><i class="bi bi-pencil me-2"></i>Edit</button></li><li><button class="dropdown-item" type="button" onclick="moveProfileMemory(${Number(memory.id)})"><i class="bi bi-folder2-open me-2"></i>Move to folder</button></li><li><hr class="dropdown-divider"></li><li><button class="dropdown-item text-danger" type="button" onclick="deleteMemory(${Number(memory.id)})"><i class="bi bi-trash me-2"></i>Delete</button></li>` : ''}</ul></div></div><div class="memory-comments mt-3" data-memory-comments="${Number(memory.id)}"><div class="small text-muted">Loading comments...</div></div></div></article></div>`;
}

function profileGalleryCard(memory, type) {
    const filePath = profileMemoryMediaUrl(memory);
    const folder = profileFolderName(memory.folder_id);
    const media = type === 'photos'
        ? `<img src="${filePath}" alt="${escapeHtml(memory.title || 'Memory photo')}" loading="lazy">`
        : `<video controls preload="metadata" ${memory.video_thumbnail_path ? `poster="/data/uploads/${String(memory.video_thumbnail_path).split('/').map(safeUploadPathSegment).join('/')}"` : ''}><source src="${filePath}" type="${escapeHtml(memory.file_type || 'video/mp4')}">Your browser cannot play this video.</video>`;
    return `<article class="profile-gallery-card" data-open-memory="${Number(memory.id)}" tabindex="0" role="link" aria-label="Open ${escapeHtml(memory.title || (type === 'photos' ? 'photo memory' : 'video memory'))}"><div class="profile-gallery-media">${media}<span class="profile-gallery-view"><i class="bi bi-arrows-fullscreen"></i> View memory</span></div><div class="profile-gallery-copy"><div class="profile-media-card-meta"><span><i class="bi bi-folder2"></i> ${escapeHtml(folder || 'Unfiled')}</span><span>${memory.memory_date ? new Date(memory.memory_date).toLocaleDateString() : ''}</span></div><h6>${escapeHtml(memory.title || (type === 'photos' ? 'Photo memory' : 'Video memory'))}</h6><p>${escapeHtml(memory.description || 'No description added.')}</p><span class="profile-gallery-open">Open exact memory <i class="bi bi-arrow-right"></i></span></div></article>`;
}

function renderProfileMediaBrowser(type) {
    const container = document.getElementById(type === 'memories' ? 'memories-container' : `${type}-container`);
    if (!container) return;
    const state = profileMediaState[type];
    const items = profileMediaItems(type);
    const totalPages = Math.max(1, Math.ceil(items.length / PROFILE_MEDIA_PAGE_SIZE));
    state.page = Math.min(Math.max(1, state.page), totalPages);
    const pageItems = items.slice((state.page - 1) * PROFILE_MEDIA_PAGE_SIZE, state.page * PROFILE_MEDIA_PAGE_SIZE);
    const title = type === 'memories' ? 'Memory Library' : type === 'photos' ? 'Photo Gallery' : 'Video Gallery';
    const description = type === 'memories' ? 'Browse this life story by folder.' : type === 'photos' ? 'Photos collected automatically from visible memories.' : 'Videos collected automatically from visible memories.';
    const selected = state.folderId ? profileFolderName(state.folderId) : `All ${type}`;
    container.innerHTML = `<section class="profile-media-browser profile-media-${type}"><header class="profile-media-heading"><div class="profile-media-heading-icon"><i class="bi ${type === 'photos' ? 'bi-images' : type === 'videos' ? 'bi-camera-video' : 'bi-journal-richtext'}"></i></div><div><h5>${title}</h5><p>${description}</p></div><span>${items.length} ${items.length === 1 ? 'item' : 'items'}</span></header><div class="profile-media-layout"><aside class="profile-media-sidebar"><div class="profile-media-sidebar-title"><span>Folders</span><small>Choose a folder</small></div><div data-profile-folder-tree="${type}" class="profile-media-folder-tree"></div></aside><main class="profile-media-content"><div class="profile-media-current"><span><i class="bi bi-folder2-open"></i> ${escapeHtml(selected)}</span><small>Showing ${pageItems.length} of ${items.length}</small></div>${pageItems.length ? (type === 'memories' ? `<div id="memories-grid" class="row g-3">${pageItems.map(profileMemoryCard).join('')}</div>` : `<div class="profile-gallery-grid">${pageItems.map(memory => profileGalleryCard(memory, type)).join('')}</div>`) : `<div class="profile-media-empty"><i class="bi ${type === 'photos' ? 'bi-image' : type === 'videos' ? 'bi-camera-video' : 'bi-journal'}"></i><strong>No ${type} in this folder</strong><span>${state.folderId ? 'Choose another folder or open All items.' : 'Visible items will appear here when they are added.'}</span></div>`}${profileMediaPagination(type, items.length, totalPages)}</main></div></section>`;
    renderProfileFolderTree(type);
    container.querySelectorAll('[data-profile-page]').forEach(button => button.addEventListener('click', () => {
        state.page = Number(button.dataset.profilePage);
        renderProfileMediaBrowser(type);
        container.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }));
    const openExactMemory = (card, event) => {
        if (event?.type === 'click' && event.target.closest('video,a,button,input')) return;
        if (event?.type === 'keydown' && !['Enter', ' '].includes(event.key)) return;
        event?.preventDefault();
        profileMediaState.memories.folderId = 0;
        const memoryId = Number(card.dataset.openMemory);
        const index = profileMemoriesCache.findIndex(memory => Number(memory.id) === memoryId);
        profileMediaState.memories.page = Math.max(1, Math.floor(index / PROFILE_MEDIA_PAGE_SIZE) + 1);
        const memoryTab = document.querySelector('[data-bs-toggle="tab"][href="#memories-tab"]');
        if (memoryTab && window.bootstrap) bootstrap.Tab.getOrCreateInstance(memoryTab).show();
        renderProfileMediaBrowser('memories');
        requestAnimationFrame(() => {
            const target = document.getElementById(`memory-card-${memoryId}`);
            if (!target) return;
            target.classList.add('profile-memory-focus');
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(() => target.classList.remove('profile-memory-focus'), 2200);
        });
    };
    container.querySelectorAll('[data-open-memory]').forEach(card => {
        card.addEventListener('click', event => openExactMemory(card, event));
        card.addEventListener('keydown', event => openExactMemory(card, event));
    });
    if (type === 'memories') pageItems.forEach(memory => loadMemoryComments(memory.id));
}

function renderMemoryVideoTab() {
    renderProfileMediaBrowser('photos');
    renderProfileMediaBrowser('videos');
}

// ---------- Load Memories ----------
async function loadMemories() {
    const memoriesContainer = document.getElementById('memories-container');
    if (memoriesContainer) memoriesContainer.innerHTML = '<div class="profile-media-loading"><span class="spinner-border spinner-border-sm"></span> Loading memories and folders...</div>';
    try {
        const [memoryResponse, folderResponse] = await Promise.all([
            fetch(`/backend/memories/list.php?user_id=${encodeURIComponent(profileUserId)}&per_page=100&page=1`),
            fetch(`/backend/memories/folders/list.php?user_id=${encodeURIComponent(profileUserId)}&direction=asc&sort=name`)
        ]);
        const data = await memoryResponse.json();
        const folderData = await folderResponse.json();
        if (!data.success) throw new Error(data.message || 'Unable to load memories');
        profileMemoriesCache = data.memories || [];
        const pages = Number(data.pagination?.total_pages || 1);
        for (let page = 2; page <= pages; page += 1) {
            const next = await fetch(`/backend/memories/list.php?user_id=${encodeURIComponent(profileUserId)}&per_page=100&page=${page}`).then(response => response.json());
            if (next.success) profileMemoriesCache.push(...(next.memories || []));
        }
        profileMemoryFoldersCache = folderData.success ? (folderData.data?.folders || []) : [];
        const focusMemoryId = Number(new URLSearchParams(window.location.search).get('focus_memory') || 0);
        renderProfileMediaBrowser('memories');
        renderMemoryVideoTab();
        if (focusMemoryId) {
            const index = profileMemoriesCache.findIndex(memory => Number(memory.id) === focusMemoryId);
            if (index >= 0) {
                profileMediaState.memories.page = Math.floor(index / PROFILE_MEDIA_PAGE_SIZE) + 1;
                renderProfileMediaBrowser('memories');
                requestAnimationFrame(() => {
                    const focused = document.getElementById(`memory-card-${focusMemoryId}`);
                    if (focused) { focused.classList.add('about-item-focus'); focused.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
                });
            }
            const url = new URL(window.location.href);
            url.searchParams.delete('focus_memory');
            history.replaceState(null, '', `${url.pathname}${url.search}${url.hash}`);
        }
    } catch (error) {
        console.error('Error loading memories:', error);
        if (memoriesContainer) memoriesContainer.innerHTML = '<div class="profile-media-empty"><i class="bi bi-exclamation-circle"></i><strong>Memories could not be loaded</strong><span>Please refresh the page and try again.</span></div>';
        ['photos-container', 'videos-container'].forEach(id => { const container = document.getElementById(id); if (container) container.innerHTML = '<div class="profile-media-empty"><strong>Media unavailable</strong><span>Please try again shortly.</span></div>'; });
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

// ---------- Load Tributes ----------

async function loadTributes() {
    try {
        const response = await fetch(`/backend/tributes/list.php?memorial_user_id=${profileUserId}`);
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
                        ? `/data/uploads/photos/${tribute.profile_photo}`
                        : '/data/uploads/photos/default-profile.png';
                    displayName = tribute.registered_user_name;
                    userBadge = '<span class="badge bg-primary ms-2" style="font-size: 0.7rem;">Member</span>';
                } else {
                    // Guest user
                    avatarUrl = '/data/uploads/photos/default-profile.png';
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
        const response = await fetch('/backend/tributes/delete.php', {
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
        const response = await fetch(`/backend/events/list.php?user_id=${profileUserId}`);
        const data = await response.json();

        const container = document.getElementById('events-container');

        if (!data.success) {
            container.innerHTML = '<div class="alert alert-danger">Error loading events</div>';
            return;
        }

        window.profileEventsCache = data.events || [];
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
            pastSection.className = 'past-events-section mb-4'; pastSection.innerHTML = '<h6 class="text-muted mb-3 past-events-heading"><i class="bi bi-clock" aria-hidden="true"></i><span>Past Events</span></h6>';

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
    const card = document.createElement('article');
    card.id = `event-${Number(event.id)}`;
    card.className = `card mb-3 event-display-card ${event.media_url ? 'has-media' : ''} ${isPast ? 'is-past' : 'is-upcoming'}`;
    const eventDate = new Date(event.scheduled_date);
    const formattedDate = eventDate.toLocaleDateString(undefined, {weekday:'long',year:'numeric',month:'long',day:'numeric'});
    const formattedTime = eventDate.toLocaleTimeString(undefined, {hour:'2-digit',minute:'2-digit'});
    const eventTypes = {birthday:{icon:'bi-cake2',color:'text-danger'},anniversary:{icon:'bi-heart',color:'text-danger'},memorial:{icon:'bi-flower1',color:'text-info'},remembrance:{icon:'bi-star',color:'text-warning'},celebration:{icon:'bi-balloon',color:'text-success'},other:{icon:'bi-calendar-event',color:'text-secondary'}};
    const typeInfo = eventTypes[event.event_type] || eventTypes.other;
    const privacyBadges = {public:'bg-success',family:'bg-warning text-dark',friends:'bg-primary',private:'bg-secondary',specific_people:'bg-info text-dark',release_date:'bg-dark',release_event:'bg-dark'};
    const canDelete = loggedInUser && (Number(loggedInUser.id) === Number(profileUserId) || loggedInUser.role === 'admin');
    const media = event.media_url ? (event.media_type === 'video'
        ? `<div class="event-card-media"><video controls preload="metadata"><source src="${escapeHtml(event.media_url)}" type="${escapeHtml(event.media_mime || 'video/mp4')}">Your browser cannot play this video.</video></div>`
        : `<div class="event-card-media"><img src="${escapeHtml(event.media_url)}" alt="${escapeHtml(event.title || 'Event photo')}" loading="lazy"></div>`) : '';
    card.innerHTML = `${media}<div class="card-body event-card-body"><div class="event-card-main"><div class="event-card-title-row"><span class="event-card-icon"><i class="bi ${typeInfo.icon} ${typeInfo.color}"></i></span><h5>${escapeHtml(event.title || 'Untitled event')}</h5><span class="badge ${privacyBadges[event.privacy_level] || 'bg-secondary'}">${escapeHtml(event.privacy_level || 'private')}</span>${isPast ? '<span class="badge bg-secondary">Past</span>' : '<span class="badge bg-info text-dark">Upcoming</span>'}</div><p class="event-card-date"><i class="bi bi-calendar3"></i> ${escapeHtml(formattedDate)} at ${escapeHtml(formattedTime)}</p>${event.message ? `<p class="event-card-message">${escapeHtml(event.message)}</p>` : ''}</div>${canDelete ? `<button class="btn btn-sm btn-outline-danger event-delete-button" onclick="deleteEvent(${Number(event.id)})" aria-label="Delete event"><i class="bi bi-trash"></i></button>` : ''}</div>`;
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
    try { const result=await fetch(`/backend/memories/folders/list.php?user_id=${encodeURIComponent(profileUserId)}`).then(r=>r.json()); profileMemoryFoldersCache=result.data?.folders||[]; profileMemoryFoldersCache.forEach(item=>{const option=document.createElement('option');option.value=item.id;option.textContent=item.name;folder.appendChild(option);}); } catch(e) {}
    folder.value=memory.folder_id||0;
    if(!profileMemoryPrivacyWidget){profileMemoryPrivacyWidget=privacyComponent('profile-memory-edit',Number(profileUserId));document.getElementById('profile-edit-memory-privacy').appendChild(profileMemoryPrivacyWidget);}
    profileMemoryPrivacyWidget.querySelector('.privacy-type').value=memory.privacy_level||'public';
    await profileMemoryPrivacyWidget.loadRule('memory',memory.id);
    bootstrap.Modal.getOrCreateInstance(modal).show();
}
document.getElementById('profileEditMemoryForm')?.addEventListener('submit',async event=>{event.preventDefault();if(!currentUser||Number(currentUser.id)!==Number(profileUserId))return;const error=document.getElementById('profile-edit-memory-error');const save=document.getElementById('profile-edit-memory-save');error.textContent='';save.disabled=true;try{const rule=profileMemoryPrivacyWidget.getRule();const response=await fetch('/backend/memories/update.php',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrfToken},body:JSON.stringify({memory_id:Number(document.getElementById('profile-edit-memory-id').value),title:document.getElementById('profile-edit-memory-title').value.trim(),description:document.getElementById('profile-edit-memory-description').value,memory_date:document.getElementById('profile-edit-memory-date').value,folder_id:Number(document.getElementById('profile-edit-memory-folder').value),privacy_level:rule.visibility_type})});const data=await response.json();if(!data.success)throw new Error(data.message||'Unable to update memory.');await savePrivacyRule(csrfToken,'memory',data.data.memory_id,rule);bootstrap.Modal.getInstance(document.getElementById('profileEditMemoryModal')).hide();loadMemories();}catch(e){error.textContent=e.message;}finally{save.disabled=false;}});
// Preserve the selected profile tab across refreshes.
document.addEventListener('DOMContentLoaded', () => {
    initAboutLifeJournal();
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

// Give media libraries the full profile workspace and keep tab navigation visible.
function syncProfileMediaWorkspace(activeHref = null) {
    const href = activeHref || document.querySelector('.profile-tabs [data-bs-toggle="tab"].active')?.getAttribute('href') || window.location.hash;
    const isMedia = ['#memories-tab', '#photos-tab', '#videos-tab', '#events-tab'].includes(href);
    document.body.classList.toggle('profile-media-workspace-active', isMedia);
}

document.addEventListener('shown.bs.tab', event => {
    const tab = event.target.closest('.profile-tabs [data-bs-toggle="tab"]');
    if (!tab) return;
    const href = tab.getAttribute('href');
    syncProfileMediaWorkspace(href);
    if (['#memories-tab', '#photos-tab', '#videos-tab', '#events-tab'].includes(href)) {
        requestAnimationFrame(() => document.querySelector('.profile-tabs')?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
    }
});
document.addEventListener('DOMContentLoaded', () => setTimeout(() => syncProfileMediaWorkspace(), 150));

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
        const visibleCount = window.innerWidth <= 767 ? 3 : 6;
        originalItems.slice(visibleCount).forEach(moveToMenu);
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

