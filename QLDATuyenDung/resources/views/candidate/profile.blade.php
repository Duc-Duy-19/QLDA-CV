<!DOCTYPE html>
<html lang="vi">
<head>
    @include('layouts.head')
    <link href="{{ asset('css/shared/header.css') }}" rel="stylesheet">
    <link href="{{ asset('css/shared/footer.css') }}" rel="stylesheet">
    <link href="{{ asset('css/roles/candidate/profile.css') }}" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>Hồ sơ cá nhân - WebCV</title>
</head>
<body>
    @include('layouts.header')
    
    

    <!-- Main Container -->
    <div class="main-container">
        <!-- Profile Sidebar -->
        <aside class="profile-sidebar">
            <div class="profile-card">
                <div class="profile-avatar">
                    <img id="profile-avatar-img" src="" alt="Avatar" style="display: none;">
                    <i class="fas fa-user" id="profile-avatar-icon"></i>
                    <button class="change-avatar-btn" onclick="document.getElementById('avatar-input').click()">
                        <i class="fas fa-camera"></i>
                    </button>
                    <input type="file" id="avatar-input" accept="image/*" style="display: none;">
                </div>
                <h2 id="profile-name">Chưa cập nhật</h2>
                
                <!-- Hiển thị title và stats theo role -->
                @if(auth()->check() && auth()->user()->role === 'candidate')
                    <p id="profile-title">Ứng viên</p>
                @elseif(auth()->check() && (auth()->user()->role === 'recruiter' || auth()->user()->role === 'employer'))
                    <p id="profile-title">Nhà tuyển dụng</p>
                @else
                    <p id="profile-title">Khách</p>
                @endif
        </div>

            <div class="profile-menu">
                <!-- Menu cho tất cả user -->
                <a href="#personal-info" class="menu-item active" onclick="showSection('personal-info')">
                    <i class="fas fa-user"></i> Thông tin cá nhân
                </a>
                
                <!-- Menu thông báo cho candidate -->
                @if(auth()->check() && auth()->user()->role === 'candidate')
                    <a href="{{ route('candidate.notifications') }}" class="menu-item">
                        <i class="fas fa-bell"></i> Thông báo
                        <span class="notification-badge" id="notification-badge" style="display: none;">0</span>
                    </a>
                @endif

                <!-- Menu dành cho Candidate -->
                @if(auth()->check() && auth()->user()->role === 'candidate')
                    {{-- Removed: Kinh nghiệm / Học vấn / Chứng chỉ / Ngoại ngữ menu items --}}
                @endif

                <!-- Menu dành cho Recruiter/Employer -->
                {{-- Recruiter/Employer specific menu items removed per request --}}
                    </div>
        </aside>

        <!-- Profile Content -->
        <main class="profile-content">
            <!-- Personal Information Section -->
            <div class="profile-section active" id="personal-info">
                <div class="section-header">
                    <h2>Thông tin cá nhân</h2>
                    <button class="btn btn-primary" onclick="editSection('personal-info')">
                        <i class="fas fa-edit"></i> Chỉnh sửa
                        </button>
                </div>

                <div class="section-content">
                    <form id="personal-info-form" onsubmit="handleFormSubmit(event, 'personal-info')" novalidate>
                        <div class="error-message" id="profile-error-message" style="display:none; margin-bottom:10px; color:#d9534f;">
                            <i class="fas fa-exclamation-circle"></i>
                            <span id="profile-error-text"></span>
                        </div>
                <div class="form-row">
                    <div class="form-group">
                                <label>Họ và tên *</label>
                                <input type="text" id="full-name" readonly style="background-color: #f8f9fa; cursor: not-allowed;">
                    </div>
                    <div class="form-group">
                                <label>Email *</label>
                                <input type="email" id="email" readonly style="background-color: #f8f9fa; cursor: not-allowed;">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                                <label>Số điện thoại</label>
                                <input type="tel" id="phone" maxlength="10" placeholder="Nhập số điện thoại">
                                <div class="error" id="phone-error" style="margin-top:6px; color:#d9534f; font-size:0.95em;"></div>
                    </div>
                    <div class="form-group">
                                <label>Ngày sinh</label>
                                <input type="date" id="birthday" placeholder="Chọn ngày sinh">
                                <div class="error" id="birthday-error" style="margin-top:6px; color:#d9534f; font-size:0.95em;"></div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                                <label>Giới tính</label>
                                <select id="gender">
                            <option value="male">Nam</option>
                            <option value="female">Nữ</option>
                            <option value="other">Khác</option>
                        </select>
                        <div class="error" id="gender-error" style="margin-top:6px; color:#d9534f; font-size:0.95em;"></div>
                    </div>
                </div>

                        <div class="form-group">
                            <label>Địa chỉ</label>
                            <textarea id="address" rows="3" maxlength="255" placeholder="Nhập địa chỉ đầy đủ..."></textarea>
                            <div class="error" id="address-error" style="margin-top:6px; color:#d9534f; font-size:0.95em;"></div>
            </div>

                        <div class="form-actions" style="display: none;">
                            <button type="button" class="btn btn-secondary" onclick="cancelEdit('personal-info')">Hủy</button>
                            <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Experience / Education / Certificates / Languages sections removed per request --}}
        </main>
    </div>

    <script src="{{ asset('js/api.js') }}"></script>
    <script>
        let currentSection = 'personal-info';
        let isEditing = false;
        let candidateData = null;

        // Helper function to format date for input type="date"
        function formatDateForInput(dateString) {
            if (!dateString) return '';
            
            try {
                const date = new Date(dateString);
                if (isNaN(date.getTime())) return '';
                return date.toISOString().split('T')[0];
            } catch (error) {
                console.error('Error formatting date:', error);
                return '';
            }
        }

        // Load candidate data from API (for any authenticated user).
        // Previously this only fetched for users with role 'candidate', which caused
        // employer updates (saved into candidates table) to disappear on reload because
        // the page didn't fetch candidate records for employers. We now always call
        // the endpoint so profile data persisted in the `candidates` table is shown
        // regardless of user role.
        async function loadCandidateData() {
            try {
                const currentUser = JSON.parse(localStorage.getItem('currentUser') || '{}');

                console.log('Loading candidate profile for user (if exists):', currentUser);
                const response = await APIHelper.getCandidateProfile();
                console.log('Profile response:', response);
                // Backend returns { profile: { ... } }
                candidateData = response.profile;

                updateProfileDisplay();
                updateFormFields();
            } catch (error) {
                console.error('Error loading candidate data:', error);
                // Vẫn hiển thị thông tin user từ localStorage ngay cả khi có lỗi
                candidateData = null;
                updateProfileDisplay();
                updateFormFields();
            }
        }

        // Update profile display
        function updateProfileDisplay() {
            // Lấy thông tin user từ localStorage (từ session đăng nhập)
            const currentUser = JSON.parse(localStorage.getItem('currentUser') || '{}');
            
            // Update profile name from user data
            const profileName = document.getElementById('profile-name');
            if (profileName) {
                profileName.textContent = currentUser.name || 'Chưa cập nhật';
            }
            
            // Update profile title based on role
            const profileTitle = document.getElementById('profile-title');
            if (profileTitle) {
                if (currentUser.role === 'recruiter' || currentUser.role === 'employer') {
                    profileTitle.textContent = 'Nhà tuyển dụng';
                } else if (currentUser.role === 'candidate') {
                    profileTitle.textContent = 'Ứng viên';
                } else {
                    profileTitle.textContent = 'Khách';
                }
            }
            
            // Update avatar - ưu tiên từ candidateData, nếu không có thì từ currentUser
            let avatarUrl = null;
            if (candidateData && candidateData.avatar_url) {
                avatarUrl = candidateData.avatar_url;
            } else if (currentUser.avatar) {
                avatarUrl = currentUser.avatar;
            }
            
            if (avatarUrl) {
                // Làm sạch format avatar - xử lý trường hợp backend thêm prefix sai
                let cleanAvatarUrl = avatarUrl;
                
                // Kiểm tra xem có bị duplicate prefix không
                if (avatarUrl.includes('data:image/png;base64,data:')) {
                    // Trường hợp: data:image/png;base64,data:image/jpeg;base64,...
                    // Lấy phần sau prefix thứ hai
                    const parts = avatarUrl.split('data:image/jpeg;base64,');
                    if (parts.length > 1) {
                        cleanAvatarUrl = 'data:image/jpeg;base64,' + parts[1];
                    }
                } else if (avatarUrl.includes('data:image/png;base64,data:image/png;base64,')) {
                    // Trường hợp: data:image/png;base64,data:image/png;base64,...
                    const parts = avatarUrl.split('data:image/png;base64,');
                    if (parts.length > 1) {
                        cleanAvatarUrl = 'data:image/png;base64,' + parts[1];
                    }
                }
                
                const avatarImg = document.getElementById('profile-avatar-img');
                const avatarIcon = document.getElementById('profile-avatar-icon');
                
                console.log('Original avatar URL:', avatarUrl);
                console.log('Cleaned avatar URL:', cleanAvatarUrl);
                console.log('Avatar element found:', !!avatarImg);
                if (avatarImg) {
                    avatarImg.src = cleanAvatarUrl;
                    avatarImg.style.display = 'block';
                }
                if (avatarIcon) {
                    avatarIcon.style.display = 'none';
                }
            }
        }

        // Update form fields
        function updateFormFields() {
            // Lấy thông tin user từ localStorage (từ session đăng nhập)
            const currentUser = JSON.parse(localStorage.getItem('currentUser') || '{}');
            
            const fields = {
                'full-name': currentUser.name || '',
                'email': currentUser.email || '',
                'phone': candidateData?.phone || '',
                'birthday': formatDateForInput(candidateData?.birthday),
                'gender': candidateData?.gender || 'male',
                'address': candidateData?.address || ''
            };
            
            Object.keys(fields).forEach(fieldId => {
                const element = document.getElementById(fieldId);
                if (element) {
                    element.value = fields[fieldId];
                }
            });
        }
            

        // Load existing data for display
        function loadExistingData(user) {
            // Populate simple form fields via updateFormFields()
            // Lists for experiences, education, certificates and languages were removed per request.
            try {
                updateFormFields();
            } catch (e) {
                console.warn('updateFormFields not available or failed', e);
            }
        }

        // Show section
        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.profile-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Remove active class from all menu items
            document.querySelectorAll('.menu-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Show selected section
            document.getElementById(sectionId).classList.add('active');
            
            // Add active class to selected menu item
            document.querySelector(`[href="#${sectionId}"]`).classList.add('active');
            
            currentSection = sectionId;
        }

        // Edit section
        function editSection(sectionId) {
            isEditing = true;
            const form = document.getElementById(`${sectionId}-form`);
            if (form) {
                form.querySelectorAll('input, textarea, select').forEach(input => {
                    // Không cho phép chỉnh sửa các trường readonly (name, email)
                    if (input.readOnly) {
                        return;
                    }
                    
                    input.disabled = false;
                    input.style.backgroundColor = '#fff';
                    input.style.border = '1px solid #28a745';
                    input.style.color = '#333';
                });
                
                // Show save/cancel buttons
                const formActions = form.querySelector('.form-actions');
                if (formActions) {
                    formActions.style.display = 'flex';
                }
            }
        }

        // Cancel edit
        function cancelEdit(sectionId) {
            isEditing = false;
            const form = document.getElementById(`${sectionId}-form`);
            if (form) {
                form.querySelectorAll('input, textarea, select').forEach(input => {
                    // Không thay đổi các trường readonly (name, email)
                    if (input.readOnly) {
                        return;
                    }
                    
                    input.disabled = true;
                    input.style.backgroundColor = '#f8f9fa';
                    input.style.border = '1px solid #e9ecef';
                });
                
                // Hide save/cancel buttons
                const formActions = form.querySelector('.form-actions');
                if (formActions) {
                    formActions.style.display = 'none';
                }
            }
        }

        // Add experience (removed) — function stub left intentionally
        function addExperience() {
            console.debug('addExperience was removed; action is disabled.');
        }

        // Add education (removed) — function stub left intentionally
        function addEducation() {
            console.debug('addEducation was removed; action is disabled.');
        }

        // Removed: addSkill - skills section deleted

        // Add certificate (removed) — function stub left intentionally
        function addCertificate() {
            console.debug('addCertificate was removed; action is disabled.');
        }

        // Add language (removed) — function stub left intentionally
        function addLanguage() {
            console.debug('addLanguage was removed; action is disabled.');
        }

        // Remove item
        function removeItem(button) {
            button.closest('.experience-item, .education-item, .skill-item, .certificate-item, .language-item').remove();
        }


        // Removed: editSkill - skills section deleted

        // Removed: saveCertificate - certificates section deleted

        // Removed: saveLanguage - languages section deleted

        // Handle form submit
        async function handleFormSubmit(event, sectionId) {
            event.preventDefault();
            
            if (sectionId === 'personal-info') {
                await updatePersonalInfo();
            } else {
                // Other sections handled by individual functions
                console.log(`${sectionId} data handled by individual save functions`);
            }
        }

        // Update personal information via API
        async function updatePersonalInfo() {
            // prepare payload outside try so fallback can access it
            const birthdayValue = document.getElementById('birthday').value;
            const formData = {
                phone: document.getElementById('phone').value,
                birthday: birthdayValue || null, // Gửi null nếu không có giá trị
                gender: document.getElementById('gender').value,
                address: document.getElementById('address').value
            };

            // Add avatar file if selected
            const avatarInput = document.getElementById('avatar-input');
            if (avatarInput && avatarInput.files[0]) {
                formData.avatar = avatarInput.files[0];
            }

            // Client-side validation to match CandidateUpdateRequest rules/messages
            // Clear previous errors first
            clearProfileFieldErrors();

            const clientErrors = {};
            const phone = (formData.phone || '').toString().trim();
            if (phone) {
                // only digits
                if (!/^[0-9]+$/.test(phone)) {
                    clientErrors.phone = ['Số điện thoại không hợp lệ, chỉ được chứa chữ số.'];
                }
                if (phone.length > 10) {
                    clientErrors.phone = clientErrors.phone || [];
                    clientErrors.phone.push('Số điện thoại không được vượt quá 10 ký tự.');
                }
            }

            const address = (formData.address || '').toString();
            if (address && address.length > 255) {
                clientErrors.address = ['Địa chỉ không được dài quá 255 ký tự.'];
            }

            const bd = formData.birthday;
            if (bd) {
                const d = new Date(bd);
                if (isNaN(d.getTime())) {
                    clientErrors.birthday = ['Ngày sinh phải là ngày hợp lệ.'];
                }
            }

            const gender = formData.gender;
            if (gender && ['male','female','other'].indexOf(gender) === -1) {
                clientErrors.gender = ['Giới tính phải là Nam, Nữ hoặc Khác.'];
            }

            // Avatar validations: image type, allowed mimes, max size 2MB
            if (formData.avatar instanceof File) {
                const file = formData.avatar;
                const allowed = ['image/jpeg','image/jpg','image/png','image/gif'];
                if (!file.type || allowed.indexOf(file.type.toLowerCase()) === -1) {
                    clientErrors.avatar = clientErrors.avatar || [];
                    clientErrors.avatar.push('Ảnh chỉ được định dạng jpg, jpeg, png, gif.');
                }
                const maxBytes = 2 * 1024 * 1024; // 2MB
                if (file.size && file.size > maxBytes) {
                    clientErrors.avatar = clientErrors.avatar || [];
                    clientErrors.avatar.push('Ảnh không được vượt quá 2MB.');
                }
            }

            // If any client-side errors, display and abort
            if (Object.keys(clientErrors).length > 0) {
                displayProfileFieldErrors(clientErrors);
                // focus first invalid field
                const firstKey = Object.keys(clientErrors)[0];
                const el = document.getElementById(firstKey === 'avatar' ? 'avatar-input' : firstKey);
                if (el) el.focus();
                return;
            }

            // Clear any previous field errors
            clearProfileFieldErrors();

            try {
                // Ensure CSRF cookie if backend uses Sanctum cookie auth
                await APIHelper.ensureCsrf();

                const response = await APIHelper.updateCandidateProfile(formData);

                // Backend returns { profile: { ... } } on success
                if (response.profile) {
                    candidateData = response.profile;
                    updateProfileDisplay();
                    alert('Cập nhật thông tin thành công!');
                    cancelEdit('personal-info');
                }
            } catch (error) {
                    console.error('Error updating profile:', error);
                    // If backend returned validation errors (errors object), display them under fields
                    const resp = error?.response || error?.response?.response || null;
                    const serverData = error?.response || error?.response?.response || null;
                    const errors = (error && error.response && error.response.errors) ? error.response.errors : (serverData && serverData.errors ? serverData.errors : null);

                    if (errors) {
                        displayProfileFieldErrors(errors);
                    } else {
                        // Try to show server-side message if available
                        const serverMessage = (error && error.response && error.response.message) ? error.response.message : (error && error.message ? error.message : null);
                        if (serverMessage) {
                            showProfileBannerMessage(serverMessage);
                        } else {
                            showProfileBannerMessage('Có lỗi xảy ra khi cập nhật thông tin. Vui lòng thử lại.');
                        }
                    }

                    // Fallback: save to localStorage so user doesn't lose changes
                    try {
                        const currentUser = JSON.parse(localStorage.getItem('currentUser') || '{}');
                        currentUser.phone = formData.phone;
                        currentUser.birthday = formData.birthday;
                        currentUser.gender = formData.gender;
                        currentUser.address = formData.address;
                        localStorage.setItem('currentUser', JSON.stringify(currentUser));
                        // note: do not show success alert on error
                    } catch (e) {
                        console.error('Error saving fallback data to localStorage:', e);
                    }
            }
        }

        // Helpers for profile field errors
        function clearProfileFieldErrors() {
            const ids = ['phone','birthday','gender','address','avatar'];
            ids.forEach(id => {
                const el = document.getElementById(id + '-error');
                if (el) el.textContent = '';
                const input = document.getElementById(id === 'avatar' ? 'avatar-input' : id);
                if (input) input.classList && input.classList.remove('is-invalid');
            });
            const banner = document.getElementById('profile-error-message');
            if (banner) banner.style.display = 'none';
            const bannerText = document.getElementById('profile-error-text');
            if (bannerText) bannerText.textContent = '';
        }

        function displayProfileFieldErrors(errors) {
            // errors is an object like { phone: ['...'], avatar: ['...'] }
            let any = false;
            Object.keys(errors).forEach(key => {
                const id = key === 'password_confirmation' ? 'confirm-password' : (key === 'avatar' ? 'avatar' : key);
                const el = document.getElementById(id + '-error');
                const input = document.getElementById(id === 'avatar' ? 'avatar-input' : id);
                const msg = Array.isArray(errors[key]) ? errors[key].join(', ') : errors[key];
                if (el) {
                    el.textContent = msg;
                    any = true;
                } else {
                    // append to banner
                    showProfileBannerMessage(msg);
                }
                if (input) input.classList && input.classList.add('is-invalid');
            });
            return any;
        }

        function showProfileBannerMessage(message) {
            const banner = document.getElementById('profile-error-message');
            const bannerText = document.getElementById('profile-error-text');
            if (banner && bannerText) {
                bannerText.textContent = message;
                banner.style.display = 'block';
            } else {
                alert(message);
            }
        }

        // Removed: saveExperience - experience section deleted

        // Removed: saveEducation - education section deleted

        // Removed: saveSkill - skills section deleted

        // Handle avatar selection: preview and mark profile as edited.
        // NOTE: we intentionally DO NOT upload the avatar immediately here.
        // The avatar file will be submitted together with the personal-info form when the user clicks "Lưu thay đổi".
        async function handleAvatarUpload(event) {
            const file = event.target.files[0];
            if (!file) return;

            // Validate file type
            if (!file.type || !file.type.startsWith('image/')) {
                alert('Vui lòng chọn file hình ảnh hợp lệ!');
                event.target.value = '';
                return;
            }

            // Validate file size (max 2MB as per backend validation)
            if (file.size > 2 * 1024 * 1024) {
                alert('Kích thước file không được vượt quá 2MB!');
                event.target.value = '';
                return;
            }

            // Preview the selected image in the avatar img element
            try {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const avatarImg = document.getElementById('profile-avatar-img');
                    if (avatarImg) {
                        avatarImg.src = e.target.result;
                        avatarImg.style.display = 'block';
                    }
                };
                reader.readAsDataURL(file);

                // Mark the personal-info form as edited and show save button
                const form = document.getElementById('personal-info-form');
                if (form) {
                    const formActions = form.querySelector('.form-actions');
                    if (formActions) formActions.style.display = 'flex';
                    // Enable inputs so user can save
                    form.querySelectorAll('input, textarea, select').forEach(input => {
                        if (input.readOnly) return;
                        input.disabled = false;
                        input.style.backgroundColor = '#fff';
                        input.style.border = '1px solid #28a745';
                        input.style.color = '#333';
                    });
                }

                // Do NOT call API here; update will happen when updatePersonalInfo() is called on form submit.
            } catch (err) {
                console.error('Error previewing avatar:', err);
                alert('Không thể đọc file ảnh. Vui lòng thử lại.');
            }
        }

        // Save company info
        function saveCompanyInfo() {
            const companyName = document.getElementById('company-name').value;
            const taxCode = document.getElementById('company-tax-code').value;
            const industry = document.getElementById('company-industry').value;
            const size = document.getElementById('company-size').value;
            const address = document.getElementById('company-address').value;
            const website = document.getElementById('company-website').value;
            const description = document.getElementById('company-description').value;

            if (!companyName) {
                alert('Vui lòng nhập tên công ty!');
                return;
            }

            try {
                // Save to localStorage temporarily
                const currentUser = JSON.parse(localStorage.getItem('currentUser') || '{}');
                currentUser.companyInfo = {
                    name: companyName,
                    taxCode: taxCode,
                    industry: industry,
                    size: size,
                    address: address,
                    website: website,
                    description: description
                };
                localStorage.setItem('currentUser', JSON.stringify(currentUser));
                
                // Here you would normally call API to save to database
                // await APIHelper.updateCompanyInfo(companyInfo);
                
                alert('Thông tin công ty đã được lưu thành công!');
                cancelEdit('company-info');
            } catch (error) {
                console.error('Error saving company info:', error);
                alert('Có lỗi xảy ra khi lưu thông tin. Vui lòng thử lại.');
            }
        }

        // Save recruiting settings
        function saveRecruitingSettings() {
            const email = document.getElementById('recruiting-email').value;
            const phone = document.getElementById('recruiting-phone').value;
            const specialization = document.getElementById('recruiting-specialization').value;
            const area = document.getElementById('recruiting-area').value;
            const experience = document.getElementById('recruiting-experience').value;

            try {
                // Save to localStorage temporarily
                const currentUser = JSON.parse(localStorage.getItem('currentUser') || '{}');
                currentUser.recruitingSettings = {
                    email: email,
                    phone: phone,
                    specialization: specialization,
                    area: area,
                    experience: experience
                };
                localStorage.setItem('currentUser', JSON.stringify(currentUser));
                
                // Here you would normally call API to save to database
                // await APIHelper.updateRecruitingSettings(recruitingSettings);
                
                alert('Cài đặt tuyển dụng đã được lưu thành công!');
                cancelEdit('recruiting-settings');
            } catch (error) {
                console.error('Error saving recruiting settings:', error);
                alert('Có lỗi xảy ra khi lưu thông tin. Vui lòng thử lại.');
            }
        }

        // Load company data if user is recruiter
        function loadCompanyData() {
            const currentUser = JSON.parse(localStorage.getItem('currentUser') || '{}');
            
            if (currentUser.role === 'recruiter' || currentUser.role === 'employer') {
                // Load company info if exists
                if (currentUser.companyInfo) {
                    const companyInfo = currentUser.companyInfo;
                    document.getElementById('company-name').value = companyInfo.name || '';
                    document.getElementById('company-tax-code').value = companyInfo.taxCode || '';
                    document.getElementById('company-industry').value = companyInfo.industry || '';
                    document.getElementById('company-size').value = companyInfo.size || '';
                    document.getElementById('company-address').value = companyInfo.address || '';
                    document.getElementById('company-website').value = companyInfo.website || '';
                    document.getElementById('company-description').value = companyInfo.description || '';
                }

                // Load recruiting settings if exists
                if (currentUser.recruitingSettings) {
                    const settings = currentUser.recruitingSettings;
                    document.getElementById('recruiting-email').value = settings.email || '';
                    document.getElementById('recruiting-phone').value = settings.phone || '';
                    document.getElementById('recruiting-specialization').value = settings.specialization || '';
                    document.getElementById('recruiting-area').value = settings.area || '';
                    document.getElementById('recruiting-experience').value = settings.experience || '';
                }
            }
        }



        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Check if APIHelper is available
            if (typeof APIHelper === 'undefined') {
                console.error('APIHelper is not loaded. Please check if api.js is included.');
                alert('Lỗi tải API Helper. Vui lòng tải lại trang.');
                return;
            }
            
            loadCandidateData();
            
            // Load company data for recruiters
            loadCompanyData();
            
            // Add event listener for avatar upload
            const avatarInput = document.getElementById('avatar-input');
            if (avatarInput) {
                avatarInput.addEventListener('change', handleAvatarUpload);
            }
            
            // Set initial disabled state for all forms
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.querySelectorAll('input, textarea, select').forEach(input => {
                    // Không thay đổi các trường readonly (name, email)
                    if (input.readOnly) {
                        return;
                    }
                    
                    input.disabled = true;
                    input.style.backgroundColor = '#f8f9fa';
                    input.style.border = '1px solid #e9ecef';
                    input.style.color = '#6c757d';
                });
            });
            
            // Load notification count
            loadNotificationCount();
            
            // Update notification count every 15 seconds
            setInterval(loadNotificationCount, 15000);
        });
        
        // Global function to update notification count
        function updateNotificationCount() {
            loadNotificationCount();
        }
        
        function loadNotificationCount() {
            const authToken = localStorage.getItem('authToken');
            if (!authToken) {
                return;
            }
            
            // Count pending company invites
            fetch('/api/company-invites', {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${authToken}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                const badge = document.getElementById('notification-badge');
                if (badge) {
                    const count = data.invites ? data.invites.length : 0;
                    if (count > 0) {
                        badge.textContent = count > 99 ? '99+' : count;
                        badge.style.display = 'flex';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            })
            .catch(error => {
                console.error('Error loading notification count:', error);
            });
        }
    </script>
    
    @include('layouts.footer')
</body>
</html>
