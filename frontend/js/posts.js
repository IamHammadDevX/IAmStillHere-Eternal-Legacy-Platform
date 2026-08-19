const POSTS_API = '/backend/posts';
let postPage = 1;
let postTotalPages = 1;
let postPrivacyWidget = null;
let scheduledPostsLoaded = false;
let scheduledPostsCache = [];
let editingScheduledPostId = 0;
const POST_EMOJIS = [
    '\u2764\uFE0F',
    '\uD83D\uDE0A',
    '\uD83D\uDE02',
    '\uD83D\uDE22',
    '\uD83D\uDE4F',
    '\uD83C\uDF89',
    '\u2728',
    '\uD83C\uDF38',
    '\uD83D\uDD4A\uFE0F',
    '\uD83D\uDC9C'
];

function postMediaUrl(media) {
    const folder = media.media_type === 'video' ? 'videos' : 'photos';
    return `/data/uploads/${folder}/${encodeURIComponent(media.file_path)}`;
}

function postAuthorPhoto(photo) {
    return photo ? `/data/uploads/photos/${encodeURIComponent(photo)}` : '/frontend/images/default-profile.png';
}

function el(tag, className = '', text = '') {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== '') node.textContent = text;
    return node;
}

function safePostHtml(html) {
    const template = document.createElement('template');
    template.innerHTML = String(html || '');
    const allowed = new Set(['STRONG', 'B', 'EM', 'I', 'U', 'UL', 'OL', 'LI', 'BR', 'P', 'DIV']);
    template.content.querySelectorAll('*').forEach(node => {
        if (!allowed.has(node.tagName)) node.replaceWith(document.createTextNode(node.textContent || ''));
        else [...node.attributes].forEach(attribute => node.removeAttribute(attribute.name));
    });
    return template.innerHTML;
}

function initInlineEmojiPicker(button, target) {
    button.addEventListener('click', () => {
        let picker = button.parentElement.querySelector('.post-emoji-picker');
        if (picker) { picker.remove(); return; }
        picker = document.createElement('div'); picker.className = 'post-emoji-picker post-comment-emoji-picker';
        POST_EMOJIS.forEach(emoji => {
            const item = document.createElement('button'); item.type = 'button'; item.className = 'post-emoji-option'; item.textContent = emoji; item.title = `Add ${emoji}`;
            item.addEventListener('click', () => { target.value += emoji; picker.remove(); target.focus(); }); picker.appendChild(item);
        });
        button.parentElement.appendChild(picker);
    });
}
function initPostEditor(){
    document.querySelectorAll('[data-post-command]').forEach(button=>button.addEventListener('click',()=>{document.execCommand(button.dataset.postCommand,false,null);document.getElementById('post-body')?.focus();}));
    const emojiButton=document.getElementById('post-emoji-button');
    emojiButton?.addEventListener('click',()=>{
        let picker=document.querySelector('.post-emoji-picker');
        if(picker){picker.remove();return;}
        picker=document.createElement('div'); picker.className='post-emoji-picker';
        POST_EMOJIS.forEach(emoji=>{
            const item=document.createElement('button'); item.type='button'; item.className='post-emoji-option'; item.textContent=emoji; item.title=`Add ${emoji}`;
            item.addEventListener('click',()=>{document.execCommand('insertText',false,emoji);picker.remove();document.getElementById('post-body')?.focus();}); picker.appendChild(item);
        });
        emojiButton.parentElement.appendChild(picker);
    });
}
function postBodyHtml(){return document.getElementById('post-body')?.innerHTML.trim()||'';}
function postBodyText(){return document.getElementById('post-body')?.innerText.trim()||'';}function initPostsFeature() {
    const composer = document.getElementById('post-composer');
    const viewerId = currentUser && (currentUser.id || currentUser.user_id);
    const isProfileOwner = viewerId && String(viewerId) === String(profileUserId);
    const canEditProfile = document.getElementById('edit-profile-btn') && document.getElementById('edit-profile-btn').offsetParent !== null;

    if (composer && (isProfileOwner || canEditProfile)) {
        composer.style.display = 'block';
    }

    const oldPrivacy = document.getElementById('post-privacy');
    if (oldPrivacy && typeof privacyComponent === 'function' && currentUser && currentUser.id) {
        oldPrivacy.style.display = 'none';
        postPrivacyWidget = privacyComponent('post', currentUser.id);
        oldPrivacy.parentElement.appendChild(postPrivacyWidget);
    }
    initPostEditor();
    enhancePostComposerForScheduling();
    document.getElementById('post-form')?.addEventListener('submit', submitPost);
    loadPosts(1);
    loadScheduledPosts();
}

function postLocalToIso(value){if(!value)return '';const d=new Date(value);return Number.isNaN(d.getTime())?'':d.toISOString();}
function postUtcToLocal(value){if(!value)return 'not scheduled';const d=new Date(String(value).replace(' ','T')+'Z');return Number.isNaN(d.getTime())?value:d.toLocaleString();}
function enhancePostComposerForScheduling(){
    const form=document.getElementById('post-form'); if(!form||form.dataset.scheduleReady==='1')return; form.dataset.scheduleReady='1';
    const toolbar=form.querySelector('.d-flex'); if(!toolbar)return;
    const wrap=document.createElement('div'); wrap.className='border rounded p-2 my-2 bg-light';
    wrap.innerHTML='<div class="d-flex flex-wrap gap-2 align-items-center mb-2"><div class="btn-group btn-group-sm" role="group"><input type="radio" class="btn-check" name="post-mode" id="post-mode-now" value="now" checked><label class="btn btn-outline-primary" for="post-mode-now">Publish</label><input type="radio" class="btn-check" name="post-mode" id="post-mode-schedule" value="schedule"><label class="btn btn-outline-primary" for="post-mode-schedule">Schedule</label></div></div><div id="post-schedule-fields" class="row g-2 d-none"><div class="col-md-4"><label class="form-label small">Trigger</label><select id="post-schedule-trigger" class="form-select form-select-sm"><option value="specific_datetime">Specific date/time</option><option value="birthday">Birthday</option><option value="anniversary">Anniversary</option><option value="custom_recurring">Recurring date</option><option value="linked_milestone_event">Milestone/Event</option></select></div><div class="col-md-4"><label class="form-label small">Date/time</label><input id="post-schedule-at" type="datetime-local" class="form-control form-control-sm"></div><div class="col-md-4"><label class="form-label small" id="post-linked-id-label">Linked ID optional</label><select id="post-linked-type" class="form-select form-select-sm mb-1"><option value="event">Event ID</option><option value="milestone">Milestone ID</option></select><select id="post-linked-id" class="form-select form-select-sm"><option value="">Choose an event or milestone</option></select></div></div>';
    toolbar.parentNode.insertBefore(wrap,toolbar);
    document.querySelectorAll('[name="post-mode"]').forEach(r=>r.addEventListener('change',()=>document.getElementById('post-schedule-fields').classList.toggle('d-none',document.querySelector('[name="post-mode"]:checked').value!=='schedule')));
    const trigger=document.getElementById('post-schedule-trigger');
    const linkedLabel=document.getElementById('post-linked-id-label');
    const linkedInput=document.getElementById('post-linked-id');
    const loadLinkedResources=async()=>{const type=document.getElementById('post-linked-type').value; const userId=window.profileUserId||currentUser?.id; if(!userId)return; linkedInput.innerHTML='<option value="">Loading...</option>'; try{const url=type==='event'?`/backend/events/list.php?user_id=${encodeURIComponent(userId)}`:`/backend/milestones/list.php?user_id=${encodeURIComponent(userId)}`; const data=await fetch(url).then(r=>r.json()); const rows=type==='event'?(data.events||[]):(data.milestones||[]); linkedInput.innerHTML='<option value="">Choose an item</option>'+rows.map(x=>`<option value="${x.id}">${escapeHtml(x.title||x.name||'Untitled')} (#${x.id})</option>`).join(''); if(!rows.length)linkedInput.innerHTML='<option value="">No items available</option>';}catch(e){linkedInput.innerHTML='<option value="">Unable to load items</option>';}};
    document.getElementById('post-linked-type').addEventListener('change',loadLinkedResources);
    const syncLinkedRequirement=()=>{const required=trigger.value==='linked_milestone_event';linkedLabel.textContent=required?'Event/Milestone ID required':'Linked ID optional';linkedInput.required=required;linkedInput.placeholder=required?'Choose an event or milestone':'event/milestone id';};
    trigger.addEventListener('change',()=>{syncLinkedRequirement();if(trigger.value==='linked_milestone_event')loadLinkedResources();});
    syncLinkedRequirement();
}
async function submitScheduledPost(form){
    const fd=new FormData(); const rule=postPrivacyWidget?postPrivacyWidget.getRule():{visibility_type:document.getElementById('post-privacy').value,user_ids:[],release_at:'',release_event_id:0};
    fd.append('body',postBodyHtml()); fd.append('privacy_level',rule.visibility_type); fd.append('csrf_token',csrfToken);
    fd.append('trigger_type',document.getElementById('post-schedule-trigger').value); fd.append('trigger_at',postLocalToIso(document.getElementById('post-schedule-at').value));
    const trigger=document.getElementById('post-schedule-trigger').value; const linked=Number(document.getElementById('post-linked-id').value||0); const linkedType=document.getElementById('post-linked-type').value; if(trigger==='linked_milestone_event'&&!linked){showAlert('Enter the event or milestone ID before scheduling.','warning');return;} if(linked>0){fd.append('linked_resource_type',linkedType);fd.append('linked_resource_id',linked);} const media=document.getElementById('post-media').files[0]; if(media)fd.append('media',media);
    const endpoint=editingScheduledPostId?POSTS_API+'/reschedule.php':POSTS_API+'/schedule.php';
    if(editingScheduledPostId)fd.append('scheduled_post_id',editingScheduledPostId);
    const response=await fetch(endpoint,{method:'POST',body:fd}); const data=await response.json(); if(!data.success){const detail=data.errors?Object.values(data.errors).join(' '):'';throw new Error([data.message,detail].filter(Boolean).join(': ' )||'Unable to save scheduled post.');}
    editingScheduledPostId=0; form.reset(); showAlert(data.message||'Wall post scheduled.','success'); scheduledPostsLoaded=false; loadScheduledPosts();
}
async function loadScheduledPosts(){
    const container=document.getElementById('posts-container'); if(!container||!currentUser||String(currentUser.id)!==String(profileUserId)||scheduledPostsLoaded)return; scheduledPostsLoaded=true;
    let panel=document.getElementById('scheduled-posts-panel'); if(!panel){panel=document.createElement('div');panel.id='scheduled-posts-panel';panel.className='card mb-3';panel.innerHTML='<div class="card-body"><h6>Scheduled Posts</h6><div id="scheduled-posts-list" class="small text-muted">Loading...</div></div>';container.parentNode.insertBefore(panel,container);}
    try{const data=await fetch(POSTS_API+'/scheduled_list.php',{cache:'no-store'}).then(r=>r.json());const list=document.getElementById('scheduled-posts-list');const rows=data.data?.scheduled_posts||[];scheduledPostsCache=rows;list.innerHTML=rows.length?rows.map(renderScheduledPostRow).join(''):'No scheduled posts.';}catch(e){document.getElementById('scheduled-posts-list').textContent='Unable to load scheduled posts.';}
}
function renderScheduledPostRow(p){
    const actions=p.status==='scheduled'?`<button class="btn btn-link btn-sm p-0 me-2" onclick="editScheduledPost(${p.id})">Edit</button><button class="btn btn-link btn-sm p-0 me-2" onclick="publishScheduledPostNow(${p.id})">Publish</button><button class="btn btn-link btn-sm p-0 text-danger" onclick="cancelScheduledPost(${p.id})">Cancel</button>`:'';
    return `<div class="border rounded p-2 mb-2"><div class="d-flex justify-content-between gap-2"><strong>${escapeHtml(p.body).slice(0,80)}</strong><span class="badge bg-secondary">${escapeHtml(p.status)}</span></div><div>${escapeHtml(postUtcToLocal(p.trigger_at))}  -  ${escapeHtml(p.privacy_level)}</div>${actions}</div>`;
}
function editScheduledPost(id){
    const p=scheduledPostsCache.find(item=>Number(item.id)===Number(id)); if(!p)return;
    editingScheduledPostId=Number(p.id||0); document.getElementById('post-body').innerHTML=p.body||''; document.getElementById('post-mode-schedule').checked=true; document.getElementById('post-schedule-fields').classList.remove('d-none'); document.getElementById('post-schedule-trigger').value=p.trigger_type||'specific_datetime';
    const d=p.trigger_at?new Date(String(p.trigger_at).replace(' ','T')+'Z'):null; if(d&&!Number.isNaN(d.getTime())){const pad=n=>String(n).padStart(2,'0');document.getElementById('post-schedule-at').value=`${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;}
    showAlert('Editing scheduled post. Submit again to save changes.','info'); document.getElementById('post-body').scrollIntoView({behavior:'smooth',block:'center'});
}
async function cancelScheduledPost(id){if(!confirm('Cancel scheduled post?'))return;const data=await fetch(`${POSTS_API}/cancel_scheduled.php`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrfToken},body:JSON.stringify({scheduled_post_id:id,csrf_token:csrfToken})}).then(r=>r.json());showAlert(data.message||'Done',data.success?'success':'danger');scheduledPostsLoaded=false;loadScheduledPosts();}
async function publishScheduledPostNow(id){const data=await fetch(`${POSTS_API}/publish_now.php`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrfToken},body:JSON.stringify({scheduled_post_id:id,csrf_token:csrfToken})}).then(r=>r.json());showAlert(data.message||'Done',data.success?'success':'danger');scheduledPostsLoaded=false;loadScheduledPosts();}
async function loadPosts(page = 1) {
    const container = document.getElementById('posts-container');
    if (!container || !profileUserId) return;
    container.innerHTML = '<div class="text-muted">Loading posts...</div>';

    try {
        const response = await fetch(`${POSTS_API}/list.php?user_id=${encodeURIComponent(profileUserId)}&page=${page}&limit=10`);
        const data = await response.json();
        if (!data.success) {
            container.innerHTML = `<div class="alert alert-danger">${escapeHtml(data.message || 'Unable to load posts.')}</div>`;
            return;
        }

        postPage = data.data.pagination.current_page;
        postTotalPages = data.data.pagination.total_pages;
        renderPosts(data.data.posts, data.data.pagination);
    } catch (error) {
        console.error('Error loading posts:', error);
        container.innerHTML = '<div class="alert alert-danger">Error loading posts.</div>';
    }
}

function renderPosts(posts, pagination) {
    const container = document.getElementById('posts-container');
    container.innerHTML = '';

    if (!posts.length) {
        container.appendChild(el('div', 'card card-body text-muted text-center', 'No posts yet.'));
        renderPostMediaTabs([]);
        return;
    }

    posts.forEach(post => {
        container.appendChild(createPostCard(post));
        loadPostComments(post.id);
    });
    renderPostPagination(container, pagination);
    renderPostMediaTabs(posts);
}

function createPostCard(post) {
    const card = el('div', 'card mb-3 post-card');
    const body = el('div', 'card-body');

    const header = el('div', 'd-flex align-items-start gap-2 mb-2');
    const img = el('img', 'rounded-circle flex-shrink-0');
    img.src = postAuthorPhoto(post.author_profile_photo);
    img.alt = '';
    img.style.width = '42px';
    img.style.height = '42px';
    img.style.objectFit = 'cover';

    const meta = el('div', 'flex-grow-1');
    meta.appendChild(el('div', 'fw-semibold', post.author_name || 'Unknown'));
    meta.appendChild(el('small', 'text-muted', `${new Date(post.created_at).toLocaleString()} \u00b7 ${post.privacy_level}`));

    const actions = el('div', 'dropdown');
    if (post.can_edit || post.can_delete) {
        const button = el('button', 'btn btn-sm btn-light');
        button.type = 'button';
        button.dataset.bsToggle = 'dropdown';
        button.textContent = '\u22ef';
        const menu = el('ul', 'dropdown-menu dropdown-menu-end');
        if (post.can_edit) {
            const item = el('button', 'dropdown-item', 'Edit');
            item.type = 'button';
            item.addEventListener('click', () => editPost(post));
            const li = el('li'); li.appendChild(item); menu.appendChild(li);
        }
        if (post.can_delete) {
            const item = el('button', 'dropdown-item text-danger', 'Delete');
            item.type = 'button';
            item.addEventListener('click', () => deletePost(post.id));
            const li = el('li'); li.appendChild(item); menu.appendChild(li);
        }
        actions.appendChild(button);
        actions.appendChild(menu);
    }

    header.appendChild(img);
    header.appendChild(meta);
    header.appendChild(actions);
    body.appendChild(header);

    if (post.body) {
        const text = el('div', 'post-body mb-2');
        text.innerHTML = safePostHtml(post.body);
        body.appendChild(text);
    }

    (post.media || []).forEach(media => body.appendChild(createPostMedia(media)));

    const comments = el('div', 'post-comments mt-3');
    comments.dataset.postComments = post.id;
    body.appendChild(comments);
    card.appendChild(body);

    return card;
}

function createPostMedia(media) {
    const wrap = el('div', 'post-media mb-2');
    const url = postMediaUrl(media);
    if (media.media_type === 'video') {
        const video = document.createElement('video');
        video.controls = true;
        video.preload = 'metadata';
        video.src = url;
        video.className = 'w-100 rounded';
        wrap.appendChild(video);
    } else {
        const img = document.createElement('img');
        img.src = url;
        img.alt = '';
        img.className = 'w-100 rounded post-image';
        wrap.appendChild(img);
    }
    return wrap;
}

function renderPostPagination(container, pagination) {
    if (pagination.total_pages <= 1) return;
    const controls = el('div', 'd-flex justify-content-between my-3');
    const prev = el('button', 'btn btn-outline-secondary btn-sm', 'Previous');
    prev.disabled = pagination.current_page <= 1;
    prev.addEventListener('click', () => loadPosts(pagination.current_page - 1));
    const next = el('button', 'btn btn-outline-secondary btn-sm', 'Next');
    next.disabled = pagination.current_page >= pagination.total_pages;
    next.addEventListener('click', () => loadPosts(pagination.current_page + 1));
    controls.appendChild(prev);
    controls.appendChild(el('span', 'small text-muted align-self-center', `Page ${pagination.current_page} of ${pagination.total_pages}`));
    controls.appendChild(next);
    container.appendChild(controls);
}

async function submitPost(event) {
    event.preventDefault();
    if (!csrfToken) return showAlert('Missing CSRF token. Refresh and try again.', 'danger');
    const submit = event.target.querySelector('button[type="submit"]');
    const originalText = submit?.innerHTML || 'Post';
    if (submit?.disabled) return;
    if (submit) { submit.disabled = true; submit.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span> Posting...'; }
    const form = new FormData();
    form.append('body', postBodyHtml());
    form.append('privacy_level', postPrivacyWidget ? postPrivacyWidget.getRule().visibility_type : document.getElementById('post-privacy').value);
    form.append('csrf_token', csrfToken);
    const media = document.getElementById('post-media').files[0];
    if (media) form.append('media', media);
    try {
        if (document.querySelector('[name="post-mode"]:checked')?.value === 'schedule') { submit.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span> Scheduling...'; await submitScheduledPost(event.target); return; }
        const response = await fetch(`${POSTS_API}/create.php`, { method: 'POST', body: form });
        const data = await response.json();
        if (data.success) {
            try { await savePrivacyRule(csrfToken, 'post', data.data.post.id, postPrivacyWidget ? postPrivacyWidget.getRule() : {visibility_type: document.getElementById('post-privacy').value, user_ids: [], release_at: '', release_event_id: 0}); } catch (privacyError) { showAlert('Post created, but privacy settings were not saved: ' + privacyError.message, 'warning'); return; }
            document.getElementById('post-form').reset();
            loadPosts(1);
        } else showAlert(data.message || 'Unable to create post.', 'danger');
    } catch (error) {
        console.error('Error creating post:', error);
        showAlert(error.message || 'Error creating post.', 'danger');
    } finally {
        if (submit) { submit.disabled = false; submit.innerHTML = originalText; }
    }
}
async function editPost(post) {
    const modal=document.getElementById('postEditModal'); const body=document.getElementById('post-edit-body'); const error=document.getElementById('post-edit-error'); error.textContent=''; if(!window.postEditPrivacyWidget){window.postEditPrivacyWidget=privacyComponent('post-edit',currentUser.id);document.getElementById('post-edit-privacy').appendChild(window.postEditPrivacyWidget);} body.value=post.body||''; window.postEditPrivacyWidget.querySelector('.privacy-type').value=post.privacy_level||'public'; await window.postEditPrivacyWidget.loadRule('post',post.id); bootstrap.Modal.getOrCreateInstance(modal).show(); document.getElementById('post-edit-save').onclick=async()=>{try{const response=await fetch(`${POSTS_API}/update.php`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrfToken},body:JSON.stringify({post_id:post.id,body:body.value.trim(),privacy_level:window.postEditPrivacyWidget.getRule().visibility_type})});const data=await response.json();if(!data.success)throw new Error(data.message||'Unable to update post.');await savePrivacyRule(csrfToken,'post',post.id,window.postEditPrivacyWidget.getRule());bootstrap.Modal.getInstance(modal).hide();loadPosts(postPage);}catch(e){error.textContent=e.message;}};
}

async function deletePost(postId) {
    if (!confirm('Delete this post?')) return;
    try {
        const response = await fetch(`${POSTS_API}/delete.php`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken }, body: JSON.stringify({ post_id: postId }) });
        const data = await response.json();
        if (data.success) loadPosts(postPage); else showAlert(data.message || 'Unable to delete post.', 'danger');
    } catch (error) { showAlert('Error deleting post.', 'danger'); }
}

async function loadPostComments(postId) {
    const container = document.querySelector(`[data-post-comments="${postId}"]`);
    if (!container) return;
    container.innerHTML = '<div class="small text-muted">Loading comments...</div>';
    try {
        const response = await fetch(`${POSTS_API}/comments/list.php?post_id=${postId}&limit=20`);
        const data = await response.json();
        if (!data.success) { container.innerHTML = '<div class="small text-danger">Unable to load comments.</div>'; return; }
        renderPostComments(container, postId, data.data.comments, data.data.pagination.total_items);
    } catch (error) { container.innerHTML = '<div class="small text-danger">Unable to load comments.</div>'; }
}

async function renderPostComments(container, postId, comments, total) {
    container.innerHTML = '';
    container.appendChild(el('div', 'small fw-semibold text-muted mb-2', `Comments (${total})`));
    const list = el('div', 'post-comments-list');
    comments.forEach(comment => list.appendChild(createPostComment(comment, postId)));
    if (!comments.length) list.appendChild(el('div', 'small text-muted mb-2', 'No comments yet.'));
    container.appendChild(list);

    if (currentUser) {
        if (!csrfToken && typeof loadCsrfToken === 'function') await loadCsrfToken();
        if (!csrfToken) return;
        const form = el('form', 'post-comment-form mt-2');
        const group = el('div', 'post-comment-composer');
        const input = document.createElement('input');
        input.className = 'form-control'; input.placeholder = 'Write a comment...'; input.maxLength = 2000; input.required = true;
        const emojiButton = el('button', 'btn btn-light post-comment-emoji', '\uD83D\uDE0A'); emojiButton.type = 'button'; emojiButton.title = 'Add emoji';
        const button = el('button', 'btn btn-primary', 'Post'); button.type = 'submit';
        group.appendChild(input); group.appendChild(emojiButton); group.appendChild(button); form.appendChild(group);
        initInlineEmojiPicker(emojiButton, input);
        form.addEventListener('submit', e => submitPostComment(e, postId, input));
        container.appendChild(form);
    }
}

function createPostComment(comment, postId) {
    const row = el('div', 'd-flex gap-2 mb-2');
    const img = el('img', 'rounded-circle flex-shrink-0');
    img.src = postAuthorPhoto(comment.author_profile_photo); img.alt = ''; img.style.width = '32px'; img.style.height = '32px'; img.style.objectFit = 'cover';
    const body = el('div', 'flex-grow-1');
    const bubble = el('div', 'post-comment-bubble');
    const meta = el('div', 'd-flex justify-content-between gap-2');
    meta.appendChild(el('strong', 'small', comment.author_name || 'Deleted user'));
    meta.appendChild(el('small', 'text-muted', new Date(comment.created_at).toLocaleString()));
    const text = el('div', 'small post-comment-text'); text.textContent = comment.comment_text;
    bubble.appendChild(meta); bubble.appendChild(text); body.appendChild(bubble);
    if (comment.can_edit || comment.can_delete) {
        const actions = el('div', 'small mt-1');
        if (comment.can_edit) { const edit = el('button', 'btn btn-link btn-sm p-0 me-2', 'Edit'); edit.type = 'button'; edit.addEventListener('click', () => editPostComment(comment, postId)); actions.appendChild(edit); }
        if (comment.can_delete) { const del = el('button', 'btn btn-link btn-sm p-0 text-danger', 'Delete'); del.type = 'button'; del.addEventListener('click', () => deletePostComment(comment.id, postId)); actions.appendChild(del); }
        body.appendChild(actions);
    }
    row.appendChild(img); row.appendChild(body); return row;
}

async function submitPostComment(event, postId, input) {
    event.preventDefault();
    const text = input.value.trim(); if (!text) return;
    const response = await fetch(`${POSTS_API}/comments/create.php`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken }, body: JSON.stringify({ post_id: postId, comment_text: text }) });
    const data = await response.json();
    if (data.success) { input.value = ''; loadPostComments(postId); } else showAlert(data.message || 'Unable to comment.', 'danger');
}

async function editPostComment(comment, postId) {
    const text = prompt('Edit comment:', comment.comment_text); if (text === null) return;
    const response = await fetch(`${POSTS_API}/comments/update.php`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken }, body: JSON.stringify({ comment_id: comment.id, comment_text: text }) });
    const data = await response.json();
    if (data.success) loadPostComments(postId); else showAlert(data.message || 'Unable to edit comment.', 'danger');
}

async function deletePostComment(commentId, postId) {
    if (!confirm('Delete this comment?')) return;
    const response = await fetch(`${POSTS_API}/comments/delete.php`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken }, body: JSON.stringify({ comment_id: commentId }) });
    const data = await response.json();
    if (data.success) loadPostComments(postId); else showAlert(data.message || 'Unable to delete comment.', 'danger');
}

function renderPostMediaTabs(posts) {
    const photos = document.getElementById('photos-container');
    const videos = document.getElementById('videos-container');
    if (!photos || !videos) return;
    photos.innerHTML = ''; videos.innerHTML = '';
    const media = posts.flatMap(post => (post.media || []).map(item => ({ ...item, post })));
    const photoItems = media.filter(item => item.media_type === 'image');
    const videoItems = media.filter(item => item.media_type === 'video');
    renderMediaGrid(photos, photoItems, 'No post photos yet.');
    renderMediaGrid(videos, videoItems, 'No post videos yet.');
    if (typeof renderMemoryVideoTab === 'function') renderMemoryVideoTab();
}

function renderMediaGrid(container, items, emptyText) {
    if (!items.length) { container.appendChild(el('p', 'text-muted', emptyText)); return; }
    items.forEach(item => {
        const col = el('div', 'col-md-6');
        col.appendChild(createPostMedia(item));
        container.appendChild(col);
    });
}

function initPostsFeatureWhenReady(attempt = 0) {
    const hasProfile = typeof profileUserId !== 'undefined' && profileUserId;
    const sessionKnown = typeof currentUser !== 'undefined';

    if (!hasProfile || !sessionKnown) {
        if (attempt < 30) {
            setTimeout(() => initPostsFeatureWhenReady(attempt + 1), 150);
        }
        return;
    }

    initPostsFeature();
}

document.addEventListener('DOMContentLoaded', () => {
    initPostsFeatureWhenReady();
});

