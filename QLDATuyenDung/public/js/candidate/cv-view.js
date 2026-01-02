// CV View JavaScript
// Hiển thị chi tiết CV để xem và in

// ============================================================================
// PHẦN LAYOUTS (từ cv-view-layouts.js - đã gộp)
// ============================================================================

/**
 * Color mapping
 */
const colorMap = {
    'green': '#28a745',
    'blue': '#007bff',
    'purple': '#6f42c1',
    'red': '#dc3545',
    'orange': '#fd7e14',
    'teal': '#20c997'
};

/**
 * Format date để hiển thị
 */
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

/**
 * Format date range (start - end)
 */
function formatDateRange(startDate, endDate) {
    try {
        const start = startDate ? formatDate(startDate) : '';
        const end = endDate ? formatDate(endDate) : 'Hiện tại';
        if (!start && !end) return '';
        return `${start} - ${end}`;
    } catch (e) {
        console.warn('Error formatting date range:', e);
        // Fallback: trả về format đơn giản
        return startDate && endDate ? `${startDate} - ${endDate}` : (startDate || endDate || '');
    }
}

/**
 * Escape HTML để tránh XSS
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Lấy template settings từ CV
 */
function getTemplateSettings(cv) {
    let settings = {
        template: 'sidebar',
        color: 'green',
        font: 'Arial',
        fontSize: '14px'
    };

    // Thử parse từ personal_info
    if (cv.personal_info) {
        try {
            const parsed = JSON.parse(cv.personal_info);
            if (parsed.template) settings.template = parsed.template;
            if (parsed.color) settings.color = parsed.color;
            if (parsed.font) settings.font = parsed.font;
            if (parsed.fontSize) settings.fontSize = parsed.fontSize + 'px';
        } catch (e) {
            // Nếu không parse được, dùng default
            console.warn('Could not parse template settings from personal_info');
        }
    }

    return settings;
}

/**
 * Lấy template-specific styles
 */
function getTemplateStyles(template, primaryColor, fontSize, fontFamily) {
    const styles = {
        wrapper: `font-family: ${fontFamily || 'Arial, sans-serif'}; font-size: ${fontSize || '14px'};`,
        header: '',
        content: '',
        section: '',
        item: ''
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
            styles.content = `display: grid; grid-template-columns: 1fr 1fr; gap: 20px; padding: 25px; background: white;`;
            styles.section = `margin-bottom: 25px; border-left: 3px solid ${primaryColor}; padding-left: 15px;`;
            styles.item = `margin-bottom: 20px;`;
            break;

        case 'sidebar':
        case 'header-content':
            // Layout 2 cột với sidebar tối (giống modern-professional)
            styles.wrapper = `font-family: ${fontFamily || 'Arial, sans-serif'}; font-size: ${fontSize || '14px'}; display: flex; min-height: 100vh; transition: all 0.3s ease;`;
            styles.header = `display: none;`; // Không dùng header riêng, sẽ tích hợp vào sidebar
            styles.content = `display: flex; width: 100%; gap: 0; transition: all 0.3s ease;`;
            // Sidebar trái (màu tối)
            styles.leftColumn = `width: 35%; background-color: #2c3e50; color: #ffffff; padding: 30px 20px; display: flex; flex-direction: column; transition: all 0.3s ease;`;
            // Cột phải (nền trắng)
            styles.rightColumn = `width: 65%; background-color: #ffffff; padding: 30px 25px; color: #333; transition: all 0.3s ease;`;
            // Sections trong sidebar trái (màu trắng)
            styles.sectionLeft = `margin-bottom: 30px; width: 100%; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 20px; opacity: 0; animation: fadeIn 0.5s ease forwards;`;
            styles.sectionTitleLeft = `font-size: 1.2em; font-weight: bold; margin-bottom: 15px; color: #fff; text-transform: uppercase; letter-spacing: 1px;`;
            styles.sectionContentLeft = `color: #fff; line-height: 1.6; font-size: 0.95em;`;
            // Sections trong cột phải
            styles.sectionRight = `margin-bottom: 25px; opacity: 0; animation: fadeIn 0.5s ease forwards;`;
            styles.sectionTitleRight = `font-size: 1.3em; color: #2c3e50; font-weight: bold; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; transition: all 0.3s ease;`;
            styles.sectionContentRight = `color: #555; line-height: 1.6;`;
            styles.item = `margin-bottom: 20px; transition: transform 0.2s ease;`;
            styles.itemHover = `transform: translateX(5px);`;
            break;

        case 'executive':
            styles.header = `background: #f8f9fa; color: #333; padding: 30px; border-bottom: 4px solid ${primaryColor};`;
            styles.content = `padding: 30px; background: white;`;
            styles.section = `margin-bottom: 30px; background: white; padding: 25px; border: 1px solid #e0e0e0; border-radius: 4px;`;
            styles.item = `margin-bottom: 20px;`;
            break;

        case 'corporate':
            styles.header = `background: white; color: #333; padding: 25px; border-left: 5px solid ${primaryColor};`;
            styles.content = `padding: 25px; background: white;`;
            styles.section = `margin-bottom: 25px; border-left: 5px solid ${primaryColor}; padding-left: 20px;`;
            styles.item = `margin-bottom: 20px;`;
            break;

        case 'minimalist':
            styles.header = `background: white; color: #333; padding: 30px; text-align: center; border-bottom: 2px solid ${primaryColor};`;
            styles.content = `padding: 30px; background: white;`;
            styles.section = `margin-bottom: 30px; text-align: center; border-top: 1px solid #eee; padding-top: 20px; border-left: 2px solid ${primaryColor}; padding-left: 15px;`;
            styles.item = `margin-bottom: 20px;`;
            break;

        case 'modern-professional':
        case 'modern-pro':
            // Layout 2 cột với sidebar tối bên trái
            styles.wrapper = `font-family: ${fontFamily || 'Arial, sans-serif'}; font-size: ${fontSize || '14px'}; display: flex; min-height: 100vh; transition: all 0.3s ease;`;
            styles.header = `display: none;`; // Không dùng header riêng, sẽ tích hợp vào sidebar
            styles.content = `display: flex; width: 100%; gap: 0; transition: all 0.3s ease;`;
            // Sidebar trái (màu tối)
            styles.leftColumn = `width: 35%; background-color: #2c3e50; color: #ffffff; padding: 30px 20px; display: flex; flex-direction: column; transition: all 0.3s ease;`;
            // Cột phải (nền trắng)
            styles.rightColumn = `width: 65%; background-color: #ffffff; padding: 30px 25px; color: #333; transition: all 0.3s ease;`;
            // Sections trong sidebar trái (màu trắng)
            styles.sectionLeft = `margin-bottom: 30px; width: 100%; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 20px; opacity: 0; animation: fadeIn 0.5s ease forwards;`;
            styles.sectionTitleLeft = `font-size: 1.2em; font-weight: bold; margin-bottom: 15px; color: #fff; text-transform: uppercase; letter-spacing: 1px;`;
            styles.sectionContentLeft = `color: #fff; line-height: 1.6; font-size: 0.95em;`;
            // Sections trong cột phải
            styles.sectionRight = `margin-bottom: 25px; opacity: 0; animation: fadeIn 0.5s ease forwards;`;
            styles.sectionTitleRight = `font-size: 1.3em; color: #2c3e50; font-weight: bold; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; transition: all 0.3s ease;`;
            styles.sectionContentRight = `color: #555; line-height: 1.6;`;
            styles.item = `margin-bottom: 20px; transition: transform 0.2s ease;`;
            styles.itemHover = `transform: translateX(5px);`;
            break;

        case 'professional-sidebar':
            // Layout 2 cột với sidebar tối (màu xám đậm hơn)
            styles.wrapper = `font-family: ${fontFamily || 'Arial, sans-serif'}; font-size: ${fontSize || '14px'}; display: flex; min-height: 100vh; transition: all 0.3s ease;`;
            styles.header = `display: none;`; // Không dùng header riêng
            styles.content = `display: flex; width: 100%; gap: 0; transition: all 0.3s ease;`;
            // Sidebar trái (màu xám đậm #34495e)
            styles.leftColumn = `width: 30%; background-color: #34495e; color: #ffffff; padding: 30px 20px; display: flex; flex-direction: column; transition: all 0.3s ease;`;
            // Cột phải (nền trắng)
            styles.rightColumn = `width: 70%; background-color: #ffffff; padding: 30px 25px; color: #333; transition: all 0.3s ease;`;
            // Sections trong sidebar trái
            styles.sectionLeft = `margin-bottom: 30px; width: 100%; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 20px; opacity: 0; animation: fadeIn 0.5s ease forwards;`;
            styles.sectionTitleLeft = `font-size: 1.1em; font-weight: bold; margin-bottom: 15px; color: #fff; text-transform: uppercase; letter-spacing: 0.5px;`;
            styles.sectionContentLeft = `color: #fff; line-height: 1.6; font-size: 0.9em;`;
            // Sections trong cột phải
            styles.sectionRight = `margin-bottom: 25px; opacity: 0; animation: fadeIn 0.5s ease forwards;`;
            styles.sectionTitleRight = `font-size: 1.4em; color: #34495e; font-weight: bold; border-bottom: 3px solid #34495e; padding-bottom: 10px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; transition: all 0.3s ease;`;
            styles.sectionContentRight = `color: #555; line-height: 1.6;`;
            styles.item = `margin-bottom: 20px; transition: transform 0.2s ease;`;
            styles.itemHover = `transform: translateX(5px);`;
            break;

        case 'simple':
            styles.wrapper = `font-family: ${fontFamily || 'Arial, sans-serif'}; font-size: ${fontSize || '14px'}; max-width: 800px; margin: 0 auto; background: white; padding: 30px; box-shadow: 0 0 10px rgba(0,0,0,0.1);`;
            styles.header = `padding: 30px 0; border-bottom: 2px solid ${primaryColor}; margin-bottom: 30px;`;
            styles.content = `padding: 0; background: white;`;
            styles.section = `margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #eee;`;
            styles.item = `margin-bottom: 20px;`;
            break;

        default:
            // Default: single-column
            styles.header = `background: #f8f9fa; color: #333; padding: 25px; text-align: center; border: 1px solid #ddd; border-bottom: 3px solid ${primaryColor};`;
            styles.content = `padding: 25px; background: white;`;
            styles.section = `margin-bottom: 25px; border-top: 1px solid #ddd; padding-top: 20px; border-left: 3px solid ${primaryColor}; padding-left: 15px;`;
            styles.item = `margin-bottom: 20px;`;
    }

    return styles;
}

// ============================================================================
// PHẦN CV VIEW (code hiện tại)
// ============================================================================

const CV_BUILDER_ROUTE = window.CV_BUILDER_ROUTE || '/candidate/cv-builder';
const CV_INDEX_ROUTE = window.CV_INDEX_ROUTE || '/candidate/cv';
let currentCVId = null;

/**
 * Tính cỡ chữ chuẩn cho CV
 */
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
 * Lấy CV ID từ URL query parameter
 */
function getCVIdFromUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('id');
}

/**
 * Tải CV từ API
 */
async function loadCV() {
    const cvId = getCVIdFromUrl();
    if (!cvId) {
        showError('Không tìm thấy ID của CV');
        return;
    }

    currentCVId = cvId;

    try {
        const response = await fetch(`/api/resumes/${cvId}`, {
            method: 'GET',
            headers: getAuthHeaders(),
            credentials: 'same-origin'
        });

        if (!response.ok) {
            if (response.status === 403) {
                throw new Error('Bạn không có quyền xem CV này');
            }
            throw new Error('Không thể tải CV');
        }

        let result;
        try {
            result = await response.json();
        } catch (e) {
            console.error('Error parsing response:', e);
            throw new Error('Không thể đọc phản hồi từ server. Vui lòng thử lại.');
        }
        
        if (!result.success || !result.data) {
            throw new Error(result.message || 'Không tìm thấy dữ liệu CV');
        }

        displayCV(result.data);
        hideLoading();
    } catch (error) {
        console.error('Error loading CV:', error);
        showError('Có lỗi xảy ra khi tải CV: ' + error.message);
    }
}

/**
 * Hiển thị CV với layout modern-professional (2 cột với sidebar tối)
 */
function displayModernProfessionalCV(cv, styles, primaryColor, fontSize, fontFamily) {
    const content = document.getElementById('cv-content');
    if (!content) return;

    const header = cv.header || {};
    const title = cv.title || 'CV chưa đặt tên';
    const fullName = header.Full_name || 'Họ và tên';
    const avatar = header.avatar || cv.photo || null;
    
    // Tính cỡ chữ chuẩn
    const baseFontSize = parseFloat(fontSize) || 11;
    const fontSizes = getFontSizes(baseFontSize);
    
    // Bắt đầu render với wrapper và 2 cột
    let html = `
        <div class="cv-wrapper modern-professional" style="${styles.wrapper}">
            <!-- Sidebar trái (màu tối) -->
            <div class="cv-left-column" style="${styles.leftColumn}">
                <!-- Avatar và tên -->
                ${avatar ? `
                    <div style="text-align: center; margin-bottom: 20px;">
                        <img src="${escapeHtml(avatar)}" alt="Ảnh đại diện" 
                             style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid rgba(255,255,255,0.3);">
                    </div>
                ` : ''}
                <h1 style="margin: 0 0 10px 0; font-size: ${fontSizes.name}; font-weight: bold; text-transform: uppercase; text-align: center; color: #fff;">${escapeHtml(fullName)}</h1>
                <p style="margin: 0 0 30px 0; font-size: ${fontSizes.contact}; font-weight: 300; text-align: center; color: rgba(255,255,255,0.9);">${escapeHtml(title)}</p>
                
                <!-- Thông tin liên hệ -->
                <div class="cv-section-left" style="${styles.sectionLeft}">
                    <h2 class="cv-section-title-left" style="${styles.sectionTitleLeft}; font-size: ${fontSizes.sectionTitle};">Thông tin liên hệ</h2>
                    <div class="cv-section-content-left" style="${styles.sectionContentLeft}; font-size: ${fontSizes.contact};">
                        ${header.Phone ? `<p style="margin: 8px 0; font-size: ${fontSizes.contact};"><i class="fas fa-phone"></i> ${escapeHtml(header.Phone)}</p>` : ''}
                        ${header.Email ? `<p style="margin: 8px 0; font-size: ${fontSizes.contact};"><i class="fas fa-envelope"></i> ${escapeHtml(header.Email)}</p>` : ''}
                        ${header.address ? `<p class="cv-address-print" style="margin: 8px 0; font-size: ${fontSizes.contact};"><i class="fas fa-map-marker-alt"></i> ${escapeHtml(header.address)}</p>` : ''}
                        ${header.BirthDay ? `<p style="margin: 8px 0; font-size: ${fontSizes.contact};"><i class="fas fa-birthday-cake"></i> ${formatDate(header.BirthDay)}</p>` : ''}
                        ${header.gender ? `<p style="margin: 8px 0; font-size: ${fontSizes.contact};"><i class="fas fa-user"></i> ${header.gender === 'male' ? 'Nam' : header.gender === 'female' ? 'Nữ' : 'Khác'}</p>` : ''}
                        ${header.Website ? `<p style="margin: 8px 0; font-size: ${fontSizes.contact};"><i class="fas fa-globe"></i> <a href="${escapeHtml(header.Website)}" target="_blank" style="color: #fff; text-decoration: underline;">${escapeHtml(header.Website)}</a></p>` : ''}
                    </div>
                </div>
                
                <!-- Học vấn -->
                ${cv.educations && cv.educations.length > 0 ? `
                    <div class="cv-section-left" style="${styles.sectionLeft}">
                        <h2 class="cv-section-title-left" style="${styles.sectionTitleLeft}; font-size: ${fontSizes.sectionTitle};">Học vấn</h2>
                        <div class="cv-section-content-left" style="${styles.sectionContentLeft}; font-size: ${fontSizes.content};">
                            ${cv.educations.map((edu, index) => {
                                const dateRange = formatDateRange(edu.start_date, edu.end_date);
                                return `
                                    <div style="margin-bottom: ${index < cv.educations.length - 1 ? '20px' : '0'};">
                                        <h3 style="color: #fff; font-size: ${fontSizes.content}; margin: 0 0 5px 0; font-weight: 600;">${escapeHtml(edu.degree || 'Bằng cấp')}</h3>
                                        <p style="color: rgba(255,255,255,0.9); margin: 0 0 8px 0; font-weight: 500; font-size: ${fontSizes.contact};">${escapeHtml(edu.school_name || '')} ${edu.major ? `- ${escapeHtml(edu.major)}` : ''}</p>
                                        ${dateRange ? `<p style="color: rgba(255,255,255,0.8); margin: 0 0 8px 0; font-size: ${fontSizes.contact};">${dateRange}</p>` : ''}
                                        ${edu.description ? `<p style="color: rgba(255,255,255,0.8); margin: 0; line-height: 1.6; font-size: ${fontSizes.content};">${escapeHtml(edu.description)}</p>` : ''}
                                    </div>
                                    ${index < cv.educations.length - 1 ? '<hr style="border: none; border-top: 1px solid rgba(255,255,255,0.2); margin: 15px 0;">' : ''}
                                `;
                            }).join('')}
                        </div>
                    </div>
                ` : ''}
                
                <!-- Kỹ năng -->
                ${cv.skills && cv.skills.length > 0 ? `
                    <div class="cv-section-left" style="${styles.sectionLeft}">
                        <h2 class="cv-section-title-left" style="${styles.sectionTitleLeft}; font-size: ${fontSizes.sectionTitle};">Kỹ năng</h2>
                        <div class="cv-section-content-left" style="${styles.sectionContentLeft}; font-size: ${fontSizes.content};">
                            ${cv.skills.map(skill => `
                                <div style="margin-bottom: 15px;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                        <span style="font-size: ${fontSizes.content};">${escapeHtml(skill.skill_name || skill)}</span>
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
                ${cv.hobbies && cv.hobbies.length > 0 ? `
                    <div class="cv-section-left" style="${styles.sectionLeft}">
                        <h2 class="cv-section-title-left" style="${styles.sectionTitleLeft}; font-size: ${fontSizes.sectionTitle};">Sở thích</h2>
                        <div class="cv-section-content-left" style="${styles.sectionContentLeft}; font-size: ${fontSizes.content};">
                            <ul style="margin: 0; padding-left: 20px; font-size: ${fontSizes.content};">
                                ${cv.hobbies.map(hobby => `<li style="font-size: ${fontSizes.content};">${escapeHtml(hobby.description || hobby)}</li>`).join('')}
                            </ul>
                        </div>
                    </div>
                ` : ''}
            </div>
            
            <!-- Cột phải (nền trắng) -->
            <div class="cv-right-column" style="${styles.rightColumn}">
    `;
    
    // Mục tiêu nghề nghiệp
    if (cv.skills_summary) {
        html += `
            <div class="cv-section-right" style="${styles.sectionRight}">
                <h2 class="cv-section-title-right" style="${styles.sectionTitleRight}; font-size: ${fontSizes.sectionTitle};">
                    <i class="fas fa-bullseye"></i> Mục tiêu nghề nghiệp
                </h2>
                <div class="cv-section-content-right" style="${styles.sectionContentRight}; font-size: ${fontSizes.content};">
                    <p style="font-size: ${fontSizes.content}; line-height: 1.6;">${escapeHtml(cv.skills_summary)}</p>
                </div>
            </div>
        `;
    }
    
    // Kinh nghiệm làm việc
    if (cv.experiences && cv.experiences.length > 0) {
        html += `
            <div class="cv-section-right" style="${styles.sectionRight}">
                <h2 class="cv-section-title-right" style="${styles.sectionTitleRight}; font-size: ${fontSizes.sectionTitle};">
                    <i class="fas fa-briefcase"></i> Kinh nghiệm làm việc
                </h2>
                <div class="cv-section-content-right" style="${styles.sectionContentRight}; font-size: ${fontSizes.content};">
        `;
        
        cv.experiences.forEach((exp, index) => {
            const dateRange = formatDateRange(exp.start_date, exp.end_date);
            html += `
                <div class="cv-item" style="${styles.item}">
                    <h3 style="color: #333; font-size: ${fontSizes.content}; margin: 0 0 5px 0; font-weight: 600;">${escapeHtml(exp.position || 'Chức vụ')}</h3>
                    <p style="color: #666; margin: 0 0 8px 0; font-weight: 500; font-size: ${fontSizes.contact};">${escapeHtml(exp.company_name || '')} ${dateRange ? `| ${dateRange}` : ''}</p>
                    ${exp.description ? `<p style="color: #555; margin: 0; line-height: 1.6; font-size: ${fontSizes.content};">${escapeHtml(exp.description)}</p>` : ''}
                </div>
                ${index < cv.experiences.length - 1 ? '<hr style="border: none; border-top: 1px solid #eee; margin: 15px 0;">' : ''}
            `;
        });
        
        html += `</div></div>`;
    }
    
    // Dự án
    if (cv.projects && cv.projects.length > 0) {
        html += `
            <div class="cv-section-right" style="${styles.sectionRight}">
                <h2 class="cv-section-title-right" style="${styles.sectionTitleRight}; font-size: ${fontSizes.sectionTitle};">
                    <i class="fas fa-project-diagram"></i> Dự án
                </h2>
                <div class="cv-section-content-right" style="${styles.sectionContentRight}; font-size: ${fontSizes.content};">
        `;
        
        cv.projects.forEach((project, index) => {
            const dateRange = formatDateRange(project.start_date, project.end_date);
            html += `
                <div class="cv-item" style="${styles.item}">
                    <h3 style="color: #333; font-size: ${fontSizes.content}; margin: 0 0 5px 0; font-weight: 600;">${escapeHtml(project.project_name || 'Dự án')}</h3>
                    ${project.role ? `<p style="color: #666; margin: 0 0 8px 0; font-size: ${fontSizes.contact};">Vai trò: ${escapeHtml(project.role)} ${dateRange ? `| ${dateRange}` : ''}</p>` : dateRange ? `<p style="color: #666; margin: 0 0 8px 0; font-size: ${fontSizes.contact};">${dateRange}</p>` : ''}
                    ${project.technologies ? `<p style="color: ${primaryColor}; margin: 0 0 8px 0; font-weight: 500; font-size: ${fontSizes.contact};">Công nghệ: ${escapeHtml(project.technologies)}</p>` : ''}
                    ${project.description ? `<p style="color: #555; margin: 0; line-height: 1.6; font-size: ${fontSizes.content};">${escapeHtml(project.description)}</p>` : ''}
                </div>
                ${index < cv.projects.length - 1 ? '<hr style="border: none; border-top: 1px solid #eee; margin: 15px 0;">' : ''}
            `;
        });
        
        html += `</div></div>`;
    }
    
    // Chứng chỉ
    if (cv.certifications && cv.certifications.length > 0) {
        html += `
            <div class="cv-section-right" style="${styles.sectionRight}">
                <h2 class="cv-section-title-right" style="${styles.sectionTitleRight}; font-size: ${fontSizes.sectionTitle};">
                    <i class="fas fa-certificate"></i> Chứng chỉ
                </h2>
                <div class="cv-section-content-right" style="${styles.sectionContentRight}; font-size: ${fontSizes.content};">
        `;
        
        cv.certifications.forEach((cert, index) => {
            html += `
                <div class="cv-item" style="${styles.item}">
                    <h3 style="color: #333; font-size: ${fontSizes.content}; margin: 0 0 5px 0; font-weight: 600;">${escapeHtml(cert.cert_name || 'Chứng chỉ')}</h3>
                    ${cert.organization ? `<p style="color: #666; margin: 0 0 8px 0; font-size: ${fontSizes.contact};">Tổ chức: ${escapeHtml(cert.organization)} ${cert.date_received ? `| ${formatDate(cert.date_received)}` : ''}</p>` : cert.date_received ? `<p style="color: #666; margin: 0 0 8px 0; font-size: ${fontSizes.contact};">${formatDate(cert.date_received)}</p>` : ''}
                    ${cert.description ? `<p style="color: #555; margin: 0; line-height: 1.6; font-size: ${fontSizes.content};">${escapeHtml(cert.description)}</p>` : ''}
                </div>
                ${index < cv.certifications.length - 1 ? '<hr style="border: none; border-top: 1px solid #eee; margin: 15px 0;">' : ''}
            `;
        });
        
        html += `</div></div>`;
    }
    
    // Giải thưởng
    if (cv.awards && cv.awards.length > 0) {
        html += `
            <div class="cv-section-right" style="${styles.sectionRight}">
                <h2 class="cv-section-title-right" style="${styles.sectionTitleRight}; font-size: ${fontSizes.sectionTitle};">
                    <i class="fas fa-trophy"></i> Giải thưởng
                </h2>
                <div class="cv-section-content-right" style="${styles.sectionContentRight}; font-size: ${fontSizes.content};">
        `;
        
        cv.awards.forEach((award, index) => {
            html += `
                <div class="cv-item" style="${styles.item}">
                    <h3 style="color: #333; font-size: ${fontSizes.content}; margin: 0 0 5px 0; font-weight: 600;">${escapeHtml(award.award_name || 'Giải thưởng')}</h3>
                    ${award.organization ? `<p style="color: #666; margin: 0 0 8px 0; font-size: ${fontSizes.contact};">Tổ chức: ${escapeHtml(award.organization)} ${award.date_received ? `| ${formatDate(award.date_received)}` : ''}</p>` : award.date_received ? `<p style="color: #666; margin: 0 0 8px 0; font-size: ${fontSizes.contact};">${formatDate(award.date_received)}</p>` : ''}
                    ${award.description ? `<p style="color: #555; margin: 0; line-height: 1.6; font-size: ${fontSizes.content};">${escapeHtml(award.description)}</p>` : ''}
                </div>
                ${index < cv.awards.length - 1 ? '<hr style="border: none; border-top: 1px solid #eee; margin: 15px 0;">' : ''}
            `;
        });
        
        html += `</div></div>`;
    }
    
    // Hoạt động
    if (cv.activities && cv.activities.length > 0) {
        html += `
            <div class="cv-section-right" style="${styles.sectionRight}">
                <h2 class="cv-section-title-right" style="${styles.sectionTitleRight}; font-size: ${fontSizes.sectionTitle};">
                    <i class="fas fa-users"></i> Hoạt động
                </h2>
                <div class="cv-section-content-right" style="${styles.sectionContentRight}; font-size: ${fontSizes.content};">
        `;
        
        cv.activities.forEach((activity, index) => {
            const dateRange = formatDateRange(activity.start_date, activity.end_date);
            html += `
                <div class="cv-item" style="${styles.item}">
                    <h3 style="color: #333; font-size: ${fontSizes.content}; margin: 0 0 5px 0; font-weight: 600;">${escapeHtml(activity.organization_name || 'Hoạt động')}</h3>
                    ${activity.role ? `<p style="color: #666; margin: 0 0 8px 0; font-size: ${fontSizes.contact};">Vai trò: ${escapeHtml(activity.role)} ${dateRange ? `| ${dateRange}` : ''}</p>` : dateRange ? `<p style="color: #666; margin: 0 0 8px 0; font-size: ${fontSizes.contact};">${dateRange}</p>` : ''}
                    ${activity.description ? `<p style="color: #555; margin: 0; line-height: 1.6; font-size: ${fontSizes.content};">${escapeHtml(activity.description)}</p>` : ''}
                </div>
                ${index < cv.activities.length - 1 ? '<hr style="border: none; border-top: 1px solid #eee; margin: 15px 0;">' : ''}
            `;
        });
        
        html += `</div></div>`;
    }
    
    // Người tham khảo
    if (cv.references && cv.references.length > 0) {
        html += `
            <div class="cv-section-right" style="${styles.sectionRight}">
                <h2 class="cv-section-title-right" style="${styles.sectionTitleRight}; font-size: ${fontSizes.sectionTitle};">
                    <i class="fas fa-user-friends"></i> Người tham khảo
                </h2>
                <div class="cv-section-content-right" style="${styles.sectionContentRight}; font-size: ${fontSizes.content};">
        `;
        
        cv.references.forEach((ref, index) => {
            html += `
                <div class="cv-item" style="${styles.item}">
                    <h3 style="color: #333; font-size: ${fontSizes.content}; margin: 0 0 5px 0; font-weight: 600;">${escapeHtml(ref.name || 'Người tham khảo')}</h3>
                    ${ref.relationship ? `<p style="color: #666; margin: 0 0 8px 0; font-size: ${fontSizes.contact};">Quan hệ: ${escapeHtml(ref.relationship)}</p>` : ''}
                    ${ref.contact_info ? `<p style="color: #555; margin: 0; line-height: 1.6; font-size: ${fontSizes.content};">Liên hệ: ${escapeHtml(ref.contact_info)}</p>` : ''}
                </div>
                ${index < cv.references.length - 1 ? '<hr style="border: none; border-top: 1px solid #eee; margin: 15px 0;">' : ''}
            `;
        });
        
        html += `</div></div>`;
    }
    
    // Thông tin bổ sung
    if (cv.extraInfos && cv.extraInfos.length > 0) {
        html += `
            <div class="cv-section-right" style="${styles.sectionRight}">
                <h2 class="cv-section-title-right" style="${styles.sectionTitleRight}; font-size: ${fontSizes.sectionTitle};">
                    <i class="fas fa-info-circle"></i> Thông tin bổ sung
                </h2>
                <div class="cv-section-content-right" style="${styles.sectionContentRight}; font-size: ${fontSizes.content};">
        `;
        
        cv.extraInfos.forEach((extra, index) => {
            html += `
                <div class="cv-item" style="${styles.item}">
                    <p style="color: #555; margin: 0; line-height: 1.6; font-size: ${fontSizes.content};">${escapeHtml(extra.content || extra)}</p>
                </div>
                ${index < cv.extraInfos.length - 1 ? '<hr style="border: none; border-top: 1px solid #eee; margin: 15px 0;">' : ''}
            `;
        });
        
        html += `</div></div>`;
    }
    
    html += `</div></div>`;
    
    // Thêm logo WebCV ở cuối CV (chỉ hiển thị khi in)
    html += `
        <div class="cv-footer-logo" style="display: none; position: fixed; bottom: 20px; right: 20px; text-align: right; font-size: 12px; color: #666; z-index: 1000;">
            <div style="font-weight: bold; color: #06b6d4; font-size: 14px;">WebCV</div>
        </div>
    `;
    
    content.innerHTML = html;
    
    // Trigger animation sau khi render
    setTimeout(() => {
        const sections = content.querySelectorAll('.cv-section-left, .cv-section-right');
        sections.forEach((section, index) => {
            section.style.animationDelay = `${index * 0.1}s`;
        });
    }, 100);
}

/**
 * Hiển thị CV với layout đơn giản (single-column với header có avatar bên trái)
 */
function displaySimpleCV(cv, styles, primaryColor, fontSize, fontFamily) {
    const content = document.getElementById('cv-content');
    if (!content) return;

    const header = cv.header || {};
    const title = cv.title || 'CV chưa đặt tên';
    const fullName = header.Full_name || 'Họ và tên';
    const avatar = header.avatar || cv.photo || null;
    
    // Tính cỡ chữ chuẩn
    const baseFontSize = parseFloat(fontSize) || 11;
    const fontSizes = getFontSizes(baseFontSize);
    
    let html = `
        <div class="cv-wrapper simple" style="${styles.wrapper}">
            <!-- Header với avatar bên trái -->
            <div class="cv-header" style="${styles.header}">
                <div style="display: flex; align-items: flex-start; gap: 25px;">
                    ${avatar ? `
                        <div style="width: 120px; height: 120px; border-radius: 50%; overflow: hidden; border: 3px solid ${primaryColor}; flex-shrink: 0;">
                            <img src="${escapeHtml(avatar)}" alt="Ảnh đại diện" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    ` : ''}
                    <div style="flex: 1;">
                        <h1 style="margin: 0 0 10px 0; font-size: ${fontSizes.name}; font-weight: bold; color: #333;">${escapeHtml(fullName)}</h1>
                        <h2 style="margin: 0 0 20px 0; font-size: ${fontSizes.sectionTitle}; font-weight: 500; color: #666;">${escapeHtml(title)}</h2>
                        <div style="display: flex; flex-wrap: wrap; gap: 15px; font-size: ${fontSizes.contact}; color: #555;">
                            ${header.BirthDay ? `<span><i class="fas fa-birthday-cake"></i> ${formatDate(header.BirthDay)}</span>` : ''}
                            ${header.gender ? `<span><i class="fas fa-user"></i> ${header.gender === 'male' ? 'Nam' : header.gender === 'female' ? 'Nữ' : 'Khác'}</span>` : ''}
                            ${header.Phone ? `<span><i class="fas fa-phone"></i> ${escapeHtml(header.Phone)}</span>` : ''}
                            ${header.Email ? `<span><i class="fas fa-envelope"></i> ${escapeHtml(header.Email)}</span>` : ''}
                            ${header.Website ? `<span><i class="fas fa-globe"></i> <a href="${escapeHtml(header.Website)}" target="_blank" style="color: ${primaryColor}; text-decoration: underline;">${escapeHtml(header.Website)}</a></span>` : ''}
                            ${header.address ? `<span class="cv-address-print"><i class="fas fa-map-marker-alt"></i> ${escapeHtml(header.address)}</span>` : ''}
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Content -->
            <div class="cv-content" style="${styles.content}">
                <!-- Mục tiêu nghề nghiệp -->
                ${cv.skills_summary ? `
                    <div class="cv-section" style="${styles.section}">
                        <h3 style="margin: 0 0 15px 0; font-size: ${fontSizes.sectionTitle}; font-weight: bold; color: #333; border-bottom: 2px solid ${primaryColor}; padding-bottom: 8px;">MỤC TIÊU NGHỀ NGHIỆP</h3>
                        <p style="margin: 0; line-height: 1.8; font-size: ${fontSizes.content};">${escapeHtml(cv.skills_summary)}</p>
                    </div>
                ` : ''}
                
                <!-- Học vấn -->
                ${cv.educations && cv.educations.length > 0 ? `
                    <div class="cv-section" style="${styles.section}">
                        <h3 style="margin: 0 0 15px 0; font-size: ${fontSizes.sectionTitle}; font-weight: bold; color: #333; border-bottom: 2px solid ${primaryColor}; padding-bottom: 8px;">HỌC VẤN</h3>
                        ${cv.educations.map(edu => {
                            const dateRange = formatDateRange(edu.start_date, edu.end_date);
                            return `
                                <div class="cv-item" style="${styles.item}">
                                    <h4 style="margin: 0 0 5px 0; font-size: ${fontSizes.content}; font-weight: 600; color: #333;">${escapeHtml(edu.degree || 'Bằng cấp')}${edu.major ? ` - ${escapeHtml(edu.major)}` : ''}</h4>
                                    <p style="margin: 0 0 8px 0; font-size: ${fontSizes.contact}; color: #666;">${escapeHtml(edu.school_name || '')}${dateRange ? ` - ${dateRange}` : ''}</p>
                                    ${edu.description ? `<p style="margin: 0; line-height: 1.6; font-size: ${fontSizes.content};">${escapeHtml(edu.description)}</p>` : ''}
                                </div>
                            `;
                        }).join('')}
                    </div>
                ` : ''}
                
                <!-- Kinh nghiệm làm việc -->
                ${cv.experiences && cv.experiences.length > 0 ? `
                    <div class="cv-section" style="${styles.section}">
                        <h3 style="margin: 0 0 15px 0; font-size: ${fontSizes.sectionTitle}; font-weight: bold; color: #333; border-bottom: 2px solid ${primaryColor}; padding-bottom: 8px;">KINH NGHIỆM LÀM VIỆC</h3>
                        ${cv.experiences.map(exp => {
                            const dateRange = formatDateRange(exp.start_date, exp.end_date);
                            return `
                                <div class="cv-item" style="${styles.item}">
                                    <h4 style="margin: 0 0 5px 0; font-size: ${fontSizes.content}; font-weight: 600; color: #333;">${escapeHtml(exp.position || 'Chức vụ')}</h4>
                                    <p style="margin: 0 0 8px 0; font-size: ${fontSizes.contact}; font-weight: 500; color: #666;">${escapeHtml(exp.company_name || '')}${dateRange ? ` - ${dateRange}` : ''}</p>
                                    ${exp.description ? `<p style="margin: 0; line-height: 1.8; font-size: ${fontSizes.content}; white-space: pre-line;">${escapeHtml(exp.description)}</p>` : ''}
                                </div>
                            `;
                        }).join('')}
                    </div>
                ` : ''}
                
                <!-- Kỹ năng -->
                ${cv.skills && cv.skills.length > 0 ? `
                    <div class="cv-section" style="${styles.section}">
                        <h3 style="margin: 0 0 15px 0; font-size: ${fontSizes.sectionTitle}; font-weight: bold; color: #333; border-bottom: 2px solid ${primaryColor}; padding-bottom: 8px;">KỸ NĂNG</h3>
                        <p style="margin: 0; line-height: 1.8; font-size: ${fontSizes.content};">${cv.skills.map(skill => escapeHtml(skill.skill_name || skill)).join(', ')}</p>
                    </div>
                ` : ''}
                
                <!-- Chứng chỉ -->
                ${cv.certifications && cv.certifications.length > 0 ? `
                    <div class="cv-section" style="${styles.section}">
                        <h3 style="margin: 0 0 15px 0; font-size: ${fontSizes.sectionTitle}; font-weight: bold; color: #333; border-bottom: 2px solid ${primaryColor}; padding-bottom: 8px;">CHỨNG CHỈ</h3>
                        ${cv.certifications.map(cert => `
                            <div class="cv-item" style="${styles.item}">
                                <h4 style="margin: 0 0 5px 0; font-size: ${fontSizes.content}; font-weight: 600; color: #333;">${escapeHtml(cert.cert_name || 'Chứng chỉ')}</h4>
                                <p style="margin: 0; font-size: ${fontSizes.contact}; color: #666;">${escapeHtml(cert.organization || '')}${cert.date_received ? ` - ${formatDate(cert.date_received)}` : ''}</p>
                                ${cert.description ? `<p style="margin: 5px 0 0 0; line-height: 1.6; font-size: ${fontSizes.content};">${escapeHtml(cert.description)}</p>` : ''}
                            </div>
                        `).join('')}
                    </div>
                ` : ''}
                
                <!-- Giải thưởng -->
                ${cv.awards && cv.awards.length > 0 ? `
                    <div class="cv-section" style="${styles.section}">
                        <h3 style="margin: 0 0 15px 0; font-size: ${fontSizes.sectionTitle}; font-weight: bold; color: #333; border-bottom: 2px solid ${primaryColor}; padding-bottom: 8px;">GIẢI THƯỞNG</h3>
                        ${cv.awards.map(award => `
                            <div class="cv-item" style="${styles.item}">
                                <h4 style="margin: 0 0 5px 0; font-size: ${fontSizes.content}; font-weight: 600; color: #333;">${escapeHtml(award.award_name || 'Giải thưởng')}</h4>
                                <p style="margin: 0; font-size: ${fontSizes.contact}; color: #666;">${escapeHtml(award.organization || '')}${award.date_received ? ` - ${formatDate(award.date_received)}` : ''}</p>
                                ${award.description ? `<p style="margin: 5px 0 0 0; line-height: 1.6; font-size: ${fontSizes.content};">${escapeHtml(award.description)}</p>` : ''}
                            </div>
                        `).join('')}
                    </div>
                ` : ''}
            </div>
        </div>
    `;
    
    content.innerHTML = html;
}

/**
 * Hiển thị CV với layout đã chọn
 */
function displayCV(cv) {
    const content = document.getElementById('cv-content');
    if (!content) return;

    // Lấy template settings từ CV
    const templateSettings = getTemplateSettings(cv);
    const primaryColor = colorMap[templateSettings.color] || colorMap['green'];
    const fontSize = templateSettings.fontSize || '14px';
    const fontFamily = templateSettings.font || 'Arial, sans-serif';
    const template = templateSettings.template || 'sidebar';
    
    const styles = getTemplateStyles(template, primaryColor, fontSize, fontFamily);
    const isTwoColumn = template === 'two-column' || template === 'sidebar' || template === 'split-layout' || template === 'modern-professional' || template === 'modern-pro' || template === 'professional-sidebar' || template === 'header-content';
    const isModernProfessional = template === 'modern-professional' || template === 'modern-pro' || template === 'professional-sidebar' || template === 'sidebar' || template === 'header-content';
    
    // Nếu là modern-professional, professional-sidebar, sidebar, hoặc header-content, render layout 2 cột đặc biệt
    if (isModernProfessional) {
        return displayModernProfessionalCV(cv, styles, primaryColor, fontSize, fontFamily);
    }
    
    // Nếu là simple, render layout đơn giản
    if (template === 'simple') {
        return displaySimpleCV(cv, styles, primaryColor, fontSize, fontFamily);
    }
    
    const header = cv.header || {};
    const title = cv.title || 'CV chưa đặt tên';
    const fullName = header.Full_name || 'Họ và tên';
    const avatar = header.avatar || cv.photo || null;
    
    // Render header
    let html = `
        <div class="cv-wrapper" style="${styles.wrapper}">
            <div class="cv-header" style="${styles.header}">
                ${avatar ? `
                    <div style="text-align: center; margin-bottom: 20px;">
                        <img src="${escapeHtml(avatar)}" alt="Ảnh đại diện" 
                             style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid ${primaryColor};">
                    </div>
                ` : ''}
                <h1 style="margin: 0; font-size: 2em; color: #333;">${escapeHtml(fullName)}</h1>
                <p style="margin: 10px 0 0 0; font-size: 1.2em; color: #666;">${escapeHtml(title)}</p>
                ${header.Email || header.Phone || header.address ? `
                    <div style="margin-top: 15px; font-size: 0.9em; color: #666;">
                        ${header.Email ? `<span>${escapeHtml(header.Email)}</span>` : ''}
                        ${header.Phone ? `<span style="margin-left: 15px;">${escapeHtml(header.Phone)}</span>` : ''}
                        ${header.address ? `<div class="cv-address-print" style="margin-top: 5px;">${escapeHtml(header.address)}</div>` : ''}
                    </div>
                ` : ''}
            </div>
            <div class="cv-content" style="${styles.content}">
    `;

    // Thông tin cá nhân (nếu có thông tin bổ sung)
    if (header.BirthDay || header.gender || header.Website) {
        html += `
            <div class="cv-section" style="${styles.section}">
                <h2 class="section-title" style="font-size: 1.3em; font-weight: 600; color: ${primaryColor}; margin-bottom: 15px; padding-bottom: 8px; border-bottom: 2px solid #e0e0e0;">Thông tin cá nhân</h2>
                <div class="section-content" style="color: #555; line-height: 1.6;">
                    ${header.BirthDay ? `<p><strong>Ngày sinh:</strong> ${formatDate(header.BirthDay)}</p>` : ''}
                    ${header.gender ? `<p><strong>Giới tính:</strong> ${header.gender === 'male' ? 'Nam' : header.gender === 'female' ? 'Nữ' : 'Khác'}</p>` : ''}
                    ${header.Website ? `<p><strong>Website:</strong> <a href="${escapeHtml(header.Website)}" target="_blank">${escapeHtml(header.Website)}</a></p>` : ''}
                </div>
            </div>
        `;
    }

    // Mục tiêu nghề nghiệp
    if (cv.skills_summary) {
        html += `
            <div class="cv-section" style="${styles.section}">
                <h2 class="section-title" style="font-size: 1.3em; font-weight: 600; color: ${primaryColor}; margin-bottom: 15px; padding-bottom: 8px; border-bottom: 2px solid #e0e0e0;">Mục tiêu nghề nghiệp</h2>
                <div class="section-content" style="color: #555; line-height: 1.6;">
                    <p>${escapeHtml(cv.skills_summary)}</p>
                </div>
            </div>
        `;
    }

    // Kinh nghiệm làm việc
    if (cv.experiences && cv.experiences.length > 0) {
        html += `
            <div class="cv-section" style="${styles.section}">
                <h2 class="section-title" style="font-size: 1.3em; font-weight: 600; color: ${primaryColor}; margin-bottom: 15px; padding-bottom: 8px; border-bottom: 2px solid #e0e0e0;">Kinh nghiệm làm việc</h2>
                <div class="section-content" style="color: #555; line-height: 1.6;">
        `;
        
        cv.experiences.forEach(exp => {
            const dateRange = formatDateRange(exp.start_date, exp.end_date);
            html += `
                <div class="experience-item" style="${styles.item}">
                    <h3 style="color: #333; font-size: 1.1em; margin: 0 0 5px 0;">${escapeHtml(exp.position || 'Chức vụ')}</h3>
                    <p style="color: #666; margin: 0 0 8px 0;">${escapeHtml(exp.company_name || '')} ${dateRange ? `| ${dateRange}` : ''}</p>
                    ${exp.description ? `<p style="color: #555; margin: 0;">${escapeHtml(exp.description)}</p>` : ''}
                </div>
            `;
        });
        
        html += `</div></div>`;
    }

    // Học vấn
    if (cv.educations && cv.educations.length > 0) {
        html += `
            <div class="cv-section" style="${styles.section}">
                <h2 class="section-title" style="font-size: 1.3em; font-weight: 600; color: ${primaryColor}; margin-bottom: 15px; padding-bottom: 8px; border-bottom: 2px solid #e0e0e0;">Học vấn</h2>
                <div class="section-content" style="color: #555; line-height: 1.6;">
        `;
        
        cv.educations.forEach(edu => {
            const dateRange = formatDateRange(edu.start_date, edu.end_date);
            html += `
                <div class="education-item" style="${styles.item}">
                    <h3 style="color: #333; font-size: 1.1em; margin: 0 0 5px 0;">${escapeHtml(edu.degree || 'Bằng cấp')}</h3>
                    <p style="color: #666; margin: 0 0 8px 0;">${escapeHtml(edu.school_name || '')} ${edu.major ? `- ${escapeHtml(edu.major)}` : ''} ${dateRange ? `| ${dateRange}` : ''}</p>
                    ${edu.description ? `<p style="color: #555; margin: 0;">${escapeHtml(edu.description)}</p>` : ''}
                </div>
            `;
        });
        
        html += `</div></div>`;
    }

    // Kỹ năng
    if (cv.skills && cv.skills.length > 0) {
        html += `
            <div class="cv-section" style="${styles.section}">
                <h2 class="section-title" style="font-size: 1.3em; font-weight: 600; color: ${primaryColor}; margin-bottom: 15px; padding-bottom: 8px; border-bottom: 2px solid #e0e0e0;">Kỹ năng</h2>
                <div class="section-content" style="color: #555; line-height: 1.6;">
                    <ul style="margin: 0; padding-left: 20px; display: flex; flex-wrap: wrap; gap: 10px; list-style: none;">
        `;
        
        cv.skills.forEach(skill => {
            html += `<li style="background: #e3f2fd; color: #1976d2; padding: 6px 12px; border-radius: 20px; font-size: 0.9em;">${escapeHtml(skill.skill_name || skill)}</li>`;
        });
        
        html += `</ul></div></div>`;
    }

    // Dự án
    if (cv.projects && cv.projects.length > 0) {
        html += `
            <div class="cv-section" style="${styles.section}">
                <h2 class="section-title" style="font-size: 1.3em; font-weight: 600; color: ${primaryColor}; margin-bottom: 15px; padding-bottom: 8px; border-bottom: 2px solid #e0e0e0;">Dự án</h2>
                <div class="section-content" style="color: #555; line-height: 1.6;">
        `;
        
        cv.projects.forEach(project => {
            const dateRange = formatDateRange(project.start_date, project.end_date);
            html += `
                <div class="experience-item" style="${styles.item}">
                    <h3 style="color: #333; font-size: 1.1em; margin: 0 0 5px 0;">${escapeHtml(project.project_name || 'Dự án')}</h3>
                    ${project.role ? `<p style="color: #666; margin: 0 0 8px 0;">Vai trò: ${escapeHtml(project.role)} ${dateRange ? `| ${dateRange}` : ''}</p>` : dateRange ? `<p style="color: #666; margin: 0 0 8px 0;">${dateRange}</p>` : ''}
                    ${project.technologies ? `<p style="color: ${primaryColor}; margin: 0 0 8px 0;">Công nghệ: ${escapeHtml(project.technologies)}</p>` : ''}
                    ${project.description ? `<p style="color: #555; margin: 0;">${escapeHtml(project.description)}</p>` : ''}
                </div>
            `;
        });
        
        html += `</div></div>`;
    }

    // Chứng chỉ
    if (cv.certifications && cv.certifications.length > 0) {
        html += `
            <div class="cv-section" style="${styles.section}">
                <h2 class="section-title" style="font-size: 1.3em; font-weight: 600; color: ${primaryColor}; margin-bottom: 15px; padding-bottom: 8px; border-bottom: 2px solid #e0e0e0;">Chứng chỉ</h2>
                <div class="section-content" style="color: #555; line-height: 1.6;">
        `;
        
        cv.certifications.forEach(cert => {
            html += `
                <div class="experience-item" style="${styles.item}">
                    <h3 style="color: #333; font-size: 1.1em; margin: 0 0 5px 0;">${escapeHtml(cert.cert_name || 'Chứng chỉ')}</h3>
                    ${cert.organization ? `<p style="color: #666; margin: 0 0 8px 0;">Tổ chức: ${escapeHtml(cert.organization)} ${cert.date_received ? `| ${formatDate(cert.date_received)}` : ''}</p>` : cert.date_received ? `<p style="color: #666; margin: 0 0 8px 0;">${formatDate(cert.date_received)}</p>` : ''}
                    ${cert.description ? `<p style="color: #555; margin: 0;">${escapeHtml(cert.description)}</p>` : ''}
                </div>
            `;
        });
        
        html += `</div></div>`;
    }

    // Giải thưởng
    if (cv.awards && cv.awards.length > 0) {
        html += `
            <div class="cv-section" style="${styles.section}">
                <h2 class="section-title" style="font-size: 1.3em; font-weight: 600; color: ${primaryColor}; margin-bottom: 15px; padding-bottom: 8px; border-bottom: 2px solid #e0e0e0;">Giải thưởng</h2>
                <div class="section-content" style="color: #555; line-height: 1.6;">
        `;
        
        cv.awards.forEach(award => {
            html += `
                <div class="experience-item" style="${styles.item}">
                    <h3 style="color: #333; font-size: 1.1em; margin: 0 0 5px 0;">${escapeHtml(award.award_name || 'Giải thưởng')}</h3>
                    ${award.organization ? `<p style="color: #666; margin: 0 0 8px 0;">Tổ chức: ${escapeHtml(award.organization)} ${award.date_received ? `| ${formatDate(award.date_received)}` : ''}</p>` : award.date_received ? `<p style="color: #666; margin: 0 0 8px 0;">${formatDate(award.date_received)}</p>` : ''}
                    ${award.description ? `<p style="color: #555; margin: 0;">${escapeHtml(award.description)}</p>` : ''}
                </div>
            `;
        });
        
        html += `</div></div>`;
    }

    // Hoạt động
    if (cv.activities && cv.activities.length > 0) {
        html += `
            <div class="cv-section" style="${styles.section}">
                <h2 class="section-title" style="font-size: 1.3em; font-weight: 600; color: ${primaryColor}; margin-bottom: 15px; padding-bottom: 8px; border-bottom: 2px solid #e0e0e0;">Hoạt động</h2>
                <div class="section-content" style="color: #555; line-height: 1.6;">
        `;
        
        cv.activities.forEach(activity => {
            const dateRange = formatDateRange(activity.start_date, activity.end_date);
            html += `
                <div class="experience-item" style="${styles.item}">
                    <h3 style="color: #333; font-size: 1.1em; margin: 0 0 5px 0;">${escapeHtml(activity.organization_name || 'Hoạt động')}</h3>
                    ${activity.role ? `<p style="color: #666; margin: 0 0 8px 0;">Vai trò: ${escapeHtml(activity.role)} ${dateRange ? `| ${dateRange}` : ''}</p>` : dateRange ? `<p style="color: #666; margin: 0 0 8px 0;">${dateRange}</p>` : ''}
                    ${activity.description ? `<p style="color: #555; margin: 0;">${escapeHtml(activity.description)}</p>` : ''}
                </div>
            `;
        });
        
        html += `</div></div>`;
    }

    // Người tham khảo
    if (cv.references && cv.references.length > 0) {
        html += `
            <div class="cv-section" style="${styles.section}">
                <h2 class="section-title" style="font-size: 1.3em; font-weight: 600; color: ${primaryColor}; margin-bottom: 15px; padding-bottom: 8px; border-bottom: 2px solid #e0e0e0;">Người tham khảo</h2>
                <div class="section-content" style="color: #555; line-height: 1.6;">
        `;
        
        cv.references.forEach(ref => {
            html += `
                <div class="experience-item" style="${styles.item}">
                    <h3 style="color: #333; font-size: 1.1em; margin: 0 0 5px 0;">${escapeHtml(ref.name || 'Người tham khảo')}</h3>
                    ${ref.relationship ? `<p style="color: #666; margin: 0 0 8px 0;">Quan hệ: ${escapeHtml(ref.relationship)}</p>` : ''}
                    ${ref.contact_info ? `<p style="color: #555; margin: 0;">Liên hệ: ${escapeHtml(ref.contact_info)}</p>` : ''}
                </div>
            `;
        });
        
        html += `</div></div>`;
    }

    // Sở thích
    if (cv.hobbies && cv.hobbies.length > 0) {
        html += `
            <div class="cv-section" style="${styles.section}">
                <h2 class="section-title" style="font-size: 1.3em; font-weight: 600; color: ${primaryColor}; margin-bottom: 15px; padding-bottom: 8px; border-bottom: 2px solid #e0e0e0;">Sở thích</h2>
                <div class="section-content" style="color: #555; line-height: 1.6;">
                    <ul style="margin: 0; padding-left: 20px;">
        `;
        
        cv.hobbies.forEach(hobby => {
            html += `<li>${escapeHtml(hobby.description || hobby)}</li>`;
        });
        
        html += `</ul></div></div>`;
    }

    // Thông tin bổ sung
    if (cv.extraInfos && cv.extraInfos.length > 0) {
        html += `
            <div class="cv-section" style="${styles.section}">
                <h2 class="section-title" style="font-size: 1.3em; font-weight: 600; color: ${primaryColor}; margin-bottom: 15px; padding-bottom: 8px; border-bottom: 2px solid #e0e0e0;">Thông tin bổ sung</h2>
                <div class="section-content" style="color: #555; line-height: 1.6;">
        `;
        
        cv.extraInfos.forEach(extra => {
            html += `
                <div class="experience-item" style="${styles.item}">
                    <p style="color: #555; margin: 0;">${escapeHtml(extra.content || extra)}</p>
                </div>
            `;
        });
        
        html += `</div></div>`;
    }

    html += `</div></div>`;
    content.innerHTML = html;
}

/**
 * Chỉnh sửa CV hiện tại
 */
function editCurrentCV() {
    if (currentCVId) {
        window.location.href = `${CV_BUILDER_ROUTE}?id=${currentCVId}`;
    }
}

/**
 * Hiển thị lỗi
 */
function showError(message) {
    const content = document.getElementById('cv-content');
    if (!content) return;

    content.innerHTML = `
        <div class="error-message">
            <i class="fas fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 20px; display: block;"></i>
            <h3>Có lỗi xảy ra</h3>
            <p>${escapeHtml(message)}</p>
            <a href="${CV_INDEX_ROUTE}" class="btn-back" style="margin-top: 20px; display: inline-block;">
                <i class="fas fa-arrow-left"></i>
                Quay lại danh sách CV
            </a>
        </div>
    `;
    hideLoading();
}

/**
 * Ẩn loading overlay
 */
function hideLoading() {
    const loading = document.getElementById('loading');
    if (loading) {
        loading.style.display = 'none';
    }
}

// Export functions để có thể gọi từ global scope
window.editCurrentCV = editCurrentCV;

// Khởi tạo khi DOM ready
document.addEventListener('DOMContentLoaded', function() {
    loadCV();
});

