<!DOCTYPE html>
<html lang="vi">

<head>
    @include('layouts.head')
    <link href="{{ asset('css/shared/header.css') }}" rel="stylesheet">
    <link href="{{ asset('css/shared/footer.css') }}" rel="stylesheet">
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
                        <option value="pending">Chờ duyệt</option>
                        <option value="reviewed">Đã xem</option>
                        <option value="approved">Phù hợp</option>
                        <option value="rejected">Không phù hợp</option>
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

    <!-- Load OpenCV.js (async) and set ready flag -->
    <script async src="https://docs.opencv.org/4.x/opencv.js"
        onload="cv['onRuntimeInitialized']=()=>{ window.openCvReady = true; console.info('OpenCV ready'); }"></script>

    <!-- define openCv wrapper / fallback -->
    <script>
        if (typeof window.openCv !== 'function') {
            window.openCv = function(resourceUrl, opts = {}) {
                if (window.openCvReady) {
                    // nếu đã tải OpenCV, gọi hàm xử lý thực tế (implement trong apps nếu cần)
                    try {
                        // placeholder — thực hiện xử lý bằng cv ở đây nếu cần
                        console.info('OpenCV ready but no implementation provided', resourceUrl, opts);
                        // TODO: gọi hàm xử lý file bằng cv
                    } catch (e) {
                        console.error(e);
                    }
                } else {
                    // fallback: mở file CV trực tiếp
                    if (resourceUrl) window.open(resourceUrl, '_blank');
                    else console.warn('openCv called but no resourceUrl', opts);
                }
            };
        }
    </script>

    @include('layouts.footer')
</body>

</html>