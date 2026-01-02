<!DOCTYPE html>
<html lang="vi">
<head>
    @include('layouts.head')
    <link rel="stylesheet" href="{{ asset('css/shared/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/shared/footer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/home.css') }}">
    <title>Danh mục việc làm - WebCV</title>
</head>
<body>
    @include('layouts.header')

    <!-- Main Content -->
    <main class="main">
        <!-- Page Header -->
        <section class="page-header">
            <div class="container-inner">
                <div class="page-header-content">
                    <h1>Danh mục việc làm</h1>
                    <p>Tìm việc làm theo ngành nghề phù hợp với bạn</p>
                </div>
            </div>
        </section>

        <!-- Categories Section -->
        <section class="job-categories-page">
            <div class="container-inner">
                <div class="categories-grid" id="categories-grid">
                    <!-- Categories will be loaded here -->
                </div>
                
                <div class="categories-pagination">
                    <div class="pagination-dots" id="pagination-dots">
                        <!-- Pagination dots will be generated here -->
                    </div>
                </div>
            </div>
        </section>
    </main>

    @include('layouts.footer')

    @vite(['resources/js/app.js'])
    <script>
    // API Base URL (legacy mock disabled)
    const API_BASE_URL = '';
        
        let categoriesData = [];
        let currentCategoryPage = 0;
        const categoriesPerPage = 12;
        
        // Category icons mapping
        const categoryIcons = {
            'Công nghệ Thông tin': 'fas fa-laptop-code',
            'Kinh doanh/Bán hàng': 'fas fa-chart-line',
            'Marketing/PR/Quảng cáo': 'fas fa-bullhorn',
            'Chăm sóc khách hàng': 'fas fa-headset',
            'Nhân sự/Hành chính/Pháp chế': 'fas fa-users',
            'Lao động phổ thông': 'fas fa-tools',
            'Kế toán/Kiểm toán': 'fas fa-calculator',
            'Quảng cáo/Marketing': 'fas fa-megaphone',
            'Nông nghiệp': 'fas fa-seedling',
            'Nghệ thuật': 'fas fa-palette',
            'Ngân hàng': 'fas fa-university',
            'Thư ký/Hành chính': 'fas fa-clipboard-list',
            'Y tế': 'fas fa-user-md',
            'Giáo dục': 'fas fa-graduation-cap',
            'Xây dựng': 'fas fa-hammer',
            'Vận tải': 'fas fa-truck',
            'Du lịch': 'fas fa-plane',
            'Thể thao': 'fas fa-dumbbell',
            'Báo chí': 'fas fa-newspaper',
            'Luật': 'fas fa-gavel'
        };
        
        // Load job categories
        async function loadJobCategories() {
            try {
                const response = await fetch(`${API_BASE_URL}/jobs`);
                const jobs = await response.json();
                
                // Count jobs by category
                const categoryCounts = {};
                jobs.forEach(job => {
                    const category = job.category || 'Khác';
                    categoryCounts[category] = (categoryCounts[category] || 0) + 1;
                });
                
                // Convert to array and sort by count
                categoriesData = Object.entries(categoryCounts)
                    .map(([name, count]) => ({ name, count }))
                    .sort((a, b) => b.count - a.count);
                
                displayCategories();
            } catch (error) {
                console.error('Error loading categories:', error);
            }
        }
        
        // Display categories
        function displayCategories() {
            const grid = document.getElementById('categories-grid');
            const startIndex = currentCategoryPage * categoriesPerPage;
            const endIndex = startIndex + categoriesPerPage;
            const currentCategories = categoriesData.slice(startIndex, endIndex);
            
            grid.innerHTML = currentCategories.map(category => {
                const icon = categoryIcons[category.name] || 'fas fa-briefcase';
                return `
                    <div class="category-card" data-category="${category.name}">
                        <div class="category-icon">
                            <i class="${icon}"></i>
                        </div>
                        <div class="category-title">${category.name}</div>
                        <div class="category-count">${category.count} việc làm</div>
                    </div>
                `;
            }).join('');

            // Attach pointer handlers to prevent accidental navigation while dragging
            setTimeout(() => {
                const cards = grid.querySelectorAll('.category-card');
                cards.forEach(card => {
                    let startX = 0, startY = 0, isDragging = false;
                    card.addEventListener('pointerdown', (ev) => {
                        startX = ev.clientX || 0;
                        startY = ev.clientY || 0;
                        isDragging = false;
                        try { card.setPointerCapture(ev.pointerId); } catch (e) {}
                    }, { passive: true });
                    card.addEventListener('pointermove', (ev) => {
                        const dx = (ev.clientX || 0) - startX;
                        const dy = (ev.clientY || 0) - startY;
                        if (Math.hypot(dx, dy) > 8) isDragging = true;
                    }, { passive: true });
                    card.addEventListener('pointerup', (ev) => {
                        try { card.releasePointerCapture(ev.pointerId); } catch (e) {}
                        if (!isDragging) {
                            const cat = card.dataset.category;
                            if (cat) goToJobSearch(cat);
                        }
                    });
                    card.addEventListener('click', (ev) => { if (isDragging) ev.stopImmediatePropagation(); });
                });
            }, 0);
            
            updatePaginationDots();
        }
        
        // Update pagination dots
        function updatePaginationDots() {
            const dotsContainer = document.getElementById('pagination-dots');
            const totalPages = Math.ceil(categoriesData.length / categoriesPerPage);
            
            if (totalPages <= 1) {
                dotsContainer.style.display = 'none';
                return;
            }
            
            dotsContainer.innerHTML = Array.from({ length: totalPages }, (_, i) => 
                `<div class="pagination-dot ${i === currentCategoryPage ? 'active' : ''}" 
                     onclick="goToCategoryPage(${i})"></div>`
            ).join('');
        }
        
        // Go to specific category page
        function goToCategoryPage(page) {
            currentCategoryPage = page;
            displayCategories();
        }
        
        // Go to category jobs page
        function goToJobSearch(categoryName) {
            // Test with debug route first
            console.log('Category name:', categoryName);
            console.log('Encoded:', encodeURIComponent(categoryName));
            
            // Use simple URL construction
            const url = `/category/${encodeURIComponent(categoryName)}`;
            console.log('URL:', url);
            window.location.href = url;
        }
        
        // Initialize categories when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadJobCategories();
        });
    </script>
</body>
</html>