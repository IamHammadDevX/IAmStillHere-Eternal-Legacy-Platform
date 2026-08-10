async function checkSession() {
    try {
        const response = await fetch('http://localhost/IAmStillHere/backend/auth/check_session.php');
        const data = await response.json();
        
        if (data.logged_in) {
            document.getElementById('nav-login').style.display = 'none';
            document.getElementById('nav-register').style.display = 'none';
            document.getElementById('nav-dashboard').style.display = 'block';
            document.getElementById('nav-profile').style.display = 'block';
            document.getElementById('nav-logout').style.display = 'block';
            document.getElementById('username-display').textContent = data.user.full_name;
            setupNotificationBell();
            
            if (data.user.role === 'admin') {
                document.getElementById('nav-admin').style.display = 'block';
                document.getElementById('nav-dashboard').style.display = 'none';
                document.getElementById('username-display').style.display = 'none';
            }
        } else {
            document.getElementById('nav-login').style.display = 'block';
            document.getElementById('nav-register').style.display = 'block';
            document.getElementById('nav-dashboard').style.display = 'none';
            document.getElementById('nav-profile').style.display = 'none';
            document.getElementById('nav-logout').style.display = 'none';
            document.getElementById('nav-admin').style.display = 'none';
            removeNotificationBell();
        }
    } catch (error) {
        console.error('Session check failed:', error);
    }
}

async function logout() {
    try {
        const response = await fetch('http://localhost/IAmStillHere/backend/auth/logout.php');
        const data = await response.json();
        
        if (data.success) {
            window.location.href = 'http://localhost/IAmStillHere';
        }
    } catch (error) {
        console.error('Logout failed:', error);
    }
}

function showAlert(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show alert-custom`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

document.addEventListener('DOMContentLoaded', checkSession);

const NOTIFICATIONS_API = 'http://localhost/IAmStillHere/backend/notifications';
let notificationCsrfToken = null;

function notificationEl(tag, className = '', text = '') {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text) node.textContent = text;
    return node;
}

function removeNotificationBell() {
    const existing = document.getElementById('nav-notifications');
    if (existing) existing.remove();
}

function setupNotificationBell() {
    if (document.getElementById('nav-notifications')) {
        loadNotificationCount();
        return;
    }

    const navProfile = document.getElementById('nav-profile');
    if (!navProfile || !navProfile.parentNode) return;

    const item = notificationEl('li', 'nav-item dropdown');
    item.id = 'nav-notifications';

    const toggle = notificationEl('a', 'nav-link position-relative');
    toggle.href = '#';
    toggle.id = 'notificationDropdown';
    toggle.setAttribute('role', 'button');
    toggle.setAttribute('data-bs-toggle', 'dropdown');
    toggle.setAttribute('aria-expanded', 'false');

    const icon = notificationEl('i', 'bi bi-bell');
    const badge = notificationEl('span', 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger');
    badge.id = 'notification-count-badge';
    badge.style.display = 'none';
    badge.textContent = '0';
    toggle.append(icon, badge);

    const menu = notificationEl('div', 'dropdown-menu dropdown-menu-end shadow notification-dropdown p-0');
    menu.setAttribute('aria-labelledby', 'notificationDropdown');
    menu.style.minWidth = '0';

    const header = notificationEl('div', 'd-flex justify-content-between align-items-center px-3 py-2 border-bottom');
    header.appendChild(notificationEl('strong', '', 'Notifications'));
    const markAll = notificationEl('button', 'btn btn-link btn-sm p-0', 'Mark all read');
    markAll.type = 'button';
    markAll.addEventListener('click', async (event) => {
        event.preventDefault();
        event.stopPropagation();
        await markAllNotificationsRead();
    });
    header.appendChild(markAll);

    const list = notificationEl('div', 'notification-dropdown-list');
    list.id = 'notification-dropdown-list';
    list.style.maxHeight = '360px';
    list.style.overflowY = 'auto';

    const footer = notificationEl('a', 'dropdown-item text-center small py-2 border-top', 'View all notifications');
    footer.href = 'notifications.php';

    menu.append(header, list, footer);
    item.append(toggle, menu);
    navProfile.parentNode.insertBefore(item, navProfile);

    toggle.addEventListener('click', loadNotificationsDropdown);
    loadNotificationCount();
    initNotificationsPage();
}

async function notificationCsrf() {
    if (notificationCsrfToken) return notificationCsrfToken;
    try {
        const response = await fetch('http://localhost/IAmStillHere/backend/auth/csrf_token.php');
        const data = await response.json();
        notificationCsrfToken = data.success ? data.data.csrf_token : null;
    } catch (error) {
        notificationCsrfToken = null;
    }
    return notificationCsrfToken;
}

async function loadNotificationCount() {
    const badge = document.getElementById('notification-count-badge');
    if (!badge) return;
    try {
        const response = await fetch(`${NOTIFICATIONS_API}/count.php`);
        const data = await response.json();
        if (!data.success) return;
        const count = Number(data.data.unread_count || 0);
        badge.textContent = count > 99 ? '99+' : String(count);
        badge.style.display = count > 0 ? 'inline-block' : 'none';
    } catch (error) {
        console.error('Notification count failed:', error);
    }
}

async function loadNotificationsDropdown() {
    const list = document.getElementById('notification-dropdown-list');
    if (!list) return;
    list.textContent = '';
    list.appendChild(notificationEl('div', 'dropdown-item text-muted small', 'Loading notifications...'));

    try {
        const response = await fetch(`${NOTIFICATIONS_API}/list.php?limit=8`);
        const data = await response.json();
        list.textContent = '';
        if (!data.success) {
            list.appendChild(notificationEl('div', 'dropdown-item text-danger small', 'Unable to load notifications.'));
            return;
        }
        const notifications = data.data.notifications || [];
        if (!notifications.length) {
            list.appendChild(notificationEl('div', 'dropdown-item text-muted small', 'No notifications yet.'));
            return;
        }
        notifications.forEach((notification) => list.appendChild(renderNotificationItem(notification, true)));
    } catch (error) {
        list.textContent = '';
        list.appendChild(notificationEl('div', 'dropdown-item text-danger small', 'Unable to load notifications.'));
    }
}

function renderNotificationItem(notification, compact = false) {
    const link = notificationEl('a', compact ? 'dropdown-item py-2' : 'list-group-item list-group-item-action');
    link.href = notification.link || 'profile.php';
    if (!notification.is_read) link.classList.add('fw-semibold');

    const message = notificationEl('div', 'small notification-dropdown-message', `${notification.actor_name || 'Someone'} ${notification.message || ''}`.trim());
    const meta = notificationEl('div', 'text-muted small notification-dropdown-meta', new Date(notification.created_at).toLocaleString());
    link.append(message, meta);
    link.addEventListener('click', async (event) => {
        event.preventDefault();
        await markNotificationRead(notification.id);
        window.location.href = link.href;
    });

    return link;
}

async function markNotificationRead(notificationId) {
    const csrf = await notificationCsrf();
    if (!csrf) return;
    await fetch(`${NOTIFICATIONS_API}/mark_read.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify({ notification_id: notificationId })
    });
    await loadNotificationCount();
}

async function markAllNotificationsRead() {
    const csrf = await notificationCsrf();
    if (!csrf) return;
    await fetch(`${NOTIFICATIONS_API}/mark_all_read.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify({})
    });
    await loadNotificationCount();
    await loadNotificationsDropdown();
    await initNotificationsPage(true);
}

async function initNotificationsPage(force = false) {
    const pageList = document.getElementById('notifications-page-list');
    if (!pageList || (pageList.dataset.loaded === '1' && !force)) return;
    pageList.dataset.loaded = '1';
    pageList.textContent = '';
    pageList.appendChild(notificationEl('div', 'list-group-item text-muted', 'Loading notifications...'));

    try {
        const response = await fetch(`${NOTIFICATIONS_API}/list.php?limit=20`);
        const data = await response.json();
        pageList.textContent = '';
        if (!data.success) {
            pageList.appendChild(notificationEl('div', 'list-group-item text-danger', 'Unable to load notifications.'));
            return;
        }
        const notifications = data.data.notifications || [];
        if (!notifications.length) {
            pageList.appendChild(notificationEl('div', 'list-group-item text-muted', 'No notifications yet.'));
            return;
        }
        notifications.forEach((notification) => pageList.appendChild(renderNotificationItem(notification, false)));
    } catch (error) {
        pageList.textContent = '';
        pageList.appendChild(notificationEl('div', 'list-group-item text-danger', 'Unable to load notifications.'));
    }
}
