async function checkSession() {
    try {
        const response = await fetch('/backend/auth/check_session.php');
        const data = await response.json();
        
        if (data.logged_in) {
            document.getElementById('nav-login').style.display = 'none';
            document.getElementById('nav-register').style.display = 'none';
            document.getElementById('nav-dashboard').style.display = 'block';
            document.getElementById('nav-profile').style.display = 'block';
            document.getElementById('nav-logout').style.display = 'block';
            document.getElementById('username-display').textContent = data.user.full_name;
            
            if (data.user.role === 'admin') {
                document.getElementById('nav-admin').style.display = 'block';
            }
        } else {
            document.getElementById('nav-login').style.display = 'block';
            document.getElementById('nav-register').style.display = 'block';
            document.getElementById('nav-dashboard').style.display = 'none';
            document.getElementById('nav-profile').style.display = 'none';
            document.getElementById('nav-logout').style.display = 'none';
            document.getElementById('nav-admin').style.display = 'none';
        }
    } catch (error) {
        console.error('Session check failed:', error);
    }
}

async function logout() {
    try {
        const response = await fetch('/backend/auth/logout.php');
        const data = await response.json();
        
        if (data.success) {
            window.location.href = '/index.php';
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
