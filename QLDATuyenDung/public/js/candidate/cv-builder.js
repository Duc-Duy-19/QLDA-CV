
        // Global variables
        const API_BASE_URL = '/api';
        let currentTemplate = 'simple';
        let currentColor = 'green';
        let currentFont = 'Arial';
        let editMode = false;
        let currentResumeId = null; // ID của CV đang chỉnh sửa (nếu có)
        let cvData = {
            name: 'Họ và tên',
            email: 'email@example.com',
            phone: 'Số điện thoại',
            location: 'Địa chỉ',
            title: 'Chức danh',
            objective: 'Mục tiêu nghề nghiệp',
            experiences: [],
            educations: [],
            activities: [],
            certifications: [],
            awards: [],
            skills: [],
            references: [],
            projects: [],
            hobbies: [],
            extrainfos: []
        };

        // Helper: Tính cỡ chữ chuẩn cho CV
        function getFontSizes(baseFontSize) {
            // baseFontSize là số (11-12) từ input, chuyển sang pt
            const basePt = parseFloat(baseFontSize) || 11;
            
            return {
                name: Math.max(16, Math.min(20, basePt * 1.5)) + 'pt', // Tên ứng viên: 16-20pt
                sectionTitle: Math.max(13, Math.min(14, basePt * 1.15)) + 'pt', // Tiêu đề mục: 13-14pt
                content: basePt + 'pt', // Nội dung chính: 11-12pt
                contact: Math.max(10, Math.min(11, basePt * 0.9)) + 'pt', // Thông tin liên hệ: 10-11pt
                footer: Math.max(9, Math.min(10, basePt * 0.85)) + 'pt' // Chân trang: 9-10pt
            };
        }

        // Helper: Format date string to Vietnamese format
        function formatDate(dateString) {
            if (!dateString) return '';
            try {
                const date = new Date(dateString);
                if (isNaN(date.getTime())) return dateString; // Fallback nếu date không hợp lệ
                return date.toLocaleDateString('vi-VN', { year: 'numeric', month: 'long', day: 'numeric' });
            } catch (e) {
                console.warn('Error formatting date:', e);
                return dateString; // Fallback về giá trị gốc nếu có lỗi
            }
        }

        // Helper: Format date range
        function formatDateRange(startDate, endDate) {
            const start = startDate ? formatDate(startDate) : '';
            const end = endDate ? formatDate(endDate) : 'Hiện tại';
            if (!start && !end) return '';
            return `${start} - ${end}`;
        }

        // Helper: Get auth token
        function getAuthToken() {
            // Thử nhiều vị trí lưu token có thể có
            const tokenSources = [
                () => localStorage.getItem('authToken'),
                () => localStorage.getItem('auth_token'),
                () => localStorage.getItem('token'),
                () => {
                    const currentUser = localStorage.getItem('currentUser');
                    if (currentUser) {
                        try {
                            const user = JSON.parse(currentUser);
                            return user.token || user.auth_token || user.access_token || user.authToken;
                        } catch (e) {
                            console.warn('Lỗi khi parse currentUser để lấy token:', e);
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

            console.error('Không tìm thấy token xác thực');
            return null;
        }

        // Handle avatar file upload and convert to base64
        function handleHeaderAvatarUpload(event) {
            const file = event.target.files[0];
            if (!file) return;

            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                alert('Vui lòng chọn file hình ảnh hợp lệ (JPG, PNG, GIF)!');
                event.target.value = '';
                return;
            }

            // Validate file size (max 2MB)
            if (file.size > 2 * 1024 * 1024) {
                alert('Kích thước file không được vượt quá 2MB!');
                event.target.value = '';
                return;
            }

            // Convert to base64 and preview
            const reader = new FileReader();
            reader.onload = function(e) {
                const base64String = e.target.result; // This includes data:image/...;base64,...
                
                // Store base64 in hidden input
                const hiddenInput = document.getElementById('header-avatar');
                if (hiddenInput) {
                    hiddenInput.value = base64String;
                }

                // Show preview
                const previewImg = document.getElementById('header-avatar-preview');
                const previewContainer = document.getElementById('header-avatar-preview-container');
                if (previewImg && previewContainer) {
                    previewImg.src = base64String;
                    previewContainer.style.display = 'block';
                }
            };
            reader.onerror = function() {
                alert('Lỗi khi đọc file!');
                event.target.value = '';
            };
            reader.readAsDataURL(file);
        }

        // Helper: Get auth headers
        function getAuthHeaders() {
            const token = getAuthToken();
            if (!token) {
                console.error('Không có token xác thực');
                // Chuyển hướng đến login hoặc hiển thị lỗi
                return {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                };
            }
            
            return {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': token.startsWith('Bearer ') ? token : `Bearer ${token}`
            };
        }

        // Color mapping
        const colorMap = {
            'green': '#28a745',
            'blue': '#007bff',
            'purple': '#6f42c1',
            'red': '#dc3545',
            'orange': '#fd7e14',
            'teal': '#20c997'
        };

        // Template selection
        function selectTemplate(templateName) {
            console.log('Template selected:', templateName);
        document.querySelectorAll('.template-item').forEach(item => {
                item.classList.remove('active');
            });
            event.target.closest('.template-item').classList.add('active');
            currentTemplate = templateName;
            console.log('Current template changed to:', currentTemplate);
                renderCV();
            saveCVSettings();
        }

        // Color selection
        function selectColor(colorName) {
            console.log('Color selected:', colorName);
            document.querySelectorAll('.color-option').forEach(item => {
                item.classList.remove('active');
            });
            event.target.classList.add('active');
            currentColor = colorName;
            console.log('Current color changed to:', currentColor);
            renderCV();
            saveCVSettings();
        }

        // Font selection
        function selectFont(fontName) {
            console.log('Font selected:', fontName);
            document.querySelectorAll('.font-option').forEach(item => {
                item.classList.remove('active');
            });
            event.target.classList.add('active');
            currentFont = fontName;
                renderCV();
            saveCVSettings();
        }

        // Update font size
        function updateFontSize() {
                renderCV();
            saveCVSettings();
        }


        // Toggle section visibility
        function toggleSection(sectionType) {
            const isChecked = document.getElementById(`show-${sectionType}`).checked;
            localStorage.setItem(`cv-section-${sectionType}`, isChecked);
                renderCV();
            saveCVSettings();
        }

        // Toggle edit mode
        function toggleEditMode() {
            editMode = !editMode;
            const btn = document.getElementById('edit-mode-btn');
            if (btn) {
                if (editMode) {
                    btn.innerHTML = '<i class="fas fa-save"></i> Lưu thay đổi';
                    btn.classList.remove('btn-secondary');
                    btn.classList.add('btn-primary');
                } else {
                    btn.innerHTML = '<i class="fas fa-edit"></i> Chế độ chỉnh sửa';
                    btn.classList.remove('btn-primary');
                    btn.classList.add('btn-secondary');
                }
            }
            renderCV();
            saveCVSettings();
        }

        // Export CV to PDF
        function exportCV() {
            const cvElement = document.getElementById('cv-template');
            if (!cvElement) {
                alert('Không tìm thấy nội dung CV để xuất!');
                return;
            }

            // Lấy template hiện tại để áp dụng CSS phù hợp
            const wrapperElement = cvElement.querySelector('.cv-template-wrapper');
            const currentTemplateClass = wrapperElement?.getAttribute('data-template') || '';
            const isTwoColumn = currentTemplateClass === 'modern-professional' || currentTemplateClass === 'modern-pro' || 
                              currentTemplateClass === 'professional-sidebar' || currentTemplateClass === 'sidebar' || 
                              currentTemplateClass === 'header-content';

            // Lấy HTML và đảm bảo có logo WebCV
            let cvHTML = cvElement.innerHTML;
            if (!cvHTML.includes('cv-footer-logo')) {
                cvHTML += `
                    <div class="cv-footer-logo" style="display: none;">
                        <div>WebCV</div>
                    </div>
                `;
            }

            // Tạo HTML đầy đủ cho print window
            const fullHTML = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>CV - ${cvData.name}</title>
                    <meta charset="UTF-8">
                    <style>
                        body { 
                            font-family: ${currentFont}; 
                            margin: 0; 
                            padding: 0; 
                            background: white;
                        }
                        .cv-template-wrapper { 
                            max-width: 100%; 
                            margin: 0; 
                            background: white;
                            box-shadow: none;
                        }
                        
                        /* Ẩn header và footer mặc định của trình duyệt (URL và ngày in) */
                        @page {
                            margin: 0.5cm !important;
                            size: A4 !important;
                        }
                        
                        @media print {
                            /* Ẩn header/footer của trình duyệt */
                            @page {
                                margin-top: 0.5cm !important;
                                margin-bottom: 0.5cm !important;
                                margin-left: 0.5cm !important;
                                margin-right: 0.5cm !important;
                            }
                            
                            body { 
                                margin: 0 !important; 
                                padding: 0 !important; 
                                background: white !important;
                            }
                            
                            .cv-template-wrapper { 
                                box-shadow: none !important;
                                margin: 0 !important;
                                padding: 0 !important;
                                max-width: 100% !important;
                                width: 100% !important;
                            }
                            
                            /* Đảm bảo màu sắc được in ra */
                            * {
                                -webkit-print-color-adjust: exact !important;
                                print-color-adjust: exact !important;
                                color-adjust: exact !important;
                            }
                            
                            /* Giữ nguyên layout 2 cột cho modern-professional và các template 2 cột */
                            ${isTwoColumn ? `
                            .cv-template-wrapper[data-template="${currentTemplateClass}"],
                            .cv-template-wrapper.modern-professional,
                            .cv-template-wrapper.professional-sidebar {
                                display: flex !important;
                                flex-direction: row !important;
                                min-height: auto !important;
                                width: 100% !important;
                                margin: 0 !important;
                                padding: 0 !important;
                                page-break-inside: avoid !important;
                                break-inside: avoid !important;
                            }
                            
                            .cv-left-column {
                                width: ${currentTemplateClass === 'professional-sidebar' ? '30%' : '35%'} !important;
                                min-width: ${currentTemplateClass === 'professional-sidebar' ? '30%' : '35%'} !important;
                                max-width: ${currentTemplateClass === 'professional-sidebar' ? '30%' : '35%'} !important;
                                background: ${currentTemplateClass === 'professional-sidebar' ? '#34495e' : '#2c3e50'} !important;
                                background-color: ${currentTemplateClass === 'professional-sidebar' ? '#34495e' : '#2c3e50'} !important;
                                color: #ffffff !important;
                                padding: 20px 15px !important;
                                margin: 0 !important;
                                -webkit-print-color-adjust: exact !important;
                                print-color-adjust: exact !important;
                                color-adjust: exact !important;
                                page-break-inside: avoid !important;
                                break-inside: avoid !important;
                            }
                            
                            .cv-right-column {
                                width: ${currentTemplateClass === 'professional-sidebar' ? '70%' : '65%'} !important;
                                min-width: ${currentTemplateClass === 'professional-sidebar' ? '70%' : '65%'} !important;
                                max-width: ${currentTemplateClass === 'professional-sidebar' ? '70%' : '65%'} !important;
                                background: #ffffff !important;
                                background-color: #ffffff !important;
                                color: #333 !important;
                                padding: 20px 25px !important;
                                margin: 0 !important;
                                page-break-inside: avoid !important;
                                break-inside: avoid !important;
                            }
                            ` : ''}
                            
                            /* Tránh ngắt trang trong các section */
                            .cv-section-left,
                            .cv-section-right,
                            .section {
                                page-break-inside: avoid !important;
                                break-inside: avoid !important;
                            }
                            
                            .cv-item,
                            .item {
                                page-break-inside: avoid !important;
                                break-inside: avoid !important;
                            }
                            
                            /* Đảm bảo hình ảnh được in ra */
                            img {
                                max-width: 100% !important;
                                height: auto !important;
                                page-break-inside: avoid !important;
                                break-inside: avoid !important;
                            }
                            
                            /* Đảm bảo không có overflow */
                            * {
                                overflow: visible !important;
                            }
                            
                            /* Loại bỏ các hiệu ứng không cần thiết khi in */
                            .cv-section-left:hover,
                            .cv-section-right:hover,
                            .cv-item:hover,
                            .section:hover,
                            .item:hover {
                                transform: none !important;
                                box-shadow: none !important;
                            }
                            
                            /* Ẩn địa chỉ khi in PDF */
                            .cv-address-print {
                                display: none !important;
                            }
                            
                            /* Ẩn icon địa chỉ khi in PDF */
                            .fa-map-marker-alt {
                                display: none !important;
                            }
                            
                            /* Hiển thị logo WebCV ở góc phải dưới cùng khi in */
                            .cv-footer-logo {
                                display: block !important;
                                position: fixed !important;
                                bottom: 15px !important;
                                right: 20px !important;
                                text-align: right !important;
                                font-size: 12px !important;
                                color: #666 !important;
                                z-index: 1000 !important;
                            }
                            
                            .cv-footer-logo div {
                                font-weight: bold !important;
                                color: #06b6d4 !important;
                                font-size: 14px !important;
                            }
                        }
                    </style>
                </head>
                <body>
                    ${cvHTML}
                </body>
                </html>
            `;

            // Sử dụng Blob URL thay vì about:blank để tránh hiển thị URL
            const blob = new Blob([fullHTML], { type: 'text/html;charset=utf-8' });
            const blobUrl = URL.createObjectURL(blob);
            
            // Mở window với blob URL
            const printWindow = window.open(blobUrl, '_blank');
            
            if (!printWindow) {
                alert('Vui lòng cho phép popup để xuất PDF!');
                URL.revokeObjectURL(blobUrl);
                return;
            }
            
            // Đợi window load xong rồi mới print
            // Sử dụng setTimeout với kiểm tra readyState vì Blob URL có thể không trigger onload
            setTimeout(() => {
                try {
                    // Kiểm tra xem window đã load chưa
                    if (printWindow.document && printWindow.document.readyState === 'complete') {
                        printWindow.focus();
                        setTimeout(() => {
                            printWindow.print();
                            // Revoke blob URL sau khi print
                            setTimeout(() => {
                                URL.revokeObjectURL(blobUrl);
                            }, 1000);
                        }, 250);
                    } else {
                        // Nếu chưa load, dùng onload làm fallback
                        printWindow.onload = function() {
                            printWindow.focus();
                            setTimeout(() => {
                                printWindow.print();
                                setTimeout(() => {
                                    URL.revokeObjectURL(blobUrl);
                                }, 1000);
                            }, 250);
                        };
                        // Fallback: nếu onload không trigger sau 2 giây, vẫn thử print
                        setTimeout(() => {
                            if (printWindow.document && printWindow.document.readyState !== 'complete') {
                                printWindow.focus();
                                setTimeout(() => {
                                    printWindow.print();
                                    setTimeout(() => {
                                        URL.revokeObjectURL(blobUrl);
                                    }, 1000);
                                }, 250);
                            }
                        }, 2000);
                    }
                } catch (e) {
                    console.error('Error printing CV:', e);
                    alert('Có lỗi khi xuất PDF. Vui lòng thử lại.');
                    URL.revokeObjectURL(blobUrl);
                }
            }, 100);
        }

        // Export CV to Word
        function exportToWord() {
            const cvElement = document.getElementById('cv-template');
            if (!cvElement) {
                alert('Không tìm thấy nội dung CV để xuất!');
                return;
            }

            // Create HTML content for Word
            const htmlContent = `
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <title>CV - ${cvData.name}</title>
                    <style>
                        body { 
                            font-family: ${currentFont}; 
                            margin: 0; 
                            padding: 20px; 
                            background: white;
                        }
                        .cv-template-wrapper { 
                            max-width: 800px; 
                            margin: 0 auto; 
                            background: white;
                        }
                    </style>
                </head>
                <body>
                    ${cvElement.innerHTML}
                </body>
                </html>
            `;

            // Create blob and download
            const blob = new Blob([htmlContent], { type: 'application/msword' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `CV_${cvData.name.replace(/\s+/g, '_')}.doc`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        // Export CV to HTML
        function exportToHTML() {
            const cvElement = document.getElementById('cv-template');
            if (!cvElement) {
                alert('Không tìm thấy nội dung CV để xuất!');
                return;
            }

            // Create complete HTML document
            const htmlContent = `
                <!DOCTYPE html>
                <html lang="vi">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>CV - ${cvData.name}</title>
                    <style>
                        body { 
                            font-family: ${currentFont}; 
                            margin: 0; 
                            padding: 20px; 
                            background: #f5f5f5;
                        }
                        .cv-template-wrapper { 
                            max-width: 800px; 
                            margin: 0 auto; 
                            background: white;
                            box-shadow: 0 0 20px rgba(0,0,0,0.1);
                            border-radius: 8px;
                            overflow: hidden;
                        }
                        @media print {
                            body { margin: 0; padding: 0; background: white; }
                            .cv-template-wrapper { box-shadow: none; border-radius: 0; }
                        }
                    </style>
                </head>
                <body>
                    ${cvElement.innerHTML}
                </body>
                </html>
            `;

            // Create blob and download
            const blob = new Blob([htmlContent], { type: 'text/html' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `CV_${cvData.name.replace(/\s+/g, '_')}.html`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        // Toggle export dropdown
        function toggleExportDropdown() {
            const menu = document.getElementById('export-menu');
            if (menu) {
                menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
            }
        }

        // Close export dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const exportDropdown = document.querySelector('.export-dropdown');
            const exportMenu = document.getElementById('export-menu');
            
            if (exportDropdown && !exportDropdown.contains(event.target)) {
                if (exportMenu) {
                    exportMenu.style.display = 'none';
                }
            }
        });

        // Load from profile (refresh from current user profile data)
        function loadFromProfile() {
            console.log('Refreshing from current user profile...');
            const user = JSON.parse(localStorage.getItem('currentUser') || '{}');
            
            if (user.name && user.name !== 'undefined') {
                // Clean user data first
                const cleanedUser = cleanUserData(user);
                
                // Load fresh data from user profile - no sample data
                cvData = {
                    name: cleanedUser.name || 'Họ và tên',
                    email: cleanedUser.email || 'email@example.com',
                    phone: cleanedUser.phone || 'Số điện thoại',
                    location: cleanedUser.location || 'Địa chỉ',
                    title: cleanedUser.title || 'Chức danh',
                    objective: cleanedUser.objective || 'Mục tiêu nghề nghiệp',
                    avatar: cleanedUser.avatar || '',
                    experiences: cleanedUser.experiences || [],
                    educations: cleanedUser.educations || [],
                    skills: cleanedUser.skills || [],
                    certificates: cleanedUser.certificates || [],
                    languages: cleanedUser.languages || []
                };
                
                // Save cleaned data back to localStorage
                localStorage.setItem('currentUser', JSON.stringify(cleanedUser));
                
                console.log('Refreshed CV data from profile:', cvData);
                renderCV();
            } else {
                alert('Không tìm thấy dữ liệu hồ sơ. Vui lòng cập nhật hồ sơ cá nhân trước.');
            }
        }

        // Clean up user data to remove undefined values but preserve existing data
        function cleanUserData(user) {
            if (!user) return user;
            
            // Clean experiences - only remove truly undefined values, keep existing data
            if (user.experiences && Array.isArray(user.experiences)) {
                user.experiences = user.experiences.filter(exp => exp && (exp.position || exp.title) && (exp.position || exp.title) !== 'undefined');
                // Keep original data, don't replace with placeholders
                user.experiences = user.experiences.map(exp => ({
                    position: exp.position || exp.title, // Handle both position and title
                    company: exp.company,
                    duration: exp.duration || (exp.startDate ? `${exp.startDate} - ${exp.current ? 'Hiện tại' : (exp.endDate || 'Tháng/Năm')}` : 'Tháng/Năm'),
                    startDate: exp.startDate,
                    endDate: exp.endDate,
                    current: exp.current,
                    description: exp.description
                }));
            }
            
            // Clean educations - only remove truly undefined values, keep existing data
            if (user.educations && Array.isArray(user.educations)) {
                user.educations = user.educations.filter(edu => edu && edu.degree && edu.degree !== 'undefined');
                user.educations = user.educations.map(edu => ({
                    degree: edu.degree,
                    school: edu.school,
                    year: edu.year
                }));
            }
            
            // Clean skills - only remove truly undefined values, keep existing data
            if (user.skills && Array.isArray(user.skills)) {
                user.skills = user.skills.filter(skill => skill && skill !== 'undefined' && skill !== '');
            }
            
            // Clean certificates - only remove truly undefined values, keep existing data
            if (user.certificates && Array.isArray(user.certificates)) {
                user.certificates = user.certificates.filter(cert => cert && cert.name && cert.name !== 'undefined');
                user.certificates = user.certificates.map(cert => ({
                    name: cert.name,
                    issuer: cert.issuer,
                    date: cert.date,
                    description: cert.description
                }));
            }
            
            // Clean languages - only remove truly undefined values, keep existing data
            if (user.languages && Array.isArray(user.languages)) {
                user.languages = user.languages.filter(lang => lang && lang.name && lang.name !== 'undefined');
                user.languages = user.languages.map(lang => ({
                    name: lang.name,
                    level: lang.level,
                    certificate: lang.certificate
                }));
            }
            
            return user;
        }

        // Sync with CV builder (called from profile page)
        window.syncWithCVBuilder = function() {
            console.log('Syncing with CV builder...');
            loadUserData();
            renderCV(); // Re-render CV after loading new data
        };

        // Save user data to database (shared function)
        window.saveToDatabase = function(userData) {
            console.log('Saving CV data to database:', userData);
            
            // Convert position to title for database compatibility
            const experiencesForDB = (userData.experiences || []).map(exp => ({
                id: exp.id,
                title: exp.position || exp.title,
                company: exp.company,
                startDate: exp.startDate,
                endDate: exp.endDate,
                current: exp.current,
                description: exp.description
            }));

            // Save to persistent storage
            const persistentData = {
                id: userData.id || 1,
                name: userData.name,
                email: userData.email,
                phone: userData.phone,
                location: userData.location,
                title: userData.title,
                objective: userData.objective,
                avatar: userData.avatar,
                experiences: experiencesForDB,
                languages: userData.languages || [],
                educations: userData.educations || [],
                certificates: userData.certificates || [],
                skills: userData.skills || []
            };
            
            localStorage.setItem('persistentUserData', JSON.stringify(persistentData));
            console.log('CV data saved to persistent storage:', persistentData);

            // Also try to save via API (if available)
            const token = getAuthToken();
            if (!token) {
                console.log('No auth token, skipping API save');
                return;
            }
            
            fetch(`${API_BASE_URL}/users`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': token.startsWith('Bearer ') ? token : `Bearer ${token}`,
                    'Accept': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify({
                    userId: userData.id || 1,
                    experiences: experiencesForDB,
                    languages: userData.languages || [],
                    educations: userData.educations || [],
                    certificates: userData.certificates || [],
                    skills: userData.skills || []
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('CV data saved to API:', data);
            })
            .catch(error => {
                console.log('API not available, using localStorage only:', error);
            });
        };

        // Get section visibility
        function getSectionVisibility(sectionType) {
            const saved = localStorage.getItem(`cv-section-${sectionType}`);
            return saved !== null ? saved === 'true' : true; // Default to true if not saved
        }

        // Initialize section visibility
        function initializeSectionVisibility() {
            const sections = ['experience', 'education', 'skills', 'certificates', 'languages'];
            sections.forEach(section => {
                const checkbox = document.getElementById(`show-${section}`);
                if (checkbox) {
                    checkbox.checked = getSectionVisibility(section);
                }
            });
        }


        // Load user data from current logged-in account profile
        function loadUserData() {
            // First try to load from persistent storage
            const persistentData = localStorage.getItem('persistentUserData');
            let user = null;
            
            if (persistentData) {
                try {
                    user = JSON.parse(persistentData);
                    console.log('Loading from persistent storage:', user.name || 'Unknown');
                } catch (error) {
                    console.error('Error parsing persistent data:', error);
                }
            }
            
            // Fallback to currentUser localStorage
            if (!user) {
                user = JSON.parse(localStorage.getItem('currentUser') || '{}');
                console.log('Loading from currentUser localStorage:', user.name || 'Unknown');
            }
            
            if (user.name && user.name !== 'undefined') {
                // Clean user data first
                const cleanedUser = cleanUserData(user);
                
                // Load only from user's actual profile data - no sample data
                cvData = {
                    name: cleanedUser.name || 'Họ và tên',
                    email: cleanedUser.email || 'email@example.com',
                    phone: cleanedUser.phone || 'Số điện thoại',
                    location: cleanedUser.location || 'Địa chỉ',
                    title: cleanedUser.title || 'Chức danh',
                    objective: cleanedUser.objective || 'Mục tiêu nghề nghiệp',
                    avatar: cleanedUser.avatar || '',
                    experiences: cleanedUser.experiences || [],
                    educations: cleanedUser.educations || [],
                    skills: cleanedUser.skills || [],
                    certificates: cleanedUser.certificates || [],
                    languages: cleanedUser.languages || []
                };
                
                // Save cleaned data back to localStorage
                localStorage.setItem('currentUser', JSON.stringify(cleanedUser));
                
                console.log('Loaded profile data for user:', user.name, cvData);
                
                // Display user info
                const userInfoElement = document.getElementById('user-info');
                if (userInfoElement) {
                    userInfoElement.innerHTML = `
                        <i class="fas fa-user"></i> Đang tạo CV cho: <strong>${user.name}</strong> (${user.email})
                    `;
                }
                
                // Show message if no experience data
                if (!cvData.experiences || cvData.experiences.length === 0) {
                    console.log('No experience data found for user:', user.name);
                    // You can add a notification here if needed
                }
                
            } else {
                // Show message if no user is logged in
                alert('Vui lòng đăng nhập để tạo CV. Dữ liệu sẽ được lấy từ hồ sơ cá nhân của bạn.');
                
                // Create empty CV structure
                cvData = {
                    name: 'Họ và tên',
                    email: 'email@example.com',
                    phone: 'Số điện thoại',
                    location: 'Địa chỉ',
                    title: 'Chức danh',
                    objective: 'Mục tiêu nghề nghiệp',
                    avatar: '',
                    experiences: [],
                    educations: [],
                    skills: [],
                    certificates: [],
                    languages: []
                };
                
                // Display no user info
                const userInfoElement = document.getElementById('user-info');
                if (userInfoElement) {
                    userInfoElement.innerHTML = `
                        <i class="fas fa-exclamation-triangle" style="color: #ffc107;"></i> 
                        <strong>Chưa đăng nhập</strong> - Vui lòng đăng nhập để lấy dữ liệu từ hồ sơ
                    `;
                }
            }
            
            renderCV();
        }

        // Get template-specific styles
        function getTemplateStyles(template, primaryColor, fontSize) {
    console.log('Getting styles for template:', template);
            const styles = {
        wrapper: `font-family: ${currentFont}; font-size: ${fontSize}; min-height: 1000px; display: flex; width: 100%; overflow: hidden; box-shadow: 0 0 10px rgba(0,0,0,0.1);`,
        // Các thuộc tính chung khác...
    };
            switch(template) {
                case 'single-column':
                    styles.header = `background: #f8f9fa; color: #333; padding: 25px; text-align: center; border: 1px solid #ddd; border-bottom: 3px solid ${primaryColor};`;
                    styles.content = `padding: 25px; background: white;`;
                    styles.section = `margin-bottom: 25px; border-top: 1px solid #ddd; padding-top: 20px; border-left: 3px solid ${primaryColor}; padding-left: 15px;`;
                    styles.item = `margin-bottom: 20px;`;
                    break;

                case 'two-column':
                    styles.header = `background: #f8f9fa; color: #333; padding: 25px; border: 1px solid #ddd; border-bottom: 3px solid ${primaryColor};`;
                    styles.content = `display: flex; padding: 25px; background: white;`;
                    styles.section = `margin-bottom: 25px; border-left: 3px solid ${primaryColor}; padding-left: 15px;`;
                    styles.item = `margin-bottom: 20px;`;
                    break;

                case 'sidebar':
                case 'header-content':
                    // Layout 2 cột với sidebar tối (giống modern-professional)
                    styles.wrapper = `font-family: ${currentFont}; font-size: ${fontSize}; display: flex; min-height: 1000px; width: 100%; overflow: hidden; box-shadow: 0 0 10px rgba(0,0,0,0.1);`;
                    styles.header = `display: none;`; // Không dùng header riêng, sẽ tích hợp vào sidebar
                    styles.content = `display: flex; width: 100%; gap: 0;`;
                    // Sidebar trái (màu tối)
                    styles.leftColumn = `width: 35%; background-color: #2c3e50; color: #ffffff; padding: 30px 20px; display: flex; flex-direction: column; transition: all 0.3s ease;`;
                    // Cột phải (nền trắng)
                    styles.rightColumn = `width: 65%; background-color: #ffffff; padding: 30px 25px; color: #333; transition: all 0.3s ease;`;
                    // Sections trong sidebar trái (màu trắng)
                    styles.sectionLeft = `margin-bottom: 30px; width: 100%; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 20px;`;
                    styles.sectionTitleLeft = `font-size: 1.2em; font-weight: bold; margin-bottom: 15px; color: #fff; text-transform: uppercase; letter-spacing: 1px;`;
                    styles.sectionContentLeft = `color: #fff; line-height: 1.6; font-size: 0.95em;`;
                    // Sections trong cột phải
                    styles.sectionRight = `margin-bottom: 25px;`;
                    styles.sectionTitleRight = `font-size: 1.3em; color: #2c3e50; font-weight: bold; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; transition: all 0.3s ease;`;
                    styles.sectionContentRight = `color: #555; line-height: 1.6;`;
                    styles.section = `margin-bottom: 25px; background: white; padding: 20px; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 3px solid ${primaryColor};`;
                    styles.item = `margin-bottom: 20px; transition: transform 0.2s ease;`;
                    break;

                case 'executive':
                    styles.header = `background: #f8f9fa; color: #333; padding: 25px; border: 1px solid #ddd;`;
                    styles.content = `padding: 25px; background: white;`;
                    styles.section = `margin-bottom: 25px; background: white; padding: 20px; border: 1px solid #ddd; border-radius: 4px;`;
                    styles.item = `margin-bottom: 20px;`;
                    break;

                case 'corporate':
                    styles.header = `background: white; color: #333; padding: 25px; border-left: 4px solid ${primaryColor};`;
                    styles.content = `padding: 25px; background: white;`;
                    styles.section = `margin-bottom: 25px; border-left: 4px solid ${primaryColor}; padding-left: 20px;`;
                    styles.item = `margin-bottom: 20px;`;
                    break;

                case 'minimalist':
                    styles.header = `background: white; color: #333; padding: 25px; text-align: center; border: 1px solid #ddd; border-bottom: 2px solid ${primaryColor};`;
                    styles.content = `padding: 25px; background: white;`;
                    styles.section = `margin-bottom: 25px; text-align: center; border-top: 1px solid #eee; padding-top: 20px; border-left: 2px solid ${primaryColor}; padding-left: 15px;`;
                    styles.item = `margin-bottom: 20px;`;
                    break;

                case 'modern-professional':
                case 'modern-pro':
                    // Layout 2 cột với sidebar tối bên trái
                    styles.wrapper = `font-family: ${currentFont}; font-size: ${fontSize}; display: flex; min-height: 1000px; width: 100%; overflow: hidden; box-shadow: 0 0 10px rgba(0,0,0,0.1);`;
                    styles.header = `display: none;`; // Không dùng header riêng, sẽ tích hợp vào sidebar
                    styles.content = `display: flex; width: 100%; gap: 0;`;
                    // Sidebar trái (màu tối)
                    styles.leftColumn = `width: 35%; background-color: #2c3e50; color: #ffffff; padding: 30px 20px; display: flex; flex-direction: column; transition: all 0.3s ease;`;
                    // Cột phải (nền trắng)
                    styles.rightColumn = `width: 65%; background-color: #ffffff; padding: 30px 25px; color: #333; transition: all 0.3s ease;`;
                    // Sections trong sidebar trái (màu trắng)
                    styles.sectionLeft = `margin-bottom: 30px; width: 100%; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 20px;`;
                    styles.sectionTitleLeft = `font-size: 1.2em; font-weight: bold; margin-bottom: 15px; color: #fff; text-transform: uppercase; letter-spacing: 1px;`;
                    styles.sectionContentLeft = `color: #fff; line-height: 1.6; font-size: 0.95em;`;
                    // Sections trong cột phải
                    styles.sectionRight = `margin-bottom: 25px;`;
                    styles.sectionTitleRight = `font-size: 1.3em; color: #2c3e50; font-weight: bold; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; transition: all 0.3s ease;`;
                    styles.sectionContentRight = `color: #555; line-height: 1.6;`;
                    styles.section = `margin-bottom: 25px; background: white; padding: 20px; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 3px solid ${primaryColor};`;
                    styles.item = `margin-bottom: 20px; transition: transform 0.2s ease;`;
                    break;

                case 'professional-sidebar':
                    // Layout 2 cột với sidebar tối (màu xám đậm hơn)
                    styles.wrapper = `font-family: ${currentFont}; font-size: ${fontSize}; display: flex; min-height: 1000px; width: 100%; overflow: hidden; box-shadow: 0 0 10px rgba(0,0,0,0.1);`;
                    styles.header = `display: none;`; // Không dùng header riêng
                    styles.content = `display: flex; width: 100%; gap: 0;`;
                    // Sidebar trái (màu xám đậm #34495e)
                    styles.leftColumn = `width: 30%; background-color: #34495e; color: #ffffff; padding: 30px 20px; display: flex; flex-direction: column; transition: all 0.3s ease;`;
                    // Cột phải (nền trắng)
                    styles.rightColumn = `width: 70%; background-color: #ffffff; padding: 30px 25px; color: #333; transition: all 0.3s ease;`;
                    // Sections trong sidebar trái
                    styles.sectionLeft = `margin-bottom: 30px; width: 100%; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 20px;`;
                    styles.sectionTitleLeft = `font-size: 1.1em; font-weight: bold; margin-bottom: 15px; color: #fff; text-transform: uppercase; letter-spacing: 0.5px;`;
                    styles.sectionContentLeft = `color: #fff; line-height: 1.6; font-size: 0.9em;`;
                    // Sections trong cột phải
                    styles.sectionRight = `margin-bottom: 25px;`;
                    styles.sectionTitleRight = `font-size: 1.4em; color: #34495e; font-weight: bold; border-bottom: 3px solid #34495e; padding-bottom: 10px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; transition: all 0.3s ease;`;
                    styles.sectionContentRight = `color: #555; line-height: 1.6;`;
                    styles.section = `margin-bottom: 25px; background: white; padding: 20px; border: 1px solid #ddd; border-radius: 4px; border-left: 4px solid ${primaryColor};`;
                    styles.item = `margin-bottom: 20px; transition: transform 0.2s ease;`;
                    break;

                case 'simple':
                    styles.wrapper = `font-family: ${currentFont}; font-size: ${fontSize}; max-width: 800px; margin: 0 auto; background: white; padding: 30px; box-shadow: 0 0 10px rgba(0,0,0,0.1);`;
                    styles.header = `padding: 30px 0; border-bottom: 2px solid ${primaryColor}; margin-bottom: 30px;`;
                    styles.content = `padding: 0; background: white;`;
                    styles.section = `margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #eee;`;
                    styles.item = `margin-bottom: 20px;`;
                    break;

                default:
                    styles.header = `background: #f8f9fa; color: #333; padding: 25px; text-align: center; border: 1px solid #ddd; border-bottom: 3px solid ${primaryColor};`;
                    styles.content = `padding: 25px; background: white;`;
                    styles.section = `margin-bottom: 25px; border-top: 1px solid #ddd; padding-top: 20px; border-left: 3px solid ${primaryColor}; padding-left: 15px;`;
                    styles.item = `margin-bottom: 20px;`;
            }

            console.log('Template styles generated:', styles);
            return styles;
        }

        // Render CV với layout modern-professional (2 cột với sidebar tối)
        function renderModernProfessionalCV() {
            const cvElement = document.getElementById('cv-template');
            if (!cvElement) return;

            const primaryColor = colorMap[currentColor];
            const baseFontSize = parseFloat(document.getElementById('font-size')?.value) || 11;
            const fontSizes = getFontSizes(baseFontSize);
            const fontSize = baseFontSize + 'px';
            const templateStyles = getTemplateStyles(currentTemplate, primaryColor, fontSize);
            
            // Bắt đầu render với wrapper và 2 cột
            let cvHTML = `
                <div class="cv-template-wrapper ${editMode ? 'edit-mode' : ''}" data-template="${currentTemplate}" style="${templateStyles.wrapper}">
                    <!-- Sidebar trái (màu tối) -->
                    <div class="cv-left-column" style="${templateStyles.leftColumn}">
                        <!-- Avatar và tên -->
                        ${cvData.avatar ? `
                            <div style="text-align: center; margin-bottom: 20px;">
                                <img src="${cvData.avatar}" alt="Avatar" 
                                     style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid rgba(255,255,255,0.3); ${editMode ? 'border: 4px dashed rgba(255,255,255,0.5);' : ''}">
                            </div>
                        ` : ''}
                        <h1 style="margin: 0 0 10px 0; font-size: ${fontSizes.name}; font-weight: bold; text-transform: uppercase; text-align: center; color: #fff; ${editMode ? 'border: 2px dashed rgba(255,255,255,0.5); padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${cvData.name}</h1>
                        <p style="margin: 0 0 30px 0; font-size: ${fontSizes.contact}; font-weight: 300; text-align: center; color: rgba(255,255,255,0.9); ${editMode ? 'border: 2px dashed rgba(255,255,255,0.5); padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${cvData.title}</p>
                        
                        <!-- Thông tin liên hệ -->
                        <div class="cv-section-left" style="${templateStyles.sectionLeft}">
                            <h2 class="cv-section-title-left" style="${templateStyles.sectionTitleLeft}; font-size: ${fontSizes.sectionTitle};">Thông tin liên hệ</h2>
                            <div class="cv-section-content-left" style="${templateStyles.sectionContentLeft}; font-size: ${fontSizes.contact};">
                                ${(() => {
                                    const headerBirthday = document.getElementById('header-birthday')?.value;
                                    const headerGender = document.getElementById('header-gender')?.value;
                                    return headerBirthday ? `<p style="margin: 8px 0; font-size: ${fontSizes.contact};"><i class="fas fa-birthday-cake"></i> <span ${editMode ? 'contenteditable="true" style="border: 2px dashed rgba(255,255,255,0.5); padding: 2px;"' : ''}>${headerBirthday}</span></p>` : '';
                                })()}
                                ${(() => {
                                    const headerGender = document.getElementById('header-gender')?.value;
                                    if (!headerGender) return '';
                                    const genderText = headerGender === 'male' ? 'Nam' : headerGender === 'female' ? 'Nữ' : 'Khác';
                                    return `<p style="margin: 8px 0; font-size: ${fontSizes.contact};"><i class="fas fa-user"></i> <span ${editMode ? 'contenteditable="true" style="border: 2px dashed rgba(255,255,255,0.5); padding: 2px;"' : ''}>${genderText}</span></p>`;
                                })()}
                                ${cvData.phone ? `<p style="margin: 8px 0; font-size: ${fontSizes.contact};"><i class="fas fa-phone"></i> <span ${editMode ? 'contenteditable="true" style="border: 2px dashed rgba(255,255,255,0.5); padding: 2px;"' : ''}>${cvData.phone}</span></p>` : ''}
                                ${cvData.email ? `<p style="margin: 8px 0; font-size: ${fontSizes.contact};"><i class="fas fa-envelope"></i> <span ${editMode ? 'contenteditable="true" style="border: 2px dashed rgba(255,255,255,0.5); padding: 2px;"' : ''}>${cvData.email}</span></p>` : ''}
                                ${(() => {
                                    const headerWebsite = document.getElementById('header-website')?.value;
                                    return headerWebsite ? `<p style="margin: 8px 0; font-size: ${fontSizes.contact};"><i class="fas fa-globe"></i> <a href="${headerWebsite}" target="_blank" style="color: #fff; text-decoration: underline;">${headerWebsite}</a></p>` : '';
                                })()}
                                ${cvData.location ? `<p style="margin: 8px 0; font-size: ${fontSizes.contact};"><i class="fas fa-map-marker-alt"></i> <span ${editMode ? 'contenteditable="true" style="border: 2px dashed rgba(255,255,255,0.5); padding: 2px;"' : ''}>${cvData.location}</span></p>` : ''}
                            </div>
                        </div>
                        
                        <!-- Học vấn -->
                        ${getSectionVisibility('education') && cvData.educations && cvData.educations.length > 0 ? `
                            <div class="cv-section-left" style="${templateStyles.sectionLeft}">
                                <h2 class="cv-section-title-left" style="${templateStyles.sectionTitleLeft}; font-size: ${fontSizes.sectionTitle};">Học vấn</h2>
                                <div class="cv-section-content-left" style="${templateStyles.sectionContentLeft}; font-size: ${fontSizes.content};">
                                    ${cvData.educations.map((edu, index) => {
                                        const dateRange = formatDateRange(edu.start_date, edu.end_date);
                                        return `
                                        <div style="margin-bottom: ${index < cvData.educations.length - 1 ? '20px' : '0'};">
                                            <h3 style="color: #fff; font-size: ${fontSizes.content}; margin: 0 0 5px 0; font-weight: 600; ${editMode ? 'border: 2px dashed rgba(255,255,255,0.5); padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${edu.degree}${edu.major ? ' - ' + edu.major : ''}</h3>
                                            <p style="color: rgba(255,255,255,0.9); margin: 0 0 8px 0; font-weight: 500; font-size: ${fontSizes.contact}; ${editMode ? 'border: 2px dashed rgba(255,255,255,0.5); padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${edu.school}${dateRange ? ' - ' + dateRange : ''}</p>
                                            ${edu.description ? `<p style="color: rgba(255,255,255,0.8); margin: 0; line-height: 1.6; font-size: ${fontSizes.content}; ${editMode ? 'border: 2px dashed rgba(255,255,255,0.5); padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${edu.description}</p>` : ''}
                                        </div>
                                        ${index < cvData.educations.length - 1 ? '<hr style="border: none; border-top: 1px solid rgba(255,255,255,0.2); margin: 15px 0;">' : ''}
                                    `;
                                    }).join('')}
                                </div>
                            </div>
                        ` : ''}
                        
                        <!-- Kỹ năng -->
                        ${getSectionVisibility('skills') && cvData.skills && cvData.skills.length > 0 ? `
                            <div class="cv-section-left" style="${templateStyles.sectionLeft}">
                                <h2 class="cv-section-title-left" style="${templateStyles.sectionTitleLeft}; font-size: ${fontSizes.sectionTitle};">Kỹ năng</h2>
                                <div class="cv-section-content-left" style="${templateStyles.sectionContentLeft}; font-size: ${fontSizes.content};">
                                    ${cvData.skills.map(skill => `
                                        <div style="margin-bottom: 15px;">
                                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                                <span style="font-size: ${fontSizes.content};" ${editMode ? 'contenteditable="true" style="border: 2px dashed rgba(255,255,255,0.5); padding: 2px;"' : ''}>${skill}</span>
                                            </div>
                                            <div style="background: rgba(255,255,255,0.2); height: 6px; border-radius: 3px; overflow: hidden;">
                                                <div style="background: #fff; height: 100%; width: 80%; border-radius: 3px; transition: width 0.5s ease;"></div>
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        ` : ''}
                        
                        <!-- Sở thích -->
                        ${getSectionVisibility('hobby') && cvData.hobbies && cvData.hobbies.length > 0 ? `
                            <div class="cv-section-left" style="${templateStyles.sectionLeft}">
                                <h2 class="cv-section-title-left" style="${templateStyles.sectionTitleLeft}; font-size: ${fontSizes.sectionTitle};">Sở thích</h2>
                                <div class="cv-section-content-left" style="${templateStyles.sectionContentLeft}; font-size: ${fontSizes.content};">
                                    <ul style="margin: 0; padding-left: 20px; font-size: ${fontSizes.content};">
                                        ${cvData.hobbies.map(hobby => `<li style="font-size: ${fontSizes.content};" ${editMode ? 'contenteditable="true" style="border: 2px dashed rgba(255,255,255,0.5); padding: 2px; margin: 5px 0;"' : ''}>${hobby}</li>`).join('')}
                                    </ul>
                                </div>
                            </div>
                        ` : ''}
                    </div>
                    
                    <!-- Cột phải (nền trắng) -->
                    <div class="cv-right-column" style="${templateStyles.rightColumn}">
            `;
            
            // Mục tiêu nghề nghiệp
            if (cvData.objective && getSectionVisibility('objective')) {
                cvHTML += `
                    <div class="cv-section-right" style="${templateStyles.sectionRight}">
                        <h2 class="cv-section-title-right" style="${templateStyles.sectionTitleRight}; font-size: ${fontSizes.sectionTitle};">
                            <i class="fas fa-bullseye"></i> Mục tiêu nghề nghiệp
                        </h2>
                        <div class="cv-section-content-right" style="${templateStyles.sectionContentRight}; font-size: ${fontSizes.content};">
                            <p style="font-size: ${fontSizes.content}; line-height: 1.6; ${editMode ? 'border: 2px dashed #ccc; padding: 5px; min-height: 50px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${cvData.objective}</p>
                        </div>
                    </div>
                `;
            }
            
            // Kinh nghiệm làm việc
            if (getSectionVisibility('experience') && cvData.experiences && cvData.experiences.length > 0) {
                cvHTML += `
                    <div class="cv-section-right" style="${templateStyles.sectionRight}">
                        <h2 class="cv-section-title-right" style="${templateStyles.sectionTitleRight}; font-size: ${fontSizes.sectionTitle};">
                            <i class="fas fa-briefcase"></i> Kinh nghiệm làm việc
                        </h2>
                        <div class="cv-section-content-right" style="${templateStyles.sectionContentRight}; font-size: ${fontSizes.content};">
                `;
                
                cvData.experiences.forEach((exp, index) => {
                    const dateRange = formatDateRange(exp.start_date, exp.end_date);
                    cvHTML += `
                        <div class="cv-item" style="${templateStyles.item}">
                            <h3 style="color: #333; font-size: ${fontSizes.content}; margin: 0 0 5px 0; font-weight: 600; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${exp.position}</h3>
                            <p style="color: #666; margin: 0 0 8px 0; font-weight: 500; font-size: ${fontSizes.contact}; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${exp.company}${dateRange ? ' | ' + dateRange : ''}</p>
                            ${exp.description ? `<p style="color: #555; margin: 0; line-height: 1.6; font-size: ${fontSizes.content}; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${exp.description}</p>` : ''}
                        </div>
                        ${index < cvData.experiences.length - 1 ? '<hr style="border: none; border-top: 1px solid #eee; margin: 15px 0;">' : ''}
                    `;
                });
                
                cvHTML += `</div></div>`;
            }
            
            // Dự án
            if (getSectionVisibility('project') && cvData.projects && cvData.projects.length > 0) {
                cvHTML += `
                    <div class="cv-section-right" style="${templateStyles.sectionRight}">
                        <h2 class="cv-section-title-right" style="${templateStyles.sectionTitleRight}; font-size: ${fontSizes.sectionTitle};">
                            <i class="fas fa-project-diagram"></i> Dự án
                        </h2>
                        <div class="cv-section-content-right" style="${templateStyles.sectionContentRight}; font-size: ${fontSizes.content};">
                `;
                
                cvData.projects.forEach((proj, index) => {
                    const dateRange = formatDateRange(proj.start_date, proj.end_date);
                    cvHTML += `
                        <div class="cv-item" style="${templateStyles.item}">
                            <h3 style="color: #333; font-size: ${fontSizes.content}; margin: 0 0 5px 0; font-weight: 600; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${proj.project_name}</h3>
                            ${proj.role ? `<p style="color: #666; margin: 0 0 8px 0; font-size: ${fontSizes.contact};">Vai trò: <span ${editMode ? 'contenteditable="true" style="border: 2px dashed #ccc; padding: 2px;"' : ''}>${proj.role}</span>${dateRange ? ' | ' + dateRange : ''}</p>` : dateRange ? `<p style="color: #666; margin: 0 0 8px 0; font-size: ${fontSizes.contact};">${dateRange}</p>` : ''}
                            ${proj.technologies ? `<p style="color: ${primaryColor}; margin: 0 0 8px 0; font-weight: 500; font-size: ${fontSizes.contact};">Công nghệ: <span ${editMode ? 'contenteditable="true" style="border: 2px dashed #ccc; padding: 2px;"' : ''}>${proj.technologies}</span></p>` : ''}
                            ${proj.description ? `<p style="color: #555; margin: 0; line-height: 1.6; font-size: ${fontSizes.content}; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${proj.description}</p>` : ''}
                        </div>
                        ${index < cvData.projects.length - 1 ? '<hr style="border: none; border-top: 1px solid #eee; margin: 15px 0;">' : ''}
                    `;
                });
                
                cvHTML += `</div></div>`;
            }
            
            // Chứng chỉ
            if (getSectionVisibility('certification') && cvData.certifications && cvData.certifications.length > 0) {
                cvHTML += `
                    <div class="cv-section-right" style="${templateStyles.sectionRight}">
                        <h2 class="cv-section-title-right" style="${templateStyles.sectionTitleRight}; font-size: ${fontSizes.sectionTitle};">
                            <i class="fas fa-certificate"></i> Chứng chỉ
                        </h2>
                        <div class="cv-section-content-right" style="${templateStyles.sectionContentRight}; font-size: ${fontSizes.content};">
                `;
                
                cvData.certifications.forEach((cert, index) => {
                    const formattedDate = cert.date_received ? formatDate(cert.date_received) : '';
                    cvHTML += `
                        <div class="cv-item" style="${templateStyles.item}">
                            <h3 style="color: #333; font-size: ${fontSizes.content}; margin: 0 0 5px 0; font-weight: 600; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${cert.cert_name}</h3>
                            ${cert.organization ? `<p style="color: #666; margin: 0 0 8px 0; font-size: ${fontSizes.contact};">Tổ chức: <span ${editMode ? 'contenteditable="true" style="border: 2px dashed #ccc; padding: 2px;"' : ''}>${cert.organization}</span>${formattedDate ? ' | ' + formattedDate : ''}</p>` : formattedDate ? `<p style="color: #666; margin: 0 0 8px 0; font-size: ${fontSizes.contact};">${formattedDate}</p>` : ''}
                            ${cert.description ? `<p style="color: #555; margin: 0; line-height: 1.6; font-size: ${fontSizes.content}; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${cert.description}</p>` : ''}
                        </div>
                        ${index < cvData.certifications.length - 1 ? '<hr style="border: none; border-top: 1px solid #eee; margin: 15px 0;">' : ''}
                    `;
                });
                
                cvHTML += `</div></div>`;
            }
            
            // Giải thưởng
            if (getSectionVisibility('award') && cvData.awards && cvData.awards.length > 0) {
                cvHTML += `
                    <div class="cv-section-right" style="${templateStyles.sectionRight}">
                        <h2 class="cv-section-title-right" style="${templateStyles.sectionTitleRight}; font-size: ${fontSizes.sectionTitle};">
                            <i class="fas fa-trophy"></i> Giải thưởng
                        </h2>
                        <div class="cv-section-content-right" style="${templateStyles.sectionContentRight}; font-size: ${fontSizes.content};">
                `;
                
                cvData.awards.forEach((award, index) => {
                    const formattedDate = award.date_received ? formatDate(award.date_received) : '';
                    cvHTML += `
                        <div class="cv-item" style="${templateStyles.item}">
                            <h3 style="color: #333; font-size: ${fontSizes.content}; margin: 0 0 5px 0; font-weight: 600; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${award.award_name}</h3>
                            ${award.organization ? `<p style="color: #666; margin: 0 0 8px 0; font-size: ${fontSizes.contact};">Tổ chức: <span ${editMode ? 'contenteditable="true" style="border: 2px dashed #ccc; padding: 2px;"' : ''}>${award.organization}</span>${formattedDate ? ' | ' + formattedDate : ''}</p>` : formattedDate ? `<p style="color: #666; margin: 0 0 8px 0; font-size: ${fontSizes.contact};">${formattedDate}</p>` : ''}
                            ${award.description ? `<p style="color: #555; margin: 0; line-height: 1.6; font-size: ${fontSizes.content}; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${award.description}</p>` : ''}
                        </div>
                        ${index < cvData.awards.length - 1 ? '<hr style="border: none; border-top: 1px solid #eee; margin: 15px 0;">' : ''}
                    `;
                });
                
                cvHTML += `</div></div>`;
            }
            
            // Hoạt động
            if (getSectionVisibility('activity') && cvData.activities && cvData.activities.length > 0) {
                cvHTML += `
                    <div class="cv-section-right" style="${templateStyles.sectionRight}">
                        <h2 class="cv-section-title-right" style="${templateStyles.sectionTitleRight}; font-size: ${fontSizes.sectionTitle};">
                            <i class="fas fa-users"></i> Hoạt động
                        </h2>
                        <div class="cv-section-content-right" style="${templateStyles.sectionContentRight}; font-size: ${fontSizes.content};">
                `;
                
                cvData.activities.forEach((act, index) => {
                    const dateRange = formatDateRange(act.start_date, act.end_date);
                    cvHTML += `
                        <div class="cv-item" style="${templateStyles.item}">
                            <h3 style="color: #333; font-size: ${fontSizes.content}; margin: 0 0 5px 0; font-weight: 600; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${act.role} - ${act.organization_name}</h3>
                            ${dateRange ? `<p style="color: #666; margin: 0 0 8px 0; font-size: ${fontSizes.contact};">${dateRange}</p>` : ''}
                            ${act.description ? `<p style="color: #555; margin: 0; line-height: 1.6; font-size: ${fontSizes.content}; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${act.description}</p>` : ''}
                        </div>
                        ${index < cvData.activities.length - 1 ? '<hr style="border: none; border-top: 1px solid #eee; margin: 15px 0;">' : ''}
                    `;
                });
                
                cvHTML += `</div></div>`;
            }
            
            // Người tham khảo
            if (getSectionVisibility('reference') && cvData.references && cvData.references.length > 0) {
                cvHTML += `
                    <div class="cv-section-right" style="${templateStyles.sectionRight}">
                        <h2 class="cv-section-title-right" style="${templateStyles.sectionTitleRight}; font-size: ${fontSizes.sectionTitle};">
                            <i class="fas fa-user-friends"></i> Người tham khảo
                        </h2>
                        <div class="cv-section-content-right" style="${templateStyles.sectionContentRight}; font-size: ${fontSizes.content};">
                `;
                
                cvData.references.forEach((ref, index) => {
                    cvHTML += `
                        <div class="cv-item" style="${templateStyles.item}">
                            <h3 style="color: #333; font-size: ${fontSizes.content}; margin: 0 0 5px 0; font-weight: 600; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${ref.name}</h3>
                            ${ref.relationship ? `<p style="color: #666; margin: 0 0 8px 0; font-size: ${fontSizes.contact};">Quan hệ: <span ${editMode ? 'contenteditable="true" style="border: 2px dashed #ccc; padding: 2px;"' : ''}>${ref.relationship}</span></p>` : ''}
                            ${ref.contact_info ? `<p style="color: #555; margin: 0; line-height: 1.6; font-size: ${fontSizes.content}; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>Liên hệ: ${ref.contact_info}</p>` : ''}
                        </div>
                        ${index < cvData.references.length - 1 ? '<hr style="border: none; border-top: 1px solid #eee; margin: 15px 0;">' : ''}
                    `;
                });
                
                cvHTML += `</div></div>`;
            }
            
            // Thông tin bổ sung
            if (getSectionVisibility('extrainfo') && cvData.extrainfos && cvData.extrainfos.length > 0) {
                cvHTML += `
                    <div class="cv-section-right" style="${templateStyles.sectionRight}">
                        <h2 class="cv-section-title-right" style="${templateStyles.sectionTitleRight}; font-size: ${fontSizes.sectionTitle};">
                            <i class="fas fa-info-circle"></i> Thông tin bổ sung
                        </h2>
                        <div class="cv-section-content-right" style="${templateStyles.sectionContentRight}; font-size: ${fontSizes.content};">
                `;
                
                cvData.extrainfos.forEach((info, index) => {
                    cvHTML += `
                        <div class="cv-item" style="${templateStyles.item}">
                            <p style="color: #555; margin: 0; line-height: 1.6; font-size: ${fontSizes.content}; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${info}</p>
                        </div>
                        ${index < cvData.extrainfos.length - 1 ? '<hr style="border: none; border-top: 1px solid #eee; margin: 15px 0;">' : ''}
                    `;
                });
                
                cvHTML += `</div></div>`;
            }
            
            cvHTML += `</div></div>`;
            
            // Thêm logo WebCV ở cuối CV (chỉ hiển thị khi in)
            cvHTML += `
                <div class="cv-footer-logo" style="display: none; position: fixed; bottom: 20px; right: 20px; text-align: right; font-size: 12px; color: #666; z-index: 1000;">
                    <div style="font-weight: bold; color: #06b6d4; font-size: 14px;">WebCV</div>
                </div>
            `;
            
            cvElement.innerHTML = cvHTML;
            
            // Add event listeners for inline editing
            if (editMode) {
                addInlineEditListeners();
            }
        }

        // Render CV với layout đơn giản (single-column với header có avatar bên trái)
        function renderSimpleCV() {
            const cvElement = document.getElementById('cv-template');
            if (!cvElement) return;

            const primaryColor = colorMap[currentColor];
            const baseFontSize = parseFloat(document.getElementById('font-size')?.value) || 11;
            const fontSizes = getFontSizes(baseFontSize);
            const fontSize = baseFontSize + 'px';
            const templateStyles = getTemplateStyles(currentTemplate, primaryColor, fontSize);
            
            let cvHTML = `
                <div class="cv-template-wrapper ${editMode ? 'edit-mode' : ''}" data-template="${currentTemplate}" style="${templateStyles.wrapper}">
                    <!-- Header với avatar bên trái -->
                    <div class="cv-header" style="${templateStyles.header}">
                        <div style="display: flex; align-items: flex-start; gap: 25px;">
                            ${cvData.avatar ? `
                                <div class="cv-avatar" style="width: 120px; height: 120px; border-radius: 50%; overflow: hidden; border: 3px solid ${primaryColor}; flex-shrink: 0; ${editMode ? 'border: 3px dashed #ccc;' : ''}">
                                    <img src="${cvData.avatar}" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                            ` : ''}
                            <div style="flex: 1;">
                                <h1 style="margin: 0 0 10px 0; font-size: ${fontSizes.name}; font-weight: bold; color: #333; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${cvData.name}</h1>
                                <h2 style="margin: 0 0 20px 0; font-size: ${fontSizes.sectionTitle}; font-weight: 500; color: #666; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${cvData.title}</h2>
                                <div style="display: flex; flex-wrap: wrap; gap: 15px; font-size: ${fontSizes.contact}; color: #555;">
                                    ${(() => {
                                        const headerBirthday = document.getElementById('header-birthday')?.value;
                                        const headerGender = document.getElementById('header-gender')?.value;
                                        const headerPhone = document.getElementById('header-phone')?.value;
                                        const headerEmail = document.getElementById('header-email')?.value;
                                        const headerWebsite = document.getElementById('header-website')?.value;
                                        const headerAddress = document.getElementById('header-address')?.value;
                                        let info = [];
                                        if (headerBirthday) info.push(`<span><i class="fas fa-birthday-cake"></i> <span ${editMode ? 'contenteditable="true" style="border: 2px dashed #ccc; padding: 2px;"' : ''}>${headerBirthday}</span></span>`);
                                        if (headerGender) {
                                            const genderText = headerGender === 'male' ? 'Nam' : headerGender === 'female' ? 'Nữ' : 'Khác';
                                            info.push(`<span><i class="fas fa-user"></i> <span ${editMode ? 'contenteditable="true" style="border: 2px dashed #ccc; padding: 2px;"' : ''}>${genderText}</span></span>`);
                                        }
                                        if (headerPhone || cvData.phone) info.push(`<span><i class="fas fa-phone"></i> <span ${editMode ? 'contenteditable="true" style="border: 2px dashed #ccc; padding: 2px;"' : ''}>${headerPhone || cvData.phone}</span></span>`);
                                        if (headerEmail || cvData.email) info.push(`<span><i class="fas fa-envelope"></i> <span ${editMode ? 'contenteditable="true" style="border: 2px dashed #ccc; padding: 2px;"' : ''}>${headerEmail || cvData.email}</span></span>`);
                                        if (headerWebsite) info.push(`<span><i class="fas fa-globe"></i> <a href="${headerWebsite}" target="_blank" style="color: ${primaryColor}; text-decoration: underline;">${headerWebsite}</a></span>`);
                                        if (headerAddress || cvData.location) info.push(`<span class="cv-address-print"><i class="fas fa-map-marker-alt"></i> <span ${editMode ? 'contenteditable="true" style="border: 2px dashed #ccc; padding: 2px;"' : ''}>${headerAddress || cvData.location}</span></span>`);
                                        return info.join(' | ');
                                    })()}
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Content -->
                    <div class="cv-content" style="${templateStyles.content}">
                        <!-- Mục tiêu nghề nghiệp -->
                        ${cvData.objective && getSectionVisibility('objective') ? `
                            <div class="section" style="${templateStyles.section}">
                                <h3 style="margin: 0 0 15px 0; font-size: ${fontSizes.sectionTitle}; font-weight: bold; color: #333; border-bottom: 2px solid ${primaryColor}; padding-bottom: 8px;">MỤC TIÊU NGHỀ NGHIỆP</h3>
                                <p style="margin: 0; line-height: 1.8; font-size: ${fontSizes.content}; ${editMode ? 'border: 2px dashed #ccc; padding: 5px; min-height: 50px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${cvData.objective}</p>
                            </div>
                        ` : ''}
                        
                        <!-- Học vấn -->
                        ${getSectionVisibility('education') && cvData.educations && cvData.educations.length > 0 ? `
                            <div class="section" style="${templateStyles.section}">
                                <h3 style="margin: 0 0 15px 0; font-size: ${fontSizes.sectionTitle}; font-weight: bold; color: #333; border-bottom: 2px solid ${primaryColor}; padding-bottom: 8px;">HỌC VẤN</h3>
                                ${cvData.educations.map(edu => {
                                    const dateRange = formatDateRange(edu.start_date, edu.end_date);
                                    return `
                                    <div class="item" style="${templateStyles.item}">
                                        <h4 style="margin: 0 0 5px 0; font-size: ${fontSizes.content}; font-weight: 600; color: #333; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${edu.degree}${edu.major ? ' - ' + edu.major : ''}</h4>
                                        <p style="margin: 0 0 8px 0; font-size: ${fontSizes.contact}; color: #666; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${edu.school}${dateRange ? ' - ' + dateRange : ''}</p>
                                        ${edu.description ? `<p style="margin: 0; line-height: 1.6; font-size: ${fontSizes.content}; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${edu.description}</p>` : ''}
                                    </div>
                                `;
                                }).join('')}
                            </div>
                        ` : ''}
                        
                        <!-- Kinh nghiệm làm việc -->
                        ${getSectionVisibility('experience') && cvData.experiences && cvData.experiences.length > 0 ? `
                            <div class="section" style="${templateStyles.section}">
                                <h3 style="margin: 0 0 15px 0; font-size: ${fontSizes.sectionTitle}; font-weight: bold; color: #333; border-bottom: 2px solid ${primaryColor}; padding-bottom: 8px;">KINH NGHIỆM LÀM VIỆC</h3>
                                ${cvData.experiences.map(exp => {
                                    const dateRange = formatDateRange(exp.start_date, exp.end_date);
                                    return `
                                    <div class="item" style="${templateStyles.item}">
                                        <h4 style="margin: 0 0 5px 0; font-size: ${fontSizes.content}; font-weight: 600; color: #333; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${exp.position}</h4>
                                        <p style="margin: 0 0 8px 0; font-size: ${fontSizes.contact}; font-weight: 500; color: #666; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${exp.company}${dateRange ? ' | ' + dateRange : ''}</p>
                                        ${exp.description ? `<p style="margin: 0; line-height: 1.8; font-size: ${fontSizes.content}; white-space: pre-line; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${exp.description}</p>` : ''}
                                    </div>
                                `;
                                }).join('')}
                            </div>
                        ` : ''}
                        
                        <!-- Kỹ năng -->
                        ${getSectionVisibility('skills') && cvData.skills && cvData.skills.length > 0 ? `
                            <div class="section" style="${templateStyles.section}">
                                <h3 style="margin: 0 0 15px 0; font-size: ${fontSizes.sectionTitle}; font-weight: bold; color: #333; border-bottom: 2px solid ${primaryColor}; padding-bottom: 8px;">KỸ NĂNG</h3>
                                <p style="margin: 0; line-height: 1.8; font-size: ${fontSizes.content}; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${cvData.skills.join(', ')}</p>
                            </div>
                        ` : ''}
                        
                        <!-- Dự án -->
                        ${getSectionVisibility('project') && cvData.projects && cvData.projects.length > 0 ? `
                            <div class="section" style="${templateStyles.section}">
                                <h3 style="margin: 0 0 15px 0; font-size: ${fontSizes.sectionTitle}; font-weight: bold; color: #333; border-bottom: 2px solid ${primaryColor}; padding-bottom: 8px;">DỰ ÁN</h3>
                                ${cvData.projects.map(proj => {
                                    const dateRange = formatDateRange(proj.start_date, proj.end_date);
                                    return `
                                    <div class="item" style="${templateStyles.item}">
                                        <h4 style="margin: 0 0 5px 0; font-size: ${fontSizes.content}; font-weight: 600; color: #333; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${proj.project_name}</h4>
                                        ${proj.role ? `<p style="margin: 0 0 8px 0; font-size: ${fontSizes.contact}; color: #666; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>Vai trò: ${proj.role}${dateRange ? ' | ' + dateRange : ''}</p>` : dateRange ? `<p style="margin: 0 0 8px 0; font-size: ${fontSizes.contact}; color: #666; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${dateRange}</p>` : ''}
                                        ${proj.technologies ? `<p style="margin: 0 0 8px 0; font-size: ${fontSizes.contact}; color: #666; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}><strong>Công nghệ:</strong> ${proj.technologies}</p>` : ''}
                                        ${proj.description ? `<p style="margin: 0; line-height: 1.8; font-size: ${fontSizes.content}; white-space: pre-line; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${proj.description}</p>` : ''}
                                    </div>
                                `;
                                }).join('')}
                            </div>
                        ` : ''}
                        
                        <!-- Chứng chỉ -->
                        ${getSectionVisibility('certification') && cvData.certifications && cvData.certifications.length > 0 ? `
                            <div class="section" style="${templateStyles.section}">
                                <h3 style="margin: 0 0 15px 0; font-size: ${fontSizes.sectionTitle}; font-weight: bold; color: #333; border-bottom: 2px solid ${primaryColor}; padding-bottom: 8px;">CHỨNG CHỈ</h3>
                                ${cvData.certifications.map(cert => {
                                    const formattedDate = cert.date_received ? formatDate(cert.date_received) : '';
                                    return `
                                    <div class="item" style="${templateStyles.item}">
                                        <h4 style="margin: 0 0 5px 0; font-size: ${fontSizes.content}; font-weight: 600; color: #333; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${cert.cert_name}</h4>
                                        <p style="margin: 0; font-size: ${fontSizes.contact}; color: #666; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${cert.organization}${formattedDate ? ' | ' + formattedDate : ''}</p>
                                        ${cert.description ? `<p style="margin: 5px 0 0 0; line-height: 1.6; font-size: ${fontSizes.content}; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${cert.description}</p>` : ''}
                                    </div>
                                `;
                                }).join('')}
                            </div>
                        ` : ''}
                        
                        <!-- Giải thưởng -->
                        ${getSectionVisibility('award') && cvData.awards && cvData.awards.length > 0 ? `
                            <div class="section" style="${templateStyles.section}">
                                <h3 style="margin: 0 0 15px 0; font-size: ${fontSizes.sectionTitle}; font-weight: bold; color: #333; border-bottom: 2px solid ${primaryColor}; padding-bottom: 8px;">GIẢI THƯỞNG</h3>
                                ${cvData.awards.map(award => {
                                    const formattedDate = award.date_received ? formatDate(award.date_received) : '';
                                    return `
                                    <div class="item" style="${templateStyles.item}">
                                        <h4 style="margin: 0 0 5px 0; font-size: ${fontSizes.content}; font-weight: 600; color: #333; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${award.award_name}</h4>
                                        <p style="margin: 0; font-size: ${fontSizes.contact}; color: #666; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${award.organization}${formattedDate ? ' | ' + formattedDate : ''}</p>
                                        ${award.description ? `<p style="margin: 5px 0 0 0; line-height: 1.6; font-size: ${fontSizes.content}; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${award.description}</p>` : ''}
                                    </div>
                                `;
                                }).join('')}
                            </div>
                        ` : ''}
                        
                        <!-- Hoạt động -->
                        ${getSectionVisibility('activity') && cvData.activities && cvData.activities.length > 0 ? `
                            <div class="section" style="${templateStyles.section}">
                                <h3 style="margin: 0 0 15px 0; font-size: ${fontSizes.sectionTitle}; font-weight: bold; color: #333; border-bottom: 2px solid ${primaryColor}; padding-bottom: 8px;">HOẠT ĐỘNG</h3>
                                ${cvData.activities.map(act => {
                                    const dateRange = formatDateRange(act.start_date, act.end_date);
                                    return `
                                    <div class="item" style="${templateStyles.item}">
                                        <h4 style="margin: 0 0 5px 0; font-size: ${fontSizes.content}; font-weight: 600; color: #333; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${act.role} - ${act.organization_name}</h4>
                                        ${dateRange ? `<p style="margin: 0 0 8px 0; font-size: ${fontSizes.contact}; color: #666; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${dateRange}</p>` : ''}
                                        ${act.description ? `<p style="margin: 0; line-height: 1.8; font-size: ${fontSizes.content}; white-space: pre-line; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${act.description}</p>` : ''}
                                    </div>
                                `;
                                }).join('')}
                            </div>
                        ` : ''}
                        
                        <!-- Sở thích -->
                        ${getSectionVisibility('hobby') && cvData.hobbies && cvData.hobbies.length > 0 ? `
                            <div class="section" style="${templateStyles.section}">
                                <h3 style="margin: 0 0 15px 0; font-size: ${fontSizes.sectionTitle}; font-weight: bold; color: #333; border-bottom: 2px solid ${primaryColor}; padding-bottom: 8px;">SỞ THÍCH</h3>
                                <p style="margin: 0; line-height: 1.8; font-size: ${fontSizes.content}; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${cvData.hobbies.join(', ')}</p>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
            
            // Thêm logo WebCV ở cuối CV (chỉ hiển thị khi in)
            cvHTML += `
                <div class="cv-footer-logo" style="display: none; position: fixed; bottom: 20px; right: 20px; text-align: right; font-size: 12px; color: #666; z-index: 1000;">
                    <div style="font-weight: bold; color: #06b6d4; font-size: 14px;">WebCV</div>
                </div>
            `;
            
            cvElement.innerHTML = cvHTML;
            
            // Add event listeners for inline editing
            if (editMode) {
                addInlineEditListeners();
            }
        }

        // Render CV
        function renderCV() {
            const cvElement = document.getElementById('cv-template');
            if (!cvElement) return;

            const primaryColor = colorMap[currentColor];
            const fontSize = (document.getElementById('font-size')?.value || '11') + 'px';
            const templateStyles = getTemplateStyles(currentTemplate, primaryColor, fontSize);
            
            // Nếu là modern-professional, professional-sidebar, sidebar, hoặc header-content, render layout 2 cột đặc biệt
            if (currentTemplate === 'modern-professional' || currentTemplate === 'modern-pro' || currentTemplate === 'professional-sidebar' || currentTemplate === 'sidebar' || currentTemplate === 'header-content') {
                return renderModernProfessionalCV();
            }
            
            // Nếu là simple, render layout đơn giản
            if (currentTemplate === 'simple') {
                return renderSimpleCV();
            }
            
            // Debug: Check experiences data
            console.log('cvData.experiences:', cvData.experiences);
            console.log('show-experience checked:', document.getElementById('show-experience')?.checked);
            
            // Check if template needs special layout
            const isTwoColumn = currentTemplate === 'two-column' || currentTemplate === 'sidebar';
            
            let cvHTML = `
                <div class="cv-template-wrapper ${editMode ? 'edit-mode' : ''}" data-template="${currentTemplate}" style="${templateStyles.wrapper}">
                    <div class="cv-header" style="${templateStyles.header}">
                        <div style="display: flex; align-items: center; gap: 20px; ${isTwoColumn ? 'flex-direction: column; text-align: center;' : ''}">
                            ${cvData.avatar ? `
                                <div class="cv-avatar" style="width: 120px; height: 120px; border-radius: 50%; overflow: hidden; border: 4px solid ${primaryColor}; flex-shrink: 0; ${editMode ? 'border: 4px dashed rgba(255,255,255,0.5);' : ''}">
                                    <img src="${cvData.avatar}" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                ` : ''}
                            <div style="flex: 1;">
                                <h1 style="margin: 0; font-size: 2em; ${editMode ? 'border: 2px dashed rgba(255,255,255,0.5); padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${cvData.name}</h1>
                                <h2 style="margin: 10px 0 0 0; font-size: 1.2em; font-weight: normal; ${editMode ? 'border: 2px dashed rgba(255,255,255,0.5); padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${cvData.title}</h2>
                                    </div>
                                </div>
                            </div>
                    
                    <div class="cv-content" style="${templateStyles.content}">
                        <div class="contact-info" style="text-align: center; margin-bottom: 20px; padding: 10px; background: #f8f9fa;">
                            ${cvData.email ? `<p style="margin: 5px 0; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>📧 ${cvData.email}</p>` : ''}
                            ${cvData.phone ? `<p style="margin: 5px 0; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>📱 ${cvData.phone}</p>` : ''}
                            ${cvData.location ? `<p style="margin: 5px 0; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>📍 ${cvData.location}</p>` : ''}
                </div>

                        ${cvData.objective && getSectionVisibility('objective') ? `
                            <div class="section" style="${templateStyles.section}">
                                <h3 style="margin: 0 0 15px 0; font-size: 1.1em; color: ${primaryColor}; border-bottom: 2px solid ${primaryColor}; padding-bottom: 5px;">Mục tiêu nghề nghiệp</h3>
                                <div class="item" style="${templateStyles.item}">
                                    <p style="margin: 0; line-height: 1.6; ${editMode ? 'border: 2px dashed #ccc; padding: 5px; min-height: 50px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${cvData.objective}</p>
                    </div>
                </div>
                ` : ''}

                        ${getSectionVisibility('experience') && cvData.experiences && cvData.experiences.length > 0 ? `
                            <div class="section" style="${templateStyles.section}">
                                <h3 style="margin: 0 0 15px 0; font-size: 1.1em; color: ${primaryColor}; border-bottom: 2px solid ${primaryColor}; padding-bottom: 5px;">Kinh nghiệm làm việc</h3>
                        ${cvData.experiences.map(exp => {
                            const dateRange = formatDateRange(exp.start_date, exp.end_date);
                            return `
                                    <div class="item" style="${templateStyles.item}">
                                        <h4 style="margin: 0; color: #333; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${exp.position}</h4>
                                        <p style="margin: 5px 0; font-weight: bold; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${exp.company}${dateRange ? ' | ' + dateRange : ''}</p>
                                        <p style="margin: 5px 0; white-space: pre-line; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${exp.description}</p>
                                    </div>
                                `;
                        }).join('')}
                                </div>
                        ` : ''}
                        
                        ${getSectionVisibility('education') && cvData.educations && cvData.educations.length > 0 ? `
                            <div class="section" style="${templateStyles.section}">
                                <h3 style="margin: 0 0 15px 0; font-size: 1.1em; color: ${primaryColor}; border-bottom: 2px solid ${primaryColor}; padding-bottom: 5px;">Học vấn</h3>
                                ${cvData.educations.map(edu => {
                                    const dateRange = formatDateRange(edu.start_date, edu.end_date);
                                    return `
                                    <div class="item" style="${templateStyles.item}">
                                        <h4 style="margin: 0; color: #333; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${edu.degree}${edu.major ? ' - ' + edu.major : ''}</h4>
                                        <p style="margin: 5px 0; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${edu.school}${dateRange ? ' - ' + dateRange : ''}</p>
                                        ${edu.description ? `<p style="margin: 5px 0; white-space: pre-line; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${edu.description}</p>` : ''}
                                    </div>
                                `;
                                }).join('')}
                            </div>
                        ` : ''}
                        
                        ${getSectionVisibility('skills') && cvData.skills && cvData.skills.length > 0 ? `
                            <div class="section" style="${templateStyles.section}">
                                <h3 style="margin: 0 0 15px 0; font-size: 1.1em; color: ${primaryColor}; border-bottom: 2px solid ${primaryColor}; padding-bottom: 5px;">Kỹ năng</h3>
                                <p style="${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${cvData.skills.join(', ')}</p>
                            </div>
                        ` : ''}

                        ${getSectionVisibility('activity') && cvData.activities && cvData.activities.length > 0 ? `
                            <div class="section" style="${templateStyles.section}">
                                <h3 style="margin: 0 0 15px 0; font-size: 1.1em; color: ${primaryColor}; border-bottom: 2px solid ${primaryColor}; padding-bottom: 5px;">Hoạt động</h3>
                                ${cvData.activities.map(act => {
                                    const dateRange = formatDateRange(act.start_date, act.end_date);
                                    return `
                                    <div class="item" style="${templateStyles.item}">
                                        <h4 style="margin: 0; color: #333; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${act.role} - ${act.organization_name}</h4>
                                        ${dateRange ? `<p style="margin: 5px 0; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${dateRange}</p>` : ''}
                                        ${act.description ? `<p style="margin: 5px 0; white-space: pre-line; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${act.description}</p>` : ''}
                                    </div>
                                `;
                                }).join('')}
                            </div>
                        ` : ''}

                        ${getSectionVisibility('certification') && cvData.certifications && cvData.certifications.length > 0 ? `
                            <div class="section" style="${templateStyles.section}">
                                <h3 style="margin: 0 0 15px 0; font-size: 1.1em; color: ${primaryColor}; border-bottom: 2px solid ${primaryColor}; padding-bottom: 5px;">Chứng chỉ</h3>
                                ${cvData.certifications.map(cert => {
                                    const formattedDate = cert.date_received ? formatDate(cert.date_received) : '';
                                    return `
                                    <div class="item" style="${templateStyles.item}">
                                        <h4 style="margin: 0; color: #333; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${cert.cert_name}</h4>
                                        <p style="margin: 5px 0; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${cert.organization}${formattedDate ? ' | ' + formattedDate : ''}</p>
                                        ${cert.description ? `<p style="margin: 5px 0; white-space: pre-line; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${cert.description}</p>` : ''}
                                    </div>
                                `;
                                }).join('')}
                            </div>
                        ` : ''}

                        ${getSectionVisibility('award') && cvData.awards && cvData.awards.length > 0 ? `
                            <div class="section" style="${templateStyles.section}">
                                <h3 style="margin: 0 0 15px 0; font-size: 1.1em; color: ${primaryColor}; border-bottom: 2px solid ${primaryColor}; padding-bottom: 5px;">Giải thưởng</h3>
                                ${cvData.awards.map(award => {
                                    const formattedDate = award.date_received ? formatDate(award.date_received) : '';
                                    return `
                                    <div class="item" style="${templateStyles.item}">
                                        <h4 style="margin: 0; color: #333; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${award.award_name}</h4>
                                        <p style="margin: 5px 0; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${award.organization}${formattedDate ? ' | ' + formattedDate : ''}</p>
                                        ${award.description ? `<p style="margin: 5px 0; white-space: pre-line; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${award.description}</p>` : ''}
                                    </div>
                                `;
                                }).join('')}
                            </div>
                        ` : ''}

                        ${getSectionVisibility('project') && cvData.projects && cvData.projects.length > 0 ? `
                            <div class="section" style="${templateStyles.section}">
                                <h3 style="margin: 0 0 15px 0; font-size: 1.1em; color: ${primaryColor}; border-bottom: 2px solid ${primaryColor}; padding-bottom: 5px;">Dự án</h3>
                                ${cvData.projects.map(proj => {
                                    const dateRange = formatDateRange(proj.start_date, proj.end_date);
                                    return `
                                    <div class="item" style="${templateStyles.item}">
                                        <h4 style="margin: 0; color: #333; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${proj.project_name}</h4>
                                        <p style="margin: 5px 0; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${proj.role}${dateRange ? ' | ' + dateRange : ''}</p>
                                        ${proj.technologies ? `<p style="margin: 5px 0; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}><strong>Công nghệ:</strong> ${proj.technologies}</p>` : ''}
                                        ${proj.description ? `<p style="margin: 5px 0; white-space: pre-line; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${proj.description}</p>` : ''}
                                    </div>
                                `;
                                }).join('')}
                            </div>
                        ` : ''}

                        ${getSectionVisibility('reference') && cvData.references && cvData.references.length > 0 ? `
                            <div class="section" style="${templateStyles.section}">
                                <h3 style="margin: 0 0 15px 0; font-size: 1.1em; color: ${primaryColor}; border-bottom: 2px solid ${primaryColor}; padding-bottom: 5px;">Người tham khảo</h3>
                                ${cvData.references.map(ref => `
                                    <div class="item" style="${templateStyles.item}">
                                        <h4 style="margin: 0; color: #333; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${ref.name}</h4>
                                        <p style="margin: 5px 0; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${ref.relationship}</p>
                                        ${ref.contact_info ? `<p style="margin: 5px 0; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${ref.contact_info}</p>` : ''}
                                    </div>
                                `).join('')}
                            </div>
                        ` : ''}

                        ${getSectionVisibility('hobby') && cvData.hobbies && cvData.hobbies.length > 0 ? `
                            <div class="section" style="${templateStyles.section}">
                                <h3 style="margin: 0 0 15px 0; font-size: 1.1em; color: ${primaryColor}; border-bottom: 2px solid ${primaryColor}; padding-bottom: 5px;">Sở thích</h3>
                                <p style="${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${cvData.hobbies.join(', ')}</p>
                            </div>
                        ` : ''}

                        ${getSectionVisibility('extrainfo') && cvData.extrainfos && cvData.extrainfos.length > 0 ? `
                            <div class="section" style="${templateStyles.section}">
                                <h3 style="margin: 0 0 15px 0; font-size: 1.1em; color: ${primaryColor}; border-bottom: 2px solid ${primaryColor}; padding-bottom: 5px;">Thông tin thêm</h3>
                                ${cvData.extrainfos.map(info => `
                                    <p style="margin: 5px 0; white-space: pre-line; ${editMode ? 'border: 2px dashed #ccc; padding: 5px;' : ''}" ${editMode ? 'contenteditable="true"' : ''}>${info}</p>
                                `).join('')}
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
            
            // Thêm logo WebCV ở cuối CV (chỉ hiển thị khi in)
            cvHTML += `
                <div class="cv-footer-logo" style="display: none; position: fixed; bottom: 20px; right: 20px; text-align: right; font-size: 12px; color: #666; z-index: 1000;">
                    <div style="font-weight: bold; color: #06b6d4; font-size: 14px;">WebCV</div>
                </div>
            `;
            
            cvElement.innerHTML = cvHTML;
            
            // Add event listeners for inline editing
            if (editMode) {
                addInlineEditListeners();
            }
        }

        // Add inline edit listeners
        function addInlineEditListeners() {
            document.querySelectorAll('[contenteditable="true"]').forEach(element => {
                element.addEventListener('blur', function() {
                    updateCVData(this);
                });
            });
        }

        // Update CV data from inline editing
        function updateCVData(element) {
            const text = element.textContent.trim();
            const tagName = element.tagName.toLowerCase();
            
            if (tagName === 'h1') {
                cvData.name = text;
            } else if (tagName === 'h2') {
                cvData.title = text;
            } else if (element.textContent.includes('📧')) {
                cvData.email = text.replace('📧 ', '');
            } else if (element.textContent.includes('📱')) {
                cvData.phone = text.replace('📱 ', '');
            } else if (element.textContent.includes('📍')) {
                cvData.location = text.replace('📍 ', '');
            } else if (element.closest('.section') && element.textContent.includes('Mục tiêu nghề nghiệp')) {
                // This is the objective section
                const nextP = element.nextElementSibling;
                if (nextP) {
                    cvData.objective = nextP.textContent.trim();
                }
            }
            
            // Always save to current user's data
            const user = JSON.parse(localStorage.getItem('currentUser') || '{}');
            if (user.name && user.name !== 'undefined') {
                // Update user data with CV changes
                user.name = cvData.name;
                user.email = cvData.email;
                user.phone = cvData.phone;
                user.location = cvData.location;
                user.title = cvData.title;
                user.objective = cvData.objective;
                user.experiences = cvData.experiences;
                user.educations = cvData.educations;
                user.skills = cvData.skills;
                user.certificates = cvData.certificates;
                user.languages = cvData.languages;
                
                localStorage.setItem('currentUser', JSON.stringify(user));
                console.log('Updated user data for:', user.name);
                
                // Save to database
                if (typeof window.saveToDatabase === 'function') {
                    window.saveToDatabase(user);
                }
            } else {
                console.log('No user logged in, changes not saved to profile');
            }
            
            saveCVSettings();
        }

        // Save CV to Database via API
        // Save CV to database - Make global
        window.saveCV = async function() {
            try {
                // Kiểm tra token trước
                const token = getAuthToken();
                if (!token) {
                    alert('Vui lòng đăng nhập để lưu CV. Đang chuyển đến trang đăng nhập...');
                    window.location.href = '/login';
                    return;
                }
                
                const formData = collectFormData();
                console.log('Đang lưu CV:', formData);
                
                const headers = getAuthHeaders();
                const url = currentResumeId 
                    ? `${API_BASE_URL}/resumes/${currentResumeId}` 
                    : `${API_BASE_URL}/resumes`;
                const method = currentResumeId ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method: method,
                    headers: headers,
                    credentials: 'include',
                    body: JSON.stringify(formData)
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    alert('Lưu CV thành công!');
                    if (result.data && result.data.id) {
                        currentResumeId = result.data.id;
                        localStorage.setItem('current_resume_id', currentResumeId);
                        // Cập nhật URL để có id
                        const newUrl = new URL(window.location);
                        newUrl.searchParams.set('id', currentResumeId);
                        window.history.replaceState({}, '', newUrl);
                        // Cập nhật cvData từ response
                        updateCVDataFromResponse(result.data);
                        renderCV();
                    }
                } else {
                    // Xử lý lỗi 401 Unauthorized đặc biệt
                    if (response.status === 401) {
                        alert('Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.');
                        window.location.href = '/login';
                        return;
                    }
                    
                    // Xử lý lỗi 403 Forbidden: Nếu đang cố cập nhật CV nhưng không có quyền, thử tạo mới
                    if (response.status === 403 && method === 'PUT' && currentResumeId) {
                        console.warn('Không có quyền cập nhật CV này. Đang thử tạo CV mới...');
                        // Reset currentResumeId và thử lại với POST
                        currentResumeId = null;
                        localStorage.removeItem('current_resume_id');
                        // Thử lại với POST
                        const newResponse = await fetch(`${API_BASE_URL}/resumes`, {
                            method: 'POST',
                            headers: headers,
                            credentials: 'include',
                            body: JSON.stringify(formData)
                        });
                        
                        const newResult = await newResponse.json();
                        if (newResponse.ok && newResult.success) {
                            alert('Đã tạo CV mới thành công!');
                            if (newResult.data && newResult.data.id) {
                                currentResumeId = newResult.data.id;
                                localStorage.setItem('current_resume_id', currentResumeId);
                                // Cập nhật URL
                                const newUrl = new URL(window.location);
                                newUrl.searchParams.set('id', currentResumeId);
                                window.history.replaceState({}, '', newUrl);
                                updateCVDataFromResponse(newResult.data);
                                renderCV();
                            }
                            return;
                        } else {
                            alert('Lỗi: ' + (newResult.message || 'Không thể tạo CV mới'));
                            console.error('Create CV error:', newResult);
                            return;
                        }
                    }
                    
                    alert('Lỗi: ' + (result.message || 'Không thể lưu CV'));
                    console.error('Save CV error:', result);
                }
            } catch (error) {
                console.error('Error saving CV:', error);
                alert('Có lỗi xảy ra khi lưu CV. Vui lòng thử lại.');
            }
        }

        // Handle form submit
        async function handleSubmitCV(event) {
            event.preventDefault();
            await saveCV();
        }

        // Collect form data
        function collectFormData() {
            // Lưu template settings vào personal_info dưới dạng JSON
            const templateSettings = {
                template: currentTemplate,
                color: currentColor,
                font: currentFont,
                fontSize: document.getElementById('font-size')?.value || '14'
            };
            
            const data = {
                title: document.getElementById('cv-title')?.value || null,
                skills_summary: document.getElementById('cv-objective')?.value || null,
                personal_info: JSON.stringify(templateSettings), // Lưu template settings
                photo: null
            };

            // Header
            const headerFullName = document.getElementById('header-full-name')?.value;
            const headerBirthday = document.getElementById('header-birthday')?.value;
            const headerGender = document.getElementById('header-gender')?.value;
            const headerPhone = document.getElementById('header-phone')?.value;
            const headerEmail = document.getElementById('header-email')?.value;
            let headerWebsite = document.getElementById('header-website')?.value?.trim();
            const headerAddress = document.getElementById('header-address')?.value;
            const headerAvatar = document.getElementById('header-avatar')?.value;

            // ✅ FIX: Format website URL và chỉ gửi nếu có giá trị hợp lệ
            if (headerWebsite && headerWebsite !== '') {
                // Nếu không có protocol, thêm http://
                if (!headerWebsite.match(/^https?:\/\//i)) {
                    headerWebsite = 'http://' + headerWebsite;
                }
            } else {
                // ✅ FIX: Set thành null nếu rỗng để không gửi field này
                headerWebsite = null;
            }

            if (headerFullName || headerBirthday || headerGender || headerPhone || headerEmail || headerWebsite || headerAddress || headerAvatar) {
                data.header = {
                    Full_name: headerFullName || null,
                    BirthDay: headerBirthday || null,
                    gender: headerGender || null,
                    Phone: headerPhone || null,
                    Email: headerEmail || null,
                    // ✅ FIX: Chỉ gửi Website nếu có giá trị (không null và không rỗng)
                    ...(headerWebsite && typeof headerWebsite === 'string' && headerWebsite.trim() !== '' ? { Website: headerWebsite } : {}),
                    address: headerAddress || null,
                    avatar: headerAvatar || null
                };
            }

            // Educations
            const educations = collectSectionItems('education');
            if (educations.length > 0) data.educations = educations;

            // Experiences
            const experiences = collectSectionItems('experience');
            if (experiences.length > 0) data.experiences = experiences;

            // Activities
            const activities = collectSectionItems('activity');
            if (activities.length > 0) data.activities = activities;

            // Certifications
            const certifications = collectSectionItems('certification');
            if (certifications.length > 0) data.certifications = certifications;

            // Awards
            const awards = collectSectionItems('award');
            if (awards.length > 0) data.awards = awards;

            // Skills
            const skills = collectSectionItems('skill');
            if (skills.length > 0) data.skills = skills;

            // References
            const references = collectSectionItems('reference');
            if (references.length > 0) data.references = references;

            // Projects
            const projects = collectSectionItems('project');
            if (projects.length > 0) data.projects = projects;

            // Hobbies
            const hobbies = collectSectionItems('hobby');
            if (hobbies.length > 0) data.hobbies = hobbies;

            // Extrainfos
            const extrainfos = collectSectionItems('extrainfo');
            if (extrainfos.length > 0) data.extrainfos = extrainfos;

            return data;
        }


        // Update CV data from API response
        function updateCVDataFromResponse(resumeData) {
            if (resumeData.header) {
                const h = resumeData.header;
                cvData.name = h.Full_name || cvData.name;
                cvData.email = h.Email || cvData.email;
                cvData.phone = h.Phone || cvData.phone;
                cvData.location = h.address || cvData.location;
                cvData.avatar = h.avatar || cvData.avatar;
            }
            if (resumeData.skills_summary) cvData.objective = resumeData.skills_summary;
            if (resumeData.title) cvData.title = resumeData.title;
            
            // Load template settings từ personal_info
            if (resumeData.personal_info) {
                try {
                    const settings = JSON.parse(resumeData.personal_info);
                    if (settings.template) currentTemplate = settings.template;
                    if (settings.color) currentColor = settings.color;
                    if (settings.font) currentFont = settings.font;
                    if (settings.fontSize) {
                        const fontSizeInput = document.getElementById('font-size');
                        if (fontSizeInput) fontSizeInput.value = settings.fontSize;
                    }
                } catch (e) {
                    console.warn('Không thể parse personal_info:', e);
                }
            }
            
            // Educations
            if (resumeData.educations) {
                cvData.educations = resumeData.educations.map(e => ({
                    degree: e.degree || '',
                    school: e.school_name || '',
                    major: e.major || '',
                    year: e.end_date ? new Date(e.end_date).getFullYear() : '',
                    start_date: e.start_date || '',
                    end_date: e.end_date || '',
                    description: e.description || ''
                }));
            }
            
            // Experiences
            if (resumeData.experiences) {
                cvData.experiences = resumeData.experiences.map(e => ({
                    position: e.position || '',
                    company: e.company_name || '',
                    duration: `${e.start_date || ''} - ${e.end_date || 'Hiện tại'}`,
                    start_date: e.start_date || '',
                    end_date: e.end_date || '',
                    description: e.description || ''
                }));
            }
            
            // Skills
            if (resumeData.skills) {
                cvData.skills = resumeData.skills.map(s => s.skill_name || s);
            }
            
            // Projects
            if (resumeData.projects) {
                cvData.projects = resumeData.projects.map(p => ({
                    project_name: p.project_name || '',
                    description: p.description || '',
                    start_date: p.start_date || '',
                    end_date: p.end_date || '',
                    technologies: p.technologies || '',
                    role: p.role || ''
                }));
            }
            
            // Certifications
            if (resumeData.certifications) {
                cvData.certifications = resumeData.certifications.map(c => ({
                    cert_name: c.cert_name || '',
                    organization: c.organization || '',
                    date_received: c.date_received || '',
                    description: c.description || ''
                }));
            }
            
            // Awards
            if (resumeData.awards) {
                cvData.awards = resumeData.awards.map(a => ({
                    award_name: a.award_name || '',
                    organization: a.organization || '',
                    date_received: a.date_received || '',
                    description: a.description || ''
                }));
            }
            
            // References
            if (resumeData.references) {
                cvData.references = resumeData.references.map(r => ({
                    name: r.name || '',
                    relationship: r.relationship || '',
                    contact: r.contact || ''
                }));
            }
            
            // Activities
            if (resumeData.activities) {
                cvData.activities = resumeData.activities.map(a => ({
                    organization: a.organization_name || '',
                    role: a.role || '',
                    start_date: a.start_date || '',
                    end_date: a.end_date || '',
                    description: a.description || ''
                }));
            }
            
            // Hobbies
            if (resumeData.hobbies) {
                cvData.hobbies = resumeData.hobbies.map(h => h.hobby_name || h.description || h);
            }
            
            // Extrainfos
            if (resumeData.extrainfos) {
                cvData.extrainfos = resumeData.extrainfos.map(e => ({
                    title: e.title || '',
                    content: e.content || ''
                }));
            }
        }

        // Save CV settings
        function saveCVSettings() {
            const settings = {
                template: currentTemplate,
                color: currentColor,
                font: currentFont,
                fontSize: document.getElementById('font-size')?.value || '11',
                editMode: editMode
            };
            
            localStorage.setItem('cvSettings', JSON.stringify(settings));
        }

        // Load CV settings
        function loadCVSettings() {
            const saved = localStorage.getItem('cvSettings');
            if (saved) {
                const settings = JSON.parse(saved);
                
                if (settings.template) {
                    currentTemplate = settings.template;
                document.querySelectorAll('.template-item').forEach(item => {
                        item.classList.remove('active');
                    });
                    const templateButton = document.querySelector(`[onclick="selectTemplate('${settings.template}')"]`);
                    if (templateButton) {
                        templateButton.classList.add('active');
                    } else {
                        // Nếu template không tồn tại trong UI (đã bị xóa), fallback về simple
                        const simpleButton = document.querySelector(`[onclick="selectTemplate('simple')"]`);
                        if (simpleButton) {
                            simpleButton.classList.add('active');
                            currentTemplate = 'simple';
                        }
                    }
                }
                
                if (settings.color) {
                    currentColor = settings.color;
                    document.querySelectorAll('.color-option').forEach(item => {
                        item.classList.remove('active');
                    });
                    document.querySelector(`[onclick="selectColor('${settings.color}')"]`)?.classList.add('active');
                }
                
                if (settings.font) {
                    currentFont = settings.font;
                    document.querySelectorAll('.font-option').forEach(item => {
                        item.classList.remove('active');
                    });
                    document.querySelector(`[onclick="selectFont('${settings.font}')"]`)?.classList.add('active');
                }
                
                if (settings.fontSize) {
                    document.getElementById('font-size').value = settings.fontSize;
                }
                
                if (settings.editMode !== undefined) {
                    editMode = settings.editMode;
                }
                
                renderCV();
            }
        }

        // Toggle form tabs - Make global
        window.showFormTab = function(tabName) {
            document.querySelectorAll('.form-tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            if (tabName === 'input') {
                document.getElementById('form-input-tab').classList.add('active');
                document.getElementById('tab-input').classList.add('active');
            } else if (tabName === 'customize') {
                document.getElementById('form-customize-tab').classList.add('active');
                document.getElementById('tab-customize').classList.add('active');
            }
        }

        // Make add functions global
        window.addEducation = function() {
            const container = document.getElementById('educations-container');
            const index = container.children.length;
            const html = `
                <div class="education-item form-item" data-index="${index}">
                    <div class="item-header">
                        <h5>Học vấn ${index + 1}</h5>
                        <button type="button" class="btn-remove" onclick="removeItem(this, 'education')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="form-group">
                        <label>Tên trường học</label>
                        <input type="text" id="education-${index}-school_name" class="form-control" placeholder="Trường Đại học...">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Bằng cấp</label>
                            <input type="text" id="education-${index}-degree" class="form-control" placeholder="Cử nhân, Thạc sĩ...">
                        </div>
                        <div class="form-group">
                            <label>Chuyên ngành</label>
                            <input type="text" id="education-${index}-major" class="form-control" placeholder="Công nghệ thông tin...">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Ngày bắt đầu</label>
                            <input type="date" id="education-${index}-start_date" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Ngày kết thúc</label>
                            <input type="date" id="education-${index}-end_date" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Mô tả</label>
                        <textarea id="education-${index}-description" rows="3" class="form-control" placeholder="Mô tả quá trình học tập hoặc thành tích..."></textarea>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        }

        window.addExperience = function() {
            const container = document.getElementById('experiences-container');
            const index = container.children.length;
            const html = `
                <div class="experience-item form-item" data-index="${index}">
                    <div class="item-header">
                        <h5>Kinh nghiệm ${index + 1}</h5>
                        <button type="button" class="btn-remove" onclick="removeItem(this, 'experience')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="form-group">
                        <label>Tên công ty</label>
                        <input type="text" id="experience-${index}-company_name" class="form-control" placeholder="Công ty TNHH...">
                    </div>
                    <div class="form-group">
                        <label>Vị trí</label>
                        <input type="text" id="experience-${index}-position" class="form-control" placeholder="Developer, Manager...">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Ngày bắt đầu</label>
                            <input type="date" id="experience-${index}-start_date" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Ngày kết thúc</label>
                            <input type="date" id="experience-${index}-end_date" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Mô tả</label>
                        <textarea id="experience-${index}-description" rows="4" class="form-control" placeholder="Mô tả kinh nghiệm làm việc của bạn..."></textarea>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        }

        window.addActivity = function() {
            const container = document.getElementById('activities-container');
            const index = container.children.length;
            const html = `
                <div class="activity-item form-item" data-index="${index}">
                    <div class="item-header">
                        <h5>Hoạt động ${index + 1}</h5>
                        <button type="button" class="btn-remove" onclick="removeItem(this, 'activity')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="form-group">
                        <label>Tên tổ chức</label>
                        <input type="text" id="activity-${index}-organization_name" class="form-control" placeholder="Câu lạc bộ...">
                    </div>
                    <div class="form-group">
                        <label>Vị trí của bạn</label>
                        <input type="text" id="activity-${index}-role" class="form-control" placeholder="Thành viên, Leader...">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Ngày bắt đầu</label>
                            <input type="date" id="activity-${index}-start_date" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Ngày kết thúc</label>
                            <input type="date" id="activity-${index}-end_date" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Mô tả</label>
                        <textarea id="activity-${index}-description" rows="3" class="form-control" placeholder="Mô tả hoạt động..."></textarea>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        }

        window.addCertification = function() {
            const container = document.getElementById('certifications-container');
            const index = container.children.length;
            const html = `
                <div class="certification-item form-item" data-index="${index}">
                    <div class="item-header">
                        <h5>Chứng chỉ ${index + 1}</h5>
                        <button type="button" class="btn-remove" onclick="removeItem(this, 'certification')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="form-group">
                        <label>Tên chứng chỉ</label>
                        <input type="text" id="certification-${index}-cert_name" class="form-control" placeholder="Chứng chỉ...">
                    </div>
                    <div class="form-group">
                        <label>Tổ chức cấp</label>
                        <input type="text" id="certification-${index}-organization" class="form-control" placeholder="PMI, Microsoft...">
                    </div>
                    <div class="form-group">
                        <label>Ngày nhận</label>
                        <input type="date" id="certification-${index}-date_received" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Mô tả</label>
                        <textarea id="certification-${index}-description" rows="2" class="form-control" placeholder="Mô tả chứng chỉ..."></textarea>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        }

        window.addAward = function() {
            const container = document.getElementById('awards-container');
            const index = container.children.length;
            const html = `
                <div class="award-item form-item" data-index="${index}">
                    <div class="item-header">
                        <h5>Giải thưởng ${index + 1}</h5>
                        <button type="button" class="btn-remove" onclick="removeItem(this, 'award')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="form-group">
                        <label>Tên giải thưởng</label>
                        <input type="text" id="award-${index}-award_name" class="form-control" placeholder="Giải Nhì...">
                    </div>
                    <div class="form-group">
                        <label>Tổ chức</label>
                        <input type="text" id="award-${index}-organization" class="form-control" placeholder="Trường Đại học...">
                    </div>
                    <div class="form-group">
                        <label>Ngày nhận</label>
                        <input type="date" id="award-${index}-date_received" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Mô tả</label>
                        <textarea id="award-${index}-description" rows="2" class="form-control" placeholder="Mô tả giải thưởng..."></textarea>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        }

        window.addSkill = function() {
            const container = document.getElementById('skills-container');
            const index = container.children.length;
            const html = `
                <div class="skill-item form-item" data-index="${index}">
                    <div class="item-header">
                        <h5>Kỹ năng ${index + 1}</h5>
                        <button type="button" class="btn-remove" onclick="removeItem(this, 'skill')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="form-group">
                        <label>Tên kỹ năng</label>
                        <input type="text" id="skill-${index}-skill_name" class="form-control" placeholder="JavaScript, Python...">
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        }

        window.addReference = function() {
            const container = document.getElementById('references-container');
            const index = container.children.length;
            const html = `
                <div class="reference-item form-item" data-index="${index}">
                    <div class="item-header">
                        <h5>Người tham khảo ${index + 1}</h5>
                        <button type="button" class="btn-remove" onclick="removeItem(this, 'reference')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="form-group">
                        <label>Họ và tên</label>
                        <input type="text" id="reference-${index}-name" class="form-control" placeholder="Nguyễn Văn A">
                    </div>
                    <div class="form-group">
                        <label>Mối quan hệ</label>
                        <input type="text" id="reference-${index}-relationship" class="form-control" placeholder="Cựu Giám đốc, Mentor...">
                    </div>
                    <div class="form-group">
                        <label>Thông tin liên hệ</label>
                        <input type="text" id="reference-${index}-contact_info" class="form-control" placeholder="Email: ... Phone: ...">
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        }

        window.addProject = function() {
            const container = document.getElementById('projects-container');
            const index = container.children.length;
            const html = `
                <div class="project-item form-item" data-index="${index}">
                    <div class="item-header">
                        <h5>Dự án ${index + 1}</h5>
                        <button type="button" class="btn-remove" onclick="removeItem(this, 'project')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="form-group">
                        <label>Tên dự án</label>
                        <input type="text" id="project-${index}-project_name" class="form-control" placeholder="Website tuyển dụng...">
                    </div>
                    <div class="form-group">
                        <label>Vai trò</label>
                        <input type="text" id="project-${index}-role" class="form-control" placeholder="Frontend Developer, Leader...">
                    </div>
                    <div class="form-group">
                        <label>Công nghệ sử dụng</label>
                        <input type="text" id="project-${index}-technologies" class="form-control" placeholder="Laravel, Vue.js, MySQL...">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Ngày bắt đầu</label>
                            <input type="date" id="project-${index}-start_date" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Ngày kết thúc</label>
                            <input type="date" id="project-${index}-end_date" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Mô tả</label>
                        <textarea id="project-${index}-description" rows="3" class="form-control" placeholder="Mô tả dự án..."></textarea>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        }

        window.addHobby = function() {
            const container = document.getElementById('hobbies-container');
            const index = container.children.length;
            const html = `
                <div class="hobby-item form-item" data-index="${index}">
                    <div class="item-header">
                        <h5>Sở thích ${index + 1}</h5>
                        <button type="button" class="btn-remove" onclick="removeItem(this, 'hobby')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="form-group">
                        <label>Mô tả sở thích</label>
                        <textarea id="hobby-${index}-description" rows="2" class="form-control" placeholder="Đọc sách, Chơi thể thao, Du lịch..."></textarea>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        }

        window.addExtrainfo = function() {
            const container = document.getElementById('extrainfos-container');
            const index = container.children.length;
            const html = `
                <div class="extrainfo-item form-item" data-index="${index}">
                    <div class="item-header">
                        <h5>Thông tin thêm ${index + 1}</h5>
                        <button type="button" class="btn-remove" onclick="removeItem(this, 'extrainfo')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="form-group">
                        <label>Nội dung</label>
                        <textarea id="extrainfo-${index}-content" rows="3" class="form-control" placeholder="Thông tin bổ sung..."></textarea>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        }

        // Remove Item - Make it global
        window.removeItem = function(button, sectionType) {
            const item = button.closest('.form-item');
            if (item) {
                // Lưu reference của container TRƯỚC KHI xóa item
                const container = item.parentElement;
                if (!container) {
                    console.error('Item không có container cha');
                    return;
                }
                
                // Xóa item
                item.remove();
                
                // Đánh số lại các items còn lại
                container.querySelectorAll('.form-item').forEach((item, index) => {
                    const title = item.querySelector('h5');
                    if (title) {
                        title.textContent = getSectionTitle(sectionType) + ' ' + (index + 1);
                    }
                });
            }
        }

        // Get section title
        function getSectionTitle(sectionType) {
            const titles = {
                'education': 'Học vấn',
                'experience': 'Kinh nghiệm',
                'activity': 'Hoạt động',
                'certification': 'Chứng chỉ',
                'award': 'Giải thưởng',
                'skill': 'Kỹ năng',
                'reference': 'Người tham khảo',
                'project': 'Dự án',
                'hobby': 'Sở thích',
                'extrainfo': 'Thông tin thêm'
            };
            return titles[sectionType] || sectionType;
        }

        // Collect section items - Updated to map field names correctly
        function collectSectionItems(sectionType) {
            const container = document.getElementById(`${sectionType}s-container`);
            if (!container) return [];

            const items = [];
            const itemElements = container.querySelectorAll(`.${sectionType}-item`);

            itemElements.forEach(itemEl => {
                const item = {};
                const inputs = itemEl.querySelectorAll('input, textarea, select');
                
                inputs.forEach(input => {
                    // Extract field name from id: e.g., "education-0-school_name" -> "school_name"
                    const idParts = input.id.split('-');
                    if (idParts.length >= 3) {
                        const fieldName = idParts.slice(2).join('_'); // Handle fields with underscores
                        if (input.value && input.value.trim()) {
                            item[fieldName] = input.value.trim();
                        }
                    }
                });

                // Only add item if it has at least one field
                if (Object.keys(item).length > 0) {
                    items.push(item);
                }
            });

            return items;
        }

        // Preview CV from form - Make global
        window.previewCV = function() {
            // Collect form data and update cvData
            const formData = collectFormData();
            
            // Update cvData from form
            if (formData.header) {
                cvData.name = formData.header.Full_name || cvData.name;
                cvData.email = formData.header.Email || cvData.email;
                cvData.phone = formData.header.Phone || cvData.phone;
                cvData.location = formData.header.address || cvData.location;
                cvData.avatar = formData.header.avatar || cvData.avatar;
            }
            
            if (formData.skills_summary) {
                cvData.objective = formData.skills_summary;
            }
            
            if (formData.title) {
                cvData.title = formData.title;
            }
            
            // Map educations
            if (formData.educations && formData.educations.length > 0) {
                cvData.educations = formData.educations.map(edu => ({
                    degree: edu.degree || '',
                    school: edu.school_name || '',
                    major: edu.major || '',
                    year: edu.end_date ? new Date(edu.end_date).getFullYear() : (edu.start_date ? new Date(edu.start_date).getFullYear() : ''),
                    start_date: edu.start_date || '',
                    end_date: edu.end_date || '',
                    description: edu.description || ''
                }));
            } else {
                cvData.educations = [];
            }
            
            // Map experiences
            if (formData.experiences && formData.experiences.length > 0) {
                cvData.experiences = formData.experiences.map(exp => ({
                    position: exp.position || '',
                    company: exp.company_name || '',
                    start_date: exp.start_date || '',
                    end_date: exp.end_date || '',
                    duration: `${exp.start_date || ''} - ${exp.end_date || 'Hiện tại'}`,
                    description: exp.description || ''
                }));
            } else {
                cvData.experiences = [];
            }
            
            // Map activities
            if (formData.activities && formData.activities.length > 0) {
                cvData.activities = formData.activities.map(act => ({
                    organization_name: act.organization_name || '',
                    role: act.role || '',
                    start_date: act.start_date || '',
                    end_date: act.end_date || '',
                    description: act.description || ''
                }));
            } else {
                cvData.activities = [];
            }
            
            // Map certifications
            if (formData.certifications && formData.certifications.length > 0) {
                cvData.certifications = formData.certifications.map(cert => ({
                    cert_name: cert.cert_name || '',
                    organization: cert.organization || '',
                    date_received: cert.date_received || '',
                    description: cert.description || ''
                }));
            } else {
                cvData.certifications = [];
            }
            
            // Map awards
            if (formData.awards && formData.awards.length > 0) {
                cvData.awards = formData.awards.map(award => ({
                    award_name: award.award_name || '',
                    organization: award.organization || '',
                    date_received: award.date_received || '',
                    description: award.description || ''
                }));
            } else {
                cvData.awards = [];
            }
            
            // Map skills
            if (formData.skills && formData.skills.length > 0) {
                cvData.skills = formData.skills.map(skill => skill.skill_name || '').filter(s => s);
            } else {
                cvData.skills = [];
            }
            
            // Map references
            if (formData.references && formData.references.length > 0) {
                cvData.references = formData.references.map(ref => ({
                    name: ref.name || '',
                    relationship: ref.relationship || '',
                    contact_info: ref.contact_info || ''
                }));
            } else {
                cvData.references = [];
            }
            
            // Map projects
            if (formData.projects && formData.projects.length > 0) {
                cvData.projects = formData.projects.map(proj => ({
                    project_name: proj.project_name || '',
                    role: proj.role || '',
                    technologies: proj.technologies || '',
                    start_date: proj.start_date || '',
                    end_date: proj.end_date || '',
                    description: proj.description || ''
                }));
            } else {
                cvData.projects = [];
            }
            
            // Map hobbies
            if (formData.hobbies && formData.hobbies.length > 0) {
                cvData.hobbies = formData.hobbies.map(hobby => hobby.description || '').filter(h => h);
            } else {
                cvData.hobbies = [];
            }
            
            // Map extrainfos
            if (formData.extrainfos && formData.extrainfos.length > 0) {
                cvData.extrainfos = formData.extrainfos.map(info => info.content || '').filter(i => i);
            } else {
                cvData.extrainfos = [];
            }
            
            console.log('Updated cvData:', cvData);
            
            // Render CV
            renderCV();
        }

        // Initialize when DOM is loaded
        // Add event listener for avatar upload when page loads
        function initAvatarUpload() {
            const avatarInput = document.getElementById('header-avatar-input');
            if (avatarInput) {
                avatarInput.addEventListener('change', handleHeaderAvatarUpload);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Initialize avatar upload handler
            initAvatarUpload();
            console.log('CV Builder initialized');
            
            // Kiểm tra URL parameter trước (ưu tiên cao nhất)
            const urlParams = new URLSearchParams(window.location.search);
            const urlResumeId = urlParams.get('id');
            
            if (urlResumeId) {
                // Nếu có id trong URL, đây là chế độ chỉnh sửa
                currentResumeId = urlResumeId;
                localStorage.setItem('current_resume_id', currentResumeId);
                loadResumeFromAPI(urlResumeId);
            } else {
                // Nếu không có id trong URL, đây là chế độ tạo mới
                // Reset currentResumeId và xóa localStorage
                currentResumeId = null;
                localStorage.removeItem('current_resume_id');
            }
            
            initializeSectionVisibility();
            loadUserData();
            loadCVSettings();
            renderCV();
            
            // Sync form with cvData on load
            syncFormWithCVData();
        });
        
        // Load resume from API
        async function loadResumeFromAPI(resumeId) {
            try {
                const headers = getAuthHeaders();
                const response = await fetch(`${API_BASE_URL}/resumes/${resumeId}`, {
                    method: 'GET',
                    headers: headers,
                    credentials: 'include'
                });
                
                if (response.ok) {
                    const result = await response.json();
                    if (result.success && result.data) {
                        // Sync form với dữ liệu từ API (bao gồm template settings)
                        syncFormWithResumeData(result.data);
                        // Update cvData từ response
                        updateCVDataFromResponse(result.data);
                        // Render CV với template đã load
                        renderCV();
                    }
                } else if (response.status === 403) {
                    // Không có quyền xem CV này, reset về chế độ tạo mới
                    console.warn('Không có quyền xem CV này. Chuyển sang chế độ tạo mới.');
                    currentResumeId = null;
                    localStorage.removeItem('current_resume_id');
                    // Xóa id khỏi URL
                    const newUrl = new URL(window.location);
                    newUrl.searchParams.delete('id');
                    window.history.replaceState({}, '', newUrl);
                    alert('Bạn không có quyền xem CV này. Đã chuyển sang chế độ tạo CV mới.');
                } else if (response.status === 404) {
                    // CV không tồn tại, reset về chế độ tạo mới
                    console.warn('CV không tồn tại. Chuyển sang chế độ tạo mới.');
                    currentResumeId = null;
                    localStorage.removeItem('current_resume_id');
                    // Xóa id khỏi URL
                    const newUrl = new URL(window.location);
                    newUrl.searchParams.delete('id');
                    window.history.replaceState({}, '', newUrl);
                }
            } catch (error) {
                console.error('Error loading resume:', error);
            }
        }
        
        // Sync form with resume data from API
        function syncFormWithResumeData(resumeData) {
            // Update header fields
            if (resumeData.header) {
                const h = resumeData.header;
                if (document.getElementById('header-full-name')) document.getElementById('header-full-name').value = h.Full_name || '';
                if (document.getElementById('header-birthday')) document.getElementById('header-birthday').value = h.BirthDay || '';
                if (document.getElementById('header-gender')) document.getElementById('header-gender').value = h.gender || '';
                if (document.getElementById('header-phone')) document.getElementById('header-phone').value = h.Phone || '';
                if (document.getElementById('header-email')) document.getElementById('header-email').value = h.Email || '';
                if (document.getElementById('header-website')) document.getElementById('header-website').value = h.Website || '';
                if (document.getElementById('header-address')) document.getElementById('header-address').value = h.address || '';
                
                // Load avatar if exists
                if (h.avatar) {
                    const hiddenInput = document.getElementById('header-avatar');
                    if (hiddenInput) {
                        hiddenInput.value = h.avatar;
                    }
                    // Show preview
                    const previewImg = document.getElementById('header-avatar-preview');
                    const previewContainer = document.getElementById('header-avatar-preview-container');
                    if (previewImg && previewContainer && h.avatar) {
                        previewImg.src = h.avatar;
                        previewContainer.style.display = 'block';
                    }
                }
            }
            
            // Update CV title and objective
            if (document.getElementById('cv-title')) document.getElementById('cv-title').value = resumeData.title || '';
            if (document.getElementById('cv-objective')) document.getElementById('cv-objective').value = resumeData.skills_summary || '';
            
            // Load template settings và cập nhật UI
            if (resumeData.personal_info) {
                try {
                    const settings = JSON.parse(resumeData.personal_info);
                    if (settings.template) {
                        currentTemplate = settings.template;
                        document.querySelectorAll('.template-item').forEach(item => {
                            item.classList.remove('active');
                        });
                        const templateButton = document.querySelector(`[onclick="selectTemplate('${settings.template}')"]`);
                        if (templateButton) {
                            templateButton.classList.add('active');
                        }
                    }
                    if (settings.color) {
                        currentColor = settings.color;
                        document.querySelectorAll('.color-option').forEach(item => {
                            item.classList.remove('active');
                        });
                        document.querySelector(`[onclick="selectColor('${settings.color}')"]`)?.classList.add('active');
                    }
                    if (settings.font) {
                        currentFont = settings.font;
                        document.querySelectorAll('.font-option').forEach(item => {
                            item.classList.remove('active');
                        });
                        document.querySelector(`[onclick="selectFont('${settings.font}')"]`)?.classList.add('active');
                    }
                    if (settings.fontSize) {
                        const fontSizeInput = document.getElementById('font-size');
                        if (fontSizeInput) fontSizeInput.value = settings.fontSize;
                    }
                } catch (e) {
                    console.warn('Không thể parse personal_info:', e);
                }
            }
            
            // Populate Educations
            if (resumeData.educations && resumeData.educations.length > 0) {
                const container = document.getElementById('educations-container');
                if (container) {
                    container.innerHTML = ''; // Xóa items cũ
                    resumeData.educations.forEach((edu, index) => {
                        window.addEducation();
                        const item = container.children[index];
                        if (item) {
                            const schoolInput = item.querySelector(`#education-${index}-school_name`);
                            const degreeInput = item.querySelector(`#education-${index}-degree`);
                            const majorInput = item.querySelector(`#education-${index}-major`);
                            const startDateInput = item.querySelector(`#education-${index}-start_date`);
                            const endDateInput = item.querySelector(`#education-${index}-end_date`);
                            const descInput = item.querySelector(`#education-${index}-description`);
                            
                            if (schoolInput) schoolInput.value = edu.school_name || '';
                            if (degreeInput) degreeInput.value = edu.degree || '';
                            if (majorInput) majorInput.value = edu.major || '';
                            if (startDateInput) startDateInput.value = edu.start_date ? edu.start_date.split('T')[0] : '';
                            if (endDateInput) endDateInput.value = edu.end_date ? edu.end_date.split('T')[0] : '';
                            if (descInput) descInput.value = edu.description || '';
                        }
                    });
                }
            }
            
            // Populate Experiences
            if (resumeData.experiences && resumeData.experiences.length > 0) {
                const container = document.getElementById('experiences-container');
                if (container) {
                    container.innerHTML = '';
                    resumeData.experiences.forEach((exp, index) => {
                        window.addExperience();
                        const item = container.children[index];
                        if (item) {
                            const companyInput = item.querySelector(`#experience-${index}-company_name`);
                            const positionInput = item.querySelector(`#experience-${index}-position`);
                            const startDateInput = item.querySelector(`#experience-${index}-start_date`);
                            const endDateInput = item.querySelector(`#experience-${index}-end_date`);
                            const descInput = item.querySelector(`#experience-${index}-description`);
                            
                            if (companyInput) companyInput.value = exp.company_name || '';
                            if (positionInput) positionInput.value = exp.position || '';
                            if (startDateInput) startDateInput.value = exp.start_date ? exp.start_date.split('T')[0] : '';
                            if (endDateInput) endDateInput.value = exp.end_date ? exp.end_date.split('T')[0] : '';
                            if (descInput) descInput.value = exp.description || '';
                        }
                    });
                }
            }
            
            // Populate Skills
            if (resumeData.skills && resumeData.skills.length > 0) {
                const container = document.getElementById('skills-container');
                if (container) {
                    container.innerHTML = '';
                    resumeData.skills.forEach((skill, index) => {
                        window.addSkill();
                        const item = container.children[index];
                        if (item) {
                            const skillInput = item.querySelector(`#skill-${index}-skill_name`);
                            if (skillInput) {
                                const skillName = skill.skill_name || skill;
                                skillInput.value = skillName;
                            }
                        }
                    });
                }
            }
            
            // Populate Projects
            if (resumeData.projects && resumeData.projects.length > 0) {
                const container = document.getElementById('projects-container');
                if (container) {
                    container.innerHTML = '';
                    resumeData.projects.forEach((proj, index) => {
                        window.addProject();
                        const item = container.children[index];
                        if (item) {
                            const nameInput = item.querySelector(`#project-${index}-project_name`);
                            const roleInput = item.querySelector(`#project-${index}-role`);
                            const techInput = item.querySelector(`#project-${index}-technologies`);
                            const startDateInput = item.querySelector(`#project-${index}-start_date`);
                            const endDateInput = item.querySelector(`#project-${index}-end_date`);
                            const descInput = item.querySelector(`#project-${index}-description`);
                            
                            if (nameInput) nameInput.value = proj.project_name || '';
                            if (roleInput) roleInput.value = proj.role || '';
                            if (techInput) techInput.value = proj.technologies || '';
                            if (startDateInput) startDateInput.value = proj.start_date ? proj.start_date.split('T')[0] : '';
                            if (endDateInput) endDateInput.value = proj.end_date ? proj.end_date.split('T')[0] : '';
                            if (descInput) descInput.value = proj.description || '';
                        }
                    });
                }
            }
            
            // Populate Certifications
            if (resumeData.certifications && resumeData.certifications.length > 0) {
                const container = document.getElementById('certifications-container');
                if (container) {
                    container.innerHTML = '';
                    resumeData.certifications.forEach((cert, index) => {
                        window.addCertification();
                        const item = container.children[index];
                        if (item) {
                            const nameInput = item.querySelector(`#certification-${index}-cert_name`);
                            const orgInput = item.querySelector(`#certification-${index}-organization`);
                            const dateInput = item.querySelector(`#certification-${index}-date_received`);
                            const descInput = item.querySelector(`#certification-${index}-description`);
                            
                            if (nameInput) nameInput.value = cert.cert_name || '';
                            if (orgInput) orgInput.value = cert.organization || '';
                            if (dateInput) dateInput.value = cert.date_received ? cert.date_received.split('T')[0] : '';
                            if (descInput) descInput.value = cert.description || '';
                        }
                    });
                }
            }
            
            // Populate Awards
            if (resumeData.awards && resumeData.awards.length > 0) {
                const container = document.getElementById('awards-container');
                if (container) {
                    container.innerHTML = '';
                    resumeData.awards.forEach((award, index) => {
                        window.addAward();
                        const item = container.children[index];
                        if (item) {
                            const nameInput = item.querySelector(`#award-${index}-award_name`);
                            const orgInput = item.querySelector(`#award-${index}-organization`);
                            const dateInput = item.querySelector(`#award-${index}-date_received`);
                            const descInput = item.querySelector(`#award-${index}-description`);
                            
                            if (nameInput) nameInput.value = award.award_name || '';
                            if (orgInput) orgInput.value = award.organization || '';
                            if (dateInput) dateInput.value = award.date_received ? award.date_received.split('T')[0] : '';
                            if (descInput) descInput.value = award.description || '';
                        }
                    });
                }
            }
            
            // Populate References
            if (resumeData.references && resumeData.references.length > 0) {
                const container = document.getElementById('references-container');
                if (container) {
                    container.innerHTML = '';
                    resumeData.references.forEach((ref, index) => {
                        window.addReference();
                        const item = container.children[index];
                        if (item) {
                            const nameInput = item.querySelector(`#reference-${index}-name`);
                            const relInput = item.querySelector(`#reference-${index}-relationship`);
                            const contactInput = item.querySelector(`#reference-${index}-contact`);
                            
                            if (nameInput) nameInput.value = ref.name || '';
                            if (relInput) relInput.value = ref.relationship || '';
                            if (contactInput) contactInput.value = ref.contact || '';
                        }
                    });
                }
            }
            
            // Populate Activities
            if (resumeData.activities && resumeData.activities.length > 0) {
                const container = document.getElementById('activities-container');
                if (container) {
                    container.innerHTML = '';
                    resumeData.activities.forEach((act, index) => {
                        window.addActivity();
                        const item = container.children[index];
                        if (item) {
                            const orgInput = item.querySelector(`#activity-${index}-organization_name`);
                            const roleInput = item.querySelector(`#activity-${index}-role`);
                            const startDateInput = item.querySelector(`#activity-${index}-start_date`);
                            const endDateInput = item.querySelector(`#activity-${index}-end_date`);
                            const descInput = item.querySelector(`#activity-${index}-description`);
                            
                            if (orgInput) orgInput.value = act.organization_name || '';
                            if (roleInput) roleInput.value = act.role || '';
                            if (startDateInput) startDateInput.value = act.start_date ? act.start_date.split('T')[0] : '';
                            if (endDateInput) endDateInput.value = act.end_date ? act.end_date.split('T')[0] : '';
                            if (descInput) descInput.value = act.description || '';
                        }
                    });
                }
            }
            
            // Populate Hobbies
            if (resumeData.hobbies && resumeData.hobbies.length > 0) {
                const container = document.getElementById('hobbies-container');
                if (container) {
                    container.innerHTML = '';
                    resumeData.hobbies.forEach((hobby, index) => {
                        window.addHobby();
                        const item = container.children[index];
                        if (item) {
                            const descInput = item.querySelector(`#hobby-${index}-description`);
                            if (descInput) {
                                // Hobby có thể là object với hobby_name hoặc description, hoặc là string
                                const hobbyText = hobby.hobby_name || hobby.description || hobby;
                                descInput.value = hobbyText;
                            }
                        }
                    });
                }
            }
            
            // Populate Extrainfos
            if (resumeData.extrainfos && resumeData.extrainfos.length > 0) {
                const container = document.getElementById('extrainfos-container');
                if (container) {
                    container.innerHTML = '';
                    resumeData.extrainfos.forEach((extrainfo, index) => {
                        window.addExtrainfo();
                        const item = container.children[index];
                        if (item) {
                            const contentInput = item.querySelector(`#extrainfo-${index}-content`);
                            if (contentInput) {
                                contentInput.value = extrainfo.content || '';
                            }
                        }
                    });
                }
            }
        }

        // Sync form with CV data
        function syncFormWithCVData() {
            if (cvData.name) document.getElementById('header-full-name').value = cvData.name;
            if (cvData.email) document.getElementById('header-email').value = cvData.email;
            if (cvData.phone) document.getElementById('header-phone').value = cvData.phone;
            if (cvData.location) document.getElementById('header-address').value = cvData.location;
            if (cvData.title) document.getElementById('cv-title').value = cvData.title;
            if (cvData.objective) document.getElementById('cv-objective').value = cvData.objective;
        }

