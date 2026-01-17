<!DOCTYPE html>
<html lang="vi">
<head>
    @include('layouts.head')
    <link href="{{ asset('css/shared/header.css') }}" rel="stylesheet">
    <link href="{{ asset('css/shared/footer.css') }}" rel="stylesheet">
    <link href="{{ asset('css/employer/manage-applications.css') }}" rel="stylesheet">
    <!-- Use the shared company/roles sidebar styles so sidebar looks identical to other company pages -->
    <link href="{{ asset('css/roles/company/info.css') }}" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>Quản lý ứng tuyển - WebCV</title>
</head>
<body>
    @include('layouts.header')

    <div class="main-container">
        @include('shared.company-sidebar')

        <main class="main-content">
            <div class="page-header">
                <h2>Quản lý ứng tuyển</h2>
                <p class="page-subtitle">Xem và quản lý các hồ sơ ứng tuyển cho các tin của công ty bạn.</p>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px">
                <div>
                    <label for="filter-status">Lọc trạng thái:</label>
                    <select id="filter-status">
                        <option value="">Tất cả</option>
                        <option value="pending">Chờ xử lý</option>
                        <option value="reviewed">Đã xem</option>
                        <option value="approved">Phù hợp</option>
                        <option value="rejected">Từ chối</option>
                    </select>
                </div>
                <div>
                    <button class="btn" id="btn-refresh">Làm mới</button>
                </div>
            </div>

            <div id="applications-list">
                <div class="empty-state">Đang tải...</div>
            </div>
        </main>
    </div>

    <!-- Detail modal -->
    <div class="modal" id="application-modal">
        <div class="panel" style="max-width:760px">
            <div class="close-float" id="application-modal-close" title="Đóng">✕</div>
            <h3 id="application-modal-title">Chi tiết ứng tuyển</h3>
            <div id="application-modal-body">Đang tải...</div>
            <div style="display:flex; justify-content:flex-end; margin-top:12px">
                <button class="btn" id="application-modal-close-btn">Đóng</button>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/api.js') }}"></script>
    <script src="{{ asset('js/employer/manage-applications.js') }}"></script>

    @include('layouts.footer')
</body>
</html>
