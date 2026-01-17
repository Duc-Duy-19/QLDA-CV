<!DOCTYPE html>
<html lang="vi">
<head>
    @include('layouts.head')
    <link href="{{ asset('css/shared/header.css') }}" rel="stylesheet">
    <link href="{{ asset('css/shared/footer.css') }}" rel="stylesheet">
    <link href="{{ asset('css/roles/company/members.css') }}" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>Quản lý Thành viên - WebCV</title>
</head>
<body>
    @include('layouts.header')
    
    <!-- Main Container -->
    <div class="main-container">
        <!-- Sidebar -->
        @include('shared.company-sidebar')

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Quản lý Thành viên</h1>
                <p class="page-subtitle">Quản lý danh sách thành viên công ty và phân quyền</p>
            </div>

            <!-- Members Management -->
            <div class="members-overview">
                <div class="members-card">
                    <div class="members-header">
                        <div class="members-info">
                            <h2>Danh sách Thành viên</h2>
                            <p class="members-subtitle">
                                <i class="fas fa-users"></i>
                                <span id="members-count">Đang tải...</span>
                            </p>
                        </div>
                        <div class="members-actions">
                            <button class="btn btn-primary" onclick="showAddMemberModal()">
                                <i class="fas fa-envelope"></i> Mời thành viên
                            </button>
                        </div>
                    </div>
                    
                    <div class="members-details">
                        <div class="detail-section">
                            <div class="search-box">
                                <input type="text" id="searchMembers" placeholder="Tìm kiếm thành viên..." class="form-control">
                            </div>
                            
                            <div id="loadingIndicator" class="text-center">
                                <div class="spinner-border" role="status">
                                    <span class="visually-hidden">Đang tải...</span>
                                </div>
                            </div>

                            <div id="membersTable" style="display: none;">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Avatar</th>
                                            <th>Tên</th>
                                            <th>Email</th>
                                            <th>Vai trò</th>
                                            <th>Ngày tham gia</th>
                                            <th>Trạng thái</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody id="membersTableBody">
                                        <!-- Dynamic content -->
                                    </tbody>
                                </table>
                            </div>

                            <div id="noMembersMessage" style="display: none;" class="text-center text-muted">
                                <i class="fas fa-users fa-3x mb-3"></i>
                                <p>Chưa có thành viên nào trong công ty</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Member Modal -->
    <div id="addMemberModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Mời thành viên</h3>
                <span class="close" onclick="closeModal('addMemberModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form id="addMemberForm">
                    <div class="form-group">
                        <label for="memberEmail">Email</label>
                        <input type="email" id="memberEmail" required placeholder="email@example.com">
                        <small>Nhập email của người bạn muốn mời tham gia công ty</small>
                    </div>
                    <div class="form-group">
                        <label for="memberRole">Vai trò</label>
                        <select id="memberRole" required>
                            <option value="">Chọn vai trò</option>
                            <option value="Admin">Quản trị viên</option>
                            <option value="Recruiter">Nhân viên tuyển dụng</option>
                            <option value="Viewer">Người xem</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="memberMessage">Lời nhắn (tùy chọn)</label>
                        <textarea id="memberMessage" rows="3" placeholder="Lời mời tham gia công ty..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addMemberModal')">Hủy</button>
                <button type="button" class="btn btn-primary" onclick="addMember()">Gửi lời mời</button>
            </div>
        </div>
    </div>



    <!-- Edit Member Modal -->
    <div id="editMemberModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Chỉnh sửa thành viên</h3>
                <span class="close" onclick="closeModal('editMemberModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editMemberForm">
                    <input type="hidden" id="editMemberId">
                    <div class="form-group">
                        <label for="editMemberName">Tên</label>
                        <input type="text" id="editMemberName" readonly>
                    </div>
                    <div class="form-group">
                        <label for="editMemberEmail">Email</label>
                        <input type="email" id="editMemberEmail" readonly>
                    </div>
                    <div class="form-group">
                        <label for="editMemberRole">Vai trò</label>
                        <select id="editMemberRole" required>
                            <option value="admin">Quản trị viên</option>
                            <option value="manager">Quản lý</option>
                            <option value="recruiter">Nhân viên tuyển dụng</option>
                            <option value="member">Thành viên</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editMemberModal')">Hủy</button>
                <button type="button" class="btn btn-primary" onclick="updateMember()">Cập nhật</button>
            </div>
        </div>
    </div>

    <!-- Success Message -->
    <div id="successMessage" class="success-message" style="display: none;">
        <i class="fas fa-check-circle"></i>
        <span id="successMessageText"></span>
    </div>

    <script src="{{ asset('js/company/members.js') }}"></script>
    
    @include('layouts.footer')
</body>
</html>