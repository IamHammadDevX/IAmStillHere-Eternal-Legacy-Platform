const API_BASE = "http://localhost/IAmStillHere/backend/family";
const AUTH_CHECK = "http://localhost/IAmStillHere/backend/auth/check_session.php";
const USER_LOOKUP = "http://localhost/IAmStillHere/backend/users/find.php";
const PROFILE_URL_BASE = "http://localhost/IAmStillHere/frontend/profile.php?user_id=";
const PHOTO_URL_BASE = "http://localhost/IAmStillHere/data/uploads/photos/";

// profileUserId is already declared in profile.js
let loggedInUser = null; // set after check_session
let familyMembersCache = [];
let familyTreeCache = null;
let familyViewMode = ['grid', 'list', 'tree'].includes(localStorage.getItem('familyViewMode')) ? localStorage.getItem('familyViewMode') : 'grid';

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

async function checkSession() {
    try {
        const res = await fetch(AUTH_CHECK);
        const data = await res.json();
        if (data.logged_in) {
            loggedInUser = data.user; // { id, username, full_name, role }
        } else {
            loggedInUser = null;
        }
    } catch (err) {
        console.error("Session check failed:", err);
        loggedInUser = null;
    }
}

// show/hide add form depending on whether logged-in user owns the profile
function updateAddFormVisibility() {
    const addForm = document.getElementById('add-family-form');
    if (!addForm) return;
    if (!profileUserId) {
        addForm.style.display = 'none';
        return;
    }
    if (loggedInUser && String(loggedInUser.id) === String(profileUserId)) {
        addForm.style.display = 'block';
    } else {
        addForm.style.display = 'none';
    }
}

function getMemberName(member) {
    return member.member_name || member.full_name || member.name || 'Unknown';
}

function getMemberPhoto(member) {
    return member.member_picture || 'default-profile.png';
}

function getMemberProfileUrl(member) {
    return `${PROFILE_URL_BASE}${encodeURIComponent(member.family_member_id)}`;
}

function getRelativeTime(dateString) {
    if (!dateString) return '';

    const date = new Date(String(dateString).replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) return '';

    const seconds = Math.max(0, Math.floor((Date.now() - date.getTime()) / 1000));
    if (seconds < 60) return 'just now';

    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `${minutes}m ago`;

    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours}h ago`;

    const days = Math.floor(hours / 24);
    if (days < 30) return `${days}d ago`;

    const months = Math.floor(days / 30);
    if (months < 12) return `${months}mo ago`;

    const years = Math.floor(months / 12);
    return `${years}y ago`;
}

function createActivityElement(member, compact = false) {
    const wrapper = document.createElement('div');
    wrapper.className = compact ? 'family-activity family-activity-compact' : 'family-activity mt-2';

    if (member.recent_activity) {
        const badge = document.createElement('span');
        badge.className = 'badge bg-success-subtle text-success border border-success-subtle family-recent-badge';
        badge.textContent = 'Recently updated';
        wrapper.appendChild(badge);
    }

    if (member.latest_activity_label && member.latest_activity_at) {
        const detail = document.createElement('div');
        detail.className = 'text-muted small mt-1';
        detail.textContent = `${member.latest_activity_label} ${getRelativeTime(member.latest_activity_at)}`.trim();
        wrapper.appendChild(detail);
    }

    return wrapper;
}

function canRemoveFamilyMember() {
    return loggedInUser && (String(loggedInUser.id) === String(profileUserId) || loggedInUser.role === 'admin');
}

function createRemoveButton(member, memberName, extraClass = '') {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = `btn btn-danger btn-sm ${extraClass}`.trim();
    button.dataset.familyId = member.family_member_id;
    button.title = `Remove ${memberName}`;

    const icon = document.createElement('i');
    icon.className = 'bi bi-x';
    button.appendChild(icon);

    button.addEventListener('click', (e) => {
        e.stopPropagation();
        removeFamilyMember(member.family_member_id);
    });

    return button;
}

function createGridMember(member) {
    const memberName = getMemberName(member);
    const memberPhoto = getMemberPhoto(member);

    const memberItem = document.createElement('div');
    memberItem.className = 'text-center position-relative family-grid-member';

    const imageWrap = document.createElement('div');
    imageWrap.className = 'position-relative d-inline-block';

    const imageLink = document.createElement('a');
    imageLink.href = getMemberProfileUrl(member);
    imageLink.style.textDecoration = 'none';
    imageLink.style.color = 'inherit';

    const image = document.createElement('img');
    image.src = `${PHOTO_URL_BASE}${encodeURIComponent(memberPhoto)}`;
    image.alt = memberName;
    image.className = 'rounded-circle border border-3 border-light shadow-sm family-member-avatar';
    image.title = memberName;

    imageLink.appendChild(image);
    imageWrap.appendChild(imageLink);

    if (canRemoveFamilyMember()) {
        imageWrap.appendChild(createRemoveButton(member, memberName, 'rounded-circle position-absolute family-grid-remove-btn'));
    }

    const details = document.createElement('div');
    details.className = 'mt-2';

    const nameLink = document.createElement('a');
    nameLink.href = getMemberProfileUrl(member);
    nameLink.style.textDecoration = 'none';
    nameLink.style.color = 'inherit';

    const name = document.createElement('p');
    name.className = 'mb-0 fw-semibold small text-truncate';
    name.style.maxWidth = '120px';
    name.title = memberName;
    name.textContent = memberName;
    nameLink.appendChild(name);

    const relationship = document.createElement('p');
    relationship.className = 'mb-0 text-muted';
    relationship.style.fontSize = '0.75rem';
    relationship.textContent = member.relationship || 'Family';

    details.appendChild(nameLink);
    details.appendChild(relationship);

    memberItem.appendChild(imageWrap);
    memberItem.appendChild(details);

    return memberItem;
}

function createListMember(member) {
    const memberName = getMemberName(member);
    const memberPhoto = getMemberPhoto(member);

    const row = document.createElement('div');
    row.className = 'family-list-member d-flex align-items-center gap-2 py-2 px-3 border-bottom';

    const imageLink = document.createElement('a');
    imageLink.href = getMemberProfileUrl(member);
    imageLink.className = 'flex-shrink-0';

    const image = document.createElement('img');
    image.src = `${PHOTO_URL_BASE}${encodeURIComponent(memberPhoto)}`;
    image.alt = memberName;
    image.className = 'rounded-circle family-list-avatar';
    image.title = memberName;
    imageLink.appendChild(image);

    const body = document.createElement('div');
    body.className = 'flex-grow-1 min-width-0';

    const topLine = document.createElement('div');
    topLine.className = 'd-flex flex-column flex-sm-row align-items-sm-center gap-1 gap-sm-2';

    const nameLink = document.createElement('a');
    nameLink.href = getMemberProfileUrl(member);
    nameLink.className = 'fw-semibold text-decoration-none text-dark text-truncate';
    nameLink.textContent = memberName;

    const relationship = document.createElement('span');
    relationship.className = 'text-muted small';
    relationship.textContent = member.relationship || 'Family';

    topLine.appendChild(nameLink);
    topLine.appendChild(relationship);
    body.appendChild(topLine);

    row.appendChild(imageLink);
    row.appendChild(body);

    if (canRemoveFamilyMember()) {
        row.appendChild(createRemoveButton(member, memberName, 'family-list-remove-btn'));
    }

    return row;
}

function createTreePersonCard(node, relationship = '') {
    const card = document.createElement('div');
    card.className = 'family-tree-person';

    const link = document.createElement('a');
    link.href = node.profile_access && node.profile_url ? node.profile_url : '#';
    link.className = 'family-tree-person-link text-decoration-none text-dark';
    if (!node.profile_access) {
        link.addEventListener('click', (event) => event.preventDefault());
    }

    const image = document.createElement('img');
    image.className = 'rounded-circle family-tree-avatar';
    image.src = `${PHOTO_URL_BASE}${encodeURIComponent(node.profile_photo || 'default-profile.png')}`;
    image.alt = node.name || 'Family member';

    const name = document.createElement('div');
    name.className = 'fw-semibold family-tree-name';
    name.textContent = node.name || 'Unknown';
    name.title = node.name || 'Unknown';

    const rel = document.createElement('div');
    rel.className = 'text-muted family-tree-relationship';
    rel.textContent = relationship || 'Family';

    link.appendChild(image);
    link.appendChild(name);
    link.appendChild(rel);
    card.appendChild(link);

    return card;
}
function getTreeBranchLabel(group) {
    const labels = {
        grandparents: 'Grandparents',
        parents: 'Parents',
        partners: 'Spouse / Partner',
        siblings: 'Siblings',
        children: 'Children',
        grandchildren: 'Grandchildren',
        other: 'Other family'
    };
    return labels[group] || 'Family';
}

function collectVisibleTreeBranches(root) {
    const branches = root && root.branches ? root.branches : {};
    const orderedGroups = ['grandparents', 'parents', 'partners', 'siblings', 'children', 'grandchildren', 'other'];

    return orderedGroups
        .map(group => ({
            group,
            label: getTreeBranchLabel(group),
            members: (branches[group] || []).filter(member => !member.cycle)
        }))
        .filter(section => section.members.length > 0);
}

function createTreeSection(section) {
    const sectionEl = document.createElement('section');
    sectionEl.className = `family-tree-section family-tree-section-${section.group}`;

    const heading = document.createElement('div');
    heading.className = 'family-tree-section-title text-muted small fw-semibold text-uppercase';
    heading.textContent = section.label;
    sectionEl.appendChild(heading);

    const row = document.createElement('div');
    row.className = 'family-tree-row';

    section.members.forEach(member => {
        const memberWrap = document.createElement('div');
        memberWrap.className = 'family-tree-member-wrap';
        memberWrap.appendChild(createTreePersonCard(member, member.relationship || 'Family'));
        row.appendChild(memberWrap);
    });

    sectionEl.appendChild(row);
    return sectionEl;
}

function renderFamilyTree(treeData) {
    const list = document.getElementById('family-list');
    if (!list) return;

    list.innerHTML = '';

    if (!treeData || !treeData.root) {
        const empty = document.createElement('p');
        empty.className = 'text-muted';
        empty.textContent = 'No family tree available yet.';
        list.appendChild(empty);
        return;
    }

    const root = treeData.root;
    const sections = collectVisibleTreeBranches(root);

    const card = document.createElement('div');
    card.className = 'card shadow-sm family-tree-card';

    const body = document.createElement('div');
    body.className = 'card-body';

    const viewport = document.createElement('div');
    viewport.className = 'family-tree-scroll';

    const tree = document.createElement('div');
    tree.className = 'family-tree-compact';

    const rootArea = document.createElement('div');
    rootArea.className = 'family-tree-root-area';
    rootArea.appendChild(createTreePersonCard(root, 'Profile'));
    tree.appendChild(rootArea);

    if (sections.length === 0) {
        const empty = document.createElement('p');
        empty.className = 'text-muted text-center mb-0 mt-3';
        empty.textContent = 'No accepted family relationships yet.';
        tree.appendChild(empty);
    } else {
        const controls = document.createElement('div');
        controls.className = 'family-tree-controls text-center my-3';

        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'btn btn-sm btn-outline-secondary family-tree-toggle';
        toggle.textContent = `Collapse ${sections.reduce((total, section) => total + section.members.length, 0)}`;
        controls.appendChild(toggle);
        tree.appendChild(controls);

        const branchesWrap = document.createElement('div');
        branchesWrap.className = 'family-tree-sections';
        sections.forEach(section => branchesWrap.appendChild(createTreeSection(section)));
        tree.appendChild(branchesWrap);

        toggle.addEventListener('click', () => {
            const collapsed = branchesWrap.classList.toggle('d-none');
            toggle.textContent = collapsed ? `Expand ${sections.reduce((total, section) => total + section.members.length, 0)}` : `Collapse ${sections.reduce((total, section) => total + section.members.length, 0)}`;
        });
    }

    viewport.appendChild(tree);
    body.appendChild(viewport);
    card.appendChild(body);
    list.appendChild(card);
}

async function loadFamilyTree() {
    const list = document.getElementById('family-list');
    if (!list) return;

    if (!profileUserId) {
        list.innerHTML = '<p class="text-muted">No profile selected.</p>';
        return;
    }

    list.innerHTML = '<p class="text-muted">Loading family tree...</p>';

    try {
        const res = await fetch(`${API_BASE}/tree.php?user_id=${encodeURIComponent(profileUserId)}`);
        const data = await res.json();

        if (!data.success) {
            list.innerHTML = `<p class="text-danger">${data.message || 'Failed to load family tree'}</p>`;
            return;
        }

        familyTreeCache = data;
        renderFamilyTree(familyTreeCache);
    } catch (err) {
        console.error('Error loading family tree:', err);
        list.innerHTML = '<p class="text-danger">Error loading family tree</p>';
    }
}
function renderFamilyMembers(members) {
    if (familyViewMode === 'tree') {
        if (familyTreeCache) {
            renderFamilyTree(familyTreeCache);
        } else {
            loadFamilyTree();
        }
        return;
    }

    const list = document.getElementById('family-list');
    if (!list) return;

    list.innerHTML = '';

    if (members.length === 0) {
        const empty = document.createElement('p');
        empty.className = 'text-muted';
        empty.textContent = 'No family members added yet.';
        list.appendChild(empty);
        return;
    }

    const card = document.createElement('div');
    card.className = 'card shadow-sm p-4 family-members-card';
    card.style.minHeight = '200px';

    if (familyViewMode === 'list') {
        card.classList.add('p-0');
        const listWrap = document.createElement('div');
        listWrap.className = 'family-list-view';
        members.forEach(member => listWrap.appendChild(createListMember(member)));
        card.appendChild(listWrap);
    } else {
        const membersGrid = document.createElement('div');
        membersGrid.className = 'd-flex flex-wrap gap-4 justify-content-start align-items-start family-grid-view';
        members.forEach(member => membersGrid.appendChild(createGridMember(member)));
        card.appendChild(membersGrid);
    }

    list.appendChild(card);
}

function updateFamilyToggleButtons() {
    document.querySelectorAll('[data-family-view]').forEach(button => {
        const isActive = button.dataset.familyView === familyViewMode;
        button.classList.toggle('active', isActive);
        button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    });
}

function wireFamilyViewToggle() {
    document.querySelectorAll('[data-family-view]').forEach(button => {
        button.addEventListener('click', () => {
            familyViewMode = ['grid', 'list', 'tree'].includes(button.dataset.familyView) ? button.dataset.familyView : 'grid';
            localStorage.setItem('familyViewMode', familyViewMode);
            updateFamilyToggleButtons();
            if (familyViewMode === 'tree') {
                loadFamilyTree();
            } else {
                renderFamilyMembers(familyMembersCache);
            }
        });
    });
    updateFamilyToggleButtons();
}

async function loadFamilyMembers() {
    const list = document.getElementById('family-list');
    if (!list) return;
    list.innerHTML = '<p class="text-muted">Loading family members...</p>';

    if (!profileUserId) {
        list.innerHTML = '<p class="text-muted">No profile selected.</p>';
        return;
    }

    try {
        const res = await fetch(`${API_BASE}/find.php?user_id=${encodeURIComponent(profileUserId)}`);
        const data = await res.json();

        if (!data.success) {
            list.innerHTML = `<p class="text-danger">${data.message || 'Failed to load family members'}</p>`;
            return;
        }

        familyMembersCache = data.members || [];
        if (familyViewMode === 'tree') {
            await loadFamilyTree();
        } else {
            renderFamilyMembers(familyMembersCache);
        }
    } catch (err) {
        console.error('Error loading family members:', err);
        list.innerHTML = '<p class="text-danger">Error loading family members</p>';
    }
}

async function addFamilyMember() {
    const emailEl = document.getElementById('familyEmail');
    const relEl = document.getElementById('relationship');
    if (!emailEl || !relEl) return;

    const email = emailEl.value.trim();
    const relationship = relEl.value.trim();

    if (!email || !relationship) {
        showAlert('Please provide email and relationship', 'warning');
        return;
    }
    if (!profileUserId) {
        showAlert('Profile user not specified', 'danger');
        return;
    }

    try {
        // First, find the user by email to get their ID
        const lookupRes = await fetch(`${USER_LOOKUP}?email=${encodeURIComponent(email)}`);
        const lookupData = await lookupRes.json();

        if (!lookupData.success || !lookupData.user) {
            showAlert(lookupData.message || 'User not found with that email', 'danger');
            return;
        }

        const familyMemberId = lookupData.user.id;

        // Now add the family member using JSON format as expected by add.php
        const payload = {
            user_id: parseInt(profileUserId),
            family_member_id: parseInt(familyMemberId),
            relationship: relationship
        };

        const res = await fetch(`${API_BASE}/add.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const data = await res.json();
        if (data.success) {
            showAlert(data.message || 'Family member added', 'success');
            emailEl.value = '';
            relEl.value = '';
            await loadFamilyMembers();
        } else {
            showAlert(data.message || 'Failed to add family member', 'danger');
        }
    } catch (err) {
        console.error('Error adding family member:', err);
        showAlert('Error adding family member', 'danger');
    }
}

async function removeFamilyMember(familyMemberId) {
    if (!confirm('Remove this family member?')) return;

    const payload = {
        user_id: parseInt(profileUserId),
        family_member_id: parseInt(familyMemberId)
    };

    try {
        const res = await fetch(`${API_BASE}/remove.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const data = await res.json();
        if (data.success) {
            showAlert(data.message || 'Removed', 'success');
            await loadFamilyMembers();
        } else {
            showAlert(data.message || 'Failed to remove', 'danger');
        }
    } catch (err) {
        console.error('Error removing family member:', err);
        showAlert('Error removing family member', 'danger');
    }
}

function wireAddButton() {
    const btn = document.getElementById('btn-add-family');
    if (!btn) return;
    btn.addEventListener('click', (e) => {
        e.preventDefault();
        addFamilyMember();
    });
}

// Show pending requests count (optional enhancement)
async function loadPendingRequests() {
    if (!loggedInUser) return;

    try {
        const res = await fetch(`http://localhost/IAmStillHere/backend/family/pending_requests.php?user_id=${loggedInUser.id}`);
        const data = await res.json();

        if (data.success && data.count > 0) {
            // Show notification badge (you can add UI for this)
            console.log(`You have ${data.count} pending family requests`);
        }
    } catch (err) {
        console.error('Error loading pending requests:', err);
    }
}

async function initFamilyFeature() {
    await checkSession();
    updateAddFormVisibility();
    wireFamilyViewToggle();
    wireAddButton();
    await loadFamilyMembers();
}

document.addEventListener('DOMContentLoaded', initFamilyFeature);
