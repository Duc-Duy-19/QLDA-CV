<!DOCTYPE html>
<html lang="vi">
<head>
    @include('layouts.head')
    <link rel="stylesheet" href="{{ asset('css/shared/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/shared/footer.css') }}">
    <!-- Load homepage styles first, then page-specific overrides so we get the same base styles but keep category tweaks -->
    <link rel="stylesheet" href="{{ asset('css/pages/home.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components/top-employers.css') }}">
    <link rel="stylesheet" href="{{ asset('css/roles/candidate/category-jobs.css') }}">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>{{ $category }} - WebCV</title>
</head>
<body>
    @include('layouts.header')

    <!-- Main Content -->
    <main class="main">
        <!-- Page Header -->
        <section class="page-header">
            <div class="container-inner">
                <div class="page-header-content">
                    <h1>{{ $decodedCategory ?? $category }}</h1>
                    <p>Khám phá các cơ hội việc làm trong lĩnh vực {{ $decodedCategory ?? $category }}</p>
                    <div class="breadcrumb">
                        <a href="{{ route('home') }}">Trang chủ</a>
                        <span>/</span>
                        <a href="{{ route('job-categories') }}">Danh mục việc làm</a>
                        <span>/</span>
                        <span>{{ $decodedCategory ?? $category }}</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Jobs Section -->
        <section class="jobs-section">
            <div class="container-inner">
                <!-- Search and Filter -->
                <div class="search-filter-section">
                    <div class="search-bar">
                        <input type="text" id="search-input" placeholder="Tìm kiếm trong {{ $decodedCategory ?? $category }}..." class="search-input">
                        <button class="search-btn" onclick="searchJobs()">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    
                    <div class="filter-options">
                        <select id="location-filter" class="filter-select">
                            <option value="">Tất cả địa điểm</option>
                            <option value="Hà Nội">Hà Nội</option>
                            <option value="TP. Hồ Chí Minh">TP. Hồ Chí Minh</option>
                            <option value="Đà Nẵng">Đà Nẵng</option>
                        </select>
                        
                        <div class="salary-filter-group">
                            <button type="button" class="filter-toggle-btn" onclick="toggleSalaryFilter()">
                                <i class="fas fa-dollar-sign"></i>
                                <span id="salary-filter-text">Mức lương</span>
                                <i class="fas fa-chevron-down" id="salary-chevron"></i>
                            </button>
                            
                            <div id="salary-filter-panel" class="salary-filter-panel" style="display: none;">
                                <div class="salary-filter-header">
                                    <h4>Mức lương (tháng)</h4>
                                </div>
                                
                                <div class="salary-range-container">
                                    <div class="salary-range-slider">
                                        <div class="salary-track">
                                            <div class="salary-progress" id="salary-progress"></div>
                                            <input type="range" id="salary-slider" class="salary-slider" min="0" max="50" value="0" step="1">
                                        </div>
                                    </div>
                                    
                                    <div class="salary-inputs">
                                        <div class="salary-input-group">
                                            <label>MỨC LƯƠNG MONG MUỐN</label>
                                            <input type="number" id="salary-value" class="salary-input" min="0" max="50" value="0">
                                            <span class="salary-unit">triệu</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="salary-filter-actions">
                                    <button type="button" class="apply-salary-btn" onclick="applySalaryFilter()">Áp dụng</button>
                                    <button type="button" class="clear-salary-btn" onclick="clearSalaryFilter()">
                                        <i class="fas fa-trash"></i>
                                        Xóa lọc
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Jobs Count -->
                <div class="jobs-count">
                    <span id="jobs-count">Đang tải...</span>
                </div>

                <!-- Jobs List -->
                <div class="jobs-list" id="jobs-list">
                    <!-- Jobs will be loaded here -->
                </div>

                <!-- Popup to show job details on hover (same as homepage) -->
                <div id="job-detail-popup" class="job-detail-popup"></div>

                <!-- Pagination -->
                <div class="pagination" id="pagination">
                    <!-- Pagination will be generated here -->
                </div>
            </div>
        </section>
    </main>

    @include('layouts.footer')

    @vite(['resources/js/app.js'])
    <script>
        // Pass category from Blade to JavaScript
        window.CATEGORY = '{{ $decodedCategory ?? $category }}';
    </script>
    <script src="{{ asset('js/candidate/category-jobs.js') }}"></script>
</body>
</html>
