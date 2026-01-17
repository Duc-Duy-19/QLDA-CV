<!DOCTYPE html>
<html lang="vi">
<head>
    @include('layouts.head')
    <link href="{{ asset('css/shared/header.css') }}" rel="stylesheet">
    <link href="{{ asset('css/shared/footer.css') }}" rel="stylesheet">
    <link href="{{ asset('css/roles/candidate/job-search.css') }}" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>Tìm việc làm - WebCV</title>
</head>
<body>
    @include('layouts.header')
    
    <div class="container">
        <!-- Search Section -->
        <div class="search-section">
            <div class="search-header">
                <h2>Tìm kiếm việc làm phù hợp</h2>
                <p>Khám phá hàng nghìn cơ hội việc làm hấp dẫn</p>
            </div>
            
            <form class="search-form" onsubmit="searchJobs(event)">
                <input type="text" id="search-input" placeholder="Nhập từ khóa, vị trí, công ty..." class="search-input">
                <select id="location-select" class="location-select">
                        <option value="">Tất cả địa điểm</option>
                        <option value="hanoi">Hà Nội</option>
                        <option value="hcm">TP. Hồ Chí Minh</option>
                        <option value="danang">Đà Nẵng</option>
                    <option value="other">Khác</option>
                    </select>
                <select id="experience-select" class="experience-select">
                    <option value="">Tất cả kinh nghiệm</option>
                    <option value="0">Chưa có kinh nghiệm</option>
                    <option value="1">1-2 năm</option>
                    <option value="3">3-5 năm</option>
                    <option value="5">5+ năm</option>
                    </select>
                <button type="submit" class="search-btn">
                    <i class="fas fa-search"></i> Tìm kiếm
                </button>
            </form>
        </div>

        <!-- Filter Section -->
            <div class="filter-section">
            <div class="filter-row">
                <div class="filter-group">
                    <label>Ngành nghề</label>
                    <select id="industry-filter">
                        <option value="">Tất cả ngành</option>
                        <option value="it">Công nghệ thông tin</option>
                        <option value="marketing">Marketing</option>
                        <option value="sales">Kinh doanh</option>
                        <option value="hr">Nhân sự</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Mức lương</label>
                    <select id="salary-filter">
                        <option value="">Tất cả mức lương</option>
                        <option value="0-5">Dưới 5 triệu</option>
                        <option value="5-10">5-10 triệu</option>
                        <option value="10-20">10-20 triệu</option>
                        <option value="20+">Trên 20 triệu</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Loại hình</label>
                    <select id="type-filter">
                        <option value="">Tất cả</option>
                        <option value="fulltime">Toàn thời gian</option>
                        <option value="parttime">Bán thời gian</option>
                        <option value="contract">Hợp đồng</option>
                        <option value="intern">Thực tập</option>
                    </select>
                </div>
            </div>
                </div>

        <!-- Jobs Grid -->
        <div class="jobs-grid" id="jobs-grid">
            <!-- Jobs will be loaded here -->
            </div>

        <!-- Pagination -->
        <div class="pagination" id="pagination">
            <!-- Pagination will be generated here -->
            </div>
    </div>

    <script src="{{ asset('js/candidate/job-search.js') }}"></script>
    
    @include('layouts.footer')
</body>
</html>
