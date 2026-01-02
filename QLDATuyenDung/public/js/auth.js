function showAuthButtons() {
    const userActions = document.querySelector('#user-actions');
    const userMenu = document.querySelector('#user-menu');
    const upgradeSection = document.querySelector('#upgrade-section');
    if (userActions) {
        try {
            userActions.style.setProperty('display', 'inline-flex', 'important');
        } catch (e) {
            userActions.style.display = 'flex';
        }
    }
    if (userMenu) {
        try {
            userMenu.style.setProperty('display', 'none', 'important');
        } catch (e) {
            userMenu.style.display = 'none';
        }
    }
    // hide upgrade section for logged-out users
    if (upgradeSection) {
        try {
            upgradeSection.style.setProperty('display', 'none', 'important');
        } catch (e) {
            upgradeSection.style.display = 'none';
        }
    }
}

function showUserMenu(user) {
    const userActions = document.querySelector('#user-actions');
    const userMenu = document.querySelector('#user-menu');
    const userName = document.querySelector('#user-name');
    const userRole = document.querySelector('#user-role');

    if (userActions) {
        try {
            userActions.style.setProperty('display', 'none', 'important');
        } catch (e) {
            userActions.style.display = 'none';
        }
    }
    if (userMenu) {
        try {
            userMenu.style.setProperty('display', 'inline-flex', 'important');
        } catch (e) {
            userMenu.style.display = 'inline-flex';
        }
    }
    if (userName) userName.textContent = user.name || user.companyName || 'User';

    let roleText = user.role === 'admin' ? 'Quản trị viên' : (user.role === 'employer' ? 'Nhà tuyển dụng' : 'Ứng viên');
    if (userRole) userRole.textContent = roleText;
}

function checkAuthStatus() {
    const isLoggedIn = localStorage.getItem('isLoggedIn');
    const currentUserRaw = localStorage.getItem('currentUser');
    if (isLoggedIn === 'true' && currentUserRaw) {
        try {
            const user = JSON.parse(currentUserRaw);
            showUserMenu(user);
        } catch {
            showAuthButtons();
        }
    } else {
        showAuthButtons();
    }
}

async function logout() {
    const token = localStorage.getItem('authToken') || localStorage.getItem('token') || localStorage.getItem('access_token');
    if (token) {
        try {
            await fetch('/api/logout', {
                method: 'POST',
                headers: {
                    'Authorization': token.startsWith('Bearer ') ? token : ('Bearer ' + token),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });
        } catch (e) { console.warn('API logout failed', e); }
    }

    localStorage.removeItem('isLoggedIn');
    localStorage.removeItem('currentUser');
    localStorage.removeItem('authToken');
    localStorage.removeItem('token');
    localStorage.removeItem('access_token');
    // Không xóa persistentUserData để giữ lại dữ liệu đã chỉnh sửa
    // localStorage.removeItem('persistentUserData');
    showAuthButtons();
    // Redirect to server logout route to ensure session is invalidated
    window.location.href = '/logout';
}

document.addEventListener('DOMContentLoaded', checkAuthStatus);
