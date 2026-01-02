// CV Management JavaScript
// Quản lý danh sách CV của candidate

// Lấy route từ data attribute hoặc global variable
const CV_BUILDER_ROUTE = window.CV_BUILDER_ROUTE || '/candidate/cv-builder';
const CV_VIEW_ROUTE = window.CV_VIEW_ROUTE || '/candidate/cv/view';

/**
 * Lấy auth token từ localStorage
 */
function getAuthToken() {
    const tokenSources = [
        () => localStorage.getItem('authToken'),
        () => localStorage.getItem('token'),
        () => {
            const currentUser = localStorage.getItem('currentUser');
            if (currentUser) {
                try {
                    const user = JSON.parse(currentUser);
                    return user.token || user.auth_token || user.access_token || user.authToken;
                } catch (e) {
                    return null;
                }
            }
            return null;
        }
    ];

    for (const getToken of tokenSources) {
        const token = getToken();
        if (token && token.trim()) {
            return token.trim();
        }
    }

    return null;
}

/**
 * Lấy auth headers cho API request
 */
function getAuthHeaders() {
    const token = getAuthToken();
    const headers = {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
    };
    
    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }

    return headers;
}

/**
 * Tải danh sách CV từ API
 */
async function loadCVs() {
    const cvList = document.getElementById('cv-list');
    if (!cvList) {
        console.error('CV list container not found');
        return;
    }

    try {
        const response = await fetch('/api/resumes', {
            method: 'GET',
            headers: getAuthHeaders(),
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error('Không thể tải danh sách CV');
        }

        let result;
        try {
            result = await response.json();
        } catch (e) {
            console.error('Error parsing response:', e);
            throw new Error('Không thể đọc phản hồi từ server. Vui lòng thử lại.');
        }
        
        if (!result.data || result.data.length === 0) {
            cvList.innerHTML = `
                <div class="empty-state" style="grid-column: 1 / -1;">
                    <i>📄</i>
                    <h3>Chưa có CV nào</h3>
                    <p>Bắt đầu tạo CV đầu tiên của bạn ngay bây giờ!</p>
                    <a href="${CV_BUILDER_ROUTE}" class="btn-create-cv" style="margin-top: 20px;">
                        <i class="fas fa-plus"></i>
                        Tạo CV mới
                    </a>
                </div>
            `;
            return;
        }

        cvList.innerHTML = result.data.map(cv => {
            const createdDate = new Date(cv.created_at).toLocaleDateString('vi-VN');
            const updatedDate = cv.updated_at ? new Date(cv.updated_at).toLocaleDateString('vi-VN') : null;
            
            return `
                <div class="cv-card">
                    <div class="cv-card-header">
                        <h3 class="cv-title">${escapeHtml(cv.title || 'CV chưa đặt tên')}</h3>
                        <div class="cv-actions">
                            <button class="btn-action" onclick="viewCV(${cv.id})" title="Xem CV" style="color: #06b6d4;">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn-action" onclick="editCV(${cv.id})" title="Chỉnh sửa">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-action" onclick="deleteCV(${cv.id})" title="Xóa">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="cv-meta">
                        <p><i class="fas fa-calendar"></i> Tạo ngày: ${createdDate}</p>
                        ${updatedDate ? `<p><i class="fas fa-clock"></i> Cập nhật: ${updatedDate}</p>` : ''}
                    </div>
                </div>
            `;
        }).join('');
    } catch (error) {
        console.error('Error loading CVs:', error);
        cvList.innerHTML = `
            <div class="empty-state" style="grid-column: 1 / -1;">
                <i>⚠️</i>
                <h3>Lỗi tải dữ liệu</h3>
                <p>${escapeHtml(error.message)}</p>
                <button class="btn-create-cv" onclick="loadCVs()" style="margin-top: 20px;">
                    <i class="fas fa-redo"></i>
                    Thử lại
                </button>
            </div>
        `;
    }
}

/**
 * Xem CV - chuyển đến trang xem CV với ID
 */
function viewCV(id) {
    window.location.href = `${CV_VIEW_ROUTE}?id=${id}`;
}

/**
 * Chỉnh sửa CV - chuyển đến trang CV builder với ID
 */
function editCV(id) {
    window.location.href = `${CV_BUILDER_ROUTE}?id=${id}`;
}

/**
 * Xóa CV
 */
async function deleteCV(id) {
    if (!confirm('Bạn có chắc chắn muốn xóa CV này?')) {
        return;
    }

    try {
        const response = await fetch(`/api/resumes/${id}`, {
            method: 'DELETE',
            headers: getAuthHeaders(),
            credentials: 'same-origin'
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || 'Không thể xóa CV');
        }

        alert('Xóa CV thành công!');
        loadCVs();
    } catch (error) {
        console.error('Error deleting CV:', error);
        alert('Có lỗi xảy ra khi xóa CV: ' + error.message);
    }
}

/**
 * Escape HTML để tránh XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Khởi tạo khi DOM ready
document.addEventListener('DOMContentLoaded', function() {
    // Lấy route từ data attribute nếu có
    const container = document.querySelector('.cv-list-container');
    if (container && container.dataset.cvBuilderRoute) {
        window.CV_BUILDER_ROUTE = container.dataset.cvBuilderRoute;
    }
    
    loadCVs();
});

// Export functions để có thể gọi từ global scope
window.loadCVs = loadCVs;
window.viewCV = viewCV;
window.editCV = editCV;
window.deleteCV = deleteCV;

