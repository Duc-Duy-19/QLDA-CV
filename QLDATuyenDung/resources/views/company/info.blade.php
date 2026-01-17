<!DOCTYPE html>
<html lang="vi">
<head>
    @include('layouts.head')
    <link href="{{ asset('css/shared/header.css') }}" rel="stylesheet">
    <link href="{{ asset('css/shared/footer.css') }}" rel="stylesheet">
    <link href="{{ asset('css/roles/company/info.css') }}" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>Thông tin công ty - WebCV</title>
</head>
<body>
    @include('layouts.header')
    <script>
        window.COMPANY_ID = {{ $company->id ?? 'null' }};
        window.USER_ROLE_IN_COMPANY = '{{ $userRoleInCompany ?? '' }}';
        window.USER_STATUS_IN_COMPANY = '{{ $userStatusInCompany ?? '' }}';
    </script>
    
    <!-- Main Container -->
    <div class="main-container">
        <!-- Sidebar -->
        @include('shared.company-sidebar')

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Thông tin công ty</h1>
                <p class="page-subtitle">Quản lý và cập nhật thông tin công ty của bạn</p>
            </div>

            <div id="company-permission-message" style="display:none; margin: 40px 0;">
                <div class="alert alert-danger" style="font-size: 18px; padding: 30px; text-align: center;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 32px; color: #e74c3c;"></i><br>
                    Bạn không được phép thực hiện chức năng này. Chỉ Owner hoặc Admin mới được truy cập thông tin công ty.
                </div>
            </div>

            <div id="company-info-content">
                <!-- Company Overview -->
                <div class="company-overview">
                    <div class="company-card">
                        <div class="company-header">
                            <div class="company-logo">
                                <img id="company-logo-img" src="{{ asset('images/default-company-logo.png') }}" alt="Company Logo">
                            </div>
                            <div class="company-info">
                                <h2 id="company-name">Đang tải...</h2>
                                <!-- Company size display removed -->
                            </div>
                            <div class="company-actions">
                                <button class="btn btn-primary" onclick="editCompanyInfo()">
                                    <i class="fas fa-edit"></i> Chỉnh sửa thông tin
                                </button>
                            </div>
                        </div>
                        <div class="company-details">
                            <div class="detail-section">
                                <h3><i class="fas fa-info-circle"></i> Về công ty</h3>
                                <p id="company-description" class="company-description">Đang tải thông tin công ty...</p>
                            </div>
                            <div class="detail-section">
                                <h3><i class="fas fa-address-card"></i> Thông tin liên hệ</h3>
                                <div class="contact-info">
                                    <div class="contact-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <div class="contact-content">
                                            <strong>Địa chỉ</strong>
                                            <span id="company-address">Chưa cập nhật địa chỉ</span>
                                        </div>
                                    </div>
                                    <div class="contact-item">
                                        <i class="fas fa-phone"></i>
                                        <div class="contact-content">
                                            <strong>Số điện thoại</strong>
                                            <span id="company-phone">Chưa cập nhật số điện thoại</span>
                                        </div>
                                    </div>
                                    <div class="contact-item">
                                        <i class="fas fa-envelope"></i>
                                        <div class="contact-content">
                                            <strong>Email</strong>
                                            <span id="company-email">Chưa cập nhật email</span>
                                        </div>
                                    </div>
                                    <div class="contact-item">
                                        <i class="fas fa-globe"></i>
                                        <div class="contact-content">
                                            <strong>Website</strong>
                                            <a href="#" id="company-website" target="_blank">Chưa cập nhật website</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="{{ asset('js/company/info.js') }}"></script>
    
    @include('layouts.footer')
</body>
</html>
