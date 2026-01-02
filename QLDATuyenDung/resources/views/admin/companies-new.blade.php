@extends('layouts.admin')

@section('title', 'Tất cả công ty')
@section('page-title', 'Tất cả công ty')

@section('content')
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
            </select>
            <button class="btn btn-info" onclick="filterCompanies()">
                <i class="fas fa-search"></i> Tìm kiếm
            </button>
            <button class="btn btn-success" onclick="exportCompanies()">
                <i class="fas fa-download"></i> Xuất Excel
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
    });

    async function loadAllCompanies() {
        try {
            // Lấy token từ localStorage
            const authToken = localStorage.getItem('authToken');
            if (!authToken) {
                console.error('Không tìm thấy token xác thực');
                showError('Vui lòng đăng nhập lại');
                return;
            }

            // Gọi API lấy danh sách tất cả công ty (sử dụng token)
            const response = await fetch('/api/admin/companies/pending', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + authToken,
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            if (response.ok) {
                const companies = await response.json();
                updateCompaniesTable(companies);
                document.getElementById('companiesCount').textContent = companies.length;
                console.log('Đã tải danh sách tất cả công ty thành công');
            } else {
                console.error('Lỗi khi tải danh sách công ty:', response.status);
                showError('Không thể tải danh sách công ty');
            }
        } catch (error) {
            console.error('Lỗi khi gọi API:', error);
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
                        `<img src="${company.logo}" alt="Logo" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid #ecf0f1;">
                        <div style="display:none; width: 50px; height: 50px; background: #ecf0f1; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-building" style="color: #7f8c8d; font-size: 20px;"></i>
                        </div>` :
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
                <span class="badge ${company.status === 'active' ? 'badge-success' : company.status === 'pending' ? 'badge-warning' : 'badge-danger'}">
                    ${company.status === 'active' ? 'Hoạt động' : company.status === 'pending' ? 'Chờ duyệt' : 'Từ chối'}
                </span>
            </td>
            <td>
                <div style="font-size: 14px;">${new Date(company.created_at).toLocaleDateString('vi-VN')}</div>
                <div style="font-size: 12px; color: #7f8c8d;">${new Date(company.created_at).toLocaleTimeString('vi-VN')}</div>
            </td>
            <td>
                <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                    <button class="btn btn-info btn-view-company" data-company-id="${company.id}" style="padding: 6px 12px; font-size: 12px;">
                        <i class="fas fa-eye"></i> Xem
                    </button>
                    ${company.status === 'pending' ? `
                        <form action="/admin/companies/${company.id}/approve" method="POST" style="display: inline;" class="form-approve-company" data-company-name="${company.company_name.replace(/"/g, '&quot;')}">
                            @csrf
                            <button type="submit" class="btn btn-success" style="padding: 6px 12px; font-size: 12px;">
                                <i class="fas fa-check"></i> Duyệt
                            </button>
                        </form>
                        <form action="/admin/companies/${company.id}/reject" method="POST" style="display: inline;" class="form-reject-company" data-company-name="${company.company_name.replace(/"/g, '&quot;')}">
                            @csrf
                            <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;">
                                <i class="fas fa-times"></i> Từ chối
                            </button>
                        </form>
                    ` : ''}
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
        switch (status) {
            case 'active':
                return 'Hoạt động';
            case 'pending':
                return 'Chờ duyệt';
            case 'inactive':
                return 'Từ chối';
            default:
                return 'Không xác định';
        }
    }

    function closeModal() {
        document.getElementById('companyModal').style.display = 'none';
    }

    function filterCompanies() {
        const searchTerm = document.getElementById('searchInput').value;
        const statusFilter = document.getElementById('statusFilter').value;
        // Implement search and filter functionality
        console.log('Searching for:', searchTerm, 'Status:', statusFilter);
    }

    function exportCompanies() {
        // Implement export functionality
        console.log('Exporting companies...');
        window.location.href = '/admin/companies/export';
    }

    function deleteCompany(companyId) {
        if (confirm('Xóa công ty này? Hành động này không thể hoàn tác!')) {
            // Implement deletion
            console.log('Deleting company:', companyId);
        }
    }

    // Close modal when clicking outside
    document.getElementById('companyModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    // Event delegation for buttons
    document.addEventListener('click', function(e) {
        // View company button
        if (e.target.closest('.btn-view-company')) {
            const btn = e.target.closest('.btn-view-company');
            const companyId = btn.dataset.companyId;
            if (companyId) viewCompany(companyId);
        }
    });

    // Event delegation for form submissions
    document.addEventListener('submit', function(e) {
        // Approve company form
        if (e.target.classList.contains('form-approve-company')) {
            const companyName = e.target.dataset.companyName;
            const confirmed = confirm(`Duyệt công ty "${companyName}"?`);
            if (!confirmed) {
                e.preventDefault();
            }
        }

        // Reject company form
        if (e.target.classList.contains('form-reject-company')) {
            const companyName = e.target.dataset.companyName;
            const confirmed = confirm(`Từ chối công ty "${companyName}"?`);
            if (!confirmed) {
                e.preventDefault();
            }
        }
    });
</script>
@endsection