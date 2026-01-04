@extends('layouts.admin')

@section('title', 'Công ty chờ duyệt')
@section('page-title', 'Công ty chờ duyệt')

@section('content')
<div class="admin-table">
    <div class="table-header">
        <h3 class="table-title">Danh sách công ty chờ duyệt (<span id="companiesCount">0</span> công ty)</h3>
        <div style="display: flex; gap: 10px;">
            <input type="text" id="searchInput" placeholder="Tìm kiếm công ty..." style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 5px; width: 250px;">
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
                <th>Địa chỉ</th>
                <th>Website</th>
                <th>Ngày đăng ký</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody id="companiesTableBody">
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

{{-- Pagination đã được xử lý bởi JavaScript --}}

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
    loadPendingCompanies();
    
    // Real-time updates: Polling mỗi 5 giây
    setInterval(function() {
        console.log('🔄 Kiểm tra cập nhật dữ liệu...');
        loadPendingCompanies();
        checkUserRoleUpdate(); // Kiểm tra cập nhật role
    }, 5000); // 5 giây
});

// Function load danh sách công ty chờ duyệt từ API
async function loadPendingCompanies() {
    try {
        // Lấy token từ localStorage
        const authToken = localStorage.getItem('authToken');
        if (!authToken) {
            console.error('Không tìm thấy token xác thực');
            showError('Vui lòng đăng nhập lại');
            return;
        }

        console.log('🔍 Đang tải danh sách công ty chờ duyệt...');
        // Gọi web route thay vì API route để sử dụng session authentication
        const response = await fetch('/admin/api/companies/pending', {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });

        console.log('🔍 Phản hồi API tải công ty chờ duyệt:', response.status, response.statusText);
        if (response.ok) {
            const companies = await response.json();
            console.log('🔍 Dữ liệu công ty chờ duyệt nhận được:', companies);
            updateCompaniesTable(companies);
            document.getElementById('companiesCount').textContent = companies.length;
            console.log('Đã tải danh sách công ty chờ duyệt thành công');
        } else {
            console.error('Lỗi khi tải danh sách công ty:', response.status);
            showError('Không thể tải danh sách công ty');
        }
    } catch (error) {
        console.error('Lỗi khi gọi API:', error);
        showError('Có lỗi xảy ra khi tải dữ liệu');
    }
}

// Function cập nhật bảng công ty
function updateCompaniesTable(companies) {
    const tbody = document.getElementById('companiesTableBody');
    
    if (companies.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" style="text-align: center; padding: 60px; color: #7f8c8d;">
                    <i class="fas fa-inbox" style="font-size: 64px; margin-bottom: 20px; display: block; color: #bdc3c7;"></i>
                    <h3 style="margin-bottom: 10px; color: #95a5a6;">Không có công ty nào chờ duyệt</h3>
                    <p>Tất cả công ty đã được xử lý hoặc chưa có đăng ký mới.</p>
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
                <div style="max-width: 200px; font-size: 14px;">${company.address ? company.address.substring(0, 50) + (company.address.length > 50 ? '...' : '') : 'N/A'}</div>
            </td>
            <td>
                ${company.website ? 
                    `<a href="${company.website}" target="_blank" style="color: #3498db; text-decoration: none;">
                        <i class="fas fa-external-link-alt"></i> ${company.website.length > 30 ? company.website.substring(0, 30) + '...' : company.website}
                    </a>` : 
                    '<span style="color: #7f8c8d;">N/A</span>'
                }
            </td>
            <td>
                <div style="font-size: 14px;">${new Date(company.created_at).toLocaleDateString('vi-VN')}</div>
                <div style="font-size: 12px; color: #7f8c8d;">${new Date(company.created_at).toLocaleTimeString('vi-VN')}</div>
            </td>
            <td>
                <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                    <button class="btn btn-info" onclick="viewCompany(${company.id})" style="padding: 6px 12px; font-size: 12px;">
                        <i class="fas fa-eye"></i> Xem
                    </button>
                    <button class="btn btn-success" onclick="approveCompany(${company.id}, '${company.company_name}')" style="padding: 6px 12px; font-size: 12px;">
                        <i class="fas fa-check"></i> Duyệt
                    </button>
                    <button class="btn btn-danger" onclick="rejectCompany(${company.id}, '${company.company_name}')" style="padding: 6px 12px; font-size: 12px;">
                        <i class="fas fa-times"></i> Từ chối
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

// Function kiểm tra và cập nhật role user
function checkUserRoleUpdate() {
    const currentUser = localStorage.getItem('currentUser');
    if (currentUser) {
        const user = JSON.parse(currentUser);
        console.log('🔍 Kiểm tra role hiện tại:', user.role);
        
        // Nếu role đã thay đổi thành employer, thông báo và cập nhật giao diện
        if (user.role === 'employer') {
            console.log('🔍 User đã được nâng cấp lên nhà tuyển dụng!');
            updateUserInterface(user);
        }
    }
}

// Function cập nhật giao diện user
function updateUserInterface(user) {
    // Cập nhật role trong header (nếu có)
    const roleElement = document.querySelector('.user-role');
    if (roleElement) {
        roleElement.textContent = 'Nhà Tuyển Dụng';
        console.log('🔍 Đã cập nhật role trong header');
    }
    
    // Hiển thị thông báo thành công
    alert('🎉 Chúc mừng! Tài khoản của bạn đã được nâng cấp lên Nhà Tuyển Dụng!');
    
    // Có thể thêm logic khác như:
    // - Cập nhật menu navigation
    // - Thay đổi màu sắc giao diện
    // - Redirect đến trang employer dashboard
    console.log('🔍 Giao diện đã được cập nhật cho role:', user.role);
}




function showError(message) {
    const tbody = document.getElementById('companiesTableBody');
    tbody.innerHTML = `
        <tr>
            <td colspan="6" class="text-center text-danger">
                ${message}
            </td>
        </tr>
    `;
}

function viewCompany(companyId) {
    // Load chi tiết công ty bằng AJAX
    fetch(`/admin/companies/${companyId}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('companyDetails').innerHTML = `
                <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 20px;">
                    ${data.logo ? `<img src="${data.logo}" alt="Logo" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover;">` : '<div style="width: 80px; height: 80px; background: #ecf0f1; border-radius: 50%; display: flex; align-items: center; justify-content: center;"><i class="fas fa-building" style="color: #7f8c8d; font-size: 32px;"></i></div>'}
                    <div>
                        <h2 style="margin: 0 0 10px 0; color: #2c3e50;">${data.company_name}</h2>
                        <p style="margin: 0; color: #7f8c8d;">Đăng ký: ${new Date(data.created_at).toLocaleDateString('vi-VN')}</p>
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

function closeModal() {
    document.getElementById('companyModal').style.display = 'none';
}

function filterCompanies() {
    const searchTerm = document.getElementById('searchInput').value;
    // Implement search functionality
    console.log('Searching for:', searchTerm);
}

// Approve company function
async function approveCompany(companyId, companyName) {
    if (!confirm(`Bạn có chắc chắn muốn duyệt công ty "${companyName}"?`)) {
        return;
    }

    try {
        console.log(`🔍 Đang duyệt công ty: ${companyName} (ID: ${companyId})`);
        const response = await fetch(`/admin/api/companies/${companyId}/approve`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });

        console.log('🔍 Phản hồi API duyệt công ty:', response.status, response.statusText);
        const data = await response.json();
        console.log('🔍 Dữ liệu phản hồi API duyệt công ty:', data);

        if (response.ok) {
            alert('Công ty đã được duyệt thành công!');
            console.log('🔍 Đang tải lại danh sách công ty...');

            // NOTE: API approve endpoint returns only company (no user info).
            // Để lấy email / role của owner (nếu cần), frontend sẽ gọi endpoint show company
            try {
                const companyResp = await fetch(`/admin/companies/${companyId}`, { credentials: 'same-origin' });
                if (companyResp.ok) {
                    const company = await companyResp.json();
                    console.log('🔍 Chi tiết company sau khi duyệt:', company);

                    // Nếu currentUser là owner hoặc thành viên công ty, cập nhật localStorage
                    try {
                        const currentUser = JSON.parse(localStorage.getItem('currentUser') || '{}');
                        if (currentUser && currentUser.id) {
                            const member = (company.users || []).find(u => u.id === currentUser.id);
                            if (member && member.role) {
                                currentUser.role = member.role;
                                localStorage.setItem('currentUser', JSON.stringify(currentUser));
                                console.log('🔍 Role đã được cập nhật trong localStorage từ company.show');
                            }
                        }
                    } catch (e) {
                        console.warn('Không thể cập nhật localStorage currentUser:', e);
                    }
                } else {
                    console.warn('Không thể lấy chi tiết company sau khi duyệt:', companyResp.status);
                }
            } catch (e) {
                console.error('Lỗi khi fetch chi tiết company sau khi duyệt:', e);
            }

            loadPendingCompanies(); // Reload danh sách
        } else {
            alert('Có lỗi xảy ra: ' + (data.message || 'Không thể duyệt công ty'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi duyệt công ty');
    }
}

// Reject company function
async function rejectCompany(companyId, companyName) {
    if (!confirm(`Bạn có chắc chắn muốn từ chối công ty "${companyName}"?`)) {
        return;
    }

    try {
        const response = await fetch(`/admin/api/companies/${companyId}/reject`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });

        const data = await response.json();

        if (response.ok) {
            alert('Công ty đã bị từ chối!');
            loadPendingCompanies(); // Reload danh sách
        } else {
            alert('Có lỗi xảy ra: ' + (data.message || 'Không thể từ chối công ty'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi từ chối công ty');
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
