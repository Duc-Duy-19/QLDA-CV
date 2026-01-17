@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')


<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon pending">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
            <h3 id="pendingCompaniesCount">{{ $stats['pendingCompanies'] ?? 0 }}</h3>
            <p>Công ty chờ duyệt</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon active">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
            <h3 id="activeCompaniesCount">{{ $stats['activeCompanies'] ?? 0 }}</h3>
            <p>Công ty đã duyệt</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon users">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-content">
            <h3 id="totalUsersCount">{{ $stats['totalUsers'] ?? 0 }}</h3>
            <p>Tổng số user</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon companies">
            <i class="fas fa-building"></i>
        </div>
        <div class="stat-content">
            <h3 id="totalCompaniesCount">{{ $stats['totalCompanies'] ?? 0 }}</h3>
            <p>Tổng số công ty</p>
        </div>
    </div>
</div>

<div class="admin-table">
    <div class="table-header">
        <h3 class="table-title">Ứng viên chờ duyệt nâng cấp</h3>
        <a href="{{ route('admin.companies.pending') }}" class="btn btn-info">Xem tất cả</a>
    </div>
    
    <table class="table">
        <thead>
            <tr>
                <th>Tên ứng viên</th>
                <th>Email</th>
                <th>Công ty</th>
                <th>Ngày đăng ký</th>
                <th>Trạng thái</th>
            </tr>
        </thead>
        <tbody id="recentCompaniesTableBody">
            <tr>
                <td colspan="6" class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">Đang tải...</span>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sử dụng dữ liệu từ backend thay vì gọi API
    initializeDashboard();
});

function initializeDashboard() {
    try {
        console.log('🔄 Dashboard: Khởi tạo với dữ liệu từ backend...');
        
        // Sử dụng dữ liệu đã có từ backend (được truyền từ route)
        const stats = @json($stats ?? []);
        const pendingUpgrades = @json($pendingUpgrades ?? []);
        
        console.log('📊 Stats data:', stats);
        console.log('📋 Pending companies data:', pendingUpgrades);
        
        // Kiểm tra data có hợp lệ không
        if (!stats || typeof stats !== 'object') {
            console.error('❌ Stats data invalid:', stats);
            showError('Dữ liệu thống kê không hợp lệ');
            return;
        }
        
        // Cập nhật số liệu từ backend với fallback
        const pendingCount = stats.pendingCompanies ?? 0;
        const activeCount = stats.activeCompanies ?? 0;
        const totalUsers = stats.totalUsers ?? 0;
        const totalCompanies = stats.totalCompanies ?? 0;
        
        document.getElementById('pendingCompaniesCount').textContent = pendingCount;
        document.getElementById('activeCompaniesCount').textContent = activeCount;
        document.getElementById('totalUsersCount').textContent = totalUsers;
        document.getElementById('totalCompaniesCount').textContent = totalCompanies;
        
        console.log('✅ Updated stats - Pending:', pendingCount, 'Active:', activeCount, 'Total Users:', totalUsers, 'Total Companies:', totalCompanies);
        
        // Cập nhật danh sách công ty chờ duyệt
        updatePendingUpgrades(pendingUpgrades);
        
        console.log('✅ Dashboard đã khởi tạo thành công với dữ liệu từ backend');
        
        // Ẩn error banner nếu có
        const errorBanner = document.querySelector('.error-banner');
        if (errorBanner) {
            errorBanner.style.display = 'none';
        }
        
    } catch (error) {
        console.error('Lỗi khi khởi tạo dashboard:', error);
        showError('Có lỗi xảy ra khi khởi tạo dashboard');
    }
}

function showError(message) {
    // Hiển thị lỗi trên trang
    const errorDiv = document.createElement('div');
    errorDiv.className = 'alert alert-danger';
    errorDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; padding: 15px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px;';
    errorDiv.innerHTML = `<strong>Lỗi:</strong> ${message}`;
    document.body.appendChild(errorDiv);
    
    // Tự động ẩn sau 5 giây
    setTimeout(() => {
        errorDiv.remove();
    }, 5000);
}

function updatePendingUpgrades(companies) {
    const tbody = document.getElementById('recentCompaniesTableBody');
    if (!tbody) return;

    if (companies.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" style="text-align: center; padding: 40px; color: #7f8c8d;">
                    <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                    Không có công ty nào chờ duyệt
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = companies.map(company => {
        // Find the owner user from the companyUsers relationship
        const ownerCompanyUser = company.company_users && company.company_users.length > 0 
            ? company.company_users.find(cu => cu.role_in_company === 'Owner') 
            : null;
        const owner = ownerCompanyUser ? ownerCompanyUser.user : null;
            return `
            <tr>
                <td>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 40px; height: 40px; background: #e67e22; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-building" style="color: white;"></i>
                        </div>
                        <div>
                            <div style="font-weight: 600;">${company.company_name || 'N/A'}</div>
                            <div style="font-size: 12px; color: #7f8c8d;">${owner ? owner.name : 'Chưa có Owner'}</div>
                        </div>
                    </div>
                </td>
                <td>${ownerCompanyUser ? (ownerCompanyUser.user ? ownerCompanyUser.user.email : ownerCompanyUser.email) : 'N/A'}</td>
                <td>${company.company_name || 'N/A'}</td>
                <td>${new Date(company.created_at).toLocaleDateString('vi-VN')} ${new Date(company.created_at).toLocaleTimeString('vi-VN', {hour: '2-digit', minute: '2-digit'})}</td>
                <td>
                    <span class="status-badge status-pending">Chờ duyệt</span>
                </td>
            </tr>
            `;
    }).join('');
}

// Function xem chi tiết ứng viên nâng cấp
function viewUpgrade(upgradeId) {
    console.log('Xem chi tiết ứng viên nâng cấp ID:', upgradeId);
    // Có thể mở modal hoặc chuyển trang
    alert('Chức năng xem chi tiết ứng viên nâng cấp sẽ được triển khai');
}

// Function duyệt ứng viên nâng cấp
async function approveUpgrade(upgradeId, userName) {
    if (!confirm(`Bạn có chắc chắn muốn duyệt ứng viên "${userName}" nâng cấp lên nhà tuyển dụng?`)) {
        return;
    }

    try {
        console.log(`🔍 Đang duyệt ứng viên nâng cấp: ${userName} (ID: ${upgradeId})`);
        
        // Sử dụng session authentication thay vì token
        const response = await fetch(`/admin/companies/${upgradeId}/approve`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        console.log('🔍 Phản hồi API duyệt ứng viên:', response.status, response.statusText);
        const data = await response.json();
        console.log('🔍 Dữ liệu phản hồi API duyệt ứng viên:', data);

        if (response.ok) {
            alert('Ứng viên đã được duyệt nâng cấp thành công!');
            console.log('🔍 Đang tải lại dữ liệu dashboard...');
            location.reload(); // Reload dashboard
        } else {
            alert('Có lỗi xảy ra: ' + (data.message || 'Không thể duyệt ứng viên'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi duyệt công ty');
    }
}

// Function từ chối ứng viên nâng cấp
async function rejectUpgrade(upgradeId, userName) {
    if (!confirm(`Bạn có chắc chắn muốn từ chối ứng viên "${userName}" nâng cấp lên nhà tuyển dụng?`)) {
        return;
    }

    try {
        console.log(`🔍 Đang từ chối ứng viên nâng cấp: ${userName} (ID: ${upgradeId})`);
        
        // Sử dụng session authentication thay vì token
        const response = await fetch(`/admin/companies/${upgradeId}/reject`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        console.log('🔍 Phản hồi API từ chối ứng viên:', response.status, response.statusText);
        const data = await response.json();
        console.log('🔍 Dữ liệu phản hồi API từ chối ứng viên:', data);

        if (response.ok) {
            alert('Ứng viên đã bị từ chối nâng cấp!');
            console.log('🔍 Đang tải lại dữ liệu dashboard...');
            location.reload(); // Reload dashboard
        } else {
            alert('Có lỗi xảy ra: ' + (data.message || 'Không thể từ chối ứng viên'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi từ chối ứng viên');
    }
}
</script>
@endsection