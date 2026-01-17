@extends('layouts.admin')

@section('title', 'Tất cả công ty')
@section('page-title', 'Tất cả công ty')

@section('content')
<style>
.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
}
.status-active {
    background-color: #d4edda;
    color: #155724;
}
.status-pending {
    background-color: #fff3cd;
    color: #856404;
}
.status-inactive {
    background-color: #f8d7da;
    color: #721c24;
}
.status-suspended {
    background-color: #e2e3e5;
    color: #6c757d;
}
.status-deleted {
    background-color: #f8d7da;
    color: #721c24;
}
</style>
<div class="admin-table">
    <div class="table-header">
        <h3 class="table-title">Danh sách tất cả công ty (<span id="companiesCount">0</span> công ty)</h3>
        <div style="display: flex; gap: 10px;">
            <input type="text" id="searchInput" placeholder="Tìm kiếm công ty..." style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 5px; width: 250px;">
            <select id="statusFilter" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 5px;">
                <option value="">Tất cả trạng thái</option>
                <option value="active">Hoạt động</option>
                <option value="pending">Chờ duyệt</option>
                <option value="inactive">Từ chối</option>
                <option value="suspended">Tạm dừng</option>
                <option value="deleted">Đã xóa</option>
            </select>
            <button class="btn btn-info" onclick="filterCompanies()">
                <i class="fas fa-search"></i> Tìm kiếm
            </button>
        </div>
    </div>
    
    <table class="table">
        <thead>
            <tr>
                <th>Tên công ty</th>
                <th>Email</th>
                <th>Trạng thái</th>
                <th>Ngày đăng ký</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody id="companiesTableBody">
            <tr>
                <td colspan="5" class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">Đang tải...</span>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<!-- Modal xem chi tiết công ty -->
<div id="companyModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 10px; width: 90%; max-width: 600px; max-height: 80vh; overflow-y: auto;">
        <div style="padding: 20px; border-bottom: 1px solid #ecf0f1; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0;">Chi tiết công ty</h3>
            <button onclick="closeModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <div id="companyDetails" style="padding: 20px;">
            <!-- Nội dung chi tiết sẽ được load bằng JavaScript -->
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadAllCompanies();

    // Thêm event listener cho Enter key trong input search
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                filterCompanies();
            }
        });
    }
});

function loadAllCompanies() {
    try {
        console.log('🔄 Đang tải danh sách công ty...');
        
        // Sử dụng dữ liệu từ backend (được truyền từ route)
        const companies = @json($companies ?? []);
        
        updateCompaniesTable(companies);
        document.getElementById('companiesCount').textContent = companies.length;
        console.log('✅ Đã tải danh sách công ty thành công:', companies.length, 'công ty');
        
    } catch (error) {
        console.error('Lỗi khi tải dữ liệu:', error);
        showError('Có lỗi xảy ra khi tải dữ liệu');
    }
}

function updateCompaniesTable(companies) {
    const tbody = document.getElementById('companiesTableBody');
    
    if (companies.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" style="text-align: center; padding: 60px; color: #7f8c8d;">
                    <i class="fas fa-inbox" style="font-size: 64px; margin-bottom: 20px; display: block; color: #bdc3c7;"></i>
                    <h3 style="margin-bottom: 10px; color: #95a5a6;">Không có công ty nào</h3>
                    <p>Chưa có công ty nào được đăng ký.</p>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = companies.map(company => `
        <tr>
            <td>
                <div style="display: flex; align-items: center; gap: 15px;">
                    ${company.logo ? 
                        `<img src="${company.logo}" alt="Logo" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid #ecf0f1;">` :
                        `<div style="width: 50px; height: 50px; background: #ecf0f1; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-building" style="color: #7f8c8d; font-size: 20px;"></i>
                        </div>`
                    }
                    <div>
                        <div style="font-weight: 600; font-size: 16px; margin-bottom: 5px;">${company.company_name}</div>
                        <div style="font-size: 12px; color: #7f8c8d;">
                            <i class="fas fa-phone"></i> ${company.phone || 'N/A'}
                        </div>
                    </div>
                </div>
            </td>
            <td>
                <div style="font-size: 14px;">${company.email || 'N/A'}</div>
            </td>
            <td>
                <span class="badge ${getStatusBadgeClass(company.status)}">
                    ${getStatusText(company.status)}
                </span>
            </td>
            <td>
                <div style="font-size: 14px;">${new Date(company.created_at).toLocaleDateString('vi-VN')}</div>
                <div style="font-size: 12px; color: #7f8c8d;">${new Date(company.created_at).toLocaleTimeString('vi-VN')}</div>
            </td>
            <td>
                <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                    <button class="btn btn-info" onclick="viewCompany(${company.id})" style="padding: 6px 12px; font-size: 12px;">
                        <i class="fas fa-eye"></i> Xem chi tiết
                    </button>
                    <button class="btn btn-danger" onclick="deleteCompany(${company.id}, '${company.company_name}')" style="padding: 6px 12px; font-size: 12px;">
                        <i class="fas fa-trash"></i> Xóa
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function showError(message) {
    const tbody = document.getElementById('companiesTableBody');
    tbody.innerHTML = `
        <tr>
            <td colspan="5" class="text-center text-danger">
                ${message}
            </td>
        </tr>
    `;
}

function viewCompany(companyId) {
    fetch(`/admin/companies/${companyId}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('companyDetails').innerHTML = `
                <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 20px;">
                    ${data.logo ? `<img src="${data.logo}" alt="Logo" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover;">` : '<div style="width: 80px; height: 80px; background: #ecf0f1; border-radius: 50%; display: flex; align-items: center; justify-content: center;"><i class="fas fa-building" style="color: #7f8c8d; font-size: 32px;"></i></div>'}
                    <div>
                        <h2 style="margin: 0 0 10px 0; color: #2c3e50;">${data.company_name}</h2>
                        <p style="margin: 0; color: #7f8c8d;">Đăng ký: ${new Date(data.created_at).toLocaleDateString('vi-VN')}</p>
                        <span class="status-badge status-${data.status}">${getStatusText(data.status)}</span>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div>
                        <h4 style="color: #2c3e50; margin-bottom: 10px;">Thông tin liên hệ</h4>
                        <p><strong>Email:</strong> ${data.email || 'N/A'}</p>
                        <p><strong>Điện thoại:</strong> ${data.phone || 'N/A'}</p>
                        <p><strong>Website:</strong> ${data.website ? `<a href="${data.website}" target="_blank">${data.website}</a>` : 'N/A'}</p>
                    </div>
                    <div>
                        <h4 style="color: #2c3e50; margin-bottom: 10px;">Địa chỉ</h4>
                        <p>${data.address || 'N/A'}</p>
                    </div>
                </div>
                
                <div>
                    <h4 style="color: #2c3e50; margin-bottom: 10px;">Mô tả công ty</h4>
                    <p style="line-height: 1.6;">${data.description || 'Không có mô tả'}</p>
                </div>
            `;
            document.getElementById('companyModal').style.display = 'block';
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi tải thông tin công ty');
        });
}

function getStatusText(status) {
    switch(status) {
        case 'active': return 'Hoạt động';
        case 'pending': return 'Chờ duyệt';
        case 'inactive': return 'Từ chối';
        case 'suspended': return 'Tạm dừng';
        case 'deleted': return 'Đã xóa';
        default: return 'Không xác định';
    }
}

function getStatusBadgeClass(status) {
    switch(status) {
        case 'active': return 'badge-success';
        case 'pending': return 'badge-warning';
        case 'inactive': return 'badge-danger';
        case 'suspended': return 'badge-secondary';
        case 'deleted': return 'badge-danger';
        default: return 'badge-secondary';
    }
}

function closeModal() {
    document.getElementById('companyModal').style.display = 'none';
}

async function filterCompanies() {
    const searchTerm = document.getElementById('searchInput').value.trim();
    const statusFilter = document.getElementById('statusFilter').value;
    
    try {
        // Hiển thị loading state
        const tbody = document.getElementById('companiesTableBody');
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">Đang tải...</span>
                    </div>
                </td>
            </tr>
        `;

        console.log('🔍 Đang tìm kiếm/lọc công ty...', { searchTerm, statusFilter });

        // Tạo query parameters
        const params = new URLSearchParams();
        if (searchTerm) {
            params.append('search', searchTerm);
        }
        if (statusFilter) {
            params.append('status', statusFilter);
        }

        // Gọi API với query parameters
        const response = await fetch(`/admin/api/companies?${params.toString()}`, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();
        
        // Xử lý paginated response (Laravel pagination trả về data trong property 'data')
        const companies = result.data || result;
        const total = result.total !== undefined ? result.total : companies.length;

        // Cập nhật table
        updateCompaniesTable(companies);
        
        // Cập nhật count
        document.getElementById('companiesCount').textContent = total;

        console.log('✅ Đã tìm kiếm/lọc thành công:', total, 'công ty');

        // Hiển thị thông báo nếu không có kết quả
        if (companies.length === 0) {
            console.log('⚠️ Không tìm thấy công ty nào phù hợp với điều kiện tìm kiếm');
        }

    } catch (error) {
        console.error('❌ Lỗi khi tìm kiếm/lọc:', error);
        showError('Có lỗi xảy ra khi tìm kiếm/lọc công ty. Vui lòng thử lại.');
    }
}


async function deleteCompany(companyId, companyName) {
    // Debug helper: hiện thông tin currentUser nếu có
    try {
        console.log('Current localStorage currentUser:', localStorage.getItem('currentUser'));
    } catch (e) {
        console.log('No local currentUser');
    }

    try {
        console.log(`🔍 Kiểm tra trước khi xóa công ty: ${companyName} (ID: ${companyId})`);

        // Lấy chi tiết công ty (chứa danh sách users nếu backend trả về)
        const preResp = await fetch(`/admin/companies/${companyId}`, { credentials: 'same-origin' });
        if (!preResp.ok) {
            alert('Không thể kiểm tra thông tin công ty trước khi xóa. Hủy thao tác.');
            return;
        }

        const companyData = await preResp.json();
        const members = companyData.users || [];

        // Phân loại members theo user.role (site-level)
        const siteAdmins = members.filter(u => (u.role || '').toLowerCase() === 'admin');
        const employers = members.filter(u => (u.role || '').toLowerCase() === 'employer');

        // Nếu có site-admin là member của công ty -> yêu cầu xác nhận mạnh
        if (siteAdmins.length > 0) {
            const names = siteAdmins.map(u => u.name || u.email || (`id:${u.id}`)).join(', ');
            const confirmText = `CẢNH BÁO: Công ty này có ${siteAdmins.length} tài khoản site-admin: ${names}.\n\nXóa công ty sẽ xóa membership của họ tại công ty này. Tuy nhiên nếu backend có bug thì khả năng vô tình hạ role toàn site vẫn tồn tại.\n\nĐể tiếp tục, vui lòng nhập chính xác tên công ty: `;
            const typed = prompt(confirmText, '');
            if (typed !== companyName) {
                alert('Hủy thao tác: bạn không nhập đúng tên công ty.');
                return;
            }
        } else if (employers.length > 0) {
            // Nếu có employer (site-level) -> cảnh báo rằng họ có thể bị hạ xuống candidate
            const names = employers.map(u => u.name || u.email).join(', ');
            if (!confirm(`Cảnh báo: Xóa công ty sẽ có thể hạ các tài khoản sau từ employer -> candidate: ${names}.\n\nBạn có chắc muốn tiếp tục không?`)) {
                return;
            }
        } else {
            // Không có member rủi ro đặc biệt -> confirm bình thường
            if (!confirm(`Bạn có chắc chắn muốn xóa công ty "${companyName}"?\n\nHành động này sẽ:\n- Xóa quyền quản lý công ty\n- Tài khoản vẫn tồn tại nhưng mất quyền liên quan`)) {
                return;
            }
        }

        // Thực hiện xóa (gửi cookie session và CSRF token để backend xác thực)
        const response = await fetch(`/admin/companies/${companyId}/delete`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        if (response.ok) {
            alert('Công ty đã được xóa thành công!');
            location.reload(); // Reload trang
        } else {
            let data = {};
            try { data = await response.json(); } catch (e) { /* ignore */ }
            alert('Có lỗi xảy ra: ' + (data.message || 'Không thể xóa công ty'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi xóa công ty');
    }
}


// Close modal when clicking outside
document.getElementById('companyModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>
@endsection
