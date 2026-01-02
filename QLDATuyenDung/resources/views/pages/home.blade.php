<!DOCTYPE html>
<html lang="vi">

<head>
    @include('layouts.head')
    <link rel="stylesheet" href="{{ asset('css/shared/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/shared/footer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/home.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components/top-employers.css') }}">
</head>

<body class="home-page">

    @include('layouts.header')

    <main class="main">
        <section class="hero-section">
            <div class="banner-carousel-container">
                <button class="banner-nav prev" onclick="scrollBanner('left')" onmouseenter="if(window.hoverTimeout) { clearTimeout(window.hoverTimeout); window.hoverTimeout = null; }">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <div class="banner-carousel" id="banner-carousel">
                </div>
                <button class="banner-nav next" onclick="scrollBanner('right')" onmouseenter="if(window.hoverTimeout) { clearTimeout(window.hoverTimeout); window.hoverTimeout = null; }">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>

            <!-- Banner Pagination Dots -->
            <div class="banner-pagination" id="banner-pagination">
                <!-- Dots will be generated here -->
            </div>
        </section>


        <!-- Best Jobs Section - Full Width -->
        <section class="best-jobs-section">
            <div class="container-inner">
                <div class="best-jobs">
                    <div class="section-header">
                        <h3>Việc làm mới nhất</h3>
                    </div>

                    <div class="filter-section">
                        <span>Lọc theo: Địa điểm</span>
                        <div class="filter-tags">
                            <button class="filter-tag active" data-location="">Tất cả</button>
                            <button class="filter-tag" data-location="Hà Nội">Hà Nội</button>
                            <button class="filter-tag" data-location="TP. Hồ Chí Minh">TP. Hồ Chí Minh</button>
                            <button class="filter-tag" data-location="Đà Nẵng">Đà Nẵng</button>
                        </div>
                    </div>

                    <!-- Jobs List -->
                    <div class="jobs-list" id="jobs-list">
                        <!-- Jobs will be loaded here -->
                    </div>
                    <div id="job-detail-popup" class="job-detail-popup "></div>

                </div>
        </section>

        <!-- Job Categories Section - Separate from Best Jobs -->
        <section id="job-categories" class="job-categories-section">
            <div class="container-inner">
                <div class="section-header">
                    <h3>Việc làm theo ngành nghề</h3>
                </div>

                <div class="categories-container">
                    <button class="categories-nav prev" onclick="scrollCategories('left')">
                        <i class="fas fa-chevron-left"></i>
                    </button>

                    <div class="categories-grid" id="categories-grid">
                        <!-- Categories will be loaded here -->
                    </div>

                    <button class="categories-nav next" onclick="scrollCategories('right')">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </section>

        <!-- Top Employers Section -->
        <section class="top-employers-section">
            <div class="section-header">
                <h3>Nhà tuyển dụng hàng đầu</h3>
                <a href="#" class="view-all-link" onclick="showAllCompanies()">
                    Xem tất cả
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>

            <div class="employers-container">
                <button class="employers-nav prev" onclick="scrollEmployers('left')">
                    <i class="fas fa-chevron-left"></i>
                </button>

                <div class="employers-grid" id="employers-grid">
                    <!-- Top employers will be loaded here -->
                </div>

                <button class="employers-nav next" onclick="scrollEmployers('right')">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>

            <div class="employers-pagination">
                <div class="pagination-dots" id="employers-pagination-dots">
                    <!-- Pagination dots will be generated here -->
                </div>
            </div>
        </section>

        <!-- CV CTA Section -->
        <section class="cv-cta-section">
            <div class="cv-cta-inner">
                <a href="{{ route('candidate.cv-builder') }}">
                    <img src="{{ asset('images/banner-discover.png') }}" alt="Khám phá tạo CV">
                </a>
            </div>
        </section>

        @include('layouts.footer')
    </main>

    <!-- Floating Elements -->
    <div class="floating-elements">
        <div class="floating-buttons">
            <!-- Keep favorites (heart) -->
            <button class="fab" title="Yêu thích" id="favorite-btn" onclick="window.location.href='{{ route('candidate.favorites') }}'">
                <i class="fas fa-heart"></i>
                <span class="fab-count" id="favorite-count">0</span>
            </button>

            <!-- Notification bell (replaces the 3 lower icons) -->
            <button class="fab" title="Thông báo" id="notification-btn" onclick="window.location.href='{{ route('candidate.notifications') }}'">
                <i class="fas fa-bell"></i>
                <span class="fab-count" id="notification-count">0</span>
            </button>
        </div>
    </div>

    {{-- Load Vite assets with error handling --}}
    @vite(['resources/js/app.js'])
    <script>
        // Fallback: If Vite assets fail to load, it's not critical for home page
        // as all necessary JS is already loaded inline or via asset() helper
        window.addEventListener('error', function(e) {
            if (e.target && e.target.tagName === 'SCRIPT' && e.target.src && e.target.src.includes('/build/assets/')) {
                // Silently ignore Vite asset loading errors - not critical for home page
                e.preventDefault();
                console.warn('Vite asset load failed (non-critical):', e.target.src);
            }
        }, true);
    </script>

    <script>
        // Helper to update notification badge from JS (call updateNotificationCount(n))
        function updateNotificationCount(count) {
            const el = document.getElementById('notification-count');
            if (!el) return;
            el.textContent = count > 0 ? count : '0';
            // Optionally hide when zero: el.style.display = count > 0 ? 'flex' : 'none';
        }

        // Example: if you later have an endpoint like /api/notifications/count you can poll it:
        // setInterval(async () => {
        //     try {
        //         const res = await fetch('/api/notifications/count');
        //         const data = await res.json();
        //         updateNotificationCount(data.count || 0);
        //     } catch (e) { console.error(e); }
        // }, 30000);
    </script>


    <script>
        // API Base URL
        const API_BASE_URL = '{{ url("/api") }}';

        // Small helper to avoid injecting raw strings
        function escapeHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        // Normalize a logo URL: if relative, convert to absolute using the current origin.
        function normalizeLogoUrl(url) {
            const placeholder = '{{ asset("images/company-placeholder.svg") }}';
            if (!url) return placeholder;
            try {
                // If already absolute (http/https/data), URL will parse with origin
                const parsed = new URL(url, window.location.origin);
                // If the original string did not include a scheme and looks like a storage path
                // (e.g., 'company-logos/abc.png' or '/company-logos/abc.png'), normalize to /storage/...
                const raw = String(url || '').trim();
                if (!raw.match(/^https?:\/\//i) && !raw.startsWith('data:')) {
                    // If it already starts with /storage, use it
                    if (raw.startsWith('/storage/')) return window.location.origin + raw;
                    // If it starts with a leading slash (but not /storage), assume it's relative to site root
                    if (raw.startsWith('/')) return window.location.origin + raw;
                    // Typical Laravel store path 'company-logos/...' -> map to /storage/company-logos/...
                    return window.location.origin + '/storage/' + raw.replace(/^\/+/, '');
                }
                return parsed.href;
            } catch (e) {
                return placeholder;
            }
        }

        // Enable mouse drag / touch drag to scroll horizontally
        function enableDragToScroll(container) {
            if (!container) return;
            let isDown = false;
            let startX;
            let scrollLeft;
            let moved = false; // track if user dragged

            container.style.cursor = 'grab';
            container.addEventListener('mousedown', (e) => {
                isDown = true;
                moved = false;
                container.classList.add('dragging');
                container.style.cursor = 'grabbing';
                startX = e.pageX - container.offsetLeft;
                scrollLeft = container.scrollLeft;
                e.preventDefault();
            });

            container.addEventListener('mouseleave', () => {
                isDown = false;
                container.classList.remove('dragging');
                container.style.cursor = 'grab';
            });

            container.addEventListener('mouseup', () => {
                isDown = false;
                // small delay to allow click suppression
                setTimeout(() => {
                    moved = false;
                }, 50);
                container.classList.remove('dragging');
                container.style.cursor = 'grab';
            });

            container.addEventListener('mousemove', (e) => {
                if (!isDown) return;
                e.preventDefault();
                const x = e.pageX - container.offsetLeft;
                const dx = x - startX;
                if (Math.abs(dx) > 5) moved = true; // threshold to consider as drag
                const walk = dx * 1; // scroll-fast
                container.scrollLeft = scrollLeft - walk;
            });

            // touch events for mobile
            container.addEventListener('touchstart', (e) => {
                moved = false;
                startX = e.touches[0].pageX - container.offsetLeft;
                scrollLeft = container.scrollLeft;
            }, {
                passive: true
            });

            container.addEventListener('touchmove', (e) => {
                const x = e.touches[0].pageX - container.offsetLeft;
                const dx = x - startX;
                if (Math.abs(dx) > 5) moved = true;
                const walk = dx * 1;
                container.scrollLeft = scrollLeft - walk;
            }, {
                passive: true
            });

            // Suppress clicks that are actually drags (prevents navigation) -- only for card clicks
            container.addEventListener('click', (e) => {
                if (!moved) return;
                // if the click happened on a card (or inside it), suppress it; otherwise allow (e.g., nav buttons)
                const clickedCard = e.target.closest && e.target.closest('.employer-card');
                if (clickedCard && container.contains(clickedCard)) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                moved = false;
            }, true);
        }

        // Theme Management System
        class ThemeManager {
            constructor() {
                this.currentTheme = this.detectTheme();
                this.init();
            }

            detectTheme() {
                // Check if user has a saved preference
                const savedTheme = localStorage.getItem('theme');
                if (savedTheme) {
                    return savedTheme;
                }

                // Auto-detect based on system preference
                if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    return 'dark';
                }

                // Default to light theme
                return 'light';
            }

            init() {
                this.applyTheme(this.currentTheme);
                this.setupThemeToggle();
                this.setupSystemThemeListener();
            }

            applyTheme(theme) {
                document.documentElement.setAttribute('data-theme', theme);
                this.currentTheme = theme;
                localStorage.setItem('theme', theme);

                // Update any theme-dependent elements
                this.updateDynamicElements();
            }

            updateDynamicElements() {
                // Update any elements that need special handling
                const sections = document.querySelectorAll('.best-jobs-section, .job-categories-section');
                sections.forEach(section => {
                    const bgColor = getComputedStyle(section).backgroundColor;
                    const isLight = this.isLightColor(bgColor);

                    // Update text colors based on background
                    const textElements = section.querySelectorAll('h1, h2, h3, h4, h5, h6, p, span');
                    textElements.forEach(element => {
                        if (isLight) {
                            element.style.color = '#333333';
                        } else {
                            element.style.color = '#ffffff';
                        }
                    });
                });
            }

            isLightColor(color) {
                // Convert RGB to lightness
                const rgb = color.match(/\d+/g);
                if (!rgb) return true;

                const r = parseInt(rgb[0]);
                const g = parseInt(rgb[1]);
                const b = parseInt(rgb[2]);

                // Calculate relative luminance
                const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
                return luminance > 0.5;
            }

            setupThemeToggle() {
                // Create theme toggle button if it doesn't exist
                if (!document.getElementById('theme-toggle')) {
                    const toggle = document.createElement('button');
                    toggle.id = 'theme-toggle';
                    toggle.innerHTML = this.currentTheme === 'dark' ? '☀️' : '🌙';
                    toggle.style.cssText = `
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        z-index: 1000;
                        background: var(--accent-color);
                        color: white;
                        border: none;
                        border-radius: 50%;
                        width: 50px;
                        height: 50px;
                        font-size: 20px;
                        cursor: pointer;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                        transition: all 0.3s ease;
                    `;

                    toggle.addEventListener('click', () => {
                        this.toggleTheme();
                    });

                    // NOTE: do not append the floating theme toggle to avoid layout overlap
                    // document.body.appendChild(toggle);
                }
            }

            setupSystemThemeListener() {
                // Listen for system theme changes
                if (window.matchMedia) {
                    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                        if (!localStorage.getItem('theme')) {
                            this.applyTheme(e.matches ? 'dark' : 'light');
                        }
                    });
                }
            }

            toggleTheme() {
                const newTheme = this.currentTheme === 'dark' ? 'light' : 'dark';
                this.applyTheme(newTheme);

                // Update toggle button
                const toggle = document.getElementById('theme-toggle');
                if (toggle) {
                    toggle.innerHTML = newTheme === 'dark' ? '☀️' : '🌙';
                }
            }
        }

        // Initialize theme manager when page loads
        document.addEventListener('DOMContentLoaded', function() {
            new ThemeManager();
        });

        let employerMap = {};
        let employerLogoMap = {}; // companyId -> logoUrl

        // Debug function to check localStorage
        function debugLocalStorage() {
            console.log('=== LocalStorage Debug ===');
            console.log('All localStorage keys:', Object.keys(localStorage));
            console.log('isLoggedIn:', localStorage.getItem('isLoggedIn'));
            console.log('currentUser:', localStorage.getItem('currentUser'));
            console.log('========================');
        }

        // Load jobs on page load
        document.addEventListener('DOMContentLoaded', async function() {
            console.log('🚀 DOM loaded, starting optimized loading...');

            // Load header first (with user info if logged in)
            checkLoginStatus();

            // Load data sections in parallel
            await Promise.all([
                loadEmployers(),
                loadTopEmployers(),
                loadJobs(),
                loadJobCategories(),
                loadBannerCarousel()
            ]);

            // Update UI elements
            updateCurrentDate();
            updateJobStats();

            // Force refresh avatar after everything is loaded
            setTimeout(() => {
                console.log('🔄 Final avatar refresh after page load');
                window.updateHeaderAvatar();
            }, 1000);

            // Event delegation for job hover
            const jobsList = document.getElementById("jobs-list");
            const popup = document.getElementById("job-detail-popup");

            // Handle mouse enter on job items with 2s delay before showing popup
            let hoverShowTimeout = null;
            let lastHoveredJobEl = null;
            jobsList.addEventListener('mouseenter', function(e) {
                const jobElement = e.target.closest('.job-item');
                if (jobElement) {
                    const jobId = jobElement.getAttribute('data-job-id');
                    // Cancel previous timeout if hovering a different element
                    if (hoverShowTimeout) {
                        clearTimeout(hoverShowTimeout);
                        hoverShowTimeout = null;
                    }
                    lastHoveredJobEl = jobElement;
                    // set a 2 second delay before showing details
                    hoverShowTimeout = setTimeout(() => {
                        if (lastHoveredJobEl === jobElement && jobId) {
                            showJobPopup(jobId, jobElement);
                        }
                        hoverShowTimeout = null;
                    }, 1000);
                }
            }, true);

            // Handle mouse leave on jobs list container
            jobsList.addEventListener('mouseleave', function(e) {
                // Only hide if we're leaving the entire jobs list container and not moving to popup
                const popup = document.getElementById("job-detail-popup");
                // If leaving before popup shown, cancel the pending timeout
                if (hoverShowTimeout) {
                    clearTimeout(hoverShowTimeout);
                    hoverShowTimeout = null;
                    lastHoveredJobEl = null;
                }
                if (!jobsList.contains(e.relatedTarget) && !popup.contains(e.relatedTarget)) {
                    hideJobPopup();
                }
            });

            // Handle mouse enter on popup
            popup.addEventListener('mouseenter', function() {
                if (currentPopupTimeout) {
                    clearTimeout(currentPopupTimeout);
                    currentPopupTimeout = null;
                }
            });

            // Handle mouse leave on popup
            popup.addEventListener('mouseleave', function() {
                hideJobPopup();
            });
        });

        async function loadEmployers() {
            try {
                // Try to load companies from the public companies API so we can get logos
                const apiUrl = '{{ url("/api/public/companies") }}';
                const res = await fetch(apiUrl);
                if (!res.ok) {
                    console.warn('Failed to load companies list:', res.status);
                    return;
                }
                const json = await res.json();
                const list = (json && json.data) ? json.data : json;
                if (!Array.isArray(list)) return;
                employerMap = list.reduce((acc, c) => {
                    acc[c.id] = c.company_name || c.companyName || '';
                    return acc;
                }, {});
                employerLogoMap = list.reduce((acc, c) => {
                    acc[c.id] = c.logo || c.avatar || c.logo_url || null;
                    return acc;
                }, {});
            } catch (e) {
                console.error('Error loading employers (companies):', e);
            }
        }

        // Load top employers for the carousel
        let topEmployersData = [];
        let currentEmployerPage = 0;
        const employersPerPage = 4;

        async function loadTopEmployers() {
            try {
                console.log('Loading top employers...');
                console.log('API_BASE_URL:', API_BASE_URL);

                // Thử nhiều cách gọi API
                let apiUrl = `${API_BASE_URL}/public/companies?per_page=12`;
                console.log('API URL:', apiUrl);

                // Thử với route name nếu có
                try {
                    const routeUrl = '{{ url("/api/public/companies") }}';
                    console.log('Route URL:', routeUrl);
                    apiUrl = routeUrl;
                } catch (e) {
                    console.log('Route URL not available, using default');
                }

                console.log('Final API URL being called:', apiUrl);
                const response = await fetch(apiUrl);
                console.log('Response status:', response.status);
                console.log('Response ok:', response.ok);
                console.log('Response headers:', response.headers);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const companies = await response.json();
                console.log('Companies response:', companies);

                // Handle both paginated and non-paginated responses
                const companiesList = companies.data || companies;

                if (!companiesList || companiesList.length === 0) {
                    throw new Error('No companies found');
                }

                // Get job counts for each company
                const companiesWithJobCounts = await Promise.all(
                    companiesList.map(async (company) => {
                        try {
                            const jobsResponse = await fetch(`${API_BASE_URL}/public/companies/${company.id}/jobs`);
                            const jobsData = await jobsResponse.json();
                            console.log(`Jobs data for company ${company.id}:`, jobsData);
                            const jobCount = jobsData.jobs ? jobsData.jobs.length : (jobsData.total || 0);
                            return {
                                ...company,
                                jobCount: jobCount
                            };
                        } catch (error) {
                            console.log(`Error loading jobs for company ${company.id}:`, error);
                            return {
                                ...company,
                                jobCount: 0
                            };
                        }
                    })
                );

                // Sort by job count and take top companies
                topEmployersData = companiesWithJobCounts
                    .sort((a, b) => b.jobCount - a.jobCount)
                    .slice(0, 12);

                console.log('Top employers data:', topEmployersData);
                displayTopEmployers();
                initEmployersSwipe();
            } catch (error) {
                console.error('Error loading top employers:', error);
                console.log('Falling back to sample data...');

                // Thử gọi API trực tiếp từ database thông qua Laravel
                try {
                    // Gọi API trực tiếp từ Laravel route
                    const directResponse = await fetch('{{ url("/api/public/companies") }}');
                    if (directResponse.ok) {
                        const directData = await directResponse.json();
                        console.log('Direct API response:', directData);
                        topEmployersData = directData.data || directData;
                        displayTopEmployers();
                        initEmployersSwipe();
                        return;
                    }
                } catch (directError) {
                    console.log('Direct API also failed:', directError);
                }

                // Thử với jQuery AJAX nếu có
                if (typeof $ !== 'undefined') {
                    try {
                        console.log('Trying with jQuery AJAX...');
                        const ajaxResult = await $.ajax({
                            url: '{{ url("/api/public/companies") }}',
                            type: 'GET',
                            dataType: 'json'
                        });
                        console.log('jQuery AJAX result:', ajaxResult);
                        topEmployersData = ajaxResult.data || ajaxResult;
                        displayTopEmployers();
                        initEmployersSwipe();
                        return;
                    } catch (ajaxError) {
                        console.log('jQuery AJAX also failed:', ajaxError);
                    }
                }

                // Nếu tất cả đều thất bại, hiển thị thông báo lỗi chi tiết
                const grid = document.getElementById('employers-grid');
                grid.innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #dc3545;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 20px;"></i>
                        <h4>Không thể tải danh sách công ty</h4>
                        <p>API URL: ${API_BASE_URL}/public/companies</p>
                        <p>Lỗi: ${error.message}</p>
                        <p>Vui lòng kiểm tra console để xem chi tiết lỗi</p>
                        <button onclick="loadTopEmployers()" class="btn btn-primary">Thử lại</button>
                    </div>
                `;
            }
        }

        // Display top employers with pagination
        function displayTopEmployers() {
            const grid = document.getElementById('employers-grid');

            console.log('Displaying top employers:', topEmployersData);

            if (!topEmployersData || topEmployersData.length === 0) {
                grid.innerHTML = '<div style="text-align: center; padding: 40px; color: #6c757d;">Đang tải dữ liệu...</div>';
                return;
            }

            // Calculate total pages
            const totalPages = Math.ceil(topEmployersData.length / employersPerPage);

            // Create pagination container if it doesn't exist
            let paginationContainer = document.querySelector('.employers-pagination');
            if (!paginationContainer) {
                paginationContainer = document.createElement('div');
                paginationContainer.className = 'employers-pagination';
                grid.parentNode.appendChild(paginationContainer);
            }

            // Clear and populate grid with current page
            updateEmployersPage();
            updateEmployersPaginationDots();
        }

        // Update employers page
        function updateEmployersPage() {
            const grid = document.getElementById('employers-grid');
            // Render all employers (or limit to first N) in a horizontal scrollable row
            const currentPageData = topEmployersData.slice(0, Math.max(topEmployersData.length, employersPerPage));

            grid.innerHTML = currentPageData.map((company) => {
                const logoUrl = (company.logo && company.logo !== 'null' && company.logo !== 'undefined') ? company.logo : '{{ asset("images/company-placeholder.svg") }}';

                return `
                    <div class="employer-card" data-company-id="${company.id}">
                        <div class="employer-logo-section" style="background-image: url('${logoUrl}'); background-size: cover; background-position: center; background-repeat: no-repeat;">
                        </div>
                        <div class="employer-logo">
                            <img src="${logoUrl}" 
                                 alt="${company.company_name}" 
                                 onerror="this.src='{{ asset("images/company-placeholder.svg") }}'"
                                 loading="lazy"
                                 style="object-fit: contain; width: 60px; height: 60px;">
                        </div>
                        <div class="employer-info">
                            <h4 class="employer-name">${company.company_name}</h4>
                            <div class="employer-stats">
                                <span class="job-count">
                                    <i class="fas fa-briefcase"></i>
                                    ${company.jobCount || 0} việc đang tuyển
                                </span>
                                <span class="employer-location" title="${company.address || 'Địa điểm không xác định'}">
                                    <i class="fas fa-map-marker-alt"></i>
                                    ${company.address || 'Địa điểm không xác định'}
                                </span>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            // enable horizontal scrolling + drag on the employers grid
            grid.classList.add('horizontal-scroll-ready');
            enableDragToScroll(grid);
        }

        // Navigate employers with pagination
        function scrollEmployers(direction) {
            const grid = document.getElementById('employers-grid');
            // Add a visual pressed animation to the nav button that was clicked (if available)
            try {
                const btn = event && event.currentTarget ? event.currentTarget : null;
                if (btn) {
                    btn.classList.add('pressed');
                    setTimeout(() => btn.classList.remove('pressed'), 260);
                }
            } catch (e) {
                /* ignore if event not available */
            }

            // If grid is horizontal scrollable, scroll by one card width using smoothScrollTo for better easing
            if (grid && grid.classList.contains('horizontal-scroll-ready')) {
                const firstCard = grid.querySelector('.employer-card');
                if (!firstCard) return;
                const gap = parseInt(getComputedStyle(grid).gap || 18, 10) || 18;
                const cardWidth = firstCard.offsetWidth + gap; // include gap
                const currentScroll = grid.scrollLeft;
                const maxScroll = grid.scrollWidth - grid.clientWidth;
                let target;
                if (direction === 'left') {
                    target = Math.max(0, currentScroll - cardWidth);
                } else {
                    target = Math.min(maxScroll, currentScroll + cardWidth);
                }
                smoothScrollTo(grid, target, 420);
                return;
            }

            // fallback: pagination behavior
            const totalPages = Math.ceil(topEmployersData.length / employersPerPage);
            if (direction === 'left') {
                if (currentEmployerPage > 0) {
                    currentEmployerPage--;
                    updateEmployersPage();
                    updateEmployersPaginationDots();
                }
            } else {
                if (currentEmployerPage < totalPages - 1) {
                    currentEmployerPage++;
                    updateEmployersPage();
                    updateEmployersPaginationDots();
                }
            }
        }

        // Initialize employers swipe functionality
        function initEmployersSwipe() {
            const grid = document.getElementById('employers-grid');
            let startX = 0;
            let isDragging = false;
            let velocity = 0;
            let lastX = 0;
            let lastTime = 0;

            // Mouse events
            grid.addEventListener('mousedown', (e) => {
                isDragging = true;
                startX = e.pageX - grid.offsetLeft;
                scrollLeft = grid.scrollLeft;
                grid.style.cursor = 'grabbing';
                grid.style.userSelect = 'none';
                lastX = e.pageX;
                lastTime = performance.now();

                if (animationId) {
                    cancelAnimationFrame(animationId);
                }
            });

            grid.addEventListener('mouseleave', () => {
                if (isDragging) {
                    isDragging = false;
                    grid.style.cursor = 'grab';
                    grid.style.userSelect = 'auto';
                    applyMomentum();
                }
            });

            grid.addEventListener('mouseup', () => {
                if (isDragging) {
                    isDragging = false;
                    grid.style.cursor = 'grab';
                    grid.style.userSelect = 'auto';
                    applyMomentum();
                }
            });

            grid.addEventListener('mousemove', (e) => {
                if (isDragging && e.buttons === 1) {
                    e.preventDefault();

                    if (animationId) {
                        cancelAnimationFrame(animationId);
                    }

                    const x = e.pageX - grid.offsetLeft;
                    const walk = (x - startX) * 1.0;
                    grid.scrollLeft = scrollLeft - walk;

                    const currentTime = performance.now();
                    const deltaTime = currentTime - lastTime;
                    if (deltaTime > 0) {
                        velocity = (e.pageX - lastX) / deltaTime;
                        lastX = e.pageX;
                        lastTime = currentTime;
                    }
                }
            });

            // Touch events for mobile
            grid.addEventListener('touchstart', (e) => {
                isDragging = true;
                startX = e.touches[0].pageX - grid.offsetLeft;
                scrollLeft = grid.scrollLeft;
                lastX = e.touches[0].pageX;
                lastTime = performance.now();

                if (animationId) {
                    cancelAnimationFrame(animationId);
                }
            });

            grid.addEventListener('touchmove', (e) => {
                if (isDragging) {
                    e.preventDefault();

                    if (animationId) {
                        cancelAnimationFrame(animationId);
                    }

                    const x = e.touches[0].pageX - grid.offsetLeft;
                    const walk = (x - startX) * 1.0;
                    grid.scrollLeft = scrollLeft - walk;

                    const currentTime = performance.now();
                    const deltaTime = currentTime - lastTime;
                    if (deltaTime > 0) {
                        velocity = (e.touches[0].pageX - lastX) / deltaTime;
                        lastX = e.touches[0].pageX;
                        lastTime = currentTime;
                    }
                }
            });

            grid.addEventListener('touchend', () => {
                if (isDragging) {
                    isDragging = false;
                    applyMomentum();
                }
            });

            function applyMomentum() {
                if (Math.abs(velocity) > 0.1) {
                    const momentum = velocity * 400;
                    const targetScroll = Math.max(0, Math.min(grid.scrollWidth - grid.clientWidth, grid.scrollLeft - momentum));
                    smoothScrollTo(grid, targetScroll, 600);
                }
            }
        }

        // Update employers pagination dots
        function updateEmployersPaginationDots() {
            const paginationContainer = document.querySelector('.employers-pagination');
            if (!paginationContainer) return;

            const totalPages = Math.ceil(topEmployersData.length / employersPerPage);

            paginationContainer.innerHTML = Array.from({
                    length: totalPages
                }, (_, i) =>
                `<button class="pagination-dot ${i === currentEmployerPage ? 'active' : ''}" 
                     onclick="goToEmployerPage(${i})"></button>`
            ).join('');
        }

        // Go to specific employer page
        function goToEmployerPage(page) {
            currentEmployerPage = page;
            updateEmployersPage();
            updateEmployersPaginationDots();
        }

        // View company detail
        function viewCompanyDetail(companyId) {
            window.location.href = `/companies/${companyId}`;
        }

        // Show all companies
        function showAllCompanies() {
            window.location.href = '/companies';
        }

        // Check authentication status (simplified)
        function checkAuthStatus() {
            // This function is now handled by checkLoginStatus in header.js
            console.log('🔐 Auth status check delegated to header.js');
        }



        // Logout function is now handled by header.js

        // Load jobs from API
        async function loadJobs() {
            try {
                const url = `${API_BASE_URL}/public/jobs`;
                console.log('Loading jobs from', url);
                const response = await fetch(url);
                console.log('Response status:', response.status);
                const contentType = response.headers.get('content-type') || '';
                console.log('Content-Type:', contentType);

                const text = await response.text();
                console.log('Response body snippet:', text ? text.substring(0, 1500) : '(empty)');

                if (!response.ok) {
                    console.error('Failed to load jobs', response.status, text);
                    return;
                }

                let json = null;
                try {
                    json = JSON.parse(text);
                } catch (e) {
                    console.error('Invalid JSON from /api/jobs', e);
                }
                const list = (json && json.data) ? json.data : json;
                jobs = Array.isArray(list) ? list : [];
                console.log('Parsed jobs count:', jobs.length);

                await preloadFavoriteIds();
                await preloadAppliedJobIds();
                await updateFavoriteCount();
                displayJobs(jobs);
            } catch (error) {
                console.error('Error loading jobs:', error);
            }
        }

        // Preload current user's favorite job ids for marking hearts
        let favoriteJobIdSet = new Set();
        async function preloadFavoriteIds() {
            try {
                const currentUser = JSON.parse(localStorage.getItem('currentUser') || 'null');
                const authToken = localStorage.getItem('authToken');
                favoriteJobIdSet.clear();

                // Only proceed if user is logged in, has valid token, and is a candidate
                if (!currentUser || !authToken || currentUser.role !== 'candidate') {
                    return;
                }

                const resp = await window.APIHelper.request('/saved-jobs');
                const list = resp?.data || [];
                list.forEach(f => favoriteJobIdSet.add(Number(f.job_id || f.jobId || (f.job && f.job.id))));
            } catch (e) {
                // Silently handle 403/401 - user not authenticated, not authorized, or wrong role
                if (e.status === 403 || e.status === 401) {
                    // User not logged in, token expired, or not a candidate - this is expected
                    return;
                }
                // Only log other errors
                console.warn('Failed to preload favorites', e);
            }
        }

        // Preload applied job ids so we can show 'Ứng tuyển lại' where appropriate
        let appliedJobIdSet = new Set();
        async function preloadAppliedJobIds() {
            try {
                const currentUser = JSON.parse(localStorage.getItem('currentUser') || 'null');
                const authToken = localStorage.getItem('authToken');
                appliedJobIdSet.clear();

                // Only proceed if user is logged in, has valid token, and is a candidate
                if (!currentUser || !authToken || currentUser.role !== 'candidate') {
                    return;
                }

                // Prefer APIHelper when available
                let appsResp = null;
                if (typeof window.APIHelper !== 'undefined' && typeof window.APIHelper.request === 'function') {
                    try {
                        appsResp = await window.APIHelper.request('/applications');
                    } catch (e) {
                        // Silently handle 403/401 - user not authenticated or not authorized
                        if (e.status === 403 || e.status === 401) {
                            return;
                        }
                        appsResp = null;
                    }
                }
                if (!appsResp) {
                    try {
                        const resp = await fetch(`${API_BASE_URL}/applications`, {
                            credentials: 'include',
                            headers: {
                                'Authorization': `Bearer ${authToken}`,
                                'Accept': 'application/json'
                            }
                        });
                        if (!resp.ok) {
                            // Silently handle 403/401
                            if (resp.status === 403 || resp.status === 401) {
                                return;
                            }
                            throw new Error('applications fetch failed');
                        }
                        appsResp = await resp.json().catch(() => null);
                    } catch (e) {
                        // Silently handle 403/401
                        if (e.status === 403 || e.status === 401) {
                            return;
                        }
                        appsResp = null;
                    }
                }

                const apps = (appsResp && appsResp.data) ? appsResp.data : (appsResp || []);
                apps.forEach(a => {
                    const jid = Number(a.job_id || a.jobId || (a.job && a.job.id) || 0);
                    if (jid) appliedJobIdSet.add(jid);
                });
            } catch (e) {
                // Silently handle 403/401 - user not authenticated or not authorized
                if (e.status === 403 || e.status === 401) {
                    return;
                }
                console.warn('Failed to preload applied job ids', e);
                appliedJobIdSet = new Set();
            }
        }

        function getApplyButtonHtml(jobId) {
            try {
                const base = '{{ url("jobs") }}';
                if (appliedJobIdSet.has(Number(jobId))) {
                    return `<button class="btn-apply" onclick="window.location.href='${base}/'+${jobId}"><i class=\"fas fa-sync-alt fa-spin\" style=\"margin-right:8px\"></i>Ứng tuyển lại</button>`;
                }
                return `<button class="btn-apply" onclick="window.location.href='${base}/'+${jobId}">Ứng tuyển ngay</button>`;
            } catch (e) {
                return `<button class="btn-apply" onclick="window.location.href='{{ url("jobs") }}/'+${jobId}">Ứng tuyển ngay</button>`;
            }
        }

        // Display jobs in the UI
        function displayJobs(jobs) {
            const jobsList = document.getElementById('jobs-list');
            jobsList.innerHTML = '';

            jobs.forEach(job => {
                const jobElement = createJobElement(job);
                jobsList.appendChild(jobElement);
            });
        }

        // Global popup management
        let currentPopupTimeout = null;
        let currentJobId = null;
        let jobs = []; // Store jobs globally

        // Create job element
        function createJobElement(job) {
            const jobDiv = document.createElement('div');
            jobDiv.className = 'job-item';
            jobDiv.setAttribute('data-job-id', job.id);

            const savedClass = favoriteJobIdSet.has(Number(job.id)) ? 'active' : '';
            const savedIcon = favoriteJobIdSet.has(Number(job.id)) ? 'fas' : 'far';

            // 👉 Chỉ hiển thị thông tin tóm tắt
            const companyName = (job && job.company && (job.company.company_name || job.company.name)) ? (job.company.company_name || job.company.name) : (employerMap[job.companyId] || 'Công ty không xác định');
            const salaryText = job.salary_range || job.salary || '';
            const deadlineText = formatDateToDMY(job.expiration_date || job.deadline || '');
            // Prefer the company listing logo if available (employerLogoMap), otherwise fallback to job.company.* fields
            const companyId = job.company_id || job.companyId || (job.company && job.company.id) || null;
            const listingLogo = companyId ? (employerLogoMap[companyId] || null) : null;
            const rawLogo = listingLogo || ((job && job.company && (job.company.logo || job.company.avatar)) ? (job.company.logo || job.company.avatar) : null);
            const logoUrl = normalizeLogoUrl(rawLogo) || '{{ asset("images/company-placeholder.svg") }}';
            jobDiv.innerHTML = `
                <div class="job-header">
                    <div class="job-logo-wrap">
                        <img src="${logoUrl}" alt="${escapeHtml(companyName)}" onerror="this.onerror=null;this.src='{{ asset("images/company-placeholder.svg") }}'" loading="lazy">
                    </div>
                    <div style="flex:1">
                        <h4 class="job-title"><a href="/jobs/${job.id}" class="job-title-link" onclick="event.stopPropagation();">${escapeHtml(job.title || '')}</a></h4>
                        <div style="color:#6b7280;margin-top:6px;font-size:13px"><i class="fas fa-building"></i> ${escapeHtml(companyName)}</div>
                        <div class="job-meta-row" style="margin-top:8px">
                            <div class="meta-location"><i class="fas fa-map-marker-alt"></i> <span class="meta-text">${escapeHtml(job.location || '')}</span></div>
                            <div class="meta-deadline"><i class="fas fa-calendar"></i> <span>Hạn nộp: ${escapeHtml(deadlineText)}</span></div>
                        </div>
                    </div>
                </div>
                <div class="job-actions" style="display:flex;align-items:center;justify-content:space-between;margin-top:10px">
                    <div class="job-salary">${escapeHtml(salaryText)}</div>
                    <div style="display:flex;align-items:center;gap:8px">
                        <button class="btn-ai-analysis" data-job-id="${job.id}" onclick="handleAiAnalysisHome(${job.id}); event.stopPropagation();" title="Phân tích độ phù hợp với AI" style="background:#1fae4f;border:none;padding:8px 12px;border-radius:6px;display:flex;align-items:center;gap:6px;color:white;font-size:12px;font-weight:600;cursor:pointer;transition:background 0.3s" onmouseover="this.style.background='#16a34a'" onmouseout="this.style.background='#1fae4f'">
                            <span>🤖</span>
                            <span>AI</span>
                        </button>
                        <button class="btn-save ${savedClass}" data-job-id="${job.id}" onclick="toggleFavorite(${job.id}, this)" title="Lưu việc làm">
                            <i class="${savedIcon} fa-heart"></i>
                        </button>
                    </div>
                </div>
            `;

            return jobDiv;
        }

        // Show popup for specific job
        function showJobPopup(jobId, jobElement) {
            console.log('showJobPopup called with jobId:', jobId); // Debug
            console.log('Available jobs:', jobs); // Debug

            // Clear any existing timeout
            if (currentPopupTimeout) {
                clearTimeout(currentPopupTimeout);
                currentPopupTimeout = null;
            }

            // Find job data
            const job = jobs.find(j => j.id == jobId);
            console.log('Found job:', job); // Debug
            if (!job) {
                console.log('Job not found!'); // Debug
                return;
            }

            const popup = document.getElementById("job-detail-popup");

            // Only update content if it's a different job
            if (currentJobId != jobId) {
                currentJobId = jobId;
                const detailDeadline = formatDateToDMY(job.expiration_date || job.deadline || '');
                popup.innerHTML = `
                    <h3>${escapeHtml(job.title || '')}</h3>
                    <p><strong>Công ty:</strong> ${escapeHtml((job.company && job.company.company_name) || employerMap[job.companyId] || 'N/A')}</p>
                    <p><strong>Lương:</strong> ${escapeHtml(job.salary_range || job.salary || '')}</p>
                    <p><strong>Địa điểm:</strong> ${escapeHtml(job.location || '')}</p>
                    <p><strong>Hạn nộp:</strong> ${escapeHtml(detailDeadline)}</p>
                    <div><strong>Mô tả:</strong> ${escapeHtml(job.description || '')}</div>
                    <div><strong>Yêu cầu:</strong> ${escapeHtml((Array.isArray(job.requirements) ? job.requirements.join(', ') : (job.requirements || '')))}</div>
                    <div style="margin-top:12px;">
                        ${getApplyButtonHtml(job.id)}
                    </div>
                `;
            }

            // Always update position
            popup.style.display = "block";
            const rect = jobElement.getBoundingClientRect();
            const screenWidth = window.innerWidth;

            let left = rect.right + 10;
            if (rect.right + popup.offsetWidth + 20 > screenWidth) {
                left = rect.left - popup.offsetWidth - 10;
            }

            popup.style.top = (window.scrollY + rect.top) + "px";
            popup.style.left = left + "px";
            popup.classList.add("show");
        }

        // Hide popup
        function hideJobPopup() {
            if (currentPopupTimeout) {
                clearTimeout(currentPopupTimeout);
                currentPopupTimeout = null;
            }

            currentPopupTimeout = setTimeout(() => {
                const popup = document.getElementById("job-detail-popup");
                popup.classList.remove("show");
                popup.style.display = "none";
                currentJobId = null;
            }, 200);
        }


        // Apply for job
        function applyJob(jobId) {
            const isLoggedIn = localStorage.getItem('isLoggedIn');
            const currentUser = localStorage.getItem('currentUser');

            if (isLoggedIn !== 'true' || !currentUser) {
                alert('Vui lòng đăng nhập để ứng tuyển việc làm!');
                window.location.href = '{{ route("login") }}?from=button';
                return;
            }

            const user = JSON.parse(currentUser);

            if (user.role === 'employer') {
                alert('Nhà tuyển dụng không thể ứng tuyển việc làm!');
                return;
            }

            // Here you would implement the actual application logic
            alert(`Ứng tuyển thành công cho việc làm ID: ${jobId}`);
        }

        // Save job to favorites
        async function saveJob(jobId, el) {
            try {
                const isLoggedIn = localStorage.getItem('isLoggedIn');
                const currentUser = localStorage.getItem('currentUser');
                if (isLoggedIn !== 'true' || !currentUser) {
                    alert('Vui lòng đăng nhập để lưu việc làm yêu thích');
                    window.location.href = '{{ route("login") }}?from=button';
                    return;
                }

                const user = JSON.parse(currentUser);
                // Only candidates can save jobs
                if (user.role !== 'candidate') {
                    alert('Bạn cần tài khoản Ứng viên để lưu việc. Vui lòng chuyển sang tài khoản Ứng viên hoặc tạo hồ sơ ứng viên.');
                    return;
                }
                // check if already favorite

                // Use backend saved-jobs endpoint (authenticated)
                try {
                    const existingResp = await window.APIHelper.request('/saved-jobs');
                    const existing = existingResp?.data || [];
                    // check by job id
                    if (existing.some(s => Number(s.job_id || s.jobId || (s.job && s.job.id)) === Number(jobId))) {
                        alert('Bạn đã lưu công việc này rồi');
                        if (el) markSaved(el);
                        await updateFavoriteCount();
                        return;
                    }
                } catch (ex) {
                    /* ignore */
                }

                await window.APIHelper.ensureCsrf();
                await window.APIHelper.request('/saved-jobs', {
                    method: 'POST',
                    body: JSON.stringify({
                        job_id: jobId
                    })
                });

                // cập nhật UI nút trái tim trên card
                if (el) markSaved(el);

                // cập nhật bộ đếm tổng phía góc
                await updateFavoriteCount();

                // thông báo cho trang Yêu thích tự reload nếu đang mở ở tab khác
                localStorage.setItem('favorite:updated', String(Date.now()));
                alert('Đã lưu việc làm vào yêu thích');
                // Không điều hướng nữa để người dùng không bị ngắt trải nghiệm
            } catch (e) {
                console.error('Error saving favorite job:', e);
                alert('Không thể lưu việc làm, vui lòng thử lại');
            }
        }

        // Toggle favorite (save or remove)
        async function toggleFavorite(jobId, el) {
            const isSaved = el.classList.contains('active');
            if (isSaved) {
                await removeFavorite(jobId, el);
            } else {
                await saveJob(jobId, el);
            }
        }

        // Remove favorite by jobId for current user
        async function removeFavorite(jobId, el) {
            try {
                const currentUser = JSON.parse(localStorage.getItem('currentUser') || 'null');
                if (!currentUser) return;
                try {
                    const currentUser = JSON.parse(localStorage.getItem('currentUser') || 'null');
                    if (!currentUser) return;
                    await window.APIHelper.ensureCsrf();
                    // backend destroy expects DELETE /saved-jobs/{jobId}
                    await window.APIHelper.request(`/saved-jobs/${jobId}`, {
                        method: 'DELETE'
                    });

                    el.classList.remove('active');
                    const icon = el.querySelector('i');
                    if (icon) {
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                    }
                    await updateFavoriteCount();
                    localStorage.setItem('favorite:updated', String(Date.now()));
                } catch (e) {
                    console.warn('Failed to remove favorite via API', e);
                }
            } catch (e) {
                console.error('Error removing favorite job:', e);
                alert('Không thể bỏ lưu việc làm, vui lòng thử lại');
            }
        }

        // Đánh dấu nút trái tim đã lưu
        function markSaved(buttonEl) {
            try {
                buttonEl.classList.add('active');
                const icon = buttonEl.querySelector('i');
                if (icon) {
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                }
                buttonEl.title = 'Bỏ lưu công việc';
                // ❌ KHÔNG disable nút
            } catch {}
        }


        // Lấy và cập nhật số lượng yêu thích ở góc
        async function updateFavoriteCount() {
            const currentUser = JSON.parse(localStorage.getItem('currentUser') || 'null');
            const authToken = localStorage.getItem('authToken');

            // Only proceed if user is logged in, has valid token, and is a candidate
            if (!currentUser || !authToken || currentUser.role !== 'candidate') {
                const countEl = document.querySelector('#favorite-btn .fab-count');
                if (countEl) {
                    countEl.textContent = '0';
                }
                return;
            }

            try {
                const resp = await window.APIHelper.request('/saved-jobs');
                const list = resp?.data || [];
                const countEl = document.querySelector('#favorite-btn .fab-count');
                if (countEl) {
                    countEl.textContent = list.length;
                }
            } catch (e) {
                // Silently handle 403/401 - user not authenticated or not authorized
                if (e.status === 403 || e.status === 401) {
                    const countEl = document.querySelector('#favorite-btn .fab-count');
                    if (countEl) {
                        countEl.textContent = '0';
                    }
                    return;
                }
                // Only log other errors
                console.warn('Error updating favorite count:', e);
            }
        }


        // Update current date
        function updateCurrentDate() {
            const now = new Date();
            const dateStr = now.toLocaleDateString('vi-VN');
            const el = document.getElementById('current-date');
            if (el) {
                el.textContent = dateStr;
            }
        }

        // Format ISO/DateTime string to DD-MM-YYYY
        function formatDateToDMY(value) {
            if (!value) return '';
            // value may be ISO8601 with time or MySQL datetime
            let d;
            try {
                d = new Date(value);
                if (isNaN(d.getTime())) {
                    // try extracting date part
                    const m = String(value).match(/(\d{4}-\d{2}-\d{2})/);
                    if (m) d = new Date(m[1] + 'T00:00:00');
                }
            } catch (e) {
                return ''
            }
            if (!d || isNaN(d.getTime())) return '';
            const dd = String(d.getDate()).padStart(2, '0');
            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const yyyy = d.getFullYear();
            return `${dd}-${mm}-${yyyy}`;
        }

        // Update job statistics
        async function updateJobStats() {
            try {
                const response = await fetch(`${API_BASE_URL}/public/jobs`);
                const json = await response.json();
                const list = (json && json.data) ? json.data : json;
                const jobs = Array.isArray(list) ? list : [];
                const totalEl = document.getElementById('total-jobs');
                const newEl = document.getElementById('new-jobs');
                if (totalEl) totalEl.textContent = jobs.length;
                if (newEl) newEl.textContent = Math.floor(jobs.length * 0.1);
            } catch (error) {
                console.error('Error updating stats:', error);
            }
        }


        // Removed secondary search section handlers to avoid duplication

        // Search jobs
        async function searchJobs(searchTerm, location) {
            try {
                const response = await fetch(`${API_BASE_URL}/public/jobs`);
                const json = await response.json();
                const list = (json && json.data) ? json.data : json;
                const jobs = Array.isArray(list) ? list : [];

                const keyword = (searchTerm || '').trim().toLowerCase();
                const loc = (location || '').trim().toLowerCase();

                const filtered = jobs.filter(job => {
                    const title = (job.title || '').toLowerCase();
                    const companyName = (employerMap[job.companyId] || '').toLowerCase();
                    const matchesKeyword = !keyword || title.includes(keyword) || companyName.includes(keyword);
                    // make location matching permissive: check whether the job.location contains the selected loc (case-insensitive)
                    const jobLoc = (job.location || '').toLowerCase();
                    const matchesLocation = !loc || jobLoc.includes(loc);
                    return matchesKeyword && matchesLocation;
                });

                displayJobs(filtered);
            } catch (error) {
                console.error('Error searching jobs:', error);
            }
        }

        // Filter by location quick tags
        document.querySelectorAll('.filter-tag').forEach(tag => {
            tag.addEventListener('click', function() {
                // Remove active class from all tags
                document.querySelectorAll('.filter-tag').forEach(t => t.classList.remove('active'));
                // Add active class to clicked tag
                this.classList.add('active');

                const location = this.dataset.location;
                if (location) {
                    searchJobs('', location);
                } else {
                    loadJobs();
                }
            });
        });

        // Filter by category in sidebar
        document.querySelectorAll('#category-list .category-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                // highlight selected
                document.querySelectorAll('#category-list .category-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');

                const category = this.dataset.category;
                filterByCategory(category);
            });
        });

        async function filterByCategory(category) {
            try {
                const response = await fetch(`${API_BASE_URL}/public/jobs`);
                const json = await response.json();
                const list = (json && json.data) ? json.data : json;
                const jobs = Array.isArray(list) ? list : [];
                const filtered = jobs.filter(job => ((job.category || '') + '').toLowerCase() === category.toLowerCase());
                displayJobs(filtered);
            } catch (error) {
                console.error('Error filtering by category:', error);
            }
        }
        // Slider promo-banner
        let promoIndex = 0;
        const promoSlides = document.querySelectorAll('.promo-slide');

        function showPromoSlide(idx) {
            promoSlides.forEach((slide, i) => {
                slide.classList.toggle('active', i === idx);
            });
        }

        function nextPromoSlide() {
            promoIndex = (promoIndex + 1) % promoSlides.length;
            showPromoSlide(promoIndex);
        }

        function prevPromoSlide() {
            promoIndex = (promoIndex - 1 + promoSlides.length) % promoSlides.length;
            showPromoSlide(promoIndex);
        }
        if (promoSlides.length) {
            setInterval(nextPromoSlide, 4000);
        }

        // Job Categories functionality
        let categoriesData = [];
        let currentCategoryPage = 0;
        const categoriesPerPage = 6;

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
            'Thư ký/Hành chính': 'fas fa-clipboard-list'
        };

        // Load job categories
        async function loadJobCategories() {
            try {
                const response = await fetch(`${API_BASE_URL}/public/jobs`);
                const data = await response.json();

                // Handle both array and {data: array} formats
                const jobs = Array.isArray(data) ? data : (data.data || []);

                // Count unique jobs by category (avoid counting same job multiple times)
                const categoryJobIds = {};
                jobs.forEach(job => {
                    // job.categories is an array of category objects
                    if (job.categories && job.categories.length > 0) {
                        // Only count the first category to avoid duplicates
                        const firstCategory = job.categories[0];
                        const categoryName = firstCategory.name || 'Khác';
                        if (!categoryJobIds[categoryName]) {
                            categoryJobIds[categoryName] = new Set();
                        }
                        categoryJobIds[categoryName].add(job.id);
                    } else {
                        if (!categoryJobIds['Khác']) {
                            categoryJobIds['Khác'] = new Set();
                        }
                        categoryJobIds['Khác'].add(job.id);
                    }
                });

                // Convert Sets to counts
                const categoryCounts = {};
                Object.entries(categoryJobIds).forEach(([name, jobIds]) => {
                    categoryCounts[name] = jobIds.size;
                });

                // Convert to array and sort by count
                categoriesData = Object.entries(categoryCounts)
                    .map(([name, count]) => ({
                        name,
                        count
                    }))
                    .sort((a, b) => b.count - a.count);

                displayCategories();
                initCategoriesSwipe();
            } catch (error) {
                console.error('Error loading categories:', error);
            }
        }

        // Display categories
        function displayCategories() {
            const grid = document.getElementById('categories-grid');

            grid.innerHTML = categoriesData.map(category => {
                const icon = categoryIcons[category.name] || 'fas fa-briefcase';
                // use data-category attribute and avoid inline onclick so we can detect drag vs click
                return `
                    <div class="category-card" data-category="${escapeHtml(category.name)}">
                        <div class="category-icon">
                            <i class="${icon}"></i>
                        </div>
                        <div class="category-title">${escapeHtml(category.name)}</div>
                        <div class="category-count">${category.count} việc làm</div>
                    </div>
                `;
            }).join('');

            // Attach pointer handlers to prevent accidental clicks while dragging the carousel
            // Only trigger filterByCategory if the pointer did not move beyond a small threshold
            setTimeout(() => {
                const cards = grid.querySelectorAll('.category-card');
                cards.forEach(card => {
                    let startX = 0,
                        startY = 0,
                        isDragging = false;
                    card.addEventListener('pointerdown', (ev) => {
                        startX = ev.clientX || 0;
                        startY = ev.clientY || 0;
                        isDragging = false;
                        try {
                            card.setPointerCapture(ev.pointerId);
                        } catch (e) {}
                    }, {
                        passive: true
                    });
                    card.addEventListener('pointermove', (ev) => {
                        const dx = (ev.clientX || 0) - startX;
                        const dy = (ev.clientY || 0) - startY;
                        if (Math.hypot(dx, dy) > 8) isDragging = true;
                    }, {
                        passive: true
                    });
                    card.addEventListener('pointerup', (ev) => {
                        try {
                            card.releasePointerCapture(ev.pointerId);
                        } catch (e) {}
                        if (!isDragging) {
                            const cat = card.dataset.category;
                            if (cat) filterByCategory(cat);
                        }
                    });
                    // Also prevent click handlers if dragging
                    card.addEventListener('click', (ev) => {
                        if (isDragging) ev.stopImmediatePropagation();
                    });
                });
            }, 0);

            updatePaginationDots();
        }

        // Scroll categories with smooth animation
        function scrollCategories(direction) {
            const grid = document.getElementById('categories-grid');
            const cardWidth = 220; // Width of one card + gap
            const currentScroll = grid.scrollLeft;
            const maxScroll = grid.scrollWidth - grid.clientWidth;

            let targetScroll;
            try {
                const btn = event && event.currentTarget ? event.currentTarget : null;
                if (btn) {
                    btn.classList.add('pressed');
                    setTimeout(() => btn.classList.remove('pressed'), 220);
                }
            } catch (e) {}

            if (direction === 'left') {
                targetScroll = Math.max(0, currentScroll - cardWidth);
            } else {
                targetScroll = Math.min(maxScroll, currentScroll + cardWidth);
            }

            // Smooth scroll with easing
            smoothScrollTo(grid, targetScroll, 300);
        }

        // Ultra smooth scroll function with advanced easing
        function smoothScrollTo(element, target, duration) {
            const start = element.scrollLeft;
            const change = target - start;
            const startTime = performance.now();

            function animateScroll(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);

                // Advanced easing function (ease-out-cubic with bounce)
                const easeOutCubic = 1 - Math.pow(1 - progress, 3);
                const bounce = Math.sin(progress * Math.PI) * 0.1;
                const finalEase = easeOutCubic + (bounce * (1 - progress));

                element.scrollLeft = start + (change * finalEase);

                if (progress < 1) {
                    requestAnimationFrame(animateScroll);
                }
            }

            requestAnimationFrame(animateScroll);
        }

        // Ultra smooth drag with requestAnimationFrame
        function initCategoriesSwipe() {
            const grid = document.getElementById('categories-grid');
            let startX = 0;
            let scrollLeft = 0;
            let isDragging = false;
            let velocity = 0;
            let lastX = 0;
            let lastTime = 0;
            let animationId = null;

            // Mouse events
            grid.addEventListener('mousedown', (e) => {
                isDragging = true;
                startX = e.pageX - grid.offsetLeft;
                scrollLeft = grid.scrollLeft;
                grid.style.cursor = 'grabbing';
                grid.style.userSelect = 'none';
                lastX = e.pageX;
                lastTime = performance.now();

                // Cancel any ongoing animation
                if (animationId) {
                    cancelAnimationFrame(animationId);
                }
            });

            grid.addEventListener('mouseleave', () => {
                if (isDragging) {
                    isDragging = false;
                    grid.style.cursor = 'grab';
                    grid.style.userSelect = 'auto';
                    applyMomentum();
                }
            });

            grid.addEventListener('mouseup', () => {
                if (isDragging) {
                    isDragging = false;
                    grid.style.cursor = 'grab';
                    grid.style.userSelect = 'auto';
                    applyMomentum();
                }
            });

            grid.addEventListener('mousemove', (e) => {
                if (isDragging && e.buttons === 1) {
                    e.preventDefault();

                    // Cancel previous animation
                    if (animationId) {
                        cancelAnimationFrame(animationId);
                    }

                    // Direct scroll without interpolation for immediate response
                    const x = e.pageX - grid.offsetLeft;
                    const walk = (x - startX) * 1.0; // Direct 1:1 mapping
                    grid.scrollLeft = scrollLeft - walk;

                    // Calculate velocity for momentum
                    const currentTime = performance.now();
                    const deltaTime = currentTime - lastTime;
                    if (deltaTime > 0) {
                        velocity = (e.pageX - lastX) / deltaTime;
                        lastX = e.pageX;
                        lastTime = currentTime;
                    }
                }
            });

            // Touch events for mobile
            grid.addEventListener('touchstart', (e) => {
                isDragging = true;
                startX = e.touches[0].pageX - grid.offsetLeft;
                scrollLeft = grid.scrollLeft;
                lastX = e.touches[0].pageX;
                lastTime = performance.now();

                // Cancel any ongoing animation
                if (animationId) {
                    cancelAnimationFrame(animationId);
                }
            });

            grid.addEventListener('touchmove', (e) => {
                if (isDragging) {
                    e.preventDefault();

                    // Cancel previous animation
                    if (animationId) {
                        cancelAnimationFrame(animationId);
                    }

                    // Direct scroll without interpolation for immediate response
                    const x = e.touches[0].pageX - grid.offsetLeft;
                    const walk = (x - startX) * 1.0; // Direct 1:1 mapping
                    grid.scrollLeft = scrollLeft - walk;

                    // Calculate velocity
                    const currentTime = performance.now();
                    const deltaTime = currentTime - lastTime;
                    if (deltaTime > 0) {
                        velocity = (e.touches[0].pageX - lastX) / deltaTime;
                        lastX = e.touches[0].pageX;
                        lastTime = currentTime;
                    }
                }
            });

            grid.addEventListener('touchend', () => {
                if (isDragging) {
                    isDragging = false;
                    applyMomentum();
                }
            });

            // Apply momentum scrolling with smooth animation
            function applyMomentum() {
                if (Math.abs(velocity) > 0.1) {
                    const momentum = velocity * 400; // Increased momentum
                    const targetScroll = Math.max(0, Math.min(grid.scrollWidth - grid.clientWidth, grid.scrollLeft - momentum));

                    // Use smooth scroll for momentum
                    smoothScrollTo(grid, targetScroll, 600);
                }
            }
        }

        // Update pagination dots
        function updatePaginationDots() {
            const dotsContainer = document.getElementById('pagination-dots');
            const totalPages = Math.ceil(categoriesData.length / categoriesPerPage);

            dotsContainer.innerHTML = Array.from({
                    length: totalPages
                }, (_, i) =>
                `<div class="pagination-dot ${i === currentCategoryPage ? 'active' : ''}" 
                     onclick="goToCategoryPage(${i})"></div>`
            ).join('');
        }

        // Go to specific category page
        function goToCategoryPage(page) {
            currentCategoryPage = page;
            displayCategories();
        }

        // Show all categories
        function showAllCategories() {
            window.location.href = '{{ route("job-categories") }}';
        }

        // Go to category jobs page
        function filterByCategory(categoryName) {
            // Single encode to handle special characters properly
            const encodedCategory = encodeURIComponent(categoryName);
            window.location.href = `{{ url('/category') }}/${encodedCategory}`;
        }

        // Banner carousel data
        const bannerData = [{
                id: 1,
                title: "Tìm việc làm mơ ước",
                subtitle: "Hàng nghìn cơ hội việc làm hấp dẫn đang chờ bạn",
                image: "{{ asset('images/banner.jpg') }}",
                buttonText: "Khám phá ngay",
                buttonLink: "{{ route('candidate.job-search') }}"
            },
            {
                id: 2,
                title: "Tuyển dụng hiệu quả",
                subtitle: "Tìm kiếm ứng viên tài năng cho doanh nghiệp của bạn",
                image: "{{ asset('images/dangtin1.jpg') }}",
                buttonText: "Đăng tin tuyển dụng",
                buttonLink: "#"
            },
            {
                id: 3,
                title: "Xây dựng sự nghiệp",
                subtitle: "Phát triển kỹ năng và thăng tiến trong công việc",
                image: "{{ asset('images/dangtin2.jpg') }}",
                buttonText: "Tìm hiểu thêm",
                buttonLink: "{{ route('candidate.career-development') }}"
            },
            {
                id: 4,
                title: "Cơ hội nghề nghiệp",
                subtitle: "Khám phá những vị trí việc làm hấp dẫn nhất",
                image: "{{ asset('images/dangtin3.jpg') }}",
                buttonText: "Xem ngay",
                buttonLink: "{{ route('candidate.job-search') }}"
            }
        ];

        let currentBannerIndex = 0;
        let bannerInterval;
        let canTrigger = true; // global debounce flag for slide triggering

        // Load banner carousel
        function loadBannerCarousel() {
            const bannerContainer = document.getElementById('banner-carousel');
            const paginationContainer = document.getElementById('banner-pagination');

            console.log('Loading banner carousel with data:', bannerData);

            // Create banner slides
            bannerContainer.innerHTML = bannerData.map((banner, index) => {
                console.log(`Creating slide ${index} with image:`, banner.image);
                const backgroundStyle = `background-image: url('${banner.image}') !important; background-size: cover !important; background-position: center !important; background-repeat: no-repeat !important;`;
                console.log(`Background style for slide ${index}:`, backgroundStyle);
                return `
                    <div class="banner-slide ${index === 0 ? 'active' : ''}" data-index="${index}" style="${backgroundStyle}">
                        <div class="banner-content">
                            <div class="banner-text">
                                <h2 class="banner-title">${banner.title}</h2>
                                <p class="banner-subtitle">${banner.subtitle}</p>
                                <a href="${banner.buttonLink}" class="banner-btn">${banner.buttonText}</a>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            // Create pagination dots
            paginationContainer.innerHTML = bannerData.map((_, index) => `
                <button class="banner-dot ${index === 0 ? 'active' : ''}" onclick="goToBanner(${index})" onmouseenter="if(window.hoverTimeout) { clearTimeout(window.hoverTimeout); window.hoverTimeout = null; }"></button>
            `).join('');

            // Debug: Check slides
            const slides = document.querySelectorAll('.banner-slide');
            console.log(`Total slides created: ${slides.length}`);
            slides.forEach((slide, index) => {
                console.log(`Slide ${index}:`, slide);
                console.log(`Slide ${index} background:`, slide.style.background);
            });

            // Test image loading
            bannerData.forEach((banner, index) => {
                const img = new Image();
                img.onload = () => console.log(`Image ${index} (${banner.image}) loaded successfully`);
                img.onerror = () => console.error(`Image ${index} (${banner.image}) failed to load`);
                img.src = banner.image;
            });

            // Test: Force show all slides for debugging
            setTimeout(() => {
                console.log('Testing all slides visibility...');
                const bannerCarousel = document.getElementById('banner-carousel');
                console.log('Banner carousel width:', bannerCarousel.offsetWidth);
                console.log('Banner carousel scrollWidth:', bannerCarousel.scrollWidth);

                slides.forEach((slide, index) => {
                    const computedStyle = window.getComputedStyle(slide);
                    console.log(`Slide ${index}:`, {
                        offsetWidth: slide.offsetWidth,
                        offsetHeight: slide.offsetHeight,
                        display: computedStyle.display,
                        position: computedStyle.position,
                        width: computedStyle.width,
                        minWidth: computedStyle.minWidth,
                        flexBasis: computedStyle.flexBasis,
                        backgroundImage: computedStyle.backgroundImage
                    });
                });
            }, 1000);

            // Initialize banner swipe
            initBannerSwipe();

            // Start auto-play (single reliable interval). Debug/test transitions removed.
            startBannerAutoPlay();
        }

        // Go to specific banner with directional slide animation
        function goToBanner(nextIndex, direction = 'right') {
            const slides = document.querySelectorAll('.banner-slide');
            const dots = document.querySelectorAll('.banner-dot');

            if (nextIndex === currentBannerIndex) return;

            const current = slides[currentBannerIndex];
            const next = slides[nextIndex];

            // Prepare next slide start position
            next.classList.remove('active');
            next.style.transition = 'none';
            next.style.transform = direction === 'right' ? 'translateX(100%)' : 'translateX(-100%)';
            next.style.opacity = '1';
            next.offsetHeight; // force reflow

            // Animate
            next.style.transition = '';
            current.style.transform = direction === 'right' ? 'translateX(-100%)' : 'translateX(100%)';
            current.style.opacity = '0';
            next.style.transform = 'translateX(0)';

            // Update dots
            dots.forEach(dot => dot.classList.remove('active'));
            dots[nextIndex].classList.add('active');

            // After transition, cleanup
            let cleanupCalled = false;
            const cleanup = () => {
                if (cleanupCalled) return;
                cleanupCalled = true;
                // ensure only next is active
                slides.forEach((s, i) => {
                    s.classList.toggle('active', i === nextIndex);
                    s.style.transform = '';
                    s.style.opacity = '';
                    s.style.transition = '';
                });
                current.removeEventListener('transitionend', cleanup);
                // allow next drag/slide trigger
                canTrigger = true;
            };

            current.addEventListener('transitionend', cleanup);
            // Safety fallback: if transitionend doesn't fire, re-enable after duration + small buffer
            setTimeout(() => {
                cleanup();
            }, 800); // transition is 600ms, use 800ms as buffer

            currentBannerIndex = nextIndex;
        }

        // Scroll banner left/right
        function scrollBanner(direction) {
            const totalSlides = bannerData.length;
            let nextIndex;

            if (direction === 'left') {
                nextIndex = (currentBannerIndex - 1 + totalSlides) % totalSlides;
                goToBanner(nextIndex, 'left');
            } else {
                nextIndex = (currentBannerIndex + 1) % totalSlides;
                goToBanner(nextIndex, 'right');
            }
        }

        // Initialize banner swipe functionality
        function initBannerSwipe() {
            const bannerCarousel = document.getElementById('banner-carousel');
            let startX = 0;
            let isDragging = false;
            let velocity = 0;
            let lastX = 0;
            let lastTime = 0;
            let hoverTimeout = null;

            // Hover-to-grab only (do not alter autoplay timing on hover).
            bannerCarousel.addEventListener('mouseenter', () => {
                if (!isDragging) bannerCarousel.style.cursor = 'grab';
                // Do not pause or auto-advance on hover to keep timing consistent.
            });

            bannerCarousel.addEventListener('mouseleave', () => {
                bannerCarousel.style.cursor = 'default';
            });

            // Mouse events
            bannerCarousel.addEventListener('mousedown', (e) => {
                isDragging = true;
                startX = e.pageX;
                bannerCarousel.style.cursor = 'grabbing';
                bannerCarousel.style.userSelect = 'none';
                lastX = e.pageX;
                lastTime = performance.now();

                // Stop auto-play when dragging. Hover auto-advance removed so no hover timeout to cancel.
                stopBannerAutoPlay();
                // As a safety, set a fallback to resume autoplay in case mouseup is missed
                if (window.__bannerResumeFallback) clearTimeout(window.__bannerResumeFallback);
                window.__bannerResumeFallback = setTimeout(() => {
                    console.warn('Banner resume fallback triggered');
                    startBannerAutoPlay();
                }, 5000); // resume after 5s if no mouseup/touchend detected
            });

            bannerCarousel.addEventListener('mouseleave', () => {
                if (isDragging) {
                    isDragging = false;
                    bannerCarousel.style.cursor = 'grab';
                    bannerCarousel.style.userSelect = 'auto';
                    startBannerAutoPlay();
                }
            });

            bannerCarousel.addEventListener('mouseup', () => {
                if (isDragging) {
                    isDragging = false;
                    bannerCarousel.style.cursor = 'grab';
                    bannerCarousel.style.userSelect = 'auto';
                    if (window.__bannerResumeFallback) {
                        clearTimeout(window.__bannerResumeFallback);
                        window.__bannerResumeFallback = null;
                    }
                    startBannerAutoPlay();
                }
            });

            bannerCarousel.addEventListener('mousemove', (e) => {
                if (isDragging) {
                    e.preventDefault();
                    const deltaX = e.pageX - startX;

                    // Calculate velocity
                    const currentTime = performance.now();
                    const deltaTime = currentTime - lastTime;
                    if (deltaTime > 0) {
                        velocity = (e.pageX - lastX) / deltaTime;
                        lastX = e.pageX;
                        lastTime = currentTime;
                    }

                    // Change slide based on drag distance
                    if (Math.abs(deltaX) > 50 && canTrigger) {
                        canTrigger = false; // will be re-enabled after transition completes
                        if (deltaX > 0) {
                            scrollBanner('left');
                        } else {
                            scrollBanner('right');
                        }
                        // allow continuing the drag: reset startX so further movement continues from current pointer
                        startX = e.pageX;
                        lastX = e.pageX;
                        lastTime = performance.now();
                    }
                }
            });

            // Touch events
            bannerCarousel.addEventListener('touchstart', (e) => {
                isDragging = true;
                startX = e.touches[0].pageX;
                lastX = e.touches[0].pageX;
                lastTime = performance.now();

                // Stop auto-play when dragging
                stopBannerAutoPlay();
            });

            bannerCarousel.addEventListener('touchmove', (e) => {
                if (isDragging) {
                    e.preventDefault();
                    const deltaX = e.touches[0].pageX - startX;

                    // Calculate velocity
                    const currentTime = performance.now();
                    const deltaTime = currentTime - lastTime;
                    if (deltaTime > 0) {
                        velocity = (e.touches[0].pageX - lastX) / deltaTime;
                        lastX = e.touches[0].pageX;
                        lastTime = currentTime;
                    }

                    // Change slide based on drag distance
                    if (Math.abs(deltaX) > 50 && canTrigger) {
                        canTrigger = false; // re-enabled after transition
                        if (deltaX > 0) {
                            scrollBanner('left');
                        } else {
                            scrollBanner('right');
                        }
                        // allow continuing the touch drag: reset startX
                        startX = e.touches[0].pageX;
                        lastX = e.touches[0].pageX;
                        lastTime = performance.now();
                    }
                }
            });

            bannerCarousel.addEventListener('touchend', () => {
                if (isDragging) {
                    isDragging = false;
                    if (window.__bannerResumeFallback) {
                        clearTimeout(window.__bannerResumeFallback);
                        window.__bannerResumeFallback = null;
                    }
                    startBannerAutoPlay();
                }
            });

            // Ensure we catch mouseup/touchend even if it happens outside the carousel
            function globalEndHandler() {
                if (isDragging) {
                    isDragging = false;
                    if (bannerCarousel) {
                        bannerCarousel.style.cursor = 'default';
                        bannerCarousel.style.userSelect = 'auto';
                    }
                    if (window.__bannerResumeFallback) {
                        clearTimeout(window.__bannerResumeFallback);
                        window.__bannerResumeFallback = null;
                    }
                    startBannerAutoPlay();
                }
            }
            document.addEventListener('mouseup', globalEndHandler);
            document.addEventListener('touchend', globalEndHandler);
        }

        // Start auto-play
        function startBannerAutoPlay() {
            // Clear any existing interval first to avoid duplicates
            if (bannerInterval) {
                clearInterval(bannerInterval);
                bannerInterval = null;
                console.log('Cleared existing bannerInterval before starting a new one');
            }
            bannerInterval = setInterval(() => {
                scrollBanner('right');
            }, 8000); // Change slide every 8 seconds
        }

        // Stop auto-play
        function stopBannerAutoPlay() {
            if (bannerInterval) {
                clearInterval(bannerInterval);
                bannerInterval = null;
            }
        }

        // Event delegation for employer cards
        document.addEventListener('click', function(e) {
            const employerCard = e.target.closest('.employer-card');
            if (employerCard) {
                const companyId = employerCard.dataset.companyId;
                if (companyId) {
                    viewCompanyDetail(companyId);
                }
            }
        });

        // ===== AI ANALYSIS FUNCTIONS - Now loaded from external file =====
        // See: public/js/ai-analysis.js

        // Note: loadJobCategories() is called in initHomePage() via Promise.all
        // No need to call it again in DOMContentLoaded
    </script>

    <!-- Include API Helper -->
    <script src="{{ asset('js/api.js') }}"></script>
    <script src="{{ asset('js/auth.js') }}"></script>
    <script src="{{ asset('js/ai-feedback-widget.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/ai-analysis.js') }}?v={{ time() }}"></script>
</body>

</html>