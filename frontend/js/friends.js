const FRIENDS_API = '/backend/friends';
const FRIEND_PHOTO_BASE = '/data/uploads/photos/';

function friendEl(tag, cls = '', text = '') {
  const node = document.createElement(tag);
  if (cls) node.className = cls;
  if (text) node.textContent = text;
  return node;
}

function friendPhoto(photo) {
  return photo ? `${FRIEND_PHOTO_BASE}${encodeURIComponent(photo)}` : '/frontend/images/default-profile.png';
}

async function friendEnsureCsrf() {
  if (!csrfToken && typeof loadCsrfToken === 'function') {
    await loadCsrfToken();
  }
}

async function friendPost(endpoint, payload) {
  await friendEnsureCsrf();
  const response = await fetch(`${FRIENDS_API}/${endpoint}.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken || '' },
    body: JSON.stringify(payload || {})
  });
  return response.json();
}

async function initFriendsFeature() {
  if (!currentUser || !profileUserId) return;
  await friendEnsureCsrf();
  await loadFriendStatus();
  await loadFriends();
  await loadFriendRequests();
}

async function loadFriendStatus() {
  const area = document.getElementById('friend-action-area');
  if (!area || !currentUser) return;

  area.textContent = '';
  if (String(currentUser.id) === String(profileUserId)) return;

  try {
    const response = await fetch(`${FRIENDS_API}/status.php?user_id=${encodeURIComponent(profileUserId)}`);
    const data = await response.json();
    if (data.success) renderFriendAction(area, data.data);
  } catch (error) {
    console.error('friend status failed', error);
  }
}

function makeFriendButton(label, cls, callback) {
  const button = friendEl('button', `btn btn-sm ${cls}`, label);
  button.type = 'button';
  button.addEventListener('click', callback);
  return button;
}

function renderFriendAction(area, status) {
  area.textContent = '';
  const state = status.state;

  if (state === 'none') {
    area.appendChild(makeFriendButton('Add Friend', 'btn-primary', async () => {
      await friendPost('send', { user_id: parseInt(profileUserId, 10) });
      await loadFriendStatus();
    }));
  }

  if (state === 'pending_sent') {
    area.appendChild(makeFriendButton('Pending', 'btn-secondary', async () => {
      await friendPost('cancel', { request_id: status.request_id });
      await loadFriendStatus();
    }));
  }

  if (state === 'pending_received') {
    area.appendChild(makeFriendButton('Accept', 'btn-success me-1', async () => {
      await friendPost('respond', { request_id: status.request_id, action: 'accept' });
      await loadFriendStatus();
      await loadFriends();
      await loadFriendRequests();
    }));
    area.appendChild(makeFriendButton('Reject', 'btn-outline-danger', async () => {
      await friendPost('respond', { request_id: status.request_id, action: 'reject' });
      await loadFriendStatus();
      await loadFriendRequests();
    }));
  }

  if (state === 'family') {
    area.appendChild(makeFriendButton('Family', 'btn-success', () => {}));
  }

  if (state === 'friends') {
    area.appendChild(makeFriendButton('Remove Friend', 'btn-outline-danger me-1', async () => {
      if (confirm('Remove friend?')) {
        await friendPost('remove', { user_id: parseInt(profileUserId, 10) });
        await loadFriendStatus();
        await loadFriends();
      }
    }));
    area.appendChild(makeFriendButton('Block', 'btn-danger', async () => {
      if (confirm('Block this user?')) {
        await friendPost('block', { user_id: parseInt(profileUserId, 10) });
        await loadFriendStatus();
        await loadFriends();
      }
    }));
  }

  if (state === 'blocked') {
    area.appendChild(friendEl('span', 'badge bg-danger', 'Blocked'));
  }
}

async function loadFriends() {
  const box = document.getElementById('friends-list');
  if (!box) return;

  box.textContent = '';
  box.appendChild(friendEl('p', 'text-muted', 'Loading friends...'));

  try {
    const response = await fetch(`${FRIENDS_API}/list.php?user_id=${encodeURIComponent(profileUserId)}&limit=20`);
    const data = await response.json();
    box.textContent = '';

    if (!data.success) {
      box.appendChild(friendEl('p', 'text-danger', data.message || 'Unable to load friends.'));
      return;
    }

    const friends = data.data.friends || [];
    if (!friends.length) {
      box.appendChild(friendEl('p', 'text-muted', 'No friends yet.'));
      return;
    }

    friends.forEach((friend) => box.appendChild(createFriendCard(friend)));
  } catch (error) {
    box.textContent = '';
    box.appendChild(friendEl('p', 'text-danger', 'Unable to load friends.'));
  }
}

function createFriendCard(friend) {
  const col = friendEl('div', 'col-6 col-md-4 text-center');
  const link = friendEl('a', 'text-decoration-none text-dark');
  link.href = `profile.php?user_id=${friend.id}`;

  const img = friendEl('img', 'rounded-circle shadow-sm mb-2');
  img.src = friendPhoto(friend.profile_photo);
  img.alt = friend.full_name || friend.username || 'Friend';
  img.style.width = '58px';
  img.style.height = '58px';
  img.style.objectFit = 'cover';

  const name = friendEl('div', 'small fw-semibold text-truncate', friend.full_name || friend.username || 'Friend');
  link.append(img, name);
  col.appendChild(link);
  return col;
}

async function loadFriendRequests() {
  const box = document.getElementById('friend-requests-container');
  if (!box || !currentUser || String(currentUser.id) !== String(profileUserId)) return;

  box.textContent = '';
  box.appendChild(friendEl('div', 'small text-muted', 'Loading requests...'));

  try {
    const response = await fetch(`${FRIENDS_API}/requests.php?type=incoming&limit=20`);
    const data = await response.json();
    box.textContent = '';

    if (!data.success) {
      box.textContent = data.message || 'Unable to load requests.';
      return;
    }

    const requests = data.data.requests || [];
    if (!requests.length) {
      box.appendChild(friendEl('div', 'small text-muted', 'No pending requests.'));
      return;
    }

    requests.forEach((request) => box.appendChild(createRequestRow(request)));
  } catch (error) {
    box.textContent = '';
    box.appendChild(friendEl('div', 'small text-danger', 'Unable to load requests.'));
  }
}

function createRequestRow(request) {
  const row = friendEl('div', 'd-flex align-items-center gap-2 border rounded p-2 mb-2');

  const img = friendEl('img', 'rounded-circle');
  img.src = friendPhoto(request.user.profile_photo);
  img.alt = request.user.full_name || request.user.username || 'Requester';
  img.style.width = '38px';
  img.style.height = '38px';
  img.style.objectFit = 'cover';

  const name = friendEl('div', 'flex-grow-1 small fw-semibold', request.user.full_name || request.user.username || 'Requester');
  const accept = makeFriendButton('Accept', 'btn-success', async () => {
    await friendPost('respond', { request_id: request.id, action: 'accept' });
    await loadFriendRequests();
    await loadFriends();
  });
  const reject = makeFriendButton('Reject', 'btn-outline-danger', async () => {
    await friendPost('respond', { request_id: request.id, action: 'reject' });
    await loadFriendRequests();
  });

  row.append(img, name, accept, reject);
  return row;
}

function initFriendsFeatureWhenReady(attempt = 0) {
  if (typeof profileUserId === 'undefined' || !profileUserId || typeof currentUser === 'undefined' || !currentUser) {
    if (attempt < 30) setTimeout(() => initFriendsFeatureWhenReady(attempt + 1), 150);
    return;
  }
  initFriendsFeature();
}

document.addEventListener('DOMContentLoaded', () => initFriendsFeatureWhenReady());