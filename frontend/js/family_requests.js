let currentUserId = null;

async function init() {
    const response = await fetch('http://localhost/IAmStillHere/backend/auth/check_session.php');
    const data = await response.json();

    if (!data.logged_in) {
        window.location.href = 'login.php';
        return;
    }

    currentUserId = data.user.id;
    
    await loadReceivedRequests();
    await loadSentRequests();
}

async function loadReceivedRequests() {
    const container = document.getElementById('received-requests-container');
    
    try {
        const response = await fetch(`http://localhost/IAmStillHere/backend/family/pending_requests.php?user_id=${currentUserId}`);
        const data = await response.json();

        if (!data.success) {
            container.innerHTML = '<div class="alert alert-danger">Failed to load requests</div>';
            return;
        }

        document.getElementById('received-count').textContent = data.count || 0;

        if (data.requests.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <h5 class="mt-3 text-muted">No pending requests</h5>
                    <p class="text-muted">You don't have any family requests at the moment</p>
                </div>
            `;
            return;
        }

        container.innerHTML = '';
        
        data.requests.forEach(request => {
            const card = document.createElement('div');
            card.className = 'card mb-3 shadow-sm';
            
            const photoUrl = request.profile_photo 
                ? `http://localhost/IAmStillHere/data/uploads/photos/${request.profile_photo}`
                : 'http://localhost/IAmStillHere/data/uploads/photos/default-profile.png';
            
            const timeAgo = formatTimeAgo(request.created_at);
            const requester_id = request.requester_id;
            
            card.innerHTML = `
                <div class="card-body">
                    <div class="d-flex align-items-start">
                        <a href="http://localhost/IAmStillHere/frontend/profile.php?user_id=${requester_id}" style="text-decoration: none; color: inherit;">
                            <img src="${photoUrl}" 
                                alt="${request.requester_name}" 
                                class="rounded-circle me-3" 
                                style="width: 60px; height: 60px; object-fit: cover; border: 2px solid #dee2e6;">
                        </a>
                        <div class="flex-grow-1">
                            <a href="http://localhost/IAmStillHere/frontend/profile.php?user_id=${requester_id}" style="text-decoration: none; color: inherit;">
                                <h5 class="mb-1">${request.requester_name}</h5>
                            </a>
                            <p class="text-muted mb-2">
                                <i class="bi bi-person-badge"></i> Wants to add you as: 
                                <strong>${request.relationship}</strong>
                            </p>
                            <small class="text-muted">
                                <i class="bi bi-clock"></i> ${timeAgo}
                            </small>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-success" onclick="respondToRequest(${request.id}, 'accept')">
                                <i class="bi bi-check-lg"></i> Accept
                            </button>
                            <button class="btn btn-danger" onclick="respondToRequest(${request.id}, 'reject')">
                                <i class="bi bi-x-lg"></i> Decline
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            container.appendChild(card);
        });

    } catch (error) {
        console.error('Error loading received requests:', error);
        container.innerHTML = '<div class="alert alert-danger">Error loading requests</div>';
    }
}

async function loadSentRequests() {
    const container = document.getElementById('sent-requests-container');
    
    try {
        const response = await fetch(`http://localhost/IAmStillHere/backend/family/sent_requests.php?user_id=${currentUserId}`);
        const data = await response.json();

        if (!data.success) {
            container.innerHTML = '<div class="alert alert-danger">Failed to load sent requests</div>';
            return;
        }

        document.getElementById('sent-count').textContent = data.count || 0;

        if (data.requests.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <i class="bi bi-send display-1 text-muted"></i>
                    <h5 class="mt-3 text-muted">No sent requests</h5>
                    <p class="text-muted">You haven't sent any family requests yet</p>
                </div>
            `;
            return;
        }

        container.innerHTML = '';
        
        data.requests.forEach(request => {
            const card = document.createElement('div');
            card.className = 'card mb-3 shadow-sm';
            
            const photoUrl = request.profile_photo 
                ? `http://localhost/IAmStillHere/data/uploads/photos/${request.profile_photo}`
                : 'http://localhost/IAmStillHere/data/uploads/photos/default-profile.png';
            
            const timeAgo = formatTimeAgo(request.created_at);
            const recipient_id = request.user_id;
            
            let statusBadge = '';
            if (request.status === 'pending') {
                statusBadge = '<span class="badge bg-warning text-dark">Pending</span>';
            } else if (request.status === 'accepted') {
                statusBadge = '<span class="badge bg-success">Accepted</span>';
            } else {
                statusBadge = '<span class="badge bg-danger">Declined</span>';
            }
            
            card.innerHTML = `
                <div class="card-body">
                    <div class="d-flex align-items-start">
                        <a href="http://localhost/IAmStillHere/frontend/profile.php?user_id=${recipient_id}" style="text-decoration: none; color: inherit;">
                            <img src="${photoUrl}" 
                                alt="${request.user_name}" 
                                class="rounded-circle me-3" 
                                style="width: 60px; height: 60px; object-fit: cover; border: 2px solid #dee2e6;">
                        </a>
                        <div class="flex-grow-1">
                            <a href="http://localhost/IAmStillHere/frontend/profile.php?user_id=${recipient_id}" style="text-decoration: none; color: inherit;">
                                <h5 class="mb-1">${request.user_name} ${statusBadge}</h5>
                            </a>
                            <p class="text-muted mb-2">
                                <i class="bi bi-person-badge"></i> Relationship: 
                                <strong>${request.relationship}</strong>
                            </p>
                            <small class="text-muted">
                                <i class="bi bi-clock"></i> Sent ${timeAgo}
                            </small>
                        </div>
                        ${request.status === 'pending' ? `
                            <button class="btn btn-outline-danger btn-sm" onclick="cancelRequest(${request.id})">
                                <i class="bi bi-x-circle"></i> Cancel
                            </button>
                        ` : ''}
                    </div>
                </div>
            `;
            
            container.appendChild(card);
        });

    } catch (error) {
        console.error('Error loading sent requests:', error);
        container.innerHTML = '<div class="alert alert-danger">Error loading requests</div>';
    }
}

async function respondToRequest(requestId, action) {
    if (action === 'reject' && !confirm('Are you sure you want to decline this request?')) {
        return;
    }

    try {
        const response = await fetch('http://localhost/IAmStillHere/backend/family/respond_request.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                request_id: requestId,
                action: action
            })
        });

        const data = await response.json();

        if (data.success) {
            showAlert(data.message, 'success');
            await loadReceivedRequests();
        } else {
            showAlert(data.message, 'danger');
        }
    } catch (error) {
        console.error('Error responding to request:', error);
        showAlert('An error occurred. Please try again.', 'danger');
    }
}

async function cancelRequest(requestId) {
    if (!confirm('Cancel this family request?')) {
        return;
    }

    try {
        const response = await fetch('http://localhost/IAmStillHere/backend/family/cancel_request.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ request_id: requestId })
        });

        const data = await response.json();

        if (data.success) {
            showAlert('Request cancelled', 'success');
            await loadSentRequests();
        } else {
            showAlert(data.message, 'danger');
        }
    } catch (error) {
        console.error('Error cancelling request:', error);
        showAlert('An error occurred. Please try again.', 'danger');
    }
}

function formatTimeAgo(datetime) {
    const now = new Date();
    const past = new Date(datetime);
    const diffMs = now - past;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'just now';
    if (diffMins < 60) return `${diffMins} minute${diffMins > 1 ? 's' : ''} ago`;
    if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
    if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
    
    return past.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function showAlert(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
    alertDiv.style.zIndex = '9999';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alertDiv);
    setTimeout(() => alertDiv.remove(), 5000);
}

document.addEventListener('DOMContentLoaded', init);