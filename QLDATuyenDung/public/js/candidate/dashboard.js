const API_BASE_URL = '';

// Load user data
function loadUserData() {
    const currentUser = localStorage.getItem('currentUser');
    if (currentUser) {
        const user = JSON.parse(currentUser);
        
        // Load profile data for all users (both candidate and recruiter)
        // Recruiter can see their profile in "Thông tin cá nhân" page
        loadProfileData(user);
    } else {
        // If no currentUser in localStorage, load from API
        loadUserFromAPI();
    }
}

// Load user data from API
function loadUserFromAPI() {
    fetch(`${API_BASE_URL}/users`)
        .then(response => response.json())
        .then(data => {
            if (data.users && data.users.length > 0) {
                // Find current user (assuming first candidate for now)
                const user = data.users.find(u => u.role === 'candidate') || data.users[0];
                
                // Save to localStorage
                localStorage.setItem('currentUser', JSON.stringify(user));
                
                // Load profile data for account
                loadProfileData(user);
            }
        })
        .catch(error => {
            console.error('Error loading user data:', error);
        });
}

// Load profile data for account display
function loadProfileData(user) {
    try {
        // Update profile overview
        const profileName = document.getElementById('profile-name');
        const profileEmail = document.getElementById('profile-email');
        const profilePhone = document.getElementById('profile-phone');
        const profileLocation = document.getElementById('profile-location');
        
        if (profileName) profileName.textContent = user.name || 'Chưa cập nhật';
        if (profileEmail) profileEmail.textContent = user.email || 'Chưa cập nhật';
        if (profilePhone) profilePhone.textContent = user.phone || 'Chưa cập nhật';
        if (profileLocation) profileLocation.textContent = user.location || 'Chưa cập nhật';
        
        // Update avatar
        const profileAvatarImg = document.getElementById('profile-avatar-img');
        const profileAvatarIcon = document.getElementById('profile-avatar-icon');
        
        if (user.avatar && user.avatar.trim() !== '') {
            if (profileAvatarImg) {
                profileAvatarImg.src = user.avatar;
                profileAvatarImg.style.display = 'block';
                profileAvatarImg.style.objectFit = 'cover';
            }
            if (profileAvatarIcon) {
                profileAvatarIcon.style.display = 'none';
            }
        } else {
            if (profileAvatarImg) {
                profileAvatarImg.style.display = 'none';
            }
            if (profileAvatarIcon) {
                profileAvatarIcon.style.display = 'block';
            }
        }
    
    // Calculate age from birth date
    if (user.birthDate) {
        const birthDate = new Date(user.birthDate);
        const today = new Date();
        const age = today.getFullYear() - birthDate.getFullYear();
        document.getElementById('profile-birthday').textContent = `${age} tuổi`;
    } else {
        document.getElementById('profile-birthday').textContent = 'Chưa cập nhật';
    }

    // Update skills
    const skillsContainer = document.getElementById('profile-skills');
    if (user.skills && user.skills.length > 0) {
        skillsContainer.innerHTML = user.skills.map(skill => 
            `<span class="skill-tag">${skill}</span>`
        ).join('');
    } else {
        skillsContainer.innerHTML = '<span class="skill-tag">Chưa có kỹ năng</span>';
    }

    // Update experience (simplified)
    if (user.experiences && user.experiences.length > 0) {
        const totalYears = user.experiences.reduce((total, exp) => {
            const start = new Date(exp.startDate);
            const end = exp.current ? new Date() : new Date(exp.endDate);
            const years = (end - start) / (1000 * 60 * 60 * 24 * 365);
            return total + years;
        }, 0);
        document.getElementById('profile-experience').textContent = `${Math.round(totalYears)} năm kinh nghiệm`;
    } else {
        document.getElementById('profile-experience').textContent = 'Chưa có kinh nghiệm';
    }

    // Update education
    if (user.educations && user.educations.length > 0) {
        const latestEducation = user.educations[user.educations.length - 1];
        document.getElementById('profile-education').textContent = latestEducation.school || 'Chưa cập nhật';
    } else {
        document.getElementById('profile-education').textContent = 'Chưa cập nhật';
    }

        // Load additional sections
        loadAccountExperiences(user);
        loadAccountEducations(user);
        loadAccountCertificates(user);
        loadAccountLanguages(user);
    } catch (error) {
        console.error('Error loading profile data:', error);
    }
}

// Load work experiences for account
function loadAccountExperiences(user) {
    try {
        const container = document.getElementById('dashboard-experiences');
        if (container) {
            if (user.experiences && user.experiences.length > 0) {
                const experiences = user.experiences.slice(0, 3); // Show only first 3
                container.innerHTML = experiences.map(exp => `
                    <div class="experience-item">
                        <div class="item-title">${exp.title || 'Chức vụ'}</div>
                        <div class="item-company">${exp.company || 'Tên công ty'}</div>
                        <div class="item-period">${exp.startDate || 'Tháng/Năm'} - ${exp.current ? 'Hiện tại' : (exp.endDate || 'Tháng/Năm')}</div>
                        ${exp.description ? `<div class="item-description">${exp.description}</div>` : ''}
                    </div>
                `).join('');
            } else {
                container.innerHTML = '<div class="empty-state">Chưa có kinh nghiệm làm việc</div>';
            }
        }
    } catch (error) {
        console.error('Error loading experiences:', error);
    }
}

// Load educations for account
function loadAccountEducations(user) {
    const container = document.getElementById('dashboard-educations');
    if (user.educations && user.educations.length > 0) {
        const educations = user.educations.slice(0, 3); // Show only first 3
        container.innerHTML = educations.map(edu => `
            <div class="education-item">
                <div class="item-title">${edu.degree || 'Bằng cấp'}</div>
                <div class="item-company">${edu.school || 'Tên trường'}</div>
                <div class="item-period">${edu.startDate || 'Tháng/Năm'} - ${edu.endDate || 'Tháng/Năm'}</div>
                ${edu.gpa ? `<div class="item-description">GPA: ${edu.gpa}</div>` : ''}
                ${edu.description ? `<div class="item-description">${edu.description}</div>` : ''}
            </div>
        `).join('');
    } else {
        container.innerHTML = '<div class="empty-state">Chưa có thông tin học vấn</div>';
    }
}

// Load certificates for account
function loadAccountCertificates(user) {
    const container = document.getElementById('dashboard-certificates');
    if (user.certificates && user.certificates.length > 0) {
        const certificates = user.certificates.slice(0, 3); // Show only first 3
        container.innerHTML = certificates.map(cert => `
            <div class="certificate-item">
                <div class="item-title">${cert.name || 'Tên chứng chỉ'}</div>
                <div class="item-company">${cert.issuer || 'Tổ chức cấp'}</div>
                <div class="item-period">${cert.date || 'Ngày cấp'}</div>
                ${cert.code ? `<div class="item-code">Mã: ${cert.code}</div>` : ''}
                ${cert.description ? `<div class="item-description">${cert.description}</div>` : ''}
            </div>
        `).join('');
    } else {
        container.innerHTML = '<div class="empty-state">Chưa có chứng chỉ</div>';
    }
}

// Load languages for account
function loadAccountLanguages(user) {
    const container = document.getElementById('dashboard-languages');
    if (user.languages && user.languages.length > 0) {
        const languages = user.languages.slice(0, 3); // Show only first 3
        container.innerHTML = languages.map(lang => `
            <div class="language-item">
                <div class="item-title">${lang.name || 'Ngôn ngữ'}</div>
                <div class="item-company">${lang.levelText || 'Trình độ'}</div>
                ${lang.certificate ? `<div class="item-period">Chứng chỉ: ${lang.certificate}</div>` : ''}
                ${lang.score ? `<div class="item-score">Điểm: ${lang.score}</div>` : ''}
            </div>
        `).join('');
    } else {
        container.innerHTML = '<div class="empty-state">Chưa có thông tin ngôn ngữ</div>';
    }
}

// Toggle user dropdown
function toggleUserDropdown() {
    // This would show a dropdown menu with logout option
    if (confirm('Bạn có muốn đăng xuất?')) {
        localStorage.removeItem('isLoggedIn');
        localStorage.removeItem('currentUser');
        localStorage.removeItem('favoriteJobs');
        window.location.href = '/';
    }
}

// Initialize account
document.addEventListener('DOMContentLoaded', function() {
    loadUserData();
});

// Refresh data when page becomes visible (when returning from profile)
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        loadUserData();
    }
});

// Also refresh when window gains focus
window.addEventListener('focus', function() {
    loadUserData();
});

// Listen for avatar updates from profile page
window.addEventListener('avatarUpdated', function() {
    loadUserData();
});
