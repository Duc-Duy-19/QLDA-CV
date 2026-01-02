    <header class="header">
    <div class="container-inner">
        <div class="header-content">
            <div class="logo">
                <a href="{{ route('home') }}" style="text-decoration: none; color: inherit;">
                    <h1>WebCV</h1>
                </a>
            </div>
            <button class="mobile-nav-toggle" aria-label="Mở menu" onclick="toggleMainNav()">
                <i class="fas fa-bars"></i>
            </button>
            <nav class="nav">
                <ul>
                    <li><a href="{{ route('candidate.cv-builder') }}" class="nav-link">Tạo CV</a></li>
                    <li><a href="{{ route('companies') }}" class="nav-link">Công ty</a></li>
                </ul>
            </nav>
            <!-- user-actions moved below so when logged out the buttons appear where the profile/avatar sits -->
            <!-- Upgrade to Employer Button (only for candidates) -->
            <div class="upgrade-section" id="upgrade-section" style="display: none;">
                <button class="btn-upgrade" onclick="showUpgradeModal()">
                    <i class="fas fa-building"></i>
                    <span>Nâng cấp lên Nhà tuyển dụng</span>
                </button>
            </div>

            <div class="user-menu" id="user-menu" onmouseenter="showDropdown()" onmouseleave="hideDropdown()">
                <div class="user-info" style="cursor: pointer;">
                    <div class="user-avatar">
                        <img id="header-avatar-img" src="{{ asset('images/avatar.jpg') }}" alt="Avatar" style="display: none;">
                        <i class="fas fa-user" id="header-avatar-icon"></i>
                    </div>
                    <div class="user-details">
                        <span class="user-name" id="user-name"></span>
                        <span class="user-role" id="user-role"></span>
                    </div>
                </div>
                <div class="user-dropdown" onmouseenter="showDropdown()" onmouseleave="hideDropdown()">
                    <a href="{{ route('candidate.profile') }}" class="dropdown-item"><i class="fas fa-user"></i> Hồ sơ cá nhân</a>
                    <a href="{{ route('candidate.applications') }}" class="dropdown-item"><i class="fas fa-briefcase"></i> Việc làm đã ứng tuyển</a>
                    <a href="{{ route('candidate.favorites') }}" class="dropdown-item"><i class="fas fa-heart"></i> Việc làm yêu thích</a>
                    <a href="{{ route('candidate.cv.index') }}" class="dropdown-item"><i class="fas fa-file-alt"></i> Quản lý CV</a>
                    <hr>
                    <a href="#" class="dropdown-item" onclick="logout()"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
                </div>
            </div>
            <!-- moved login/register actions to be in the same area as user-menu so they occupy the same spot when user is logged out -->
            <div class="user-actions" id="user-actions" style="display: none;">
                <button class="btn-register" onclick="window.location.href='{{ route('register') }}'">Đăng ký</button>
                <button class="btn-login" onclick="window.location.href='{{ route('login') }}?from=button'">Đăng nhập</button>
            </div>
        </div>
    </div>
</header>

<div class="mobile-nav-backdrop" onclick="toggleMainNav()" aria-hidden="true"></div>

<script>
// Show user menu when logged in
function showUserMenu(user) {
    // Build header UI for logged in user
    
    const userActions = document.getElementById('user-actions');
    const userMenu = document.getElementById('user-menu');
    const upgradeSection = document.getElementById('upgrade-section');
    const userName = document.getElementById('user-name');
    const userRole = document.getElementById('user-role');
    const headerAvatarImg = document.getElementById('header-avatar-img');
    const headerAvatarIcon = document.getElementById('header-avatar-icon');
    
    if (userActions) {
        try {
            userActions.style.setProperty('display', 'none', 'important');
        } catch (e) {
            userActions.style.display = 'none';
        }
    }
    
    if (userMenu) {
        try {
            // ensure inline placement and override CSS !important if present
            userMenu.style.setProperty('display', 'inline-flex', 'important');
        } catch (e) {
            userMenu.style.display = 'inline-flex';
        }
    }
    
    // Show upgrade button only for candidates
    if (upgradeSection) {
    // upgradeSection is shown to candidates only
        
        // Hỗ trợ cả role tiếng Anh và tiếng Việt
        const isCandidate = user.role === 'candidate' || user.role === 'Ứng Viên' || user.role === 'Candidate';
        
        
        if (isCandidate) {
            // show inline-flex so it stays inline with the profile area
            try {
                upgradeSection.style.setProperty('display', 'inline-flex', 'important');
            } catch (e) {
                upgradeSection.style.display = 'inline-flex';
            }
        } else {
            try {
                upgradeSection.style.setProperty('display', 'none', 'important');
            } catch (e) {
                upgradeSection.style.display = 'none';
            }
        }
    } else {
        console.log('❌ upgradeSection not found!');
    }
    
    if (userName) {
        userName.textContent = user.name || user.companyName || 'User';
    }
    
    let roleText = '';
    switch(user.role) {
        case 'admin':
            roleText = 'Quản trị viên';
            break;
        case 'employer':
            roleText = 'Nhà tuyển dụng';
            break;
        case 'candidate':
            roleText = 'Ứng viên';
            break;
        default:
            roleText = user.role;
    }
    
    if (userRole) {
        userRole.textContent = roleText;
    }
    
    // Update avatar
    if (user.avatar && headerAvatarImg && headerAvatarIcon) {
        headerAvatarImg.src = user.avatar;
        headerAvatarImg.style.display = 'block';
        headerAvatarIcon.style.display = 'none';
    } else if (headerAvatarImg && headerAvatarIcon) {
        headerAvatarImg.style.display = 'none';
        headerAvatarIcon.style.display = 'block';
    }

    // Ensure dropdown reflects role immediately
    setHeaderDropdownForRole(user);
    // Build dropdown content depending on role (candidate vs employer)
    try {
        const dropdown = document.querySelector('.user-dropdown');
        if (dropdown) {
            const isEmployer = user.role === 'employer' || user.role === 'recruiter' || user.role === 'Nhà tuyển dụng';
            const candidateHtml = `
                <a href="{{ route('candidate.profile') }}" class="dropdown-item"><i class="fas fa-user"></i> Hồ sơ cá nhân</a>
                <a href="{{ route('candidate.applications') }}" class="dropdown-item"><i class="fas fa-briefcase"></i> Việc làm đã ứng tuyển</a>
                <a href="{{ route('candidate.favorites') }}" class="dropdown-item"><i class="fas fa-heart"></i> Việc làm yêu thích</a>
                <a href="{{ route('candidate.cv.index') }}" class="dropdown-item"><i class="fas fa-file-alt"></i> Quản lý CV</a>
                <hr>
                <a href="{{ route('chat.index') }}" class="dropdown-item"><i class="fas fa-comments"></i>Tin nhắn</a>
                <a href="#" class="dropdown-item" onclick="logout()"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
            `;

            const employerHtml = `
                <a href="{{ route('candidate.profile') }}" class="dropdown-item"><i class="fas fa-user"></i> Hồ sơ cá nhân</a>
                <a href="{{ route('company.info') }}" class="dropdown-item"><i class="fas fa-building"></i> Thông tin công ty</a>
                <a href="{{ route('employer.jobs') }}" class="dropdown-item"><i class="fas fa-briefcase"></i> Quản lý việc làm</a>
                <a href="{{ route('company.members') }}" class="dropdown-item"><i class="fas fa-users"></i> Quản lý thành viên</a>
                <a href="{{ route('employer.manage-applications') }}" class="dropdown-item"><i class="fas fa-file-alt"></i> Quản lý ứng tuyển</a>
                <hr>
                <a href="{{ route('candidate.notifications') }}" class="dropdown-item"><i class="fas fa-bell"></i> Thông báo</a>
                <hr>
                <a href="#" class="dropdown-item" onclick="logout()"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
            `;

            dropdown.innerHTML = isEmployer ? employerHtml : candidateHtml;
        }
    } catch (e) {
        console.warn('Failed to build user dropdown based on role', e);
    }
    
}

// Check login status and update header
function checkLoginStatus() {
    const currentUser = localStorage.getItem('currentUser');
    const isLoggedIn = localStorage.getItem('isLoggedIn') === 'true';
    
    const userActions = document.getElementById('user-actions');
    const userMenu = document.getElementById('user-menu');
    
    if (isLoggedIn && currentUser) {
        try {
            const user = JSON.parse(currentUser);
            // show user menu and ensure header dropdown matches role
            showUserMenu(user);
            // Update debug box if present
            try {
                const dbg = document.getElementById('user-debug-box');
                if (dbg) {
                    const e = document.getElementById('dbg-email');
                    const r = document.getElementById('dbg-role');
                    if (e) e.textContent = (user.email || user.name || '-');
                    if (r) r.textContent = (user.role || '-');
                    dbg.style.display = 'block';
                }
            } catch (err) { console.warn('Could not update debug box', err); }
            
            // If the logged-in user is an admin, ensure they land in the admin UI.
            try {
                const path = window.location.pathname || '/';
                if (user.role === 'admin' && !path.startsWith('/admin')) {
                    // Redirect admin to admin dashboard
                    window.location.href = '{{ route("admin.dashboard") }}';
                    return;
                }
            } catch (err) {
                console.error('Error while redirecting admin:', err);
            }
        } catch (error) {
            console.error('Error parsing user data:', error);
            showLoginButtons();
        }
    } else {
        showLoginButtons();
    }
}

// Show login buttons for non-logged in users
function showLoginButtons() {
    const userActions = document.getElementById('user-actions');
    const userMenu = document.getElementById('user-menu');
    const upgradeSection = document.getElementById('upgrade-section');
    
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
    // When not logged in, also hide the upgrade button (can't upgrade without account)
    if (upgradeSection) {
        try {
            upgradeSection.style.setProperty('display', 'none', 'important');
        } catch (e) {
            upgradeSection.style.display = 'none';
        }
    }
    console.log('👤 User not logged in, showing login buttons');
}

// Logout function
async function logout() {
    const token = localStorage.getItem('authToken');
    
    if (token) {
        try {
            // Gọi API logout để xóa token trên server
            await fetch('/api/logout', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });
        } catch (error) {
            console.error('Logout API error:', error);
        }
    }
    
    // Xóa dữ liệu local
    localStorage.removeItem('currentUser');
    localStorage.removeItem('isLoggedIn');
    localStorage.removeItem('authToken');
    localStorage.removeItem('favoriteJobs');
    // Không xóa persistentUserData để giữ lại dữ liệu đã chỉnh sửa
    // localStorage.removeItem('persistentUserData');
    
    // Redirect to server logout route (will clear session) then land on homepage
    window.location.href = '{{ route("logout") }}';
}

// Show dropdown on hover
function showDropdown() {
    const userMenu = document.getElementById('user-menu');
    const dropdown = userMenu.querySelector('.user-dropdown');
    // cancel any pending hide timer and show immediately
    if (window._headerDropdownHideTimer) {
        clearTimeout(window._headerDropdownHideTimer);
        window._headerDropdownHideTimer = null;
    }
    dropdown.classList.add('show');
}

// Hide dropdown when mouse leaves
function hideDropdown() {
    const userMenu = document.getElementById('user-menu');
    const dropdown = userMenu.querySelector('.user-dropdown');
    // Delay hide slightly to allow pointer moves from avatar -> dropdown
    if (window._headerDropdownHideTimer) clearTimeout(window._headerDropdownHideTimer);
    window._headerDropdownHideTimer = setTimeout(() => {
        try { dropdown.classList.remove('show'); } catch(e){}
        window._headerDropdownHideTimer = null;
    }, 180);
}

// Toggle user menu dropdown (for click events)
function toggleUserMenu() {
    const userMenu = document.getElementById('user-menu');
    const dropdown = userMenu.querySelector('.user-dropdown');
    
    if (dropdown.classList.contains('show')) {
        dropdown.classList.remove('show');
    } else {
        dropdown.classList.add('show');
    }
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const userMenu = document.getElementById('user-menu');
    const dropdown = userMenu.querySelector('.user-dropdown');
    
    if (!userMenu.contains(event.target)) {
        dropdown.classList.remove('show');
    }
});

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    checkLoginStatus();
    initJobDropdown();
});

// Toggle main navigation for mobile
function toggleMainNav() {
    const header = document.querySelector('.header');
    const backdrop = document.querySelector('.mobile-nav-backdrop');
    if (!header) return;
    header.classList.toggle('mobile-open');
    if (backdrop) {
        backdrop.classList.toggle('show');
    }
    // prevent body scroll when mobile menu open
    document.body.classList.toggle('mobile-nav-open');
}

// Close mobile nav when pressing Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const header = document.querySelector('.header');
        const backdrop = document.querySelector('.mobile-nav-backdrop');
        if (header && header.classList.contains('mobile-open')) {
            header.classList.remove('mobile-open');
            if (backdrop) backdrop.classList.remove('show');
            document.body.classList.remove('mobile-nav-open');
        }
    }
});

// Initialize job dropdown functionality
function initJobDropdown() {
    const dropdownToggle = document.querySelector('.dropdown-toggle');
    const dropdownMenu = document.querySelector('.jobs-dropdown');
    
    if (dropdownToggle && dropdownMenu) {
        // Show dropdown on hover
        dropdownToggle.addEventListener('mouseenter', function() {
            dropdownMenu.classList.add('show');
        });
        
        // Hide dropdown when mouse leaves
        dropdownToggle.addEventListener('mouseleave', function() {
            setTimeout(() => {
                if (!dropdownMenu.matches(':hover')) {
                    dropdownMenu.classList.remove('show');
                }
            }, 100);
        });
        
        // Keep dropdown open when hovering over it
        dropdownMenu.addEventListener('mouseenter', function() {
            dropdownMenu.classList.add('show');
        });
        
        // Hide dropdown when mouse leaves
        dropdownMenu.addEventListener('mouseleave', function() {
            dropdownMenu.classList.remove('show');
        });
    }
}


// Force show upgrade button for debugging
window.forceShowUpgradeButton = function() {
    console.log('🔧 Force showing upgrade button...');
    const upgradeSection = document.getElementById('upgrade-section');
    if (upgradeSection) {
        try {
            upgradeSection.style.setProperty('display', 'inline-flex', 'important');
        } catch (e) {
            upgradeSection.style.display = 'inline-flex';
        }
        upgradeSection.style.visibility = 'visible';
        console.log('✅ Force shown upgrade button');
    } else {
        console.log('❌ upgradeSection not found for force show');
    }
};

// Check upgrade button status
window.checkUpgradeButtonStatus = function() {
    const upgradeSection = document.getElementById('upgrade-section');
    if (upgradeSection) {
        console.log('🔍 upgradeSection status:');
        console.log('- display:', upgradeSection.style.display);
        console.log('- computed display:', window.getComputedStyle(upgradeSection).display);
        console.log('- visibility:', upgradeSection.style.visibility);
        console.log('- computed visibility:', window.getComputedStyle(upgradeSection).visibility);
    } else {
        console.log('❌ upgradeSection not found');
    }
};

// Test upgrade button functionality
window.testUpgradeButton = function() {
    console.log('🔧 Testing upgrade button...');
    const upgradeSection = document.getElementById('upgrade-section');
    const upgradeButton = upgradeSection?.querySelector('.btn-upgrade');
    
    if (upgradeButton) {
        console.log('✅ Upgrade button found');
        console.log('🔧 Testing click event...');
        upgradeButton.click();
    } else {
        console.log('❌ Upgrade button not found');
    }
};

// Also check immediately in case DOM is already loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', checkLoginStatus);
} else {
    checkLoginStatus();
}

// Also check when storage changes (for multi-tab sync)
window.addEventListener('storage', function(e) {
    if (e.key === 'currentUser' || e.key === 'isLoggedIn') {
        checkLoginStatus();
    }
});

// Ensure dropdown reflects role even when page doesn't call checkLoginStatus
function setHeaderDropdownForRole(user) {
    try {
        const dropdown = document.querySelector('.user-dropdown');
        if (!dropdown) return;

        let roleUser = user;
        if (!roleUser) {
            const current = localStorage.getItem('currentUser');
            if (current) roleUser = JSON.parse(current);
        }
        const isEmployer = roleUser && (roleUser.role === 'employer' || roleUser.role === 'recruiter' || roleUser.role === 'Nhà tuyển dụng');

        const candidateHtml = `
            <a href="{{ route('candidate.profile') }}" class="dropdown-item"><i class="fas fa-user"></i> Hồ sơ cá nhân</a>
            <a href="{{ route('candidate.applications') }}" class="dropdown-item"><i class="fas fa-briefcase"></i> Việc làm đã ứng tuyển</a>
            <a href="{{ route('candidate.favorites') }}" class="dropdown-item"><i class="fas fa-heart"></i> Việc làm yêu thích</a>
            <a href="{{ route('candidate.cv.index') }}" class="dropdown-item"><i class="fas fa-file-alt"></i> Quản lý CV</a>
            <hr>
            <a href="{{ route('chat.index') }}" class="dropdown-item"><i class="fas fa-comments"></i>Tin nhắn</a>
            <a href="#" class="dropdown-item" onclick="logout()"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
        `;

        const employerHtml = `
            <a href="{{ route('candidate.profile') }}" class="dropdown-item"><i class="fas fa-user"></i> Hồ sơ cá nhân</a>
            <a href="{{ route('company.info') }}" class="dropdown-item"><i class="fas fa-building"></i> Thông tin công ty</a>
            <a href="{{ route('employer.jobs') }}" class="dropdown-item"><i class="fas fa-briefcase"></i> Quản lý việc làm</a>
            <a href="{{ route('company.members') }}" class="dropdown-item"><i class="fas fa-users"></i> Quản lý thành viên</a>
            <a href="{{ route('employer.manage-applications') }}" class="dropdown-item"><i class="fas fa-file-alt"></i> Quản lý ứng tuyển</a>
            <hr>
            <a href="{{ route('chat.index') }}" class="dropdown-item"><i class="fas fa-comments"></i>QL Tin nhắn</a>
            <a href="{{ route('candidate.notifications') }}" class="dropdown-item"><i class="fas fa-bell"></i> Thông báo</a>
            <hr>
            <a href="#" class="dropdown-item" onclick="logout()"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
        `;

        dropdown.innerHTML = isEmployer ? employerHtml : candidateHtml;
    } catch (err) {
        // ignore
    }
}

// Run once on load to ensure header dropdown is correct on all pages
document.addEventListener('DOMContentLoaded', function() {
    setHeaderDropdownForRole();
});

// Listen for custom avatar update events
window.addEventListener('avatarUpdated', function() {
    console.log('Avatar updated event received');
    updateHeaderAvatar();
});

// Function to update header avatar from other pages
window.updateHeaderAvatar = function() {
    console.log('updateHeaderAvatar called');
    const currentUser = localStorage.getItem('currentUser');
    if (currentUser) {
        const user = JSON.parse(currentUser);
        console.log('User data in updateHeaderAvatar:', user);
        console.log('User avatar:', user.avatar);
        
        const headerAvatarImg = document.getElementById('header-avatar-img');
        const headerAvatarIcon = document.getElementById('header-avatar-icon');
        
        console.log('headerAvatarImg found:', !!headerAvatarImg);
        console.log('headerAvatarIcon found:', !!headerAvatarIcon);
        
        if (user.avatar && user.avatar.trim() !== '' && headerAvatarImg && headerAvatarIcon) {
            headerAvatarImg.src = user.avatar;
            headerAvatarImg.style.display = 'block';
            headerAvatarIcon.style.display = 'none';
            console.log('Updated header avatar:', user.avatar);
        } else if (headerAvatarImg && headerAvatarIcon) {
            headerAvatarImg.style.display = 'none';
            headerAvatarIcon.style.display = 'block';
            console.log('Using default avatar icon - no avatar or empty avatar');
        }
    } else {
        console.log('No currentUser found in localStorage');
    }
};

// Also check immediately in case DOM is already loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', checkLoginStatus);
} else {
    checkLoginStatus();
}

// Upgrade to Employer Modal
function showUpgradeModal() {
    const modal = document.getElementById('upgrade-employer-modal');
    if (modal) {
        modal.style.display = 'flex';
    } else {
        createUpgradeModal();
    }
}

function hideUpgradeModal() {
    const modal = document.getElementById('upgrade-employer-modal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function createUpgradeModal() {
    const modalHTML = `
        <div id="upgrade-employer-modal" class="upgrade-modal" style="display: flex;">
            <style>
                /* Scoped styles for upgrade modal buttons - refined to match form controls */
                #upgrade-employer-modal .upgrade-modal-content { max-width:760px; width:100%; }
                /* Align buttons to the left to match form layout in screenshot and keep a consistent gap */
                #upgrade-employer-modal .form-actions { display:flex; gap:14px; justify-content:flex-start; align-items:center; margin-top:16px; }

                /* Cancel button: neutral, same height as inputs, subtle border and smaller weight */
                #upgrade-employer-modal .btn-cancel {
                    background: transparent;
                    color: #374151;
                    border: 1px solid #e6eef0;
                    padding: 0 16px;
                    height:42px;
                    min-width:96px;
                    border-radius:8px;
                    cursor:pointer;
                    font-weight:600;
                    display:inline-flex;
                    align-items:center;
                    justify-content:center;
                    font-size:14px;
                    box-shadow:none;
                    transition:all .12s ease-in-out;
                }
                #upgrade-employer-modal .btn-cancel:hover { background:#fbfdff; transform:translateY(-1px); }

                /* Upgrade button: compact icon badge on the left, matching primary brand color */
                #upgrade-employer-modal .btn-upgrade-submit {
                    background: linear-gradient(90deg,#06b6d4 0%,#10b981 100%);
                    color: #ffffff;
                    border: none;
                    padding: 0 16px 0 10px; /* tighten horizontal padding to fit badge */
                    height:42px;
                    min-width:160px;
                    border-radius:8px;
                    cursor:pointer;
                    font-weight:700;
                    display:inline-flex;
                    align-items:center;
                    gap:10px;
                    box-shadow: 0 8px 24px rgba(16,185,129,0.10);
                    font-size:14px;
                    transition:transform .12s ease, box-shadow .12s ease;
                }

                /* icon wrapper: colored circular badge with small white icon to echo other UI elements */
                #upgrade-employer-modal .btn-upgrade-submit .icon-wrap {
                    display:inline-flex;
                    align-items:center;
                    justify-content:center;
                    width:30px;
                    height:30px;
                    background: rgba(255,255,255,0.14);
                    color:#fff;
                    border-radius:999px; /* circle */
                    box-shadow: none;
                    flex:0 0 30px;
                }
                #upgrade-employer-modal .btn-upgrade-submit .icon-wrap i { font-size:14px; }

                #upgrade-employer-modal .btn-upgrade-submit:hover { transform: translateY(-2px); box-shadow: 0 14px 34px rgba(16,185,129,0.14); }

                /* small screens: full-width stacked buttons, maintain order (cancel above upgrade) */
                @media (max-width:520px) {
                    #upgrade-employer-modal .form-actions { flex-direction:column; align-items:stretch; }
                    #upgrade-employer-modal .btn-cancel, #upgrade-employer-modal .btn-upgrade-submit { width:100%; }
                    #upgrade-employer-modal .btn-upgrade-submit { padding-left:14px; padding-right:14px; }
                }
                /* Close button: red circular button positioned top-right of the modal content */
                #upgrade-employer-modal .upgrade-modal-content { position: relative; }
                #upgrade-employer-modal .close-btn {
                    position: absolute;
                    top: 12px;
                    right: 12px;
                    background: #e74c3c; /* red */
                    color: #fff;
                    border: none;
                    width: 36px;
                    height: 36px;
                    border-radius: 6px; /* square with slight rounding */
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 18px;
                    cursor: pointer;
                    box-shadow: 0 6px 18px rgba(231,76,60,0.18);
                    line-height: 1;
                }
                #upgrade-employer-modal .close-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(231,76,60,0.22); }
            </style>
            <div class="upgrade-modal-content">
                <div class="upgrade-modal-header">
                    <h2><i class="fas fa-building"></i> Nâng cấp lên Nhà tuyển dụng</h2>
                    <button class="close-btn" onclick="hideUpgradeModal()" aria-label="Đóng" title="Đóng">&times;</button>
                </div>
                
                <div class="upgrade-modal-body">
                    <div class="upgrade-info">
                        <p>Chào mừng bạn đến với WebCV! Để nâng cấp lên tài khoản Nhà tuyển dụng, vui lòng cung cấp thông tin công ty của bạn.</p>
                    </div>
                    
                    <form id="upgrade-employer-form" class="upgrade-form">
                        <div class="form-group">
                            <label for="company_name">Tên công ty *</label>
                            <input type="text" id="company_name" name="company_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Địa chỉ công ty *</label>
                            <textarea id="address" name="address" rows="3" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Mô tả công ty *</label>
                            <textarea id="description" name="description" rows="4" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="website">Website</label>
                            <input type="url" id="website" name="website" placeholder="https://example.com">
    
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email công ty *</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Số điện thoại *</label>
                            <input type="tel" id="phone" name="phone" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="logo">Logo công ty</label>
                            <input type="file" id="logo" name="logo" accept="image/jpeg,image/png,image/jpg,image/gif">
                            <small style="color: #666; font-size: 12px;">Chọn file hình ảnh logo (jpg, png, gif) - tối đa 2MB</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Trạng thái hoạt động *</label>
                            <select id="status" name="status" required>
                                <option value="">Chọn trạng thái</option>
                                <option value="active">Đang hoạt động</option>
                                <option value="inactive">Tạm dừng</option>
                            </select>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn-cancel" onclick="hideUpgradeModal()">Hủy</button>
                            <button type="button" class="btn-upgrade-submit" id="btn-upgrade-submit" onclick="submitUpgradeForm()">
                                <span class="icon-wrap"><i class="fas fa-building"></i></span>
                                <span>Nâng cấp ngay</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    // Ensure the newly inserted form disables native HTML5 validation (prevent browser tooltips)
    const createdForm = document.getElementById('upgrade-employer-form');
    if (createdForm) {
        createdForm.setAttribute('novalidate', 'novalidate');
        createdForm.noValidate = true;
        // Add form submission handler
        createdForm.addEventListener('submit', handleUpgradeSubmit);
    } else {
        // fallback: attach if later available
        const tryAttach = () => {
            const f = document.getElementById('upgrade-employer-form');
            if (f) {
                f.setAttribute('novalidate', 'novalidate');
                f.noValidate = true;
                f.addEventListener('submit', handleUpgradeSubmit);
            } else {
                setTimeout(tryAttach, 200);
            }
        };
        tryAttach();
    }
}

async function handleUpgradeSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);

    // Client-side validation using the same messages as StoreCompanyRequest
    function validateCompanyForm(fd) {
        const errors = {};
        const get = (k) => (fd.get(k) === null ? '' : fd.get(k));

        const M = {
            company_name_required: 'Vui lòng nhập tên công ty.',
            company_name_max: 'Tên công ty không được vượt quá 255 ký tự.',
            address_required: 'Vui lòng nhập địa chỉ công ty.',
            address_max: 'Địa chỉ không được vượt quá 255 ký tự.',
            description_required: 'Vui lòng nhập mô tả công ty.',
            website_required: 'Vui lòng nhập địa chỉ Website của công ty.',
            website_invalid: 'Định dạng website không hợp lệ (ví dụ: https://example.com).',
            email_required: 'Vui lòng nhập email công ty.',
            email_invalid: 'Email công ty không đúng định dạng.',
            phone_required: 'Vui lòng nhập số điện thoại.',
            phone_max: 'Số điện thoại không được vượt quá 20 ký tự.',
            logo_mimes: 'Logo phải có định dạng: jpeg, png, jpg, hoặc gif.',
            logo_max: 'Logo không được vượt quá 2MB.',
            status_required: 'Vui lòng chọn trạng thái hoạt động.',
            status_in: 'Trạng thái không hợp lệ.'
        };

        // company_name
        const company_name = String(get('company_name') || '').trim();
        if (!company_name) errors.company_name = [M.company_name_required];
        else if (company_name.length > 255) errors.company_name = [M.company_name_max];

        // address
        const address = String(get('address') || '').trim();
        if (!address) errors.address = [M.address_required];
        else if (address.length > 255) errors.address = [M.address_max];

        // description
        const description = String(get('description') || '').trim();
        if (!description) errors.description = [M.description_required];

        // website: treat as required on client-side (server currently has nullable)
        const website = String(get('website') || '').trim();
        if (!website) {
            errors.website = [M.website_required];
        } else {
            try {
                const u = new URL(website);
                if (!u.protocol.startsWith('http')) throw new Error('protocol');
            } catch (err) {
                errors.website = [M.website_invalid];
            }
        }

        // email
        const email = String(get('email') || '').trim();
        if (!email) errors.email = [M.email_required];
        else {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) errors.email = [M.email_invalid];
        }

        // phone
        const phone = String(get('phone') || '').trim();
        if (!phone) errors.phone = [M.phone_required];
        else if (phone.length > 20) errors.phone = [M.phone_max];

        // logo (file)
        const logo = fd.get('logo');
        if (logo && logo.size && logo.size > 0) {
            const allowed = ['image/jpeg','image/png','image/jpg','image/gif'];
            if (!allowed.includes(logo.type)) errors.logo = [M.logo_mimes];
            else if (logo.size > 2 * 1024 * 1024) errors.logo = [M.logo_max];
        }

        // status
        const status = String(get('status') || '').trim();
        if (!status) errors.status = [M.status_required];
        else if (!['active','inactive'].includes(status)) errors.status = [M.status_in];

        return errors;
    }

    // run client-side validation and display errors under fields if any
    const clientErrors = validateCompanyForm(formData);
    if (Object.keys(clientErrors).length > 0) {
        // ensure summary element exists
        let summary = document.getElementById('form-errors-summary');
        const form = document.getElementById('upgrade-employer-form');
        if (!summary && form) {
            summary = document.createElement('div');
            summary.id = 'form-errors-summary';
            summary.style.display = 'none';
            summary.style.background = '#fff3f3';
            summary.style.border = '1px solid #f5c6cb';
            summary.style.color = '#8a1f1f';
            summary.style.padding = '10px';
            summary.style.marginBottom = '12px';
            summary.style.borderRadius = '4px';
            form.insertAdjacentElement('afterbegin', summary);
        }

        // helper to ensure per-field error container
        function ensureErrEl(field) {
            let el = document.getElementById('error-' + field);
            if (!el) {
                const input = document.getElementById(field);
                if (!input) return null;
                el = document.createElement('div');
                el.id = 'error-' + field;
                el.className = 'invalid-feedback';
                el.style.color = '#e3342f';
                el.style.fontSize = '13px';
                el.style.marginTop = '5px';
                input.insertAdjacentElement('afterend', el);
            }
            return el;
        }

        // clear previous
        document.querySelectorAll('.invalid-feedback').forEach(n => { n.style.display = 'none'; n.innerHTML = ''; });
        document.querySelectorAll('#upgrade-employer-form input, #upgrade-employer-form textarea, #upgrade-employer-form select').forEach(el => el.classList.remove('input-error'));

        const summaryMessages = [];
        Object.keys(clientErrors).forEach(field => {
            const errEl = ensureErrEl(field);
            if (errEl) { errEl.innerHTML = clientErrors[field].join('<br>'); errEl.style.display = 'block'; }
            else { summaryMessages.push(...clientErrors[field]); }
            const inputEl = document.getElementById(field);
            if (inputEl) inputEl.classList.add('input-error');
        });

        if (summaryMessages.length && summary) { summary.innerHTML = summaryMessages.map(m => `<div>${m}</div>`).join(''); summary.style.display = 'block'; }

        const first = document.querySelector('#upgrade-employer-form .input-error');
        if (first && typeof first.focus === 'function') first.focus();
        return; // prevent sending to server
    }

    // Validate website format trước khi gửi
    const website = formData.get('website');
    if (website && website.trim() !== '') {
        try {
            new URL(website);
            // Kiểm tra protocol
            if (!website.startsWith('http://') && !website.startsWith('https://')) {
                throw new Error('Invalid protocol');
            }
            
            // Backend regex rất strict, chỉ chấp nhận URL đơn giản
            // Chuyển đổi URL phức tạp thành domain đơn giản
            const url = new URL(website);
            const simpleUrl = `${url.protocol}//${url.hostname}`;
            formData.set('website', simpleUrl);
            console.log('Website simplified from', website, 'to', simpleUrl);
        } catch (error) {
            alert('Định dạng website không hợp lệ. Vui lòng nhập URL đúng định dạng (ví dụ: https://example.com)');
            return;
        }
    }
    
    // Validate email format
    const email = formData.get('email');
    if (email && email.trim() !== '') {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            alert('Định dạng email không hợp lệ.');
            return;
        }
    }
    
    // Validate logo file (nếu có)
    const logoFile = formData.get('logo');
    if (logoFile && logoFile.size > 0) {
        const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        if (!allowedTypes.includes(logoFile.type)) {
            alert('Định dạng logo không hợp lệ. Vui lòng chọn file hình ảnh (jpg, png, gif)');
            return;
        }
        if (logoFile.size > 2 * 1024 * 1024) { // 2MB
            alert('Kích thước logo quá lớn. Vui lòng chọn file nhỏ hơn 2MB');
            return;
        }
    }
    
    // Get current user
    const currentUser = JSON.parse(localStorage.getItem('currentUser') || '{}');
    
    // Kiểm tra user đã đăng nhập chưa (không cần token vì sử dụng session)
    if (!currentUser || !currentUser.id) {
        alert('Vui lòng đăng nhập lại!');
        window.location.href = '{{ route("login") }}';
        return;
    }
    
        try {
        // Debug: Log form data
        console.log('Form data being sent:');
        for (let [key, value] of formData.entries()) {
            console.log(key, value);
        }
            // Nếu ứng dụng use token-based auth (SPA), token thường được lưu trong localStorage.
            // Lấy token nếu có từ localStorage (nhiều key phổ biến được hỗ trợ).
            function findTokenFromLocalStorage() {
                const keys = ['token','auth_token','access_token','bearer_token','jwt_token','authToken'];
                for (let k of keys) {
                    const v = localStorage.getItem(k);
                    if (v) return v;
                }
                // Thử lấy từ currentUser
                const currentUserStr = localStorage.getItem('currentUser');
                if (currentUserStr) {
                    try {
                        const u = JSON.parse(currentUserStr);
                        return u.token || u.access_token || u.auth_token || u.authToken || null;
                    } catch (e) {
                        return null;
                    }
                }
                return null;
            }

            const foundToken = findTokenFromLocalStorage();
            let response;

            if (foundToken) {
                // Nếu có token -> gọi API route (sanctum/token) để xử lý
                console.log('Found auth token in localStorage, sending to API /api/companies with Bearer token');
                response = await fetch('/api/companies', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': (foundToken.startsWith('Bearer ') ? foundToken : ('Bearer ' + foundToken))
                    },
                    body: formData
                });
            } else {
                // Fallback: gửi tới route web sử dụng session (cookie)
                console.log('No token found, sending to web route /companies with session cookies');
                response = await fetch('{{ url("/companies") }}', {
                    method: 'POST',
                    credentials: 'same-origin', // include session cookie so web auth works
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: formData // Gửi FormData thay vì JSON
                });
            }
        
        console.log('Response status:', response.status);
        const data = await response.json();
        console.log('Response data:', data);
        
        if (!response.ok) {
            // Xử lý lỗi validation chi tiết: hiển thị ở mỗi trường nếu server trả về data.errors
            if (data && data.errors) {
                // helper giống ensureErrEl dùng để tạo div lỗi nếu cần
                function ensureErrElLocal(field) {
                    let el = document.getElementById('error-' + field);
                    if (!el) {
                        // try find input by id or name (several fallbacks)
                        let input = document.getElementById(field);
                        if (!input) {
                            input = document.querySelector(`#upgrade-employer-form [name="${field}"]`)
                                || document.querySelector(`#upgrade-employer-form [name^="${field}"]`)
                                || document.querySelector(`#upgrade-employer-form [name$="[${field}]"]`)
                                || document.querySelector(`#upgrade-employer-form [name*="${field}"]`)
                                || document.querySelector(`#upgrade-employer-form input[id*="${field}"]`)
                                || document.querySelector(`#upgrade-employer-form textarea[id*="${field}"]`)
                                || document.querySelector(`#upgrade-employer-form select[id*="${field}"]`);
                        }
                        if (!input) {
                            console.warn('ensureErrElLocal: input not found for field', field);
                            return null;
                        }
                        el = document.createElement('div');
                        el.id = 'error-' + field;
                        el.className = 'invalid-feedback';
                        el.style.color = '#e3342f';
                        el.style.fontSize = '13px';
                        el.style.marginTop = '5px';
                        input.insertAdjacentElement('afterend', el);
                    }
                    return el;
                }

                // clear previous
                document.querySelectorAll('#upgrade-employer-form .invalid-feedback').forEach(n => { n.style.display = 'none'; n.innerHTML = ''; });
                document.querySelectorAll('#upgrade-employer-form input, #upgrade-employer-form textarea, #upgrade-employer-form select').forEach(el => el.classList.remove('input-error'));

                const summary = document.getElementById('form-errors-summary');
                if (summary) { summary.style.display = 'none'; summary.innerHTML = ''; }

                const summaryMessages = [];
                Object.keys(data.errors).forEach(field => {
                    // Laravel often returns keys like 'website' or 'website.url' - normalize
                    const base = String(field).split('.')[0];
                    const messages = data.errors[field] || data.errors[base] || [];
                    const errEl = ensureErrElLocal(base);
                    if (errEl) {
                        errEl.innerHTML = messages.join('<br>');
                        errEl.style.display = 'block';
                    } else {
                        summaryMessages.push(...messages);
                    }
                    const inputEl = document.getElementById(base) || document.querySelector(`#upgrade-employer-form [name="${base}"]`);
                    if (inputEl) inputEl.classList.add('input-error');
                });

                if (summaryMessages.length) {
                    let s = document.getElementById('form-errors-summary');
                    if (!s) {
                        const f = document.getElementById('upgrade-employer-form');
                        s = document.createElement('div');
                        s.id = 'form-errors-summary';
                        s.style.background = '#fff3f3';
                        s.style.border = '1px solid #f5c6cb';
                        s.style.color = '#8a1f1f';
                        s.style.padding = '10px';
                        s.style.marginBottom = '12px';
                        s.style.borderRadius = '4px';
                        if (f) f.insertAdjacentElement('afterbegin', s);
                    }
                    s.innerHTML = summaryMessages.map(m => `<div>${m}</div>`).join('');
                    s.style.display = 'block';
                }

                const first = document.querySelector('#upgrade-employer-form .input-error');
                if (first && typeof first.focus === 'function') first.focus();
                return; // stop further handling
            }
            throw new Error(data.error || data.message || 'Có lỗi xảy ra khi nâng cấp tài khoản');
        }
        
        // Cập nhật localStorage với thông tin user mới
        // Lưu ý: Role sẽ được cập nhật khi admin duyệt công ty
        if (data.company) {
            currentUser.company_id = data.company.id;
            currentUser.companyName = data.company.company_name;
            currentUser.companyStatus = 'pending'; // Đang chờ duyệt
        }
        localStorage.setItem('currentUser', JSON.stringify(currentUser));
        
        // Hiển thị thông báo thành công
        alert(data.message || 'Đăng ký công ty thành công! Công ty của bạn đang chờ admin duyệt để hoàn tất nâng cấp tài khoản.');
        
        // Đóng modal và reload trang
        hideUpgradeModal();
        location.reload();
        
    } catch (error) {
        console.error('Upgrade error:', error);
        alert('Có lỗi xảy ra khi nâng cấp tài khoản: ' + error.message);
    }
}

// Ensure upgrade button works
setTimeout(function() {
    const upgradeButton = document.querySelector('.btn-upgrade');
    if (upgradeButton) {
        console.log('🔧 Upgrade button found, ensuring it works...');
        upgradeButton.onclick = function() {
            console.log('🔧 Upgrade button clicked!');
            showUpgradeModal();
        };
        console.log('✅ Upgrade button onclick handler set');
    } else {
        console.log('❌ Upgrade button not found');
    }
}, 1000);

// Helper to submit the upgrade form programmatically (bypasses native form submit)
function submitUpgradeForm() {
    const form = document.getElementById('upgrade-employer-form');
    if (!form) {
        console.warn('submitUpgradeForm: form not found');
        return;
    }
    const fakeEvent = {
        preventDefault: function() {},
        target: form
    };
    // call and handle promise errors
    handleUpgradeSubmit(fakeEvent).catch(err => {
        console.error('submitUpgradeForm error', err);
    });
}

</script>

@auth
<script>
    // Ensure pages rendered by server also populate localStorage so header JS works
    try {
        const serverUser = {!! json_encode([
            'id' => auth()->user()->id,
            'name' => auth()->user()->name ?? null,
            'email' => auth()->user()->email ?? null,
            'role' => auth()->user()->role ?? null,
            'avatar' => auth()->user()->avatar ?? null,
        ]) !!};

        // Save minimal non-sensitive user info for header logic
        localStorage.setItem('currentUser', JSON.stringify(serverUser));
        localStorage.setItem('isLoggedIn', 'true');

        // Immediately update header UI if the functions are available
        if (typeof showUserMenu === 'function') {
            try { showUserMenu(serverUser); } catch (e) { console.warn('showUserMenu error', e); }
        }
    } catch (e) {
        console.warn('Failed to set currentUser from server:', e);
    }
</script>
@endauth
