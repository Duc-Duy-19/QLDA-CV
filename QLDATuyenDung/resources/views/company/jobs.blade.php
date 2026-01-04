<!DOCTYPE html>
<html lang="vi">
<head>
    @include('layouts.head')
    <link href="{{ asset('css/shared/header.css') }}" rel="stylesheet">
    <link href="{{ asset('css/shared/footer.css') }}" rel="stylesheet">
    <link href="{{ asset('css/employer/jobs.css') }}" rel="stylesheet">
    <link href="{{ asset('css/roles/company/members.css') }}" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>Quản lý việc làm - WebCV</title>
    
</head>
<body>
    @include('layouts.header')

    <div class="main-container">
        @include('shared.company-sidebar')

        <main class="main-content">
            <div class="page-header">
                <h2>Quản lý việc làm</h2>
                <p class="page-subtitle">Quản lý tin tuyển dụng cho công ty của bạn. Tạo, sửa, đóng/mở và xóa tin tuyển dụng.</p>
            </div>

            <div style="display:flex; justify-content:flex-end; margin-bottom:16px">
                <button class="btn btn-primary" id="btn-new-job">Tạo tin mới</button>
            </div>

            <div id="jobs-list">
                <div class="empty-state">Đang tải...</div>
            </div>
        </main>
    </div>

    <!-- Modal for create/edit -->
    <div class="modal" id="job-modal">
        <div class="panel">
            <h3 id="modal-title">Tạo việc làm</h3>
            <div>
                <div id="job-form-errors" style="color:#dc2626;margin-bottom:8px;font-size:14px"></div>
                <div class="form-row full-row">
                    <label>Tiêu đề</label>
                    <input type="text" id="job-title">
                    <div class="field-error" id="job-title-error" style="color:#dc2626;margin-top:6px;font-size:13px"></div>
                </div>
                <div class="form-row full-row">
                    <label>Mô tả</label>
                    <textarea id="job-description" rows="5"></textarea>
                    <div class="field-error" id="job-description-error" style="color:#dc2626;margin-top:6px;font-size:13px"></div>
                </div>
                <div class="form-row full-row">
                    <label>Yêu cầu</label>
                    <textarea id="job-requirements" rows="3"></textarea>
                    <div class="field-error" id="job-requirements-error" style="color:#dc2626;margin-top:6px;font-size:13px"></div>
                </div>
                <div class="form-row">
                    <label>Mức lương</label>
                    <input type="text" id="job-salary">
                    <div class="field-error" id="job-salary-error" style="color:#dc2626;margin-top:6px;font-size:13px"></div>
                </div>
                <div class="form-row">
                    <label>Địa điểm</label>
                    <input type="text" id="job-location">
                    <div class="field-error" id="job-location-error" style="color:#dc2626;margin-top:6px;font-size:13px"></div>
                </div>
                <div class="form-row">
                    <label>Ngày hết hạn</label>
                    <input type="date" id="job-expiration-date">
                    <div class="field-error" id="job-expiration-date-error" style="color:#dc2626;margin-top:6px;font-size:13px"></div>
                </div>
                <div class="form-row">
                    <label>Hình thức làm việc</label>
                    <select id="job-employment-type">
                        <option value=""></option>
                        <option value="Full-time">Full-time</option>
                        <option value="Part-time">Part-time</option>
                        <option value="Internship">Internship</option>
                        <option value="Remote">Remote</option>
                    </select>
                    <div class="field-error" id="job-employment-type-error" style="color:#dc2626;margin-top:6px;font-size:13px"></div>
                </div>
                <div class="form-row actions-row" style="margin-top:12px">
                    <button class="btn" id="btn-cancel">Hủy</button>
                    <button class="btn btn-primary" id="btn-save-job">Lưu</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for viewing details -->
    <div class="modal" id="job-detail-modal">
        <div class="panel" id="job-detail-panel" style="max-width:760px">
            <div class="close-float" id="detail-close-float" title="Đóng">✕</div>
            <h3 id="detail-title">Chi tiết việc làm</h3>
            <div id="detail-body">Đang tải...</div>
            <div style="display:flex; justify-content:flex-end; margin-top:12px">
                <button class="btn" id="detail-close">Đóng</button>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/employer/jobs.js') }}"></script>

    @include('layouts.footer')
</body>
</html>
