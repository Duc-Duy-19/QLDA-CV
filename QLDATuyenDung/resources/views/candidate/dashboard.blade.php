<!DOCTYPE html>
<html lang="vi">
<head>
    @include('layouts.head')
    <link href="{{ asset('css/shared/header.css') }}" rel="stylesheet">
    <link href="{{ asset('css/shared/footer.css') }}" rel="stylesheet">
    <link href="{{ asset('css/roles/candidate/dashboard.css') }}" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>Account - WebCV</title>
</head>
<body>
    @include('layouts.header')
    
    <!-- Debug Script -->
    <script>
        console.log('🔍 Server Auth Check:', {
            isAuthenticated: {{ auth()->check() ? 'true' : 'false' }},
            userRole: '{{ auth()->check() ? auth()->user()->role : 'not-logged-in' }}',
            userName: '{{ auth()->check() ? auth()->user()->name : 'N/A' }}'
        });
    </script>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Sidebar -->
        @if(auth()->check() && auth()->user()->role === 'employer')
            @include('shared.company-sidebar')
        @else
            <nav class="sidebar">
                <ul class="sidebar-menu">
                    <li style="display: none;"><a href="#" class="active" onclick="showSection('dashboard')">
                        <i class="fas fa-tachometer-alt"></i>
                        Account
                    </a></li>

                    <!-- Menu dành cho Candidate -->
                    <li style="display: none;"><a href="{{ route('candidate.dashboard') }}">
                        <i class="fas fa-tachometer-alt"></i>
                            Account
                    </a></li>
                    
                    <li class="menu-candidate" style="display: none;"><a href="{{ route('candidate.cv-builder') }}">
                        <i class="fas fa-file-alt"></i>
                        Tạo CV
                    </a></li>
                    
                    <li class="menu-candidate" style="display: none;"><a href="{{ route('candidate.applications') }}">
                        <i class="fas fa-briefcase"></i>
                        Ứng tuyển
                    </a></li>
                    <li class="menu-candidate" style="display: none;"><a href="{{ route('candidate.favorites') }}">
                        <i class="fas fa-heart"></i>
                        Yêu thích
                    </a></li>

                    <!-- Menu dành cho Recruiter/Employer -->
                    <li class="menu-recruiter" style="display: none;"><a href="{{ route('candidate.profile') }}">
                        <i class="fas fa-user"></i>
                        Thông tin cá nhân
                    </a></li>
                    <li class="menu-recruiter" style="display: none;"><a href="{{ route('company.info') }}">
                        <i class="fas fa-building"></i>
                        Thông tin công ty
                    </a></li>
                    <li class="menu-recruiter" style="display: none;"><a href="{{ route('employer.jobs') }}">
                        <i class="fas fa-briefcase"></i>
                        Quản lý việc làm
                    </a></li>
                    <li class="menu-recruiter" style="display: none;"><a href="{{ route('company.members') }}">
                        <i class="fas fa-users"></i>
                        Quản lý thành viên
                    </a></li>
                    <li class="menu-recruiter" style="display: none;"><a href="#" onclick="alert('Tính năng báo cáo sẽ được triển khai sớm!')">
                        <i class="fas fa-chart-line"></i>
                        Báo cáo & Thống kê
                    </a></li>

                    <!-- Menu chung cho tất cả role -->
                    <li><a href="{{ route('candidate.notifications') }}">
                        <i class="fas fa-bell"></i>
                        Thông báo
                        <span class="notification-badge" id="notification-count" style="display: none;">0</span>
                    </a></li>
                    <!-- Removed: Thống kê, Phát triển nghề nghiệp, Cộng đồng, and Cài đặt per user request -->
                </ul>
            </nav>
        @endif

        <!-- Main Content -->
        <main class="main-content">
            <!-- Account Section -->
            <div id="dashboard-section">
                <div class="page-header">
                    <h1 class="page-title">Account</h1>
                    <p class="page-subtitle">Chào mừng bạn quay trở lại! Đây là tổng quan về tài khoản của bạn.</p>
                </div>

                <!-- Profile Overview - Candidate -->
                <div class="profile-overview content-candidate" style="display: none;">
                    <div class="profile-card">
                        <div class="profile-header">
                            <div class="profile-avatar">
                                <img id="profile-avatar-img" src="{{ asset('images/avatar.jpg') }}" alt="Avatar" style="display: none;">
                                <i class="fas fa-user" id="profile-avatar-icon"></i>
                            </div>
                            <div class="profile-info">
                                <h2 id="profile-name">Nguyễn Văn A</h2>
                                <p id="profile-email">vana@example.com</p>
                                <p id="profile-phone">0909123456</p>
                                <div class="profile-skills" id="profile-skills">
                                    <span class="skill-tag">JavaScript</span>
                                    <span class="skill-tag">React</span>
                                    <span class="skill-tag">Node.js</span>
                                </div>
                            </div>
                            <div class="profile-actions">
                                <a href="{{ route('candidate.profile') }}" class="btn btn-primary">
                                    <i class="fas fa-edit"></i> Chỉnh sửa hồ sơ
                                </a>
                            </div>
                        </div>
                        <div class="profile-details">
                            <div class="detail-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span id="profile-location">Hà Nội, Việt Nam</span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-birthday-cake"></i>
                                <span id="profile-birthday">25 tuổi</span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-briefcase"></i>
                                <span id="profile-experience">2 năm kinh nghiệm</span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-graduation-cap"></i>
                                <span id="profile-education">Đại học Bách Khoa Hà Nội</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Removed: Work Experience / Education / Certificates / Languages sections per user request -->

            </div>

        </main>
    </div>

    <script src="{{ asset('js/candidate/dashboard.js') }}"></script>
    
    <script>
    // Show/Hide menu based on user role from localStorage
    document.addEventListener('DOMContentLoaded', function() {
        const currentUser = localStorage.getItem('currentUser');
        
        if (currentUser) {
            const user = JSON.parse(currentUser);
            console.log('👤 Current User from localStorage:', user);
            
            // Get all menu items
            const candidateMenus = document.querySelectorAll('.menu-candidate');
            const recruiterMenus = document.querySelectorAll('.menu-recruiter');
            const candidateContents = document.querySelectorAll('.content-candidate');
            const recruiterContents = document.querySelectorAll('.content-recruiter');
            
            if (user.role === 'candidate') {
                // Show candidate menus and contents
                candidateMenus.forEach(menu => menu.style.display = 'block');
                candidateContents.forEach(content => content.style.display = 'block');
                // Hide recruiter menus and contents
                recruiterMenus.forEach(menu => menu.style.display = 'none');
                recruiterContents.forEach(content => content.style.display = 'none');
                
                console.log('✅ Showing CANDIDATE menu');
            } else if (user.role === 'recruiter' || user.role === 'employer') {
                // Show recruiter menus and contents
                recruiterMenus.forEach(menu => menu.style.display = 'block');
                recruiterContents.forEach(content => content.style.display = 'block');
                // Hide candidate menus and contents
                candidateMenus.forEach(menu => menu.style.display = 'none');
                candidateContents.forEach(content => content.style.display = 'none');
                
                console.log('✅ Showing RECRUITER/EMPLOYER menu');
            } else {
                console.warn('⚠️ Unknown role:', user.role);
            }
        } else {
            console.warn('⚠️ No user found in localStorage');
        }
    });
    
    // Global function to update notification count
    function updateNotificationCount(count) {
        const badge = document.getElementById('notification-count');
        if (badge) {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        }
    }
    
    // Load notification count on page load
    async function loadNotificationCount() {
        try {
            const token = localStorage.getItem('authToken');
            if (!token) return;
            
            const response = await fetch('/api/company-invites', {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                const unreadCount = (data.invites || []).length; // Company invites are always unread
                updateNotificationCount(unreadCount);
            }
        } catch (error) {
            console.error('Error loading notification count:', error);
        }
    }
    
    // Real-time updates cho ứng viên: Polling mỗi 15 giây
    document.addEventListener('DOMContentLoaded', function() {
        // Load notification count immediately
        loadNotificationCount();
        
        setInterval(function() {
            console.log('🔄 Candidate Dashboard: Kiểm tra cập nhật dữ liệu...');
            // Kiểm tra thông báo mới
            loadNotificationCount();
        }, 15000); // 15 giây
    });
    </script>
    
    @include('layouts.footer')
</body>
</html>
