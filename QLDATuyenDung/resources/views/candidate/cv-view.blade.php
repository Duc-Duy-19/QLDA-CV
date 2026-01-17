<!DOCTYPE html>
<html lang="vi">
<head>
    @include('layouts.head')
    <title>Xem CV - WebCV</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .cv-viewer-container {
            max-width: 900px;
            margin: 20px auto;
            background: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            position: relative;
        }
        
        .cv-toolbar {
            background: #fff;
            border-bottom: 1px solid #e0e0e0;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .toolbar-left {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .btn-back {
            background: #666;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            transition: background 0.2s;
        }
        
        .btn-back:hover {
            background: #555;
        }
        
        .toolbar-right {
            display: flex;
            gap: 10px;
        }
        
        .btn-action {
            background: linear-gradient(90deg, #06b6d4 0%, #10b981 100%);
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            transition: transform 0.2s;
        }
        
        .btn-action:hover {
            transform: translateY(-1px);
        }
        
        .btn-edit {
            background: #f59e0b;
        }
        
        .cv-content {
            padding: 40px;
            min-height: 600px;
        }
        
        .cv-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #06b6d4;
        }
        
        .cv-name {
            font-size: 32px;
            font-weight: 700;
            color: #333;
            margin: 0 0 10px 0;
        }
        
        .cv-title-text {
            font-size: 18px;
            color: #666;
            margin: 0;
        }
        
        .cv-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #06b6d4;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .section-content {
            color: #555;
            line-height: 1.6;
        }
        
        .info-item {
            margin-bottom: 10px;
        }
        
        .info-item strong {
            color: #333;
            min-width: 100px;
            display: inline-block;
        }
        
        .experience-item, .education-item {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .experience-item:last-child, .education-item:last-child {
            border-bottom: none;
        }
        
        .item-title {
            color: #333;
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 5px 0;
        }
        
        .item-subtitle {
            color: #666;
            margin: 0 0 8px 0;
        }
        
        .item-description {
            color: #555;
            margin: 0;
        }
        
        .skills-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 0;
            padding: 0;
            list-style: none;
        }
        
        .skill-item {
            background: #e3f2fd;
            color: #1976d2;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .loading-spinner {
            text-align: center;
        }
        
        .loading-spinner i {
            font-size: 48px;
            color: #06b6d4;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .error-message {
            text-align: center;
            padding: 60px 20px;
            color: #e53e3e;
        }
        
        /* Modern Professional Layout Styles */
        .cv-wrapper.modern-professional {
            display: flex;
            min-height: 100vh;
        }
        
        .cv-left-column {
            transition: all 0.3s ease;
        }
        
        .cv-right-column {
            transition: all 0.3s ease;
        }
        
        .cv-section-left {
            transition: all 0.3s ease;
        }
        
        .cv-section-left:hover {
            transform: translateX(5px);
        }
        
        .cv-section-right {
            transition: all 0.3s ease;
        }
        
        .cv-section-right:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .cv-item {
            transition: transform 0.2s ease;
        }
        
        .cv-item:hover {
            transform: translateX(5px);
        }
        
        /* Responsive Layout */
        @media (max-width: 768px) {
            .cv-wrapper.modern-professional {
                flex-direction: column;
            }
            
            .cv-left-column {
                width: 100% !important;
            }
            
            .cv-right-column {
                width: 100% !important;
            }
            
            .cv-viewer-container {
                max-width: 100%;
                margin: 0;
            }
            
            .cv-content {
                padding: 20px;
            }
        }
        
        @media print {
            /* Ẩn header và footer mặc định của trình duyệt (URL và ngày in) */
            @page {
                margin: 0.5cm !important;
                size: A4 !important;
            }
            
            /* Loại bỏ header/footer của trình duyệt */
            /* Lưu ý: Cần tắt "Headers and footers" trong cài đặt in của trình duyệt */
            @page {
                margin-top: 0.5cm !important;
                margin-bottom: 0.5cm !important;
                margin-left: 0.5cm !important;
                margin-right: 0.5cm !important;
            }
            
            /* Ẩn toolbar và các phần không cần thiết */
            .cv-toolbar,
            .loading-overlay {
                display: none !important;
            }
            
            /* Container chính */
            .cv-viewer-container {
                box-shadow: none !important;
                margin: 0 !important;
                padding: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
            }
            
            .cv-content {
                padding: 0 !important;
                margin: 0 !important;
            }
            
            body {
                background: white !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            /* Đảm bảo màu sắc được in ra */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            
            /* Giữ nguyên layout 2 cột cho modern-professional */
            .cv-wrapper.modern-professional {
                display: flex !important;
                flex-direction: row !important;
                min-height: auto !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            
            .cv-wrapper.modern-professional .cv-left-column {
                width: 35% !important;
                min-width: 35% !important;
                max-width: 35% !important;
                background: #2c3e50 !important;
                background-color: #2c3e50 !important;
                color: #ffffff !important;
                padding: 20px 15px !important;
                margin: 0 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            
            .cv-wrapper.modern-professional .cv-right-column {
                width: 65% !important;
                min-width: 65% !important;
                max-width: 65% !important;
                background: #ffffff !important;
                background-color: #ffffff !important;
                color: #333 !important;
                padding: 20px 25px !important;
                margin: 0 !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            
            /* Professional Sidebar layout */
            .cv-wrapper.professional-sidebar {
                display: flex !important;
                flex-direction: row !important;
                min-height: auto !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            
            .cv-wrapper.professional-sidebar .cv-left-column {
                width: 30% !important;
                min-width: 30% !important;
                max-width: 30% !important;
                background: #34495e !important;
                background-color: #34495e !important;
                color: #ffffff !important;
                padding: 20px 15px !important;
                margin: 0 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            
            .cv-wrapper.professional-sidebar .cv-right-column {
                width: 70% !important;
                min-width: 70% !important;
                max-width: 70% !important;
                background: #ffffff !important;
                background-color: #ffffff !important;
                color: #333 !important;
                padding: 20px 25px !important;
                margin: 0 !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            
            /* Tránh ngắt trang trong các section */
            .cv-section-left,
            .cv-section-right {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            
            .cv-item {
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
            .cv-item:hover {
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
    <div id="loading" class="loading-overlay">
        <div class="loading-spinner">
            <i class="fas fa-spinner"></i>
            <p>Đang tải CV...</p>
        </div>
    </div>
    
    <div class="cv-viewer-container">
        <div class="cv-toolbar">
            <div class="toolbar-left">
                <a href="{{ route('candidate.cv.index') }}" class="btn-back">
                    <i class="fas fa-arrow-left"></i>
                    Quay lại
                </a>
            </div>
            <div class="toolbar-right">
                <button class="btn-action btn-edit" onclick="editCurrentCV()">
                    <i class="fas fa-edit"></i>
                    Chỉnh sửa
                </button>
                <button class="btn-action" onclick="printCV()">
                    <i class="fas fa-download"></i>
                    In/Tải PDF
                </button>
            </div>
        </div>
        
        <div id="cv-content" class="cv-content">
            <!-- CV content will be loaded here -->
        </div>
    </div>
    
    <script>
        // Truyền routes từ Blade sang JavaScript
        window.CV_BUILDER_ROUTE = '{{ route('candidate.cv-builder') }}';
        window.CV_INDEX_ROUTE = '{{ route('candidate.cv.index') }}';
        
        // Hàm in CV với cài đặt tối ưu
        function printCV() {
            // Mở dialog in
            window.print();
        }
        
        // Khi dialog in mở, thêm hướng dẫn ẩn header/footer
        window.addEventListener('beforeprint', function() {
            // Có thể thêm logic ở đây nếu cần
        });
    </script>
    <script src="{{ asset('js/candidate/cv-view.js') }}"></script>
</body>
</html>

