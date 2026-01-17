<!DOCTYPE html>
<html lang="vi">
<head>
    @include('layouts.head')
    <link href="{{ asset('css/shared/header.css') }}" rel="stylesheet">
    <link href="{{ asset('css/shared/footer.css') }}" rel="stylesheet">
    <link href="{{ asset('css/roles/candidate/career-development.css') }}" rel="stylesheet">
    <title>Phát triển nghề nghiệp - WebCV</title>
</head>
<body>
    @include('layouts.header')
    <div class="container">
        
        <!-- Tabs -->
        <div class="tabs">
            <button class="tab active" onclick="switchTab('courses')">Khóa học</button>
            <button class="tab" onclick="switchTab('webinars')">Webinar</button>
            <button class="tab" onclick="switchTab('advice')">Tư vấn</button>
            <button class="tab" onclick="switchTab('skills')">Kỹ năng</button>
            <button class="tab" onclick="switchTab('certifications')">Chứng chỉ</button>
        </div>
        
        <!-- Tab nội dung -->
        <div id="courses-tab" class="tab-content active">
            <div class="courses-grid" id="courses-list">
                <div class="course-card">
                    <div class="course-image">💻</div>
                    <div class="course-content">
                        <h3 class="course-title">JavaScript Advanced</h3>
                        <p class="course-instructor">Giảng viên: Nguyễn Văn A</p>
                        <p class="course-description">Học JavaScript nâng cao, ES6+, async/await, và các framework hiện đại.</p>
                        <div class="course-meta">
                            <span class="course-duration">8 tuần</span>
                            <div class="course-rating">⭐⭐⭐⭐⭐ (4.8)</div>
                        </div>
                        <div class="course-actions">
                            <a href="#" class="btn btn-primary">Bắt đầu học</a>
                            <a href="#" class="btn btn-outline">Xem chi tiết</a>
                        </div>
                    </div>
                </div>
                
                <div class="course-card">
                    <div class="course-image">🎨</div>
                    <div class="course-content">
                        <h3 class="course-title">UI/UX Design</h3>
                        <p class="course-instructor">Giảng viên: Trần Thị B</p>
                        <p class="course-description">Thiết kế giao diện người dùng và trải nghiệm người dùng chuyên nghiệp.</p>
                        <div class="course-meta">
                            <span class="course-duration">6 tuần</span>
                            <div class="course-rating">⭐⭐⭐⭐⭐ (4.9)</div>
                        </div>
                        <div class="course-actions">
                            <a href="#" class="btn btn-primary">Bắt đầu học</a>
                            <a href="#" class="btn btn-outline">Xem chi tiết</a>
                        </div>
                    </div>
                </div>
                
                <div class="course-card">
                    <div class="course-image">☁️</div>
                    <div class="course-content">
                        <h3 class="course-title">Cloud Computing</h3>
                        <p class="course-instructor">Giảng viên: Lê Văn C</p>
                        <p class="course-description">Học về AWS, Azure, Google Cloud và các dịch vụ cloud hiện đại.</p>
                        <div class="course-meta">
                            <span class="course-duration">10 tuần</span>
                            <div class="course-rating">⭐⭐⭐⭐⭐ (4.7)</div>
                        </div>
                        <div class="course-actions">
                            <a href="#" class="btn btn-primary">Bắt đầu học</a>
                            <a href="#" class="btn btn-outline">Xem chi tiết</a>
                        </div>
                    </div>
                </div>
                
                <div class="course-card">
                    <div class="course-image">🤖</div>
                    <div class="course-content">
                        <h3 class="course-title">AI & Machine Learning</h3>
                        <p class="course-instructor">Giảng viên: Phạm Thị D</p>
                        <p class="course-description">Trí tuệ nhân tạo và học máy từ cơ bản đến nâng cao.</p>
                        <div class="course-meta">
                            <span class="course-duration">12 tuần</span>
                            <div class="course-rating">⭐⭐⭐⭐⭐ (4.8)</div>
                        </div>
                        <div class="course-actions">
                            <a href="#" class="btn btn-primary">Bắt đầu học</a>
                            <a href="#" class="btn btn-outline">Xem chi tiết</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div id="webinars-tab" class="tab-content">
            <div class="webinars-section">
                <h3>📺 Webinar sắp tới</h3>
                
                <div class="webinar-item">
                    <div class="webinar-date">
                        <div class="webinar-date-day">15</div>
                        <div class="webinar-date-month">TH12</div>
                    </div>
                    <div class="webinar-content">
                        <h4 class="webinar-title">Xu hướng công nghệ 2024</h4>
                        <p class="webinar-speaker">Diễn giả: TS. Nguyễn Văn E</p>
                        <p class="webinar-description">Tìm hiểu về các xu hướng công nghệ mới nhất và cơ hội nghề nghiệp trong năm 2024.</p>
                    </div>
                    <div class="webinar-actions">
                        <a href="#" class="btn btn-success">Đăng ký</a>
                        <a href="#" class="btn btn-outline">Chi tiết</a>
                    </div>
                </div>
                
                <div class="webinar-item">
                    <div class="webinar-date">
                        <div class="webinar-date-day">22</div>
                        <div class="webinar-date-month">TH12</div>
                    </div>
                    <div class="webinar-content">
                        <h4 class="webinar-title">Kỹ năng phỏng vấn IT</h4>
                        <p class="webinar-speaker">Diễn giả: Anh Trần Văn F</p>
                        <p class="webinar-description">Chia sẻ kinh nghiệm và mẹo phỏng vấn thành công trong lĩnh vực IT.</p>
                    </div>
                    <div class="webinar-actions">
                        <a href="#" class="btn btn-success">Đăng ký</a>
                        <a href="#" class="btn btn-outline">Chi tiết</a>
                    </div>
                </div>
                
                <div class="webinar-item">
                    <div class="webinar-date">
                        <div class="webinar-date-day">29</div>
                        <div class="webinar-date-month">TH12</div>
                    </div>
                    <div class="webinar-content">
                        <h4 class="webinar-title">Startup và khởi nghiệp</h4>
                        <p class="webinar-speaker">Diễn giả: Chị Lê Thị G</p>
                        <p class="webinar-description">Hành trình khởi nghiệp và những bài học từ các startup thành công.</p>
                    </div>
                    <div class="webinar-actions">
                        <a href="#" class="btn btn-success">Đăng ký</a>
                        <a href="#" class="btn btn-outline">Chi tiết</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div id="advice-tab" class="tab-content">
            <div class="career-advice-section">
                <h3>💡 Tư vấn nghề nghiệp</h3>
                
                <div class="advice-grid">
                    <div class="advice-card">
                        <h4 class="advice-title">Mẹo phỏng vấn</h4>
                        <p class="advice-content">Chuẩn bị kỹ càng, nghiên cứu công ty, và thực hành trả lời các câu hỏi thường gặp.</p>
                    </div>
                    
                    <div class="advice-card">
                        <h4 class="advice-title">Viết CV hiệu quả</h4>
                        <p class="advice-content">Tập trung vào thành tích cụ thể, sử dụng từ khóa phù hợp, và định dạng rõ ràng.</p>
                    </div>
                    
                    <div class="advice-card">
                        <h4 class="advice-title">Networking</h4>
                        <p class="advice-content">Xây dựng mạng lưới quan hệ chuyên nghiệp thông qua LinkedIn và các sự kiện.</p>
                    </div>
                    
                    <div class="advice-card">
                        <h4 class="advice-title">Phát triển kỹ năng</h4>
                        <p class="advice-content">Học liên tục, cập nhật kiến thức mới, và thực hành thường xuyên.</p>
                    </div>
                    
                    <div class="advice-card">
                        <h4 class="advice-title">Quản lý thời gian</h4>
                        <p class="advice-content">Sắp xếp ưu tiên, sử dụng công cụ quản lý, và cân bằng công việc-cuộc sống.</p>
                    </div>
                    
                    <div class="advice-card">
                        <h4 class="advice-title">Thương lượng lương</h4>
                        <p class="advice-content">Nghiên cứu thị trường, chuẩn bị lý lẽ, và đàm phán một cách chuyên nghiệp.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div id="skills-tab" class="tab-content">
            <div class="skills-section">
                <h3>🎯 Kỹ năng cần phát triển</h3>
                
                <div class="skills-grid">
                    <div class="skill-item">
                        <div class="skill-icon">💻</div>
                        <div class="skill-name">JavaScript</div>
                        <div class="skill-level">Trung bình</div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 60%;"></div>
                        </div>
                    </div>
                    
                    <div class="skill-item">
                        <div class="skill-icon">⚛️</div>
                        <div class="skill-name">React</div>
                        <div class="skill-level">Cơ bản</div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 40%;"></div>
                        </div>
                    </div>
                    
                    <div class="skill-item">
                        <div class="skill-icon">🐍</div>
                        <div class="skill-name">Python</div>
                        <div class="skill-level">Trung bình</div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 70%;"></div>
                        </div>
                    </div>
                    
                    <div class="skill-item">
                        <div class="skill-icon">☁️</div>
                        <div class="skill-name">AWS</div>
                        <div class="skill-level">Cơ bản</div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 30%;"></div>
                        </div>
                    </div>
                    
                    <div class="skill-item">
                        <div class="skill-icon">🗄️</div>
                        <div class="skill-name">Database</div>
                        <div class="skill-level">Khá</div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 80%;"></div>
                        </div>
                    </div>
                    
                    <div class="skill-item">
                        <div class="skill-icon">🔧</div>
                        <div class="skill-name">DevOps</div>
                        <div class="skill-level">Cơ bản</div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 25%;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div id="certifications-tab" class="tab-content">
            <div class="certifications-section">
                <h3>🏆 Chứng chỉ khuyến nghị</h3>
                
                <div class="cert-grid">
                    <div class="cert-card">
                        <div class="cert-icon">☁️</div>
                        <h4 class="cert-name">AWS Certified Developer</h4>
                        <p class="cert-provider">Amazon Web Services</p>
                        <div class="cert-status available">Có sẵn</div>
                    </div>
                    
                    <div class="cert-card">
                        <div class="cert-icon">🐍</div>
                        <h4 class="cert-name">Python Programming</h4>
                        <p class="cert-provider">Python Institute</p>
                        <div class="cert-status recommended">Khuyến nghị</div>
                    </div>
                    
                    <div class="cert-card">
                        <div class="cert-icon">⚛️</div>
                        <h4 class="cert-name">React Developer</h4>
                        <p class="cert-provider">Meta</p>
                        <div class="cert-status available">Có sẵn</div>
                    </div>
                    
                    <div class="cert-card">
                        <div class="cert-icon">🔒</div>
                        <h4 class="cert-name">Cybersecurity</h4>
                        <p class="cert-provider">CompTIA</p>
                        <div class="cert-status recommended">Khuyến nghị</div>
                    </div>
                    
                    <div class="cert-card">
                        <div class="cert-icon">📊</div>
                        <h4 class="cert-name">Data Science</h4>
                        <p class="cert-provider">IBM</p>
                        <div class="cert-status available">Có sẵn</div>
                    </div>
                    
                    <div class="cert-card">
                        <div class="cert-icon">🎨</div>
                        <h4 class="cert-name">UI/UX Design</h4>
                        <p class="cert-provider">Google</p>
                        <div class="cert-status recommended">Khuyến nghị</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/candidate/career-development.js') }}"></script>
    @include('layouts.footer')
</body>
</html>
