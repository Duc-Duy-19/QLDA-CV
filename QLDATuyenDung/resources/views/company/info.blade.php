<!DOCTYPE html>
<html lang="vi">

<head>
    @include('layouts.head')
    <link href="{{ asset('css/shared/header.css') }}" rel="stylesheet">
    <link href="{{ asset('css/shared/footer.css') }}" rel="stylesheet">
    <link href="{{ asset('css/roles/company/info.css') }}" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: #fff;
            padding: 0;
            border-radius: 8px;
            max-width: 800px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            padding: 20px 30px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 24px;
        }

        .close {
            font-size: 28px;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
        }

        .close:hover {
            color: #000;
        }

        .modal-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
            font-size: 14px;
        }

        .form-group small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 13px;
        }

        .text-info {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            color: #004085;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 30px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }

        .btn-primary {
            background-color: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #545b62;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 13px;
        }

        /* Company Context Display */
        .company-context-info {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
        }

        .context-display .context-item {
            margin-bottom: 20px;
        }

        .context-display .context-item:last-child {
            margin-bottom: 0;
        }

        .context-display strong {
            display: block;
            margin-bottom: 8px;
            color: #333;
        }

        .context-display p {
            margin: 0;
            color: #555;
            line-height: 1.6;
        }

        .context-display .badge {
            display: inline-block;
            padding: 5px 12px;
            background: #007bff;
            color: white;
            border-radius: 20px;
            font-size: 13px;
            margin-right: 8px;
            margin-bottom: 8px;
        }

        .text-muted {
            color: #6c757d;
        }
    </style>
    <title>Thông tin công ty - WebCV</title>
</head>

<body>
    @include('layouts.header')
    <script>
        window.COMPANY_ID = {
            {
                $company - > id ?? 'null'
            }
        };
        window.USER_ROLE_IN_COMPANY = '{{ $userRoleInCompany ?? '
        ' }}';
        window.USER_STATUS_IN_COMPANY = '{{ $userStatusInCompany ?? '
        ' }}';
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

                            <!-- AI Context Section -->
                            <div class="detail-section">
                                <h3>
                                    <i class="fas fa-robot"></i> Ngữ cảnh AI (Tiêu chí tuyển dụng)
                                    <button class="btn btn-sm btn-secondary" onclick="editCompanyContext()" style="float: right;">
                                        <i class="fas fa-edit"></i> Cập nhật
                                    </button>
                                </h3>
                                <div id="company-context-display" class="company-context-info">
                                    <p class="text-muted">Chưa có ngữ cảnh AI. Nhấn "Cập nhật" để thiết lập tiêu chí tuyển dụng của công ty.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal for Company Context -->
    <div id="companyContextModal" class="modal" style="display:none;">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h2>Cập nhật Ngữ cảnh AI Công ty</h2>
                <span class="close" onclick="closeCompanyContextModal()">&times;</span>
            </div>
            <div class="modal-body">
                <p class="text-info">
                    <i class="fas fa-info-circle"></i>
                    Ngữ cảnh này giúp AI hiểu rõ văn hóa và yêu cầu chung của công ty khi phân tích ứng viên cho TẤT CẢ các vị trí tuyển dụng.
                </p>

                <div class="form-group">
                    <label for="context_culture">Văn hóa công ty</label>
                    <textarea id="context_culture" rows="3" placeholder="VD: Startup năng động, ưu tiên người chủ động, học hỏi nhanh"></textarea>
                    <small>Mô tả văn hóa, môi trường làm việc của công ty</small>
                </div>

                <div class="form-group">
                    <label for="context_values">Giá trị cốt lõi (mỗi dòng 1 giá trị)</label>
                    <textarea id="context_values" rows="3" placeholder="Innovation&#10;Teamwork&#10;Customer-first"></textarea>
                    <small>Các giá trị mà công ty đề cao</small>
                </div>

                <div class="form-group">
                    <label for="context_work_style">Phong cách làm việc</label>
                    <textarea id="context_work_style" rows="2" placeholder="VD: Remote-friendly, flexible time, work-life balance"></textarea>
                    <small>Cách thức làm việc tại công ty</small>
                </div>

                <div class="form-group">
                    <label for="context_general_requirements">Yêu cầu chung cho ứng viên</label>
                    <textarea id="context_general_requirements" rows="3" placeholder="VD: Tinh thần học hỏi, không ngại thách thức, có trách nhiệm"></textarea>
                    <small>Những điều mà tất cả ứng viên cần có</small>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeCompanyContextModal()">Hủy</button>
                    <button type="button" class="btn btn-primary" onclick="saveCompanyContext()">
                        <i class="fas fa-save"></i> Lưu ngữ cảnh
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/company/info.js') }}"></script>

    @include('layouts.footer')
</body>

</html>