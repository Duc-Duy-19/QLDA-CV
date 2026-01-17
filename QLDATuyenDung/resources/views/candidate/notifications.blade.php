<!DOCTYPE html>
<html lang="vi">
<head>
    @include('layouts.head')
    <link href="{{ asset('css/shared/header.css') }}" rel="stylesheet">
    <link href="{{ asset('css/shared/footer.css') }}" rel="stylesheet">
    <link href="{{ asset('css/roles/candidate/notifications.css') }}" rel="stylesheet">
    <title>Thông báo - WebCV</title>
</head>
<body>
    @include('layouts.header')
    
    <!-- Debug info -->
    <script>
        console.log('🔍 Notifications Page Debug:', {
            isAuthenticated: {{ auth()->check() ? 'true' : 'false' }},
            userRole: '{{ auth()->check() ? auth()->user()->role : 'not-logged-in' }}',
            userName: '{{ auth()->check() ? auth()->user()->name : 'N/A' }}'
        });
    </script>
    
    <div class="container">
        
        <!-- Filter Section -->
        <div class="filter-section">
            <div class="filter-row">
                <div class="filter-group">
                    <label>Loại thông báo</label>
                    <select id="type-filter">
                        <option value="">Tất cả</option>
                        <option value="application">Ứng tuyển</option>
                        <option value="interview">Phỏng vấn</option>
                        <option value="offer">Đề nghị</option>
                        <option value="invite">Lời mời công ty</option>
                        <option value="system">Hệ thống</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Trạng thái</label>
                    <select id="status-filter">
                        <option value="">Tất cả</option>
                        <option value="unread">Chưa đọc</option>
                        <option value="read">Đã đọc</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Từ ngày</label>
                    <input type="date" id="date-from">
                </div>
                <button class="filter-btn" onclick="applyFilters()">Lọc</button>
            </div>
        </div>
        
        <!-- Notifications List -->
        <div class="notifications-list" id="notifications-list">
            <!-- Notifications will be loaded here -->
        </div>
    </div>

    <script src="{{ asset('js/candidate/notifications.js') }}"></script>
    
    @include('layouts.footer')
</body>
</html>
