<!DOCTYPE html>
<html lang="vi">

<head>
    @include('layouts.head')
    <link href="{{ asset('css/shared/header.css') }}" rel="stylesheet">
    <link href="{{ asset('css/shared/footer.css') }}" rel="stylesheet">
    <link href="{{ asset('css/roles/candidate/application.css') }}" rel="stylesheet">
    <title>Theo dõi ứng tuyển - WebCV</title>
</head>

<body>
    @include('layouts.header')

    <div class="container">

        <!-- Thống kê -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number" id="total-applications">0</div>
                <div class="stat-label">Tổng ứng tuyển</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="pending-applications">0</div>
                <div class="stat-label">Chờ duyệt</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="interview-applications">0</div>
                <div class="stat-label">Đã xem</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="accepted-applications">0</div>
                <div class="stat-label">Phù hợp</div>
            </div>
        </div>

        <!-- Bộ lọc -->
        <div class="filter-section">
            <h3>Bộ lọc ứng tuyển</h3>
            <div class="filter-row">
                <div class="filter-group">
                    <label>Trạng thái</label>
                    <select id="status-filter">
                        <option value="">Tất cả</option>
                        <option value="pending">Chờ duyệt</option>
                        <option value="reviewed">Đã xem</option>
                        <option value="approved">Phù hợp</option>
                        <option value="rejected">Không phù hợp</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Từ ngày</label>
                    <input type="date" id="date-from">
                </div>
                <div class="filter-group">
                    <label>Đến ngày</label>
                    <input type="date" id="date-to">
                </div>
                <button class="filter-btn" onclick="applyFilters()">Lọc</button>
            </div>
        </div>

        <!-- Danh sách ứng tuyển -->
        <div class="applications-list" id="applications-list">
            <div class="empty-state" id="loading-state">
                <i>⏳</i>
                <h3>Đang tải dữ liệu...</h3>
                <p>Vui lòng chờ trong giây lát</p>
            </div>
        </div>
    </div>

    <!-- Application Details Modal -->
    <div id="applicationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Chi tiết ứng tuyển</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal()">Đóng</button>
            </div>
        </div>
    </div>

    <!-- Confirmation Dialog -->
    <div id="confirmationDialog" class="confirmation-dialog">
        <div class="confirmation-content">
            <div class="confirmation-icon">⚠️</div>
            <div class="confirmation-title">Xác nhận hủy ứng tuyển</div>
            <div class="confirmation-message" id="confirmationMessage">
                Bạn có chắc chắn muốn hủy ứng tuyển này? Hành động này không thể hoàn tác.
            </div>
            <div class="confirmation-actions">
                <button class="btn btn-secondary" onclick="closeConfirmation()">Hủy</button>
                <button class="btn btn-danger" id="confirmCancelBtn" onclick="confirmCancelApplication()">
                    <i class="fas fa-trash"></i>
                    Hủy ứng tuyển
                </button>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/candidate/applications.js') }}"></script>

    @include('layouts.footer')
</body>

</html>