<!DOCTYPE html>
<html lang="vi">
<head>
    @include('layouts.head')
    <link href="{{ asset('css/shared/header.css') }}" rel="stylesheet">
    <link href="{{ asset('css/shared/footer.css') }}" rel="stylesheet">
    <link href="{{ asset('css/roles/candidate/settings.css') }}" rel="stylesheet">
    <title>Cài đặt bảo mật - WebCV</title>
</head>
<body>
    @include('layouts.header')
    
    <div class="container">
        
        <!-- Settings Tabs -->
        <div class="settings-tabs">
            <button class="tab-btn active" onclick="showTab('security')">
                <i class="fas fa-shield-alt"></i> Bảo mật
            </button>
            <button class="tab-btn" onclick="showTab('privacy')">
                <i class="fas fa-user-secret"></i> Quyền riêng tư
            </button>
            <button class="tab-btn" onclick="showTab('notifications')">
                <i class="fas fa-bell"></i> Thông báo
            </button>
            <button class="tab-btn" onclick="showTab('account')">
                <i class="fas fa-user-cog"></i> Tài khoản
            </button>
        </div>
        
        <!-- Security Settings -->
        <div class="settings-section active" id="security">
            <div class="section-header">
                <h2>Bảo mật tài khoản</h2>
                <p>Quản lý mật khẩu và các thiết lập bảo mật</p>
            </div>
            
            <div class="settings-content">
                <div class="setting-item">
                    <div class="setting-info">
                        <h3>Đổi mật khẩu</h3>
                        <p>Thay đổi mật khẩu hiện tại của bạn</p>
                    </div>
                    <button class="btn btn-primary" onclick="changePassword()">
                        <i class="fas fa-key"></i> Đổi mật khẩu
                    </button>
                </div>
                
                <div class="setting-item">
                    <div class="setting-info">
                        <h3>Xác thực 2 bước</h3>
                        <p>Thêm lớp bảo mật bổ sung cho tài khoản</p>
                    </div>
                    <div class="setting-control">
                        <label class="switch">
                            <input type="checkbox" id="two-factor">
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
                
                <div class="setting-item">
                    <div class="setting-info">
                        <h3>Phiên đăng nhập</h3>
                        <p>Quản lý các thiết bị đã đăng nhập</p>
                    </div>
                    <button class="btn btn-outline" onclick="manageSessions()">
                        <i class="fas fa-desktop"></i> Quản lý phiên
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Privacy Settings -->
        <div class="settings-section" id="privacy">
            <div class="section-header">
                <h2>Quyền riêng tư</h2>
                <p>Kiểm soát thông tin cá nhân và quyền riêng tư</p>
            </div>
            
            <div class="settings-content">
                <div class="setting-item">
                    <div class="setting-info">
                        <h3>Hiển thị hồ sơ công khai</h3>
                        <p>Cho phép nhà tuyển dụng xem hồ sơ của bạn</p>
                    </div>
                    <div class="setting-control">
                        <label class="switch">
                            <input type="checkbox" id="public-profile" checked>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
                
                <div class="setting-item">
                    <div class="setting-info">
                        <h3>Hiển thị thông tin liên hệ</h3>
                        <p>Cho phép hiển thị email và số điện thoại</p>
                    </div>
                    <div class="setting-control">
                        <label class="switch">
                            <input type="checkbox" id="show-contact">
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
                
                <div class="setting-item">
                    <div class="setting-info">
                        <h3>Chia sẻ dữ liệu với bên thứ 3</h3>
                        <p>Cho phép chia sẻ dữ liệu để cải thiện dịch vụ</p>
                    </div>
                    <div class="setting-control">
                        <label class="switch">
                            <input type="checkbox" id="share-data">
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Notification Settings -->
        <div class="settings-section" id="notifications">
            <div class="section-header">
                <h2>Cài đặt thông báo</h2>
                <p>Quản lý các loại thông báo bạn muốn nhận</p>
            </div>
            
            <div class="settings-content">
                <div class="setting-item">
                    <div class="setting-info">
                        <h3>Thông báo email</h3>
                        <p>Nhận thông báo qua email</p>
                    </div>
                    <div class="setting-control">
                        <label class="switch">
                            <input type="checkbox" id="email-notifications" checked>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
                
                <div class="setting-item">
                    <div class="setting-info">
                        <h3>Thông báo ứng tuyển</h3>
                        <p>Thông báo về trạng thái ứng tuyển</p>
                    </div>
                    <div class="setting-control">
                        <label class="switch">
                            <input type="checkbox" id="application-notifications" checked>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
                
                <div class="setting-item">
                    <div class="setting-info">
                        <h3>Thông báo việc làm mới</h3>
                        <p>Thông báo về việc làm phù hợp</p>
                    </div>
                    <div class="setting-control">
                        <label class="switch">
                            <input type="checkbox" id="job-notifications" checked>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
                
                <div class="setting-item">
                    <div class="setting-info">
                        <h3>Thông báo marketing</h3>
                        <p>Nhận thông tin về sản phẩm và dịch vụ mới</p>
                    </div>
                    <div class="setting-control">
                        <label class="switch">
                            <input type="checkbox" id="marketing-notifications">
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Account Settings -->
        <div class="settings-section" id="account">
            <div class="section-header">
                <h2>Cài đặt tài khoản</h2>
                <p>Quản lý thông tin tài khoản và dữ liệu</p>
            </div>
            
            <div class="settings-content">
                <div class="setting-item">
                    <div class="setting-info">
                        <h3>Xuất dữ liệu</h3>
                        <p>Tải xuống tất cả dữ liệu của bạn</p>
                    </div>
                    <button class="btn btn-outline" onclick="exportData()">
                        <i class="fas fa-download"></i> Xuất dữ liệu
                    </button>
                </div>
                
                <div class="setting-item">
                    <div class="setting-info">
                        <h3>Xóa tài khoản</h3>
                        <p>Xóa vĩnh viễn tài khoản và tất cả dữ liệu</p>
                    </div>
                    <button class="btn btn-danger" onclick="deleteAccount()">
                        <i class="fas fa-trash"></i> Xóa tài khoản
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/candidate/settings.js') }}"></script>
    
    @include('layouts.footer')
</body>
</html>
