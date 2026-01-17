const API_BASE_URL = window.API_BASE_URL || '/api'; // Use global config when available, fallback to relative /api
const DEBUG_MODE = false; // Set to true for debugging

// Debug localStorage content
function debugLocalStorage() {
    if (!DEBUG_MODE) return;
    console.log('=== LOCAL STORAGE DEBUG ===');
    console.log('All localStorage keys:', Object.keys(localStorage));
    
    for (let key of Object.keys(localStorage)) {
        const value = localStorage.getItem(key);
        if (key.toLowerCase().includes('token') || key.toLowerCase().includes('auth') || key === 'currentUser') {
            try {
                const parsed = JSON.parse(value);
                console.log(`${key}:`, typeof parsed === 'object' ? parsed : value.substring(0, 50) + '...');
            } catch {
                console.log(`${key}:`, value.substring(0, 50) + '...');
            }
        }
    }
}

// Load company data when page loads
document.addEventListener('DOMContentLoaded', function() {
    if (DEBUG_MODE) {
        console.log('=== COMPANY INFO DEBUG ===');
        // Debug localStorage first
        debugLocalStorage();
        // Test API connection
        testApiConnection();
        // Test token validation  
        testTokenValidation();
    }
    
    // Load company data
    loadCompanyData();
});

// Get auth token from localStorage
function getAuthToken() {
    // Thử lấy token theo thứ tự ưu tiên
    let token = localStorage.getItem('token') || 
                localStorage.getItem('auth_token') || 
                localStorage.getItem('access_token') ||
                localStorage.getItem('bearer_token') ||
                localStorage.getItem('authToken'); // Thêm authToken
    
    // Nếu không tìm thấy, thử lấy từ currentUser
    if (!token && currentUser) {
        try {
            const userData = JSON.parse(currentUser);
            token = userData.token || userData.access_token || userData.auth_token || userData.authToken;
        } catch (e) {
            if (DEBUG_MODE) console.log('Cannot parse currentUser for token');
        }
    }
    
    if (token) {
        // Đảm bảo có Bearer prefix
        return token.startsWith('Bearer ') ? token : `Bearer ${token}`;
    }
    
    return null;
}

// Load company data from Laravel API
function loadCompanyData() {
    const token = getAuthToken();
    const currentUser = localStorage.getItem('currentUser');
    
    if (DEBUG_MODE) {
        console.log('=== LOAD COMPANY DATA ===');
        console.log('Token result:', token ? `Found: ${token.substring(0, 30)}...` : 'Not found');
        
        if (currentUser) {
            try {
                const userData = JSON.parse(currentUser);
                console.log('Current user data:', {
                    id: userData.id,
                    name: userData.name,
                    email: userData.email,
                    role: userData.role,
                    hasToken: !!userData.token,
                    hasAccessToken: !!userData.access_token
                });
            } catch (e) {
                console.log('Error parsing currentUser');
            }
        }
    }
    
    if (!token) {
        console.warn('❌ No auth token found after checking all possibilities');
        // Fallback: Kiểm tra xem có currentUser không
        if (currentUser) {
            const user = JSON.parse(currentUser);
            if (DEBUG_MODE) console.log('Using fallback mock data for user:', user.name);
            showMockCompanyData(user);
            return;
        }
        showErrorMessage('Vui lòng đăng nhập để xem thông tin công ty');
        return;
    }
    
    if (DEBUG_MODE) {
        console.log('Loading company data with Laravel API...');
        console.log('API URL:', `${API_BASE_URL}/my-company`);
        try { console.log('Token present:', !!token); } catch(e){}
    }
    
    // Gọi API Laravel để lấy thông tin công ty của employer hiện tại
    fetch(`${API_BASE_URL}/my-company`, {
        method: 'GET',
        headers: {
            'Authorization': token,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (DEBUG_MODE) {
            console.log(`API Response Status: ${response.status} ${response.statusText}`);
        }
        
        if (!response.ok) {
            // Log chi tiết để debug
            if (DEBUG_MODE) {
                console.error(`API Error Details:`, {
                    status: response.status,
                    statusText: response.statusText,
                    url: response.url,
                    headers: response.headers
                });
            }
            
            if (response.status === 401) {
                console.error('Token might be invalid or expired');
                // Thử xóa token cũ và fallback
                localStorage.removeItem('token');
                localStorage.removeItem('authToken');
                throw new Error('Token không hợp lệ hoặc đã hết hạn. Vui lòng đăng nhập lại');
            } else if (response.status === 403) {
                throw new Error('Bạn không có quyền truy cập thông tin này');
            } else if (response.status === 404) {
                throw new Error('Không tìm thấy thông tin công ty');
            }
            throw new Error(`Lỗi API: ${response.status} ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        if (DEBUG_MODE) {
            console.log('Company Data Response:', data);
            try { console.log('Response keys:', Object.keys(data)); } catch(e){}
        }
        // Kiểm tra các format response có thể
        let companyData = null;

        if (data.company) {
            // Format: { company: {...}, user_role: "..." }
            companyData = data.company;
            if (DEBUG_MODE) console.log('Found company in data.company');
        } else if (data.data && data.data.company) {
            // Format: { success: true, data: { company: {...} } }
            companyData = data.data.company;
            if (DEBUG_MODE) console.log('Found company in data.data.company');
        } else if (data.data) {
            // Format: { success: true, data: {...} } - data chính là company
            companyData = data.data;
            if (DEBUG_MODE) console.log('Found company in data.data');
        } else if (data.id || data.company_name) {
            // Response trực tiếp là company object
            companyData = data;
            if (DEBUG_MODE) console.log('Response is direct company object');
        }
        
        // Kiểm tra xem company object có dữ liệu hay không
        if (companyData) {
            if (DEBUG_MODE) {
                console.log('Company data found:', companyData);
            }
            
            // Kiểm tra company object có empty không
            const companyKeys = Object.keys(companyData);
            const hasData = companyKeys.length > 0 && (companyData.id || companyData.company_name || companyData.name);
            
            if (DEBUG_MODE) {
                console.log('Company keys:', companyKeys);
                console.log('Company has meaningful data:', hasData);
            }
            
                if (hasData) {
                    updateCompanyInfo(companyData);
                    // Ensure global and storage reflect authoritative company from API
                    try {
                        // Update global var that other scripts may read
                        window.COMPANY_ID = companyData.id;
                        window.currentCompanyId = companyData.id;

                        // Update localStorage.currentUser if present so other parts of the SPA use the same company
                        const cu = localStorage.getItem('currentUser');
                        if (cu) {
                            try {
                                const userObj = JSON.parse(cu);
                                // Keep existing keys but prefer API company info
                                userObj.company_id = companyData.id;
                                userObj.companyInfo = companyData;
                                localStorage.setItem('currentUser', JSON.stringify(userObj));
                                localStorage.setItem('isLoggedIn', 'true');
                            } catch (e) {
                                console.warn('Could not update currentUser in localStorage', e);
                            }
                        }

                        // Update any visible elements that may show company name elsewhere
                        try {
                            // main id-based name
                            const mainName = document.getElementById('company-name');
                            if (mainName) mainName.textContent = companyData.company_name || mainName.textContent;

                            // update all elements with common class selectors
                            document.querySelectorAll('.company-name').forEach(el => {
                                // avoid overwriting inputs
                                if (el.tagName.toLowerCase() !== 'input' && el.tagName.toLowerCase() !== 'textarea') {
                                    el.textContent = companyData.company_name || el.textContent;
                                }
                            });

                            // update document title to reflect company
                            if (companyData.company_name) {
                                document.title = companyData.company_name + ' - WebCV';
                            }
                        } catch (e) {
                            console.warn('Could not update all UI company name placeholders', e);
                        }
                    } catch (e) {
                        console.warn('Error while normalizing company authoritative data', e);
                    }
                } else {
                console.warn('Company object is empty or missing essential data');
                throw new Error('Không tìm thấy thông tin công ty. User có thể chưa thuộc công ty nào hoặc công ty chưa được duyệt.');
            }
        } else {
            console.error('No company data found in response:', data);
            throw new Error(data.error || data.message || 'Không tìm thấy dữ liệu công ty trong response');
        }
    })
    .catch(error => {
        console.error('Error loading company data:', error);
        
        // Hiển thị thông báo lỗi chi tiết
        if (error.message.includes('chưa thuộc công ty')) {
            showCompanyNotFoundMessage();
        } else {
            showErrorMessage(error.message || 'Không thể tải thông tin công ty');
        }
    });
}

// Update company information display
function updateCompanyInfo(company) {
    // Update company name
    const companyName = document.getElementById('company-name');
    if (companyName) companyName.textContent = company.company_name || 'Chưa cập nhật';
    
    // Update company size
    const companySize = document.getElementById('company-size');
    if (companySize) {
        const employeeCount = company.employee_count || 0;
        let sizeText = 'Chưa cập nhật';
        
        if (employeeCount > 0) {
            if (employeeCount < 50) sizeText = '1-50 nhân viên';
            else if (employeeCount < 200) sizeText = '50-200 nhân viên';
            else if (employeeCount < 500) sizeText = '200-500 nhân viên';
            else if (employeeCount < 1000) sizeText = '500-1000 nhân viên';
            else sizeText = `${employeeCount.toLocaleString('vi-VN')} nhân viên`;
        }
        
        companySize.innerHTML = `
            <i class="fas fa-users"></i>
            <span>${sizeText}</span>
        `;
    }
    
    // Update description
    const companyDescription = document.getElementById('company-description');
    if (companyDescription) {
        companyDescription.textContent = company.description || 'Chưa có mô tả công ty';
    }
    
    // Update contact info
    const companyAddress = document.getElementById('company-address');
    if (companyAddress) companyAddress.textContent = company.address || 'Chưa cập nhật địa chỉ';
    
    const companyPhone = document.getElementById('company-phone');
    if (companyPhone) companyPhone.textContent = company.phone || 'Chưa cập nhật số điện thoại';
    
    const companyEmail = document.getElementById('company-email');
    if (companyEmail) companyEmail.textContent = company.email || 'Chưa cập nhật email';
    
    const companyWebsite = document.getElementById('company-website');
    if (companyWebsite) {
        if (company.website && company.website !== '#') {
            companyWebsite.href = company.website;
            companyWebsite.textContent = company.website;
        } else {
            companyWebsite.href = '#';
            companyWebsite.textContent = 'Chưa cập nhật website';
        }
    }
    
    // Update company logo
    const companyLogo = document.getElementById('company-logo-img');
    if (companyLogo && company.logo) {
        companyLogo.src = company.logo;
        companyLogo.alt = company.company_name || 'Company Logo';
    }
}

// Test API connection
function testApiConnection() {
    console.log('Testing API connection...');
    
    fetch(`${API_BASE_URL}/my-company`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        console.log('API Test - Status:', response.status);
        console.log('API Test - StatusText:', response.statusText);
        return response.json();
    })
    .then(data => {
        console.log('API Test - Response:', data);
    })
    .catch(error => {
        console.error('API Test - Error:', error);
        console.log('API might be down or URL incorrect');
    });
}

// Test token validation
function testTokenValidation() {
    const token = getAuthToken();
    if (!token) {
        console.log('No token to test');
        return;
    }
    
    console.log('Testing token validation...');
    console.log('Using token:', token.substring(0, 30) + '...');
    
    fetch(`${API_BASE_URL}/my-company`, {
        method: 'GET',
        headers: {
            'Authorization': token,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        console.log(`Token Test - Status: ${response.status} ${response.statusText}`);
        if (response.status === 401) {
            console.error('❌ Token is invalid or expired');
        } else if (response.status === 200) {
            console.log('✅ Token is valid');
        }
        return response.json();
    })
    .then(data => {
        console.log('Token Test - Response:', data);
        console.log('Token Test - Response Structure:');
        console.log('- Keys:', Object.keys(data));
        console.log('- Type:', typeof data);
        console.log('- Has company?', !!data.company);
        console.log('- Has data?', !!data.data);
        console.log('- Has company_name?', !!data.company_name);
        
        if (data.company) {
            console.log('- Company keys:', Object.keys(data.company));
        }
        if (data.data) {
            console.log('- Data keys:', Object.keys(data.data));
        }
    })
    .catch(error => {
        console.error('Token Test - Error:', error);
    });
}

// Edit company info function
function editCompanyInfo() {
    if (!window.currentCompanyId) {
        showErrorMessage('Không tìm thấy thông tin công ty để chỉnh sửa');
        return;
    }
    
    // Tạo và hiển thị modal chỉnh sửa
    showEditModal();
}

// Show edit modal
function showEditModal() {
    // Tạo modal HTML
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.id = 'editCompanyModal';
    modal.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Chỉnh sửa thông tin công ty</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editCompanyForm">
                        <div class="mb-3">
                            <label for="editCompanyName" class="form-label">Tên công ty *</label>
                            <input type="text" class="form-control" id="editCompanyName" required>
                        </div>
                        <div class="mb-3">
                            <label for="editDescription" class="form-label">Mô tả công ty</label>
                            <textarea class="form-control" id="editDescription" rows="4"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="editAddress" class="form-label">Địa chỉ</label>
                            <input type="text" class="form-control" id="editAddress">
                        </div>
                        <div class="mb-3">
                            <label for="editPhone" class="form-label">Số điện thoại</label>
                            <input type="tel" class="form-control" id="editPhone">
                        </div>
                        <div class="mb-3">
                            <label for="editEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="editEmail">
                        </div>
                        <div class="mb-3">
                            <label for="editWebsite" class="form-label">Website</label>
                            <input type="url" class="form-control" id="editWebsite" placeholder="https://">
                        </div>
                        <div class="mb-3">
                            <label for="editLogo" class="form-label">Logo công ty</label>
                            <input type="file" class="form-control" id="editLogo" accept="image/jpeg,image/png,image/jpg,image/gif">
                            <small style="color: #666; font-size: 12px;">Chọn file hình ảnh logo (jpg, png, gif) - tối đa 2MB</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" onclick="saveCompanyInfo()">
                        <span id="saveBtn">Lưu thay đổi</span>
                    </button>
                </div>
            </div>
        </div>
    `;
    
    // Thêm modal vào body
    document.body.appendChild(modal);
    
    // Điền dữ liệu hiện tại vào form
    fillCurrentData();
    
    // Hiển thị modal (sử dụng vanilla JS thay vì Bootstrap nếu chưa có)
    modal.style.display = 'block';
    modal.classList.add('show');
    
    // Xử lý đóng modal
    const closeButtons = modal.querySelectorAll('[data-bs-dismiss="modal"]');
    closeButtons.forEach(btn => {
        btn.onclick = () => closeModal(modal);
    });
    
    // Đóng modal khi click backdrop
    modal.onclick = (e) => {
        if (e.target === modal) closeModal(modal);
    };
}

// Close modal function
function closeModal(modal) {
    modal.style.display = 'none';
    modal.classList.remove('show');
    setTimeout(() => {
        if (modal.parentNode) {
            document.body.removeChild(modal);
        }
    }, 300);
}

// Fill current company data into form
function fillCurrentData() {
    const companyName = document.getElementById('company-name').textContent;
    const companyDescription = document.getElementById('company-description').textContent;
    const companyAddress = document.getElementById('company-address').textContent;
    const companyPhone = document.getElementById('company-phone').textContent;
    const companyEmail = document.getElementById('company-email').textContent;
    const companyWebsite = document.getElementById('company-website').textContent;
    
    // Điền vào form (loại bỏ text mặc định)
    document.getElementById('editCompanyName').value = companyName !== 'Chưa cập nhật' ? companyName : '';
    // Fill description unless it's a placeholder/loading text
    const descText = (companyDescription || '').toString().trim();
    const isPlaceholder = descText === 'Đang tải thông tin công ty...' || descText === 'Chưa có mô tả công ty' || descText === '';
    document.getElementById('editDescription').value = isPlaceholder ? '' : descText;
    document.getElementById('editAddress').value = companyAddress !== 'Chưa cập nhật địa chỉ' ? companyAddress : '';
    document.getElementById('editPhone').value = companyPhone !== 'Chưa cập nhật số điện thoại' ? companyPhone : '';
    document.getElementById('editEmail').value = companyEmail !== 'Chưa cập nhật email' ? companyEmail : '';
    document.getElementById('editWebsite').value = companyWebsite !== 'Chưa cập nhật website' ? companyWebsite : '';
}

// Save company info using PUT API
function saveCompanyInfo() {
    const form = document.getElementById('editCompanyForm');
    const saveBtn = document.getElementById('saveBtn');
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Disable button
    saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang lưu...';
    
    const token = getAuthToken();
    if (!token) {
        showErrorMessage('Phiên đăng nhập đã hết hạn');
        saveBtn.innerHTML = 'Lưu thay đổi';
        return;
    }
    
    // Nếu có file logo được chọn, gửi FormData (multipart/form-data).
    const logoInput = document.getElementById('editLogo');
    const logoFile = logoInput ? logoInput.files[0] : null;

    // Ensure company name is present — fallback to header text if input is empty
    const companyNameInput = document.getElementById('editCompanyName');
    if (companyNameInput) {
        const trimmed = String(companyNameInput.value || '').trim();
        if (!trimmed) {
            const headerName = (document.getElementById('company-name') || {}).textContent || '';
            companyNameInput.value = headerName.trim() || '';
        }
    }

    if (logoFile && logoFile.size > 0) {
        // Client-side validation
        const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        if (!allowedTypes.includes(logoFile.type)) {
            showErrorMessage('Định dạng logo không hợp lệ. Vui lòng chọn file hình ảnh (jpg, png, gif)');
            saveBtn.innerHTML = 'Lưu thay đổi';
            return;
        }
        if (logoFile.size > 2 * 1024 * 1024) {
            showErrorMessage('Kích thước logo quá lớn. Vui lòng chọn file nhỏ hơn 2MB');
            saveBtn.innerHTML = 'Lưu thay đổi';
            return;
        }

    const fd = new FormData();
    const cname = (document.getElementById('editCompanyName') && document.getElementById('editCompanyName').value) || '';
    fd.append('company_name', cname);
    // employee_count removed: no longer collected from the modal
        fd.append('description', document.getElementById('editDescription').value);
        fd.append('address', document.getElementById('editAddress').value);
        fd.append('phone', document.getElementById('editPhone').value);
        fd.append('email', document.getElementById('editEmail').value);
        fd.append('website', document.getElementById('editWebsite').value || '');
        fd.append('logo', logoFile);

        // Debug: list FormData entries to help diagnose 422 validation issues
        if (DEBUG_MODE) {
            try {
                for (const pair of fd.entries()) {
                    console.log('FormData entry:', pair[0], pair[1]);
                }
            } catch (e) { console.debug('Could not iterate FormData entries', e); }

            console.log('Sending PUT request (FormData) to update company, includes logo file');
        }

        // Guard: server expects company_name
        if (!cname || String(cname).trim() === '') {
            showErrorMessage('Vui lòng nhập tên công ty trước khi lưu.');
            saveBtn.innerHTML = 'Lưu thay đổi';
            return;
        }

        // Many backends (including PHP's parsing of multipart) don't populate POST fields for
        // multipart/form-data when the HTTP method is PUT. Use POST with _method=PUT override so
        // Laravel/PHP correctly parse the FormData including non-file fields.
        fd.append('_method', 'PUT');

        fetch(`${API_BASE_URL}/companies/${window.currentCompanyId}`, {
            method: 'POST',
            headers: {
                'Authorization': token,
                'Accept': 'application/json'
                // NOTE: Do NOT set Content-Type when sending FormData; browser will set the boundary
            },
            body: fd
        })
        .then(response => {
            if (DEBUG_MODE) console.log(`PUT Response Status: ${response.status} ${response.statusText}`);
            if (!response.ok) {
                if (response.status === 401) {
                    throw new Error('Phiên đăng nhập đã hết hạn');
                } else if (response.status === 403) {
                    throw new Error('Bạn không có quyền cập nhật thông tin này');
                } else if (response.status === 422) {
                    // Parse and log full validation response to help debugging
                    return response.json().then(data => {
                        console.error('Validation failed (422) response body:', data);
                        // If Laravel-style errors object exists, show the first message
                        if (data && data.errors) {
                            const firstField = Object.keys(data.errors)[0];
                            const firstMsg = data.errors[firstField] && data.errors[firstField][0];
                            showErrorMessage(firstMsg || data.message || 'Dữ liệu không hợp lệ');
                        } else {
                            showErrorMessage(data.message || 'Dữ liệu không hợp lệ');
                        }
                        throw new Error(JSON.stringify(data));
                    });
                }
                throw new Error('Lỗi khi cập nhật thông tin');
            }
            return response.json();
        })
        .then(data => {
            console.log('PUT Response (FormData):', data);
            if (data.message && data.company) {
                // Đóng modal
                const modal = document.getElementById('editCompanyModal');
                closeModal(modal);

                // Tải lại dữ liệu
                loadCompanyData();

                // Hiển thị thông báo thành công
                showSuccessMessage(data.message || 'Cập nhật thông tin công ty thành công!');
            } else {
                throw new Error(data.error || data.message || 'Không thể cập nhật thông tin');
            }
        })
        .catch(error => {
            console.error('Error updating company:', error);
            showErrorMessage(error.message || 'Không thể cập nhật thông tin công ty');
        })
        .finally(() => {
            saveBtn.innerHTML = 'Lưu thay đổi';
        });

        return; // Done with FormData path
    }

    // Nếu không có file logo, gửi JSON như trước
    const payload = {
        company_name: (document.getElementById('editCompanyName') && document.getElementById('editCompanyName').value) || (document.getElementById('company-name') && document.getElementById('company-name').textContent) || '',
    // employee_count removed: not sending employee count from client
        description: document.getElementById('editDescription').value,
        address: document.getElementById('editAddress').value,
        phone: document.getElementById('editPhone').value,
        email: document.getElementById('editEmail').value,
        website: document.getElementById('editWebsite').value
    };

    if (DEBUG_MODE) console.log('Sending PUT request to update company (JSON):', payload);

    fetch(`${API_BASE_URL}/companies/${window.currentCompanyId}`, {
        method: 'PUT',
        headers: {
            'Authorization': token,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
    })
    .then(response => {
        if (DEBUG_MODE) console.log(`PUT Response Status: ${response.status} ${response.statusText}`);
        
        if (!response.ok) {
            if (response.status === 401) {
                throw new Error('Phiên đăng nhập đã hết hạn');
            } else if (response.status === 403) {
                throw new Error('Bạn không có quyền cập nhật thông tin này');
            } else if (response.status === 422) {
                return response.json().then(data => {
                    console.error('Validation failed (422) response body:', data);
                    if (data && data.errors) {
                        const firstField = Object.keys(data.errors)[0];
                        const firstMsg = data.errors[firstField] && data.errors[firstField][0];
                        showErrorMessage(firstMsg || data.message || 'Dữ liệu không hợp lệ');
                    } else {
                        showErrorMessage(data.message || 'Dữ liệu không hợp lệ');
                    }
                    throw new Error(JSON.stringify(data));
                });
            }
            throw new Error('Lỗi khi cập nhật thông tin');
        }
        return response.json();
    })
    .then(data => {
        console.log('PUT Response:', data);
        if (data.message && data.company) {
            // Đóng modal
            const modal = document.getElementById('editCompanyModal');
            closeModal(modal);
            
            // Tải lại dữ liệu
            loadCompanyData();
            
            // Hiển thị thông báo thành công
            showSuccessMessage(data.message || 'Cập nhật thông tin công ty thành công!');
        } else {
            throw new Error(data.error || data.message || 'Không thể cập nhật thông tin');
        }
    })
    .catch(error => {
        console.error('Error updating company:', error);
        showErrorMessage(error.message || 'Không thể cập nhật thông tin công ty');
    })
    .finally(() => {
        // Re-enable button
        saveBtn.innerHTML = 'Lưu thay đổi';
    });
}

// Try to refresh token using currentUser
async function tryRefreshToken() {
    const currentUser = localStorage.getItem('currentUser');
    if (!currentUser) return null;
    
    try {
        const userData = JSON.parse(currentUser);
        console.log('Attempting to refresh token for user:', userData.email);
        
        // Thử login lại với thông tin user hiện tại (nếu có password)
        // Hoặc gọi refresh token endpoint
        
        // Tạm thời return null vì cần implement refresh logic
        return null;
    } catch (e) {
        console.error('Error in tryRefreshToken:', e);
        return null;
    }
}

// Show mock company data for testing (fallback when no token)
function showMockCompanyData(user) {
    if (DEBUG_MODE) console.log('Showing mock company data for user:', user.name);
    
    // --- Quyền truy cập trang thông tin công ty ---
    const mockCompany = {
        company_name: 'FPT Software',
        employee_count: 25000,
        description: 'Công ty phần mềm hàng đầu Việt Nam, cung cấp dịch vụ phát triển phần mềm và giải pháp công nghệ',
        address: 'Hành phố HCM',
        phone: '0916242644',
        email: 'bsm@gmail.com',
        website: 'https://bsm.vn',
        logo: null
    };
    
    updateCompanyInfo(mockCompany);
    
    // Hiển thị thông báo debug với token info
    const authToken = localStorage.getItem('authToken');
    const companyDescription = document.getElementById('company-description');
    if (companyDescription) {
        companyDescription.innerHTML = `
            <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 4px; margin-bottom: 10px;">
                <strong>⚠️ DEBUG MODE:</strong> API trả về 401 Unauthorized<br>
                <small>Token hiện tại: ${authToken ? authToken.substring(0, 20) + '...' : 'Không có'}</small><br>
                <small>Có thể token đã hết hạn hoặc không đúng format. Cần đăng nhập lại để lấy token mới.</small><br>
                <button onclick="window.location.href='/login'" style="margin-top: 5px; padding: 5px 10px; background: #007bff; color: white; border: none; border-radius: 3px;">
                    Đăng nhập lại
                </button>
            </div>
            ${mockCompany.description}
        `;
    }
}

// Show message when company not found
function showCompanyNotFoundMessage() {
    const companyName = document.getElementById('company-name');
    const companyDescription = document.getElementById('company-description');
    
    if (companyName) {
        companyName.textContent = 'Chưa có công ty';
        companyName.style.color = '#ffc107';
    }
    
    if (companyDescription) {
        companyDescription.innerHTML = `
            <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 8px; text-align: center;">
                <h4 style="color: #856404; margin-bottom: 10px;">
                    <i class="fas fa-building"></i> Chưa có thông tin công ty
                </h4>
                <p style="color: #856404; margin-bottom: 15px;">
                    Bạn chưa thuộc công ty nào hoặc công ty chưa được duyệt.
                </p>
                <div style="margin-top: 10px;">
                    <button onclick="createCompany()" style="background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 5px; margin-right: 10px; cursor: pointer;">
                        <i class="fas fa-plus"></i> Tạo công ty mới
                    </button>
                    <button onclick="contactAdmin()" style="background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">
                        <i class="fas fa-envelope"></i> Liên hệ Admin
                    </button>
                </div>
            </div>
        `;
    }
}

// Show error message
function showErrorMessage(message) {
    const companyName = document.getElementById('company-name');
    const companyDescription = document.getElementById('company-description');
    
    if (companyName) {
        companyName.textContent = 'Lỗi';
        companyName.style.color = '#dc3545';
    }
    
    if (companyDescription) {
        companyDescription.textContent = message;
        companyDescription.style.color = '#dc3545';
    }
}

// Show success message
function showSuccessMessage(message) {
    // Tạo toast notification
    const toast = document.createElement('div');
    toast.className = 'toast-notification success';
    toast.innerHTML = `
        <div class="toast-content">
            <i class="fas fa-check-circle"></i>
            <span>${message}</span>
        </div>
    `;
    
    // Thêm CSS cho toast
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #28a745;
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        z-index: 9999;
        display: flex;
        align-items: center;
        gap: 10px;
        max-width: 400px;
        font-size: 14px;
        opacity: 0;
        transform: translateX(100%);
        transition: all 0.3s ease;
    `;
    
    document.body.appendChild(toast);
    
    // Animate in
    setTimeout(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateX(0)';
    }, 100);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (toast.parentNode) {
                document.body.removeChild(toast);
            }
        }, 300);
    }, 5000);
}

// Helper functions for company actions
function createCompany() {
    // Chuyển đến trang tạo công ty hoặc mở modal
    alert('Tính năng tạo công ty sẽ được triển khai sớm!');
    // window.location.href = '/company/create';
}

function contactAdmin() {
    // Liên hệ admin
    alert('Vui lòng liên hệ admin qua email: admin@webcv.vn');
}
