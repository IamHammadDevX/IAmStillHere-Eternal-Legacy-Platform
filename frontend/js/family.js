
const API_BASE = "http://localhost/IAmStillHere/backend/family";
const AUTH_CHECK = "http://localhost/IAmStillHere/backend/auth/check_session.php";
const USER_LOOKUP = "http://localhost/IAmStillHere/backend/users/find.php";

// profileUserId is already declared in profile.js
let loggedInUser = null; // set after check_session

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

        const members = data.members || [];
        if (members.length === 0) {
            list.innerHTML = '<p class="text-muted">No family members added yet.</p>';
            return;
        }

        list.innerHTML = '';

        const card = document.createElement('div');
        card.className = 'card shadow-sm p-4';
        card.style.minHeight = '200px';

        const membersGrid = document.createElement('div');
        membersGrid.className = 'd-flex flex-wrap gap-4 justify-content-start align-items-start';

        members.forEach(member => {
            const canRemove = loggedInUser && (String(loggedInUser.id) === String(profileUserId) || loggedInUser.role === 'admin');

            const memberName = member.member_name || member.full_name || member.name || 'Unknown';
            const memberPhoto = member.member_picture || 'default-profile.png';

            const memberItem = document.createElement('div');
            memberItem.className = 'text-center position-relative';
            memberItem.style.width = '120px';

            memberItem.innerHTML = `
                <div class="position-relative d-inline-block">
                    <a href="http://localhost/IAmStillHere/frontend/profile.php?user_id=${member.family_member_id}" style="text-decoration: none; color: inherit;">
                        <img src="http://localhost/IAmStillHere/data/uploads/photos/${memberPhoto}" 
                            alt="${memberName}" 
                            class="rounded-circle border border-3 border-light shadow-sm" 
                            style="width: 100px; height: 100px; object-fit: cover; cursor: pointer;"
                            title="${memberName}">
                    </a>
                    ${canRemove ? `
                        <button class="btn btn-danger btn-sm rounded-circle position-absolute" 
                                data-family-id="${member.family_member_id}"
                                style="width: 28px; height: 28px; padding: 0; top: -5px; right: -5px; font-size: 14px; line-height: 1;"
                                title="Remove ${memberName}">
                            <i class="bi bi-x"></i>
                        </button>
                    ` : ''}
                </div>
                <div class="mt-2">
                    <a href="http://localhost/IAmStillHere/frontend/profile.php?user_id=${member.family_member_id}" style="text-decoration: none; color: inherit;">
                        <p class="mb-0 fw-semibold small text-truncate" style="max-width: 120px;" title="${memberName}">${memberName}</p>
                    </a>
                    <p class="mb-0 text-muted" style="font-size: 0.75rem;">${member.relationship || 'Family'}</p>
                </div>
            `;

            if (canRemove) {
                const removeBtn = memberItem.querySelector('button[data-family-id]');
                removeBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    removeFamilyMember(member.family_member_id);
                });
            }

            membersGrid.appendChild(memberItem);
        });

        card.appendChild(membersGrid);
        list.appendChild(card);
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
        // First, find the user by email to get their I
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
    wireAddButton();
    await loadFamilyMembers();
}

document.addEventListener('DOMContentLoaded', initFamilyFeature);