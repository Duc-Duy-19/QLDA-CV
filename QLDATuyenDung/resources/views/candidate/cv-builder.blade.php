<!DOCTYPE html>
<html lang="vi">
<head>
    @include('layouts.head')
    <title>Tạo CV - WebCV</title>
    <link href="{{ asset('css/shared/header.css') }}" rel="stylesheet">
    <link href="{{ asset('css/shared/footer.css') }}" rel="stylesheet">
    <link href="{{ asset('css/roles/candidate/cv.css') }}" rel="stylesheet">
</head>
<body>
    @include('layouts.header')
            

    <div class="cv-builder-container">
        <!-- Templates Sidebar -->
        <aside class="templates-sidebar">
            <h3>Mẫu CV</h3>
            <!-- LAYOUT CƠ BẢN -->
            <div class="template-category">
                <h4>LAYOUT CƠ BẢN</h4>
                <div class="template-grid">
                    <div class="template-item active" onclick="selectTemplate('simple')">
                        <div class="template-preview">
                            <div style="background: white; padding: 8px; font-size: 6px; border: 1px solid #ddd; border-radius: 4px;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                                    <div style="width: 25px; height: 25px; border-radius: 50%; background: #e0e0e0; border: 1px solid #ccc; flex-shrink: 0;"></div>
                                    <div style="flex: 1;">
                                        <div style="font-weight: bold; font-size: 7px; margin-bottom: 2px; color: #333;">TÊN</div>
                                        <div style="font-size: 5px; color: #666;">Chức danh</div>
                                    </div>
                                </div>
                                <div style="border-top: 1px solid #eee; padding-top: 4px; font-size: 5px; color: #333;">Thông tin liên hệ</div>
                            </div>
                        </div>
                        <div class="template-name">Đơn giản</div>
                    </div>
                </div>
            </div>

            <!-- LAYOUT CHUYÊN NGHIỆP -->
            <div class="template-category">
                <h4>LAYOUT CHUYÊN NGHIỆP</h4>
                <div class="template-grid">
                    <div class="template-item" onclick="selectTemplate('modern-professional')">
                        <div class="template-preview">
                            <div style="display: flex; height: 45px; font-size: 6px; border: 1px solid #ddd; border-radius: 4px; overflow: hidden;">
                                <div style="background: #2c3e50; padding: 5px; width: 35%; color: white; display: flex; flex-direction: column; justify-content: center; align-items: center; border-right: 2px solid #1a252f;">
                                    <div style="font-weight: bold; font-size: 7px; margin-bottom: 2px;">TÊN</div>
                                    <div style="font-size: 5px; opacity: 0.9;">Chức danh</div>
                                </div>
                                <div style="background: white; padding: 5px; width: 65%; display: flex; flex-direction: column; justify-content: center;">
                                    <div style="font-size: 5px; margin-bottom: 2px; color: #2c3e50; font-weight: 500;">Thông tin</div>
                                    <div style="font-size: 5px; color: #666;">Nội dung</div>
                                </div>
                            </div>
                        </div>
                        <div class="template-name">Modern Pro</div>
                    </div>
                    
                    <div class="template-item" onclick="selectTemplate('professional-sidebar')">
                        <div class="template-preview">
                            <div style="display: flex; height: 45px; font-size: 6px; border: 1px solid #ddd; border-radius: 4px; overflow: hidden;">
                                <div style="background: #34495e; padding: 5px; width: 30%; color: white; display: flex; flex-direction: column; justify-content: center; align-items: center; border-right: 2px solid #2c3e50;">
                                    <div style="font-weight: bold; font-size: 7px; text-transform: uppercase; margin-bottom: 2px;">TÊN</div>
                                    <div style="font-size: 5px; opacity: 0.8;">Sidebar</div>
                                </div>
                                <div style="background: white; padding: 5px; width: 70%; display: flex; flex-direction: column; justify-content: center;">
                                    <div style="font-size: 5px; margin-bottom: 2px; font-weight: bold; color: #34495e;">Section</div>
                                    <div style="font-size: 5px; color: #666;">Nội dung chính</div>
                                </div>
                            </div>
                        </div>
                        <div class="template-name">Professional Sidebar</div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- CV Preview -->
        <main class="cv-preview">
            <div class="cv-preview-header">
                <h3>CV của bạn</h3>
                <div class="preview-actions">
                    {{-- <button onclick="loadFromProfile()" class="btn btn-info">
                        <i class="fas fa-user"></i> Tải từ hồ sơ
                    </button> --}}
                    <button onclick="toggleEditMode()" class="btn btn-secondary" id="edit-mode-btn">
                        <i class="fas fa-edit"></i> Chế độ chỉnh sửa
                    </button>
                    <button onclick="saveCV()" class="btn btn-success">
                        <i class="fas fa-save"></i> Lưu CV
                    </button>
                    <div class="export-dropdown">
                        <button class="btn btn-outline-primary dropdown-toggle" onclick="toggleExportDropdown()">
                            <i class="fas fa-download"></i> Xuất CV
                        </button>
                        <div class="export-menu" id="export-menu">
                            <button onclick="exportCV()" class="export-option">
                                <i class="fas fa-file-pdf"></i> Xuất PDF
                            </button>
                            {{-- <button onclick="exportToWord()" class="export-option">
                                <i class="fas fa-file-word"></i> Xuất Word
                            </button>
                            <button onclick="exportToHTML()" class="export-option">
                                <i class="fas fa-file-code"></i> Xuất HTML
                            </button> --}}
                        </div>
                    </div>
                </div>
            </div>
            <div id="cv-template" class="cv-template">
                    <!-- CV content will be rendered here -->
            </div>
        </main>

        <!-- Form Input Sidebar -->
        <aside class="form-input-sidebar">
            <div class="sidebar-tabs">
                <button class="tab-btn active" onclick="showFormTab('input')" id="tab-input">
                    <i class="fas fa-edit"></i> Nhập liệu
                </button>
                <button class="tab-btn" onclick="showFormTab('customize')" id="tab-customize">
                    <i class="fas fa-palette"></i> Tùy chỉnh
                </button>
            </div>

            <!-- Form Input Tab -->
            <div class="form-tab-content active" id="form-input-tab">
                <h3>Nhập thông tin CV</h3>
                <form id="cv-form" onsubmit="handleSubmitCV(event)">
                    <!-- Basic Info -->
                    <div class="form-section">
                        <h4 class="section-title">Thông tin cơ bản</h4>
                        <div class="form-group">
                            <label>Tiêu đề CV</label>
                            <input type="text" id="cv-title" placeholder="VD: CV Lê Quang Dũng" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Mục tiêu nghề nghiệp</label>
                            <textarea id="cv-objective" rows="4" placeholder="Mô tả mục tiêu nghề nghiệp của bạn..." class="form-control"></textarea>
                        </div>
                    </div>

                    <!-- Header Info -->
                    <div class="form-section">
                        <h4 class="section-title">Thông tin cá nhân</h4>
                        <div class="form-group">
                            <label>Họ và tên</label>
                            <input type="text" id="header-full-name" placeholder="Họ và tên đầy đủ" class="form-control">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Ngày sinh</label>
                                <input type="date" id="header-birthday" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Giới tính</label>
                                <select id="header-gender" class="form-control">
                                    <option value="">Chọn giới tính</option>
                                    <option value="male">Nam</option>
                                    <option value="female">Nữ</option>
                                    <option value="other">Khác</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Số điện thoại</label>
                            <input type="text" id="header-phone" placeholder="0123456789" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" id="header-email" placeholder="email@example.com" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Website</label>
                            <input type="url" id="header-website" placeholder="linkedin.com/in/profile" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Địa chỉ</label>
                            <input type="text" id="header-address" placeholder="Quận/Huyện, Tỉnh/Thành phố" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Ảnh đại diện</label>
                            <input type="file" id="header-avatar-file" accept="image/jpeg,image/jpg,image/png,image/gif" class="form-control" onchange="handleHeaderAvatarUpload(event)">
                            <input type="hidden" id="header-avatar" value="">
                            <small class="form-text text-muted" style="font-size: 12px; color: #6c757d; margin-top: 5px;">
                                Chọn file hình ảnh
                            </small>
                            <div id="header-avatar-preview-container" style="margin-top: 10px; display: none;">
                                <img id="header-avatar-preview" src="" alt="Preview" style="max-width: 150px; max-height: 150px; border-radius: 8px; border: 1px solid #ddd;">
                            </div>
                        </div>
                    </div>

                    <!-- Education -->
                    <div class="form-section">
                        <h4 class="section-title">Học vấn</h4>
                        <div id="educations-container">
                            <!-- Education items will be added here -->
                        </div>
                        <button type="button" class="btn-add-item" onclick="addEducation()">
                            <i class="fas fa-plus"></i> Thêm học vấn
                        </button>
                    </div>

                    <!-- Experience -->
                    <div class="form-section">
                        <h4 class="section-title">Kinh nghiệm làm việc</h4>
                        <div id="experiences-container">
                            <!-- Experience items will be added here -->
                        </div>
                        <button type="button" class="btn-add-item" onclick="addExperience()">
                            <i class="fas fa-plus"></i> Thêm kinh nghiệm
                        </button>
                    </div>

                    <!-- Activity -->
                    <div class="form-section">
                        <h4 class="section-title">Hoạt động</h4>
                        <div id="activities-container">
                            <!-- Activity items will be added here -->
                        </div>
                        <button type="button" class="btn-add-item" onclick="addActivity()">
                            <i class="fas fa-plus"></i> Thêm hoạt động
                        </button>
                    </div>

                    <!-- Certification -->
                    <div class="form-section">
                        <h4 class="section-title">Chứng chỉ</h4>
                        <div id="certifications-container">
                            <!-- Certification items will be added here -->
                        </div>
                        <button type="button" class="btn-add-item" onclick="addCertification()">
                            <i class="fas fa-plus"></i> Thêm chứng chỉ
                        </button>
                    </div>

                    <!-- Award -->
                    <div class="form-section">
                        <h4 class="section-title">Giải thưởng</h4>
                        <div id="awards-container">
                            <!-- Award items will be added here -->
                        </div>
                        <button type="button" class="btn-add-item" onclick="addAward()">
                            <i class="fas fa-plus"></i> Thêm giải thưởng
                        </button>
                    </div>

                    <!-- Skill -->
                    <div class="form-section">
                        <h4 class="section-title">Kỹ năng</h4>
                        <div id="skills-container">
                            <!-- Skill items will be added here -->
                        </div>
                        <button type="button" class="btn-add-item" onclick="addSkill()">
                            <i class="fas fa-plus"></i> Thêm kỹ năng
                        </button>
                    </div>

                    <!-- Reference -->
                    <div class="form-section">
                        <h4 class="section-title">Người tham khảo</h4>
                        <div id="references-container">
                            <!-- Reference items will be added here -->
                        </div>
                        <button type="button" class="btn-add-item" onclick="addReference()">
                            <i class="fas fa-plus"></i> Thêm người tham khảo
                        </button>
                    </div>

                    <!-- Project -->
                    <div class="form-section">
                        <h4 class="section-title">Dự án</h4>
                        <div id="projects-container">
                            <!-- Project items will be added here -->
                        </div>
                        <button type="button" class="btn-add-item" onclick="addProject()">
                            <i class="fas fa-plus"></i> Thêm dự án
                        </button>
                    </div>

                    <!-- Hobby -->
                    <div class="form-section">
                        <h4 class="section-title">Sở thích</h4>
                        <div id="hobbies-container">
                            <!-- Hobby items will be added here -->
                        </div>
                        <button type="button" class="btn-add-item" onclick="addHobby()">
                            <i class="fas fa-plus"></i> Thêm sở thích
                        </button>
                    </div>

                    <!-- Extrainfo -->
                    <div class="form-section">
                        <h4 class="section-title">Thông tin thêm</h4>
                        <div id="extrainfos-container">
                            <!-- Extrainfo items will be added here -->
                        </div>
                        <button type="button" class="btn-add-item" onclick="addExtrainfo()">
                            <i class="fas fa-plus"></i> Thêm thông tin
                        </button>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-success btn-block">
                            <i class="fas fa-save"></i> Lưu CV
                        </button>
                        <button type="button" class="btn btn-secondary btn-block" onclick="previewCV()">
                            <i class="fas fa-eye"></i> Xem trước
                        </button>
                    </div>
                </form>
            </div>

            <!-- Customize Tab -->
            <div class="form-tab-content" id="form-customize-tab">
                <h3>Tùy chỉnh</h3>
                
                <!-- Color Options -->
                <div class="customization-section">
                    <h4>Màu chủ đạo:</h4>
                    <div class="color-grid">
                        <div class="color-option active" onclick="selectColor('green')" style="background: #28a745;" title="Xanh lá"></div>
                        <div class="color-option" onclick="selectColor('blue')" style="background: #007bff;" title="Xanh dương"></div>
                        <div class="color-option" onclick="selectColor('purple')" style="background: #6f42c1;" title="Tím"></div>
                        <div class="color-option" onclick="selectColor('red')" style="background: #dc3545;" title="Đỏ"></div>
                        <div class="color-option" onclick="selectColor('orange')" style="background: #fd7e14;" title="Cam"></div>
                        <div class="color-option" onclick="selectColor('teal')" style="background: #20c997;" title="Xanh ngọc"></div>
                    </div>
                </div>

                <!-- Font Options -->
                <div class="customization-section">
                    <h4>Font chữ</h4>
                    <div class="font-options">
                        <div class="font-option active" onclick="selectFont('Arial')" style="font-family: Arial;">Arial</div>
                        <div class="font-option" onclick="selectFont('Calibri')" style="font-family: Calibri;">Calibri</div>
                        <div class="font-option" onclick="selectFont('Georgia')" style="font-family: Georgia;">Georgia</div>
                        <div class="font-option" onclick="selectFont('Times')" style="font-family: Times;">Times</div>
                    </div>
                </div>

                <!-- Font Size -->
                <div class="customization-section">
                    <h4>Cỡ chữ</h4>
                    <select id="font-size" onchange="updateFontSize()" class="form-control">
                        <option value="12">12px</option>
                        <option value="14" selected>14px</option>
                        <option value="16">16px</option>
                        <option value="18">18px</option>
                    </select>
                </div>
            </div>
        </aside>
    </div>

    @include('layouts.footer')
    <script src="{{ asset('js/candidate/cv-builder.js') }}"></script>
</body>
</html>
