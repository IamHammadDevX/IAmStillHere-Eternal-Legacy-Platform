const POSTS_API = 'http://localhost/IAmStillHere/backend/posts';
let postPage = 1;
let postTotalPages = 1;

function postMediaUrl(media) {
    const folder = media.media_type === 'video' ? 'videos' : 'photos';
    return `http://localhost/IAmStillHere/data/uploads/${folder}/${encodeURIComponent(media.file_path)}`;
}

function postAuthorPhoto(photo) {
    return photo ? `http://localhost/IAmStillHere/data/uploads/photos/${encodeURIComponent(photo)}` : 'http://localhost/IAmStillHere/frontend/images/default-profile.png';
}

function el(tag, className = '', text = '') {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== '') node.textContent = text;
    return node;
}

function initPostsFeature() {
    const composer = document.getElementById('post-composer');
    if (composer && currentUser && String(currentUser.id) === String(profileUserId)) {
        composer.style.display = 'block';
    }

    document.getElementById('post-form')?.addEventListener('submit', submitPost);
    loadPosts(1);
}

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

    posts.forEach(post => container.appendChild(createPostCard(post)));
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
    meta.appendChild(el('small', 'text-muted', `${new Date(post.created_at).toLocaleString()} · ${post.privacy_level}`));

    const actions = el('div', 'dropdown');
    if (post.can_edit || post.can_delete) {
        const button = el('button', 'btn btn-sm btn-light');
        button.type = 'button';
        button.dataset.bsToggle = 'dropdown';
        button.textContent = '⋯';
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
        text.textContent = post.body;
        body.appendChild(text);
    }

    (post.media || []).forEach(media => body.appendChild(createPostMedia(media)));

    const comments = el('div', 'post-comments mt-3');
    comments.dataset.postComments = post.id;
    body.appendChild(comments);
    card.appendChild(body);
    loadPostComments(post.id);
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
    const form = new FormData();
    form.append('body', document.getElementById('post-body').value.trim());
    form.append('privacy_level', document.getElementById('post-privacy').value);
    form.append('csrf_token', csrfToken);
    const media = document.getElementById('post-media').files[0];
    if (media) form.append('media', media);

    try {
        const response = await fetch(`${POSTS_API}/create.php`, { method: 'POST', body: form });
        const data = await response.json();
        if (data.success) {
            document.getElementById('post-form').reset();
            loadPosts(1);
        } else {
            showAlert(data.message || 'Unable to create post.', 'danger');
        }
    } catch (error) {
        console.error('Error creating post:', error);
        showAlert('Error creating post.', 'danger');
    }
}

async function editPost(post) {
    const body = prompt('Edit post:', post.body);
    if (body === null) return;
    const privacy = prompt('Privacy: public, family, or private', post.privacy_level) || post.privacy_level;
    try {
        const response = await fetch(`${POSTS_API}/update.php`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken }, body: JSON.stringify({ post_id: post.id, body, privacy_level: privacy }) });
        const data = await response.json();
        if (data.success) loadPosts(postPage); else showAlert(data.message || 'Unable to update post.', 'danger');
    } catch (error) { showAlert('Error updating post.', 'danger'); }
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

function renderPostComments(container, postId, comments, total) {
    container.innerHTML = '';
    container.appendChild(el('div', 'small fw-semibold text-muted mb-2', `Comments (${total})`));
    const list = el('div', 'post-comments-list');
    comments.forEach(comment => list.appendChild(createPostComment(comment, postId)));
    if (!comments.length) list.appendChild(el('div', 'small text-muted mb-2', 'No comments yet.'));
    container.appendChild(list);

    if (currentUser && csrfToken) {
        const form = el('form', 'post-comment-form mt-2');
        const group = el('div', 'input-group input-group-sm');
        const input = document.createElement('input');
        input.className = 'form-control'; input.placeholder = 'Write a comment...'; input.maxLength = 2000; input.required = true;
        const button = el('button', 'btn btn-primary', 'Post'); button.type = 'submit';
        group.appendChild(input); group.appendChild(button); form.appendChild(group);
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
}

function renderMediaGrid(container, items, emptyText) {
    if (!items.length) { container.appendChild(el('p', 'text-muted', emptyText)); return; }
    items.forEach(item => {
        const col = el('div', 'col-md-6');
        col.appendChild(createPostMedia(item));
        container.appendChild(col);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    setTimeout(initPostsFeature, 300);
});
