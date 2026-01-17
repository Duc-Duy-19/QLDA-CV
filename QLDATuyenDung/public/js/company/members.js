// Configuration
const API_BASE_URL = window.API_BASE_URL || '/api';
const DEBUG_MODE = true;

// DOM Elements
let membersTableBody, loadingIndicator, membersTable, noMembersMessage;
let currentCompanyId = null;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeElements();
    loadMembers();
    setupSearchFunctionality();
});

function initializeElements() {
    membersTableBody = document.getElementById('membersTableBody');
    loadingIndicator = document.getElementById('loadingIndicator');
    membersTable = document.getElementById('membersTable');
    noMembersMessage = document.getElementById('noMembersMessage');
    
    if (DEBUG_MODE) {
        console.log('Elements initialized:', {
            membersTableBody: !!membersTableBody,
            loadingIndicator: !!loadingIndicator,
            membersTable: !!membersTable,
            noMembersMessage: !!noMembersMessage
        });
        
        // Debug current user info
        const currentUser = localStorage.getItem('currentUser');
        const authToken = getAuthToken();
        console.log('🔍 Current user debug:', {
            hasAuthToken: !!authToken,
            tokenType: typeof authToken,
            hasCurrentUser: !!currentUser,
            currentUserData: currentUser ? JSON.parse(currentUser) : null
        });
    }
}

// Authentication token management
function getAuthToken() {
    // Try multiple possible token storage locations
    const tokenSources = [
        () => localStorage.getItem('auth_token'),
        () => localStorage.getItem('authToken'),
        () => localStorage.getItem('token'),
        () => localStorage.getItem('bearer_token'),
        () => {
            const currentUser = localStorage.getItem('currentUser');
            if (currentUser) {
                try {
                    const user = JSON.parse(currentUser);
                    return user.token || user.auth_token || user.access_token;
                } catch (e) {
                    console.warn('Error parsing currentUser:', e);
                    return null;
                }
            }
            return null;
        }
    ];

    for (const getToken of tokenSources) {
        const token = getToken();
        if (token && token.trim()) {
            if (DEBUG_MODE) {
                console.log('Found auth token');
            }
            return token.trim();
        }
    }

    console.error('No authentication token found');
    return null;
}

function getCompanyId() {
    if (currentCompanyId) {
        return currentCompanyId;
    }
    
    // Try to get company ID from localStorage or user data
    const currentUser = localStorage.getItem('currentUser');
    if (currentUser) {
        try {
            const user = JSON.parse(currentUser);
            if (user.company_id) {
                currentCompanyId = user.company_id;
                return currentCompanyId;
            }
        } catch (e) {
            console.warn('Error parsing currentUser for company ID:', e);
        }
    }
    
    // Default fallback - returning null is safer than forcing company ID 1
    // Forcing 1 caused the UI to always show the Brightstar company when
    // no company_id was available for the logged-in user. Return null so
    // callers must explicitly handle the "unknown company" case.
    return null;
}

// API calls
async function loadMembers() {
    showLoading(true);
    
    const token = getAuthToken();
    if (!token) {
        if (DEBUG_MODE) console.warn('No auth token found, prompting user to login for token-based auth');
        // Prompt developer/user to login and retry (DEV helper). This will call /api/login
        // and store the returned token to localStorage under key 'token'. Then reload.
        await promptLoginAndRetry();
        showLoading(false);
        return;
    }
    
    let companyId = getCompanyId();

    // If companyId is the default fallback (1) or not present on currentUser,
    // try to fetch the authoritative company for the logged-in user from the backend.
    if (token) {
        try {
            const currentUserRaw = localStorage.getItem('currentUser');
            let needsRefresh = false;
            if (!currentUserRaw) needsRefresh = true;
            else {
                try {
                    const cu = JSON.parse(currentUserRaw);
                    if (!cu.company_id && !(cu.companyInfo && cu.companyInfo.id)) needsRefresh = true;
                } catch (e) {
                    needsRefresh = true;
                }
            }

            if (needsRefresh || companyId === 1) {
                if (DEBUG_MODE) console.log('Attempting to get user company from /api/my-company to avoid wrong fallback id');
                const myCompanyRes = await fetch(`${API_BASE_URL}/my-company`, {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });

                if (myCompanyRes.ok) {
                    const myData = await myCompanyRes.json();
                    if (myData.company && myData.company.id) {
                        companyId = myData.company.id;
                        currentCompanyId = companyId;
                        // persist to localStorage.currentUser if possible
                        try {
                            const cur = JSON.parse(localStorage.getItem('currentUser') || '{}');
                            cur.company_id = myData.company.id;
                            cur.companyInfo = myData.company;
                            localStorage.setItem('currentUser', JSON.stringify(cur));
                        } catch (e) {
                            if (DEBUG_MODE) console.warn('Could not persist my-company to currentUser:', e);
                        }
                    }
                } else {
                    if (DEBUG_MODE) console.warn('/api/my-company returned', myCompanyRes.status);
                }
            }
        } catch (err) {
            if (DEBUG_MODE) console.warn('Error while fetching /api/my-company:', err);
        }
    }

    try {
        // If after trying to read localStorage and /api/my-company we still
        // don't have a companyId, abort — do not default to company 1.
        if (!companyId) {
            if (DEBUG_MODE) console.warn('No companyId resolved for current user, aborting members load to avoid loading company 1');
            showError('Không xác định công ty cho tài khoản hiện tại. Vui lòng đăng nhập lại hoặc liên hệ quản trị viên.');
            showLoading(false);
            return;
        }

        const response = await fetch(`${API_BASE_URL}/companies/${companyId}/users`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });
        
        if (DEBUG_MODE) {
            console.log('Load members response status:', response.status);
        }
        
        if (response.status === 401) {
            showError('Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.');
            showLoading(false);
            return;
        }
        
        if (response.status === 403) {
            const errorData = await response.json();
            showError(errorData.error || 'Bạn không có quyền truy cập chức năng này.');
            showLoading(false);
            return;
        }
        
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            const errorMessage = errorData.error || errorData.message || `HTTP error! status: ${response.status}`;
            throw new Error(errorMessage);
        }
        
        const data = await response.json();
        
        if (DEBUG_MODE) {
            console.log('Members data received:', data);
            console.log('Data structure analysis:');
            console.log('- data.data:', data.data);
            console.log('- data.users:', data.users);
            console.log('- data.members:', data.members);
            console.log('- Direct data:', data);
        }
        
        // Try different possible data structures for Laravel pagination
        let membersArray = [];
        if (data.members && data.members.data && Array.isArray(data.members.data)) {
            // Laravel pagination format: { members: { data: [...], current_page: 1, ... } }
            membersArray = data.members.data;
        } else if (Array.isArray(data.members)) {
            // Direct array format: { members: [...] }
            membersArray = data.members;
        } else if (Array.isArray(data.data)) {
            membersArray = data.data;
        } else if (Array.isArray(data.users)) {
            membersArray = data.users;
        } else if (Array.isArray(data)) {
            membersArray = data;
        } else if (data.success && Array.isArray(data.data)) {
            membersArray = data.data;
        }
        
        if (DEBUG_MODE) {
            console.log('Final members array:', membersArray);
        }
        
        displayMembers(membersArray);
        
    } catch (error) {
        console.error('Error loading members:', error);
        showError('Có lỗi xảy ra khi tải danh sách thành viên: ' + error.message);
    } finally {
        showLoading(false);
    }
}

function displayMembers(members) {
    if (DEBUG_MODE) {
        console.log('displayMembers called with:', members);
        console.log('members type:', typeof members);
        console.log('members is array:', Array.isArray(members));
    }
    
    // Ensure members is always an array
    if (!Array.isArray(members)) {
        console.warn('Members is not an array, converting...', members);
        members = [];
    }
    
    if (!members || members.length === 0) {
        showNoMembers();
        // Update member count to 0
        const memberCountElement = document.getElementById('members-count');
        if (memberCountElement) {
            memberCountElement.textContent = '0 thành viên';
        }
        return;
    }
    
    membersTableBody.innerHTML = '';
    
    members.forEach(member => {
        const row = createMemberRow(member);
        membersTableBody.appendChild(row);
    });
    
    // Update member count
    const memberCountElement = document.getElementById('members-count');
    if (memberCountElement) {
        memberCountElement.textContent = `${members.length} thành viên`;
    }
    
    showMembersTable();
}

function createMemberRow(member) {
    const tr = document.createElement('tr');
    
    // Avatar
    const avatarCell = document.createElement('td');
    if (member.user && member.user.avatar) {
        avatarCell.innerHTML = `<img src="${member.user.avatar}" alt="${member.user.name}" class="member-avatar">`;
    } else {
        // Cho pending invites, hiển thị chữ cái đầu của email
        const initials = member.user ? 
            member.user.name.charAt(0).toUpperCase() : 
            (member.email ? member.email.charAt(0).toUpperCase() : 'U');
        avatarCell.innerHTML = `<div class="member-avatar-placeholder">${initials}</div>`;
    }
    tr.appendChild(avatarCell);
    
    // Name
    const nameCell = document.createElement('td');
    nameCell.textContent = member.user ? member.user.name : 'Chưa đăng ký';
    tr.appendChild(nameCell);
    
    // Email - ưu tiên từ member.user.email, nếu không có thì từ member.email
    const emailCell = document.createElement('td');
    const email = member.user ? member.user.email : (member.email || 'Unknown');
    emailCell.textContent = email;
    tr.appendChild(emailCell);
    
    // Role - use role_in_company from CompanyUser model
    const roleCell = document.createElement('td');
    const role = member.role_in_company || member.role || 'member';
    roleCell.innerHTML = `<span class="role-badge role-${role.toLowerCase()}">${getRoleDisplayName(role)}</span>`;
    tr.appendChild(roleCell);
    
    // Join date - use joined_at from CompanyUser model
    const joinDateCell = document.createElement('td');
    const joinDate = new Date(member.joined_at || member.created_at);
    joinDateCell.textContent = joinDate.toLocaleDateString('vi-VN');
    tr.appendChild(joinDateCell);
    
    // Status
    const statusCell = document.createElement('td');
    const status = member.status || 'active';
    statusCell.innerHTML = `<span class="status-badge status-${status}">${getStatusDisplayName(status)}</span>`;
    tr.appendChild(statusCell);
    
    // Actions
    const actionsCell = document.createElement('td');
    actionsCell.innerHTML = `
        <button class="action-btn btn-edit" onclick="showEditMemberModal(${member.id})" title="Chỉnh sửa">
            <i class="fas fa-edit"></i>
        </button>
        <button class="action-btn btn-delete" onclick="confirmDeleteMember(${member.id}, '${member.user ? member.user.name : 'Unknown'}')" title="Xóa">
            <i class="fas fa-trash"></i>
        </button>
    `;
    tr.appendChild(actionsCell);
    
    return tr;
}

function getRoleDisplayName(role) {
    const roleNames = {
        'Owner': 'Chủ sở hữu',
        'Admin': 'Quản trị viên',
        'Manager': 'Quản lý',
        'Recruiter': 'Nhân viên tuyển dụng',
        'Member': 'Thành viên',
        'admin': 'Quản trị viên',
        'manager': 'Quản lý',
        'recruiter': 'Nhân viên tuyển dụng',
        'member': 'Thành viên',
        'owner': 'Chủ sở hữu'
    };
    return roleNames[role] || role;
}

function getStatusDisplayName(status) {
    const statusNames = {
        'active': 'Hoạt động',
        'pending': 'Chờ xác nhận',
        'inactive': 'Không hoạt động'
    };
    return statusNames[status] || status;
}

function showLoading(show) {
    if (show) {
        loadingIndicator.style.display = 'block';
        membersTable.style.display = 'none';
        noMembersMessage.style.display = 'none';
    } else {
        loadingIndicator.style.display = 'none';
    }
}

function showMembersTable() {
    membersTable.style.display = 'block';
    noMembersMessage.style.display = 'none';
}

function showNoMembers() {
    membersTable.style.display = 'none';
    noMembersMessage.style.display = 'block';
}

// Add member functionality - only by email
async function addMember() {
    const email = document.getElementById('memberEmail').value;
    const role = document.getElementById('memberRole').value;
    const message = document.getElementById('memberMessage').value;
    
    if (!email || !role) {
        showError('Vui lòng điền đầy đủ thông tin bắt buộc.');
        return;
    }
    
    const token = getAuthToken();
    if (!token) {
        showError('Không tìm thấy token xác thực.');
        return;
    }
    
    const companyId = getCompanyId();
    
    try {
        const requestBody = {
            email: email,
            role_in_company: role
        };
        
        // Note: message is not supported by backend AddCompanyUserRequest
        if (DEBUG_MODE) {
            console.log('Sending request to:', `${API_BASE_URL}/companies/${companyId}/users/add`);
            console.log('Request body:', requestBody);
            console.log('Auth token:', token ? 'Present' : 'Missing');
        }
        
        const response = await fetch(`${API_BASE_URL}/companies/${companyId}/users/add`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(requestBody)
        });
        
        if (DEBUG_MODE) {
            console.log('Add member response status:', response.status);
            console.log('Response headers:', response.headers);
        }
        
        // Get response text first to see what we received
        const responseText = await response.text();
        
        if (DEBUG_MODE) {
            console.log('Raw response:', responseText);
        }
        
        if (!response.ok) {
            let errorData;
            try {
                errorData = JSON.parse(responseText);
            } catch (e) {
                errorData = { message: responseText };
            }
            throw new Error(errorData.error || errorData.message || `HTTP error! status: ${response.status}`);
        }
        
        const data = JSON.parse(responseText);
        
        if (DEBUG_MODE) {
            console.log('Add member success:', data);
        }
        
        showSuccess('Lời mời đã được gửi thành công!');
        
        // Close modal and reset form
        closeModal('addMemberModal');
        
        // Reload members list
        loadMembers();
        
    } catch (error) {
        console.error('Error adding member:', error);
        showError('Có lỗi xảy ra khi gửi lời mời: ' + error.message);
    }
}



// Edit member functionality
let currentEditingMember = null;

function showEditMemberModal(memberId) {
    // Find member in current data
    const rows = membersTableBody.querySelectorAll('tr');
    let memberData = null;
    
    // For now, we'll need to make an API call to get member details
    // or store member data in a way that's accessible
    // This is a simplified version - you might want to store member data globally
    
    // Get member data from the table row
    const row = Array.from(rows).find(row => {
        const editBtn = row.querySelector('.btn-edit');
        return editBtn && editBtn.getAttribute('onclick').includes(memberId);
    });
    
    if (row) {
        const cells = row.querySelectorAll('td');
        const name = cells[1].textContent;
        const email = cells[2].textContent;
        const roleElement = cells[3].querySelector('.role-badge');
        const role = roleElement.className.split(' ').find(cls => cls.startsWith('role-')).replace('role-', '');
        
        // Populate edit form
        document.getElementById('editMemberId').value = memberId;
        document.getElementById('editMemberName').value = name;
        document.getElementById('editMemberEmail').value = email;
        document.getElementById('editMemberRole').value = role;
        
        currentEditingMember = { id: memberId, name, email, role };
        
        // Show modal
        document.getElementById('editMemberModal').style.display = 'flex';
    }
}

async function updateMember() {
    const memberId = document.getElementById('editMemberId').value;
    const role = document.getElementById('editMemberRole').value;
    
    if (!memberId || !role) {
        showError('Thông tin không hợp lệ.');
        return;
    }
    
    const token = getAuthToken();
    if (!token) {
        showError('Không tìm thấy token xác thực.');
        return;
    }
    
    const companyId = getCompanyId();
    
    try {
        const response = await fetch(`${API_BASE_URL}/companies/${companyId}/users/${memberId}/role`, {
            method: 'PUT',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                role: role
            })
        });
        
        if (DEBUG_MODE) {
            console.log('Update member response status:', response.status);
        }
        
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (DEBUG_MODE) {
            console.log('Update member success:', data);
        }
        
        showSuccess('Thông tin thành viên đã được cập nhật!');
        
        // Close modal
        closeModal('editMemberModal');
        
        // Reload members list
        loadMembers();
        
    } catch (error) {
        console.error('Error updating member:', error);
        showError('Có lỗi xảy ra khi cập nhật thông tin: ' + error.message);
    }
}

// Delete member functionality
function confirmDeleteMember(memberId, memberName) {
    if (confirm(`Bạn có chắc chắn muốn xóa thành viên "${memberName}" khỏi công ty?`)) {
        deleteMember(memberId);
    }
}

async function deleteMember(memberId) {
    const token = getAuthToken();
    if (!token) {
        showError('Không tìm thấy token xác thực.');
        return;
    }
    
    const companyId = getCompanyId();
    
    try {
        const response = await fetch(`${API_BASE_URL}/companies/${companyId}/users/${memberId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });
        
        if (DEBUG_MODE) {
            console.log('Delete member response status:', response.status);
        }
        
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (DEBUG_MODE) {
            console.log('Delete member success:', data);
        }
        
        showSuccess('Thành viên đã được xóa khỏi công ty!');
        
        // Reload members list
        loadMembers();
        
    } catch (error) {
        console.error('Error deleting member:', error);
        showError('Có lỗi xảy ra khi xóa thành viên: ' + error.message);
    }
}

// Search functionality
function setupSearchFunctionality() {
    const searchInput = document.getElementById('searchMembers');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            filterMembers(searchTerm);
        });
    }
}

function filterMembers(searchTerm) {
    const rows = membersTableBody.querySelectorAll('tr');
    
    rows.forEach(row => {
        const name = row.cells[1].textContent.toLowerCase();
        const email = row.cells[2].textContent.toLowerCase();
        const role = row.cells[3].textContent.toLowerCase();
        
        const isVisible = name.includes(searchTerm) || 
                         email.includes(searchTerm) || 
                         role.includes(searchTerm);
        
        row.style.display = isVisible ? '' : 'none';
    });
}

// Modal functions
function showAddMemberModal() {
    document.getElementById('addMemberModal').style.display = 'flex';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    
    // Reset forms when closing modals
    if (modalId === 'addMemberModal') {
        document.getElementById('addMemberForm').reset();
    }
}

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
});

// Utility functions
function showSuccess(message) {
    const successElement = document.getElementById('successMessage');
    const messageText = document.getElementById('successMessageText');
    
    messageText.textContent = message;
    successElement.style.display = 'flex';
    
    // Auto hide after 3 seconds
    setTimeout(() => {
        successElement.style.display = 'none';
    }, 3000);
}

function showError(message) {
    alert('Lỗi: ' + message);
}

// DEV helper: prompt for email/password and call API login to obtain a token
async function promptLoginAndRetry() {
    try {
        const proceed = confirm('Không tìm thấy token. Bạn có muốn đăng nhập ngay để lấy token (chỉ dùng trên môi trường phát triển)?');
        if (!proceed) return;

        const email = prompt('Email (dev):');
        if (!email) { alert('Email là bắt buộc'); return; }
        const password = prompt('Password (dev):');
        if (!password) { alert('Password là bắt buộc'); return; }

        if (DEBUG_MODE) console.log('Attempting login for', email);

        const res = await fetch(`${API_BASE_URL}/login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ email: email, password: password })
        });

        if (!res.ok) {
            let errText = await res.text().catch(() => 'No response body');
            try { errText = JSON.parse(errText); } catch(e) {}
            alert('Đăng nhập thất bại: ' + (errText.message || JSON.stringify(errText)));
            return;
        }

        const data = await res.json();
        if (DEBUG_MODE) console.log('Login response:', data);

        const token = data.token || data.access_token || (data.user && data.user.token) || null;
        if (!token) {
            alert('Không nhận được token từ server. Kiểm tra response: ' + JSON.stringify(data));
            return;
        }

        localStorage.setItem('token', token);
        alert('Đăng nhập thành công, trang sẽ được reload để tiếp tục.');
        // reload so other scripts pick up the token
        location.reload();

    } catch (err) {
        console.error('promptLoginAndRetry error', err);
        alert('Lỗi khi gọi login: ' + (err.message || err));
    }
}

// Global functions for onclick handlers
window.addMember = addMember;
window.showEditMemberModal = showEditMemberModal;
window.updateMember = updateMember;
window.confirmDeleteMember = confirmDeleteMember;
window.showAddMemberModal = showAddMemberModal;
window.closeModal = closeModal;