// Search functionality
let searchTimeout = null;

document.getElementById('search-input')?.addEventListener('input', function (e) {
    const searchTerm = e.target.value.trim();

    // Clear previous timeout
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    // Debounce search - wait 500ms after user stops typing
    searchTimeout = setTimeout(() => {
        if (searchTerm.length >= 2) {
            searchUsers(searchTerm);
        } else if (searchTerm.length === 0) {
            document.getElementById('search-results').innerHTML =
                '<p class="text-muted text-center">Enter a search term to find users</p>';
        }
    }, 500);
});

async function searchUsers(searchTerm) {
    const resultsContainer = document.getElementById('search-results');
    resultsContainer.innerHTML = '<p class="text-muted text-center"><i class="bi bi-hourglass-split"></i> Searching...</p>';
    
    try {
        // Use the new search endpoint for partial matching
        const response = await fetch(`http://localhost/IAmStillHere/backend/users/search.php?q=${encodeURIComponent(searchTerm)}`);
        const data = await response.json();
        
        if (data.success) {
            displaySearchResults(data.users || []);
        } else {
            resultsContainer.innerHTML = `<p class="text-danger text-center">${data.message}</p>`;
        }
        
    } catch (error) {
        console.error('Search error:', error);
        resultsContainer.innerHTML = '<p class="text-danger text-center">Search failed. Please try again.</p>';
    }
}

function displaySearchResults(users) {
    const resultsContainer = document.getElementById('search-results');

    // Filter out admin users
    users = users.filter(user => user.role !== 'admin');

    if (users.length === 0) {
        resultsContainer.innerHTML = '<p class="text-muted text-center">No users found</p>';
        return;
    }

    resultsContainer.innerHTML = '';

    users.forEach(user => {
        const resultItem = document.createElement('a');
        resultItem.href = `http://localhost/IAmStillHere/frontend/profile.php?user_id=${user.id}`;
        resultItem.className = 'list-group-item list-group-item-action';
        resultItem.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="bi bi-person-circle fs-2 me-3"></i>
                <div>
                    <h6 class="mb-0">${user.full_name || user.username}</h6>
                    <small class="text-muted">@${user.username}</small>
                    ${user.is_memorial ? '<span class="badge bg-secondary ms-2">Memorial</span>' : ''}
                </div>
            </div>
        `;
        resultsContainer.appendChild(resultItem);
    });
}