@extends('layouts.admin')

@section('title', 'Quản lý User')
@section('page-title', 'Quản lý User')

@section('content')
<div style="margin-bottom: 20px; display: flex; gap: 15px; align-items: center;">
    <div style="flex: 1;">
        <input type="text" id="searchInput" placeholder="Tìm kiếm theo tên, email..." style="padding: 10px 15px; border: 1px solid #ddd; border-radius: 5px; width: 100%; max-width: 400px;">
    </div>
    <div style="display: flex; gap: 10px;">
        <select id="roleFilter" style="padding: 10px 15px; border: 1px solid #ddd; border-radius: 5px;">
            <option value="">Tất cả role</option>
            <option value="candidate">Ứng viên</option>
            <option value="employer">Nhà tuyển dụng</option>
            <option value="admin">Admin</option>
        </select>
        <select id="statusFilter" style="padding: 10px 15px; border: 1px solid #ddd; border-radius: 5px;">
            <option value="">Tất cả trạng thái</option>
            <option value="active">Hoạt động</option>
            <option value="inactive">Tạm dừng</option>
            <option value="suspended">Bị khóa</option>
        </select>
        <button class="btn btn-info" onclick="filterUsers()">
            <i class="fas fa-search"></i> Lọc
        </button>
    </div>
</div>



<div class="admin-table">
    <div class="table-header">
        <h3 class="table-title">Danh sách User (<span id="usersCount">0</span> user)</h3>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>User</th>
                <th>Email</th>
                <th>Role</th>
                <th>Trạng thái</th>
                <th>Ngày tạo</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody id="usersTbody">
        </tbody>
    </table>

    <div id="usersPagination" style="margin-top: 20px; display: flex; justify-content: center; gap: 10px; align-items: center;">
    </div>
</div>

<div id="userModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 10px; width: 90%; max-width: 600px; max-height: 80vh; overflow-y: auto;">
        <div style="padding: 20px; border-bottom: 1px solid #ecf0f1; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0;">Chi tiết User</h3>
            <button onclick="closeUserModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
            </div>
            <div id="userDetails" style="padding: 20px;"></div>
        </div>
    </div>

    <script>
        const apiBase = '/api/admin/users';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        let currentPage = 1;
        let lastPage = 1;
        let perPage = 20;

        function getRoleText(role) {
            switch(role) {
                case 'admin': return 'Admin';
                case 'employer': return 'Nhà tuyển dụng';
                case 'candidate': return 'Ứng viên';
                default: return role;
            }
        }

        function getStatusText(status) {
            switch(status) {
                case 'active': return 'Hoạt động';
                case 'inactive': return 'Tạm dừng';
                case 'suspended': return 'Bị khóa';
                default: return status;
            }
        }

        function closeUserModal() {
            document.getElementById('userModal').style.display = 'none';
        }

        function renderUsers(users) {
            const tbody = document.getElementById('usersTbody');
            tbody.innerHTML = '';

            if (!users || users.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 60px; color: #7f8c8d;">
                            <i class="fas fa-users" style="font-size: 64px; margin-bottom: 20px; display: block; color: #bdc3c7;"></i>
                            <h3 style="margin-bottom: 10px; color: #95a5a6;">Không có user nào</h3>
                            <p>Chưa có user nào trong hệ thống.</p>
                        </td>
                    </tr>
                `;
                return;
            }

            users.forEach(user => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="width: 50px; height: 50px; background: #ecf0f1; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-user" style="color: #7f8c8d; font-size: 20px;"></i>
                            </div>
                            <div>
                                <div style="font-weight: 600; font-size: 16px; margin-bottom: 5px;">${escapeHtml(user.name)}</div>
                                <div style="font-size: 12px; color: #7f8c8d;">ID: ${user.id}</div>
                            </div>
                        </div>
                    </td>
                    <td><div style="font-size: 14px;">${escapeHtml(user.email)}</div></td>
                    <td><span class="status-badge" style="background: ${roleColor(user.role)}; color: white;">${getRoleText(user.role)}</span></td>
                    <td><span class="status-badge">${getStatusText(user.status)}</span></td>
                    <td>
                        <div style="font-size: 14px;">${formatDate(user.created_at)}</div>
                        <div style="font-size: 12px; color: #7f8c8d;">${formatTime(user.created_at)}</div>
                    </td>
                    <td>
                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                            <button class="btn btn-info" onclick="viewUser(${user.id})" style="padding: 6px 12px; font-size: 12px;"><i class="fas fa-eye"></i></button>
                            ${""}
                        </div>
                    </td>
                `;

                const actionCell = row.querySelector('td:last-child div');
                if (user.role !== 'admin') {
                    if (user.status === 'active') {
                        const suspendBtn = document.createElement('button');
                        suspendBtn.className = 'btn btn-warning';
                        suspendBtn.style.cssText = 'padding: 6px 12px; font-size: 12px;';
                        suspendBtn.innerHTML = '<i class="fas fa-pause"></i>';
                        suspendBtn.onclick = () => suspendUser(user.id);
                        actionCell.appendChild(suspendBtn);
                    } else {
                        const activateBtn = document.createElement('button');
                        activateBtn.className = 'btn btn-success';
                        activateBtn.style.cssText = 'padding: 6px 12px; font-size: 12px;';
                        activateBtn.innerHTML = '<i class="fas fa-play"></i>';
                        activateBtn.onclick = () => activateUser(user.id);
                        actionCell.appendChild(activateBtn);
                    }

                    const delBtn = document.createElement('button');
                    delBtn.className = 'btn btn-danger';
                    delBtn.style.cssText = 'padding: 6px 12px; font-size: 12px;';
                    delBtn.innerHTML = '<i class="fas fa-trash"></i>';
                    delBtn.onclick = () => deleteUser(user.id);
                    actionCell.appendChild(delBtn);
                }

                tbody.appendChild(row);
            });
        }

        function escapeHtml(unsafe) {
            if (unsafe === null || unsafe === undefined) return '';
            return String(unsafe)
              .replace(/&/g, '&amp;')
              .replace(/</g, '&lt;')
              .replace(/>/g, '&gt;')
              .replace(/"/g, '&quot;')
              .replace(/'/g, '&#039;');
        }

        function roleColor(role) {
            switch(role) {
                case 'admin': return '#e74c3c';
                case 'employer': return '#27ae60';
                default: return '#3498db';
            }
        }

        function formatDate(dt) {
            if (!dt) return '';
            try { return new Date(dt).toLocaleDateString('vi-VN'); } catch(e) { return dt; }
        }

        function formatTime(dt) {
            if (!dt) return '';
            try { return new Date(dt).toLocaleTimeString('vi-VN', {hour: '2-digit', minute: '2-digit'}); } catch(e) { return ''; }
        }

        function renderPagination(meta) {
            const container = document.getElementById('usersPagination');
            container.innerHTML = '';
            if (!meta) return;

            currentPage = meta.current_page || 1;
            lastPage = meta.last_page || 1;

            const prev = document.createElement('button');
            prev.textContent = '« Prev';
            prev.className = 'btn btn-light';
            prev.disabled = currentPage <= 1;
            prev.onclick = () => loadUsers(currentPage - 1);
            container.appendChild(prev);

            const info = document.createElement('span');
            info.textContent = `Trang ${currentPage} / ${lastPage}`;
            info.style.margin = '0 10px';
            container.appendChild(info);

            const next = document.createElement('button');
            next.textContent = 'Next »';
            next.className = 'btn btn-light';
            next.disabled = currentPage >= lastPage;
            next.onclick = () => loadUsers(currentPage + 1);
            container.appendChild(next);
        }

        async function loadUsers(page = 1) {
            const rawSearch = document.getElementById('searchInput').value || '';
            const rawRole = document.getElementById('roleFilter').value || '';
            const rawStatus = document.getElementById('statusFilter').value || '';
            perPage = 20;
            const params = new URLSearchParams();
            params.set('page', page);
            params.set('per_page', perPage);
            if (rawSearch.trim() !== '') params.set('search', rawSearch.trim());
            if (rawRole.trim() !== '') params.set('role', rawRole.trim());
            if (rawStatus.trim() !== '') params.set('status', rawStatus.trim());

            const query = params.toString() ? `?${params.toString()}` : '';
            try {
                const apiUrl = `${window.location.origin}/api/admin/users${query}`;
                console.log('Fetching users from:', apiUrl);

                if (window.APIHelper && typeof window.APIHelper.request === 'function') {
                    let payload;
                    try {
                        payload = await window.APIHelper.request(`/admin/users${query}`);
                    } catch (err) {
                        console.warn('APIHelper initial request failed, attempting to refresh CSRF and retry...', err && err.response ? err.response : (err && err.message ? err.message : String(err)));
                        try {
                            if (typeof window.APIHelper.ensureCsrf === 'function') {
                                await window.APIHelper.ensureCsrf();
                                payload = await window.APIHelper.request(`/admin/users${query}`);
                            } else {
                                // fallback to direct call
                                await fetch(`${window.location.origin}/sanctum/csrf-cookie`, { credentials: 'include' });
                                payload = await window.APIHelper.request(`/admin/users${query}`);
                            }
                        } catch (err2) {
                            console.warn('APIHelper retry failed:', err2 && err2.response ? err2.response : (err2 && err2.message ? err2.message : String(err2)));
                            throw err2;
                        }
                    }
                    console.debug('Raw API payload (APIHelper):', payload);
                    let users = [];
                    let meta = null;
                    let total = 0;

                    if (Array.isArray(payload)) {
                        users = payload;
                        total = payload.length;
                    } else if (payload && Array.isArray(payload.data)) {
                        users = payload.data;
                        meta = { current_page: payload.current_page, last_page: payload.last_page };
                        total = payload.total ?? users.length;
                    } else if (payload && Array.isArray(payload.users)) {
                        users = payload.users;
                        total = payload.total ?? users.length;
                    } else if (payload && typeof payload === 'object') {
                        const values = Object.values(payload).filter(v => Array.isArray(v));
                        if (values.length > 0) {
                            users = values[0];
                            total = users.length;
                        }
                    }

                    document.getElementById('usersCount').textContent = total || users.length || 0;
                    renderUsers(users);
                    renderPagination(meta);
                    return;
                }

                const url = `${apiBase}${query}`;
                console.log('Fallback fetch URL:', url);
                let res = await fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
                let text = await res.text();
                let data;
                try { data = JSON.parse(text); } catch(e) { data = { message: text }; }
                console.debug('Raw fetch response:', res.status, data);
                if (!res.ok) {
                    if ((res.status === 401 || res.status === 419) && !url.includes('csrf-retry')) {
                        console.warn('Fetch returned status ' + res.status + ', attempting to refresh CSRF and retry');
                        try {
                            await fetch(window.location.origin + '/sanctum/csrf-cookie', { credentials: 'include' });
                            const retryUrl = url + (url.includes('?') ? '&' : '?') + 'csrf-retry=1';
                            res = await fetch(retryUrl, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
                            text = await res.text();
                            try { data = JSON.parse(text); } catch(e) { data = { message: text }; }
                            console.debug('Raw fetch response (retry):', res.status, data);
                        } catch (retryErr) {
                            console.error('Fetch retry failed', retryErr);
                            const err = new Error('Network response was not ok');
                            err.status = res.status;
                            err.response = data;
                            throw err;
                        }
                    }
                }

                if (!res.ok) {
                    console.error('Users fetch failed', { status: res.status, body: data });
                    const err = new Error('Network response was not ok');
                    err.status = res.status;
                    err.response = data;
                    throw err;
                }
                let users = [];
                let meta = null;
                let total = 0;

                if (Array.isArray(data)) {
                    users = data;
                    total = data.length;
                } else if (data && Array.isArray(data.data)) {
                    users = data.data;
                    meta = { current_page: data.current_page, last_page: data.last_page };
                    total = data.total ?? users.length;
                } else if (data && Array.isArray(data.users)) {
                    users = data.users;
                    total = data.total ?? users.length;
                } else if (data && typeof data === 'object') {
                    const values = Object.values(data).filter(v => Array.isArray(v));
                    if (values.length > 0) {
                        users = values[0];
                        total = users.length;
                    }
                }

                document.getElementById('usersCount').textContent = total || users.length || 0;
                renderUsers(users);
                renderPagination(meta);
                } catch (err) {
                console.error('Failed to load users', err, err && err.response ? err.response : null);
                if (err && err.response) console.debug('API error response body:', err.response);
                if (err && err.status === 401) {
                    alert('Bạn chưa đăng nhập hoặc hết phiên làm việc. Vui lòng đăng nhập lại.');
                    window.location.href = '{{ route("login") }}';
                    return;
                }
                alert('Không thể tải danh sách user. Kiểm tra console để biết thêm chi tiết.');
            }
        }

        async function viewUser(userId) {
            try {
                if (window.APIHelper && typeof window.APIHelper.request === 'function') {
                    const params = new URLSearchParams();
                    params.set('per_page', 100);
                    params.set('page', 1);
                    const payload = await window.APIHelper.request('/admin/users?' + params.toString());
                    const usersList = Array.isArray(payload)
                        ? payload
                        : (Array.isArray(payload.data) ? payload.data : (Array.isArray(payload.users) ? payload.users : []));
                    const found = usersList.find(u => Number(u.id) === Number(userId));
                    if (found) { showUserModal(found); return; }
                    alert('Không tìm thấy thông tin user (vui lòng thử tìm kiếm hoặc làm mới trang).');
                    return;
                }

                const params = new URLSearchParams();
                params.set('per_page', 100);
                params.set('page', 1);
                const res = await fetch(`${apiBase}?${params.toString()}`, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
                if (!res.ok) throw new Error('Network response was not ok');
                const payload = await res.json();
                const usersList = Array.isArray(payload)
                    ? payload
                    : (Array.isArray(payload.data) ? payload.data : (Array.isArray(payload.users) ? payload.users : []));
                const found = usersList.find(u => Number(u.id) === Number(userId));
                if (found) {
                    showUserModal(found);
                    return;
                }
                alert('Không tìm thấy thông tin user (vui lòng thử tìm kiếm hoặc làm mới trang).');
            } catch (err) {
                console.error('Error loading user detail', err);
                alert('Có lỗi xảy ra khi tải thông tin user');
            }
        }

        function showUserModal(data) {
            document.getElementById('userDetails').innerHTML = `
                <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 20px;">
                    <div style="width: 80px; height: 80px; background: #ecf0f1; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-user" style="color: #7f8c8d; font-size: 32px;"></i>
                    </div>
                    <div>
                        <h2 style="margin: 0 0 10px 0; color: #2c3e50;">${escapeHtml(data.name)}</h2>
                        <p style="margin: 0; color: #7f8c8d;">Tạo: ${formatDate(data.created_at)}</p>
                        <span class="status-badge status-${data.status}">${getStatusText(data.status)}</span>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div>
                        <h4 style="color: #2c3e50; margin-bottom: 10px;">Thông tin cơ bản</h4>
                        <p><strong>Email:</strong> ${escapeHtml(data.email)}</p>
                        <p><strong>Role:</strong> ${getRoleText(data.role)}</p>
                        <p><strong>Trạng thái:</strong> ${getStatusText(data.status)}</p>
                    </div>
                    <div>
                        <h4 style="color: #2c3e50; margin-bottom: 10px;">Thông tin công ty</h4>
                        ${(data.companies && data.companies.length > 0) ? data.companies.map(company => `
                            <div style="margin-bottom: 10px; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                                <div style="font-weight: 600;">${escapeHtml(company.company_name)}</div>
                                <div style="font-size: 12px; color: #7f8c8d;">Role: ${company.pivot?.role_in_company || ''}</div>
                                <div style="font-size: 12px; color: #7f8c8d;">Status: ${company.pivot?.status || ''}</div>
                            </div>
                        `).join('') : '<p style="color: #7f8c8d;">Không có công ty</p>'}
                    </div>
                </div>
            `;
            document.getElementById('userModal').style.display = 'block';
        }

        async function suspendUser(userId) {
            if (!confirm('Tạm dừng user này?')) return;
            try {
                if (window.APIHelper && typeof window.APIHelper.request === 'function') {
                    const payload = await window.APIHelper.request(`/admin/users/${userId}/suspend`, { method: 'POST' });
                    alert(payload.message || 'Đã tạm dừng user');
                    loadUsers(currentPage);
                    return;
                }

                const res = await fetch(`${apiBase}/${userId}/suspend`, { method: 'POST', credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken } });
                let payload = {};
                try {
                    payload = await res.json();
                } catch (e) {
                    payload = { message: 'Không có phản hồi JSON từ server' };
                }

                if (!res.ok) {
                    // Show server-provided message if available, else generic
                    const msg = payload.message || payload.error || `Lỗi server (${res.status})`;
                    alert(msg);
                    loadUsers(currentPage);
                    return;
                }

                alert(payload.message || 'Đã tạm dừng user');
                loadUsers(currentPage);
            } catch (err) {
                console.error('Suspend failed', err);
                alert(err && err.message ? err.message : 'Không thể tạm dừng user');
            }
        }

        async function activateUser(userId) {
            if (!confirm('Kích hoạt user này?')) return;
            try {
                if (window.APIHelper && typeof window.APIHelper.request === 'function') {
                    const payload = await window.APIHelper.request(`/admin/users/${userId}/reactivate`, { method: 'POST' });
                    alert(payload.message || 'Đã kích hoạt user');
                    loadUsers(currentPage);
                    return;
                }

                const res = await fetch(`${apiBase}/${userId}/reactivate`, { method: 'POST', credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken } });
                if (!res.ok) throw new Error('Network response was not ok');
                const payload = await res.json();
                alert(payload.message || 'Đã kích hoạt user');
                loadUsers(currentPage);
            } catch (err) {
                console.error('Reactivate failed', err);
                alert('Không thể kích hoạt user');
            }
        }

        function deleteUser(userId) {
            if (!confirm('Xóa user này? Hành động này không thể hoàn tác!')) return;
            alert('Xóa user chưa được triển khai trên backend. Nếu bạn muốn, tôi có thể thêm endpoint xóa hoặc tắt nút này.');
        }

        function setupControls() {
            document.getElementById('searchInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') loadUsers(1);
            });
            document.getElementById('roleFilter').addEventListener('change', () => loadUsers(1));
            document.getElementById('statusFilter').addEventListener('change', () => loadUsers(1));
        }

        // Close modal when clicking outside
        document.getElementById('userModal').addEventListener('click', function(e) {
            if (e.target === this) closeUserModal();
        });

        document.addEventListener('DOMContentLoaded', function() {
            setupControls();
            loadUsers(1);
        });
        </script>
@endsection
