<!DOCTYPE html>
<html lang="vi">
<head>
    @include('layouts.head')
    <title>Nhà tuyển dụng hàng đầu - WebCV</title>
    <link rel="stylesheet" href="{{ asset('css/shared/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/shared/footer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/companies.css') }}">
</head>
<body>
    
    @include('layouts.header')

    <!-- Main Content -->
    <main class="main">
<div class="companies-page">
    <div class="container-inner">
        <!-- Header Section -->
        <div class="companies-header">
            <h1>Nhà tuyển dụng hàng đầu</h1>
            <p>Tìm kiếm và khám phá các công ty hàng đầu đang tuyển dụng</p>
        </div>

        <!-- Search Section -->
        <div class="search-section">
            <div class="search-container">
                <div class="search-box">
                    <input type="text" id="company-search" placeholder="Tìm công ty..." class="search-input">
                    <button class="search-btn" onclick="searchCompanies()">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="companies-content">
            <!-- Filters Sidebar -->
            <div class="filters-sidebar">
                <div class="filter-section">
                    <h3>Nơi làm việc</h3>
                    <div class="filter-options">
                        <input type="text" id="location-input" class="form-control" placeholder="Nhập địa điểm (ví dụ: Hà Nội, TP. Hồ Chí Minh)">
                        <a href="#" class="view-all-link" id="clear-location" style="display:block;margin-top:8px;">Tất cả</a>
                    </div>
                </div>

                <!-- Removed company size filter per request (filtering now only by location/search) -->
            </div>

            <!-- Companies List -->
            <div class="companies-main">
                <div class="companies-results">
                    <div class="companies-grid" id="companies-grid">
                        <!-- Companies will be loaded via JavaScript from JSON Server -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    </main>

    <!-- Company Detail Modal -->
    <div id="companyDetailModal" class="company-modal" style="display:none; position:fixed; inset:0; z-index:1050;">
        <div class="company-modal-backdrop" style="position:absolute; inset:0; background:rgba(0,0,0,0.5);"></div>
        <div class="company-modal-dialog" style="position:relative; max-width:900px; margin:60px auto; background:#fff; border-radius:8px; overflow:hidden; box-shadow:0 10px 40px rgba(0,0,0,0.3);">
            <div style="padding:20px; display:flex; gap:20px; align-items:flex-start;">
                <div style="flex:0 0 180px;">
                    <img id="modal-company-logo" src="" alt="logo" style="width:160px;height:160px;object-fit:contain;border-radius:12px;background:#fff;padding:12px;box-shadow:0 4px 16px rgba(0,0,0,0.08);">
                </div>
                <div style="flex:1;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px;">
                        <div>
                            <h2 id="modal-company-name" style="margin:0;font-size:22px;color:#184;">Company name</h2>
                            <div id="modal-company-website" style="margin-top:8px;color:#666;font-size:14px;"></div>
                        </div>
                        <div>
                            <button id="modal-close-btn" style="background:#e74c3c;color:#fff;border:none;padding:8px 12px;border-radius:6px;cursor:pointer;">Đóng</button>
                        </div>
                    </div>
                    <div id="modal-company-address" style="margin-top:12px;color:#666;"></div>
                </div>
            </div>
            <div style="padding:20px;border-top:1px solid #eee;background:#fafafa;">
                <h3 style="margin-top:0;margin-bottom:8px;color:#1b5;">Giới thiệu công ty</h3>
                <div id="modal-company-description" style="color:#333;line-height:1.6;"></div>
            </div>
        </div>
    </div>

    @include('layouts.footer')

    @vite(['resources/js/app.js'])
    <script src="{{ asset('js/companies.js') }}"></script>
</body>
</html>
