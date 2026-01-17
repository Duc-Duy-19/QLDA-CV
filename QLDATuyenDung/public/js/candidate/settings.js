let currentTab = 'security';

// Show tab
function showTab(tabId) {
    // Hide all sections
    document.querySelectorAll('.settings-section').forEach(section => {
        section.classList.remove('active');
    });
    
    // Remove active class from all tabs
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected section
    document.getElementById(tabId).classList.add('active');
    
    // Add active class to selected tab
    event.target.classList.add('active');
    
    currentTab = tabId;
}

// Change password
function changePassword() {
    const newPassword = prompt('Nhập mật khẩu mới:');
    if (newPassword && newPassword.length >= 6) {
        alert('Mật khẩu đã được thay đổi thành công!');
    } else if (newPassword) {
        alert('Mật khẩu phải có ít nhất 6 ký tự!');
    }
}

// Manage sessions
function manageSessions() {
    alert('Tính năng quản lý phiên đăng nhập sẽ được triển khai!');
}

// Export data
function exportData() {
    alert('Tính năng xuất dữ liệu sẽ được triển khai!');
}

// Delete account
function deleteAccount() {
    if (confirm('Bạn có chắc chắn muốn xóa tài khoản? Hành động này không thể hoàn tác!')) {
        if (confirm('Xác nhận lần cuối: Bạn có thực sự muốn xóa tài khoản?')) {
            alert('Tài khoản đã được xóa!');
        }
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    // Load saved settings
    loadSettings();
});

// Load settings
function loadSettings() {
    const settings = JSON.parse(localStorage.getItem('userSettings') || '{}');
    
    // Apply settings to switches
    Object.keys(settings).forEach(key => {
        const element = document.getElementById(key);
        if (element) {
            element.checked = settings[key];
        }
    });
}

// Save settings
function saveSettings() {
    const settings = {};
    document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        settings[checkbox.id] = checkbox.checked;
    });
    
    localStorage.setItem('userSettings', JSON.stringify(settings));
}

// Add event listeners to switches
document.addEventListener('change', function(e) {
    if (e.target.type === 'checkbox') {
        saveSettings();
    }
});
