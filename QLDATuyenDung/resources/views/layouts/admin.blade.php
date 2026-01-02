<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Dashboard') - WebCV</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="{{ asset('css/admin/admin.css') }}" rel="stylesheet">

    <!-- Page-specific styles stack -->
    @stack('styles')
</head>

<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <div class="admin-logo">
                <h2>WebCV</h2>
                <p>Admin Dashboard</p>
            </div>

            <nav class="admin-nav">
                <div class="nav-item">
                    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </div>

                <div class="nav-item">
                    <a href="{{ route('admin.companies.pending') }}" class="nav-link {{ request()->routeIs('admin.companies.pending') ? 'active' : '' }}">
                        <i class="fas fa-clock"></i>
                        Công ty chờ duyệt
                        @if(isset($pendingCount) && $pendingCount > 0)
                        <span class="badge" style="background: #e74c3c; color: white; padding: 2px 6px; border-radius: 10px; font-size: 10px; margin-left: auto;">{{ $pendingCount }}</span>
                        @endif
                    </a>
                </div>

                <div class="nav-item">
                    <a href="{{ route('admin.payments.pending') }}" class="nav-link {{ request()->routeIs('admin.payments.pending') ? 'active' : '' }}">
                        <i class="fas fa-credit-card"></i>
                        Thanh toán chờ duyệt
                    </a>
                </div>

                <div class="nav-item">
                    <a href="{{ route('admin.revenue-report') }}" class="nav-link {{ request()->routeIs('admin.revenue-report') ? 'active' : '' }}">
                        <i class="fas fa-chart-line"></i>
                        Báo cáo doanh thu
                    </a>
                </div>

                <div class="nav-item">
                    <a href="{{ route('admin.companies') }}" class="nav-link {{ request()->routeIs('admin.companies') ? 'active' : '' }}">
                        <i class="fas fa-building"></i>
                        Tất cả công ty
                    </a>
                </div>

                <div class="nav-item">
                    <a href="{{ route('admin.users') }}" class="nav-link {{ request()->routeIs('admin.users') ? 'active' : '' }}">
                        <i class="fas fa-users"></i>
                        Quản lý User
                    </a>
                </div>

                <div class="nav-item">
                    <a href="{{ route('admin.categories') }}" class="nav-link {{ request()->routeIs('admin.categories') ? 'active' : '' }}">
                        <i class="fas fa-tags"></i>
                        Danh mục ngành nghề
                    </a>
                </div>

                <div class="nav-item">
                    <a href="{{ route('admin.industry-contexts.index') }}" class="nav-link {{ request()->routeIs('admin.industry-contexts.*') ? 'active' : '' }}">
                        <i class="fas fa-brain"></i>
                        Ngữ cảnh AI
                    </a>
                </div>

                <div class="nav-item">
                    <a href="{{ route('admin.ai-feedback') }}" class="nav-link {{ request()->routeIs('admin.ai-feedback') ? 'active' : '' }}">
                        <i class="fas fa-comments"></i>
                        AI Feedback
                    </a>
                </div>

                <!-- 'Cài đặt' menu removed as requested -->
            </nav>
        </div>
        <!-- Backdrop (sibling of admin-sidebar so sibling CSS selectors work) -->
        <div class="sidebar-backdrop" onclick="toggleSidebar()" aria-hidden="true"></div>

        <!-- Main Content -->
        <div class="admin-main">
            <!-- Header -->
            <div class="admin-header">
                <button class="mobile-toggle" onclick="toggleSidebar()" aria-label="Toggle menu" title="Menu">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="admin-title">@yield('page-title', 'Dashboard')</h1>

                <div class="admin-user">
                    <div class="user-info">
                        <div class="user-name">{{ Auth::user()->name ?? 'Admin' }}</div>
                        <div class="user-role">Quản trị viên</div>
                    </div>
                    <button class="logout-btn" onclick="logout()">
                        <i class="fas fa-sign-out-alt"></i>
                        Đăng xuất
                    </button>
                </div>
            </div>

            <!-- Content -->
            <div class="admin-content">
                @yield('content')
            </div>
        </div>
    </div>

    <script src="{{ asset('js/api.js') }}"></script>
    <script src="{{ asset('js/auth.js') }}"></script>

    <script>
        // CRITICAL: Override alert BEFORE any other scripts run to catch modal "1"
        (function() {
            const originalAlert = window.alert;
            window.alert = function(message) {
                // Log chi tiết để debug
                console.log('🔔 Alert intercepted:', {
                    message: message,
                    type: typeof message,
                    value: message,
                    stack: new Error().stack
                });

                // Chặn hoàn toàn các alert có giá trị số hoặc boolean đơn giản
                if (typeof message === 'number' || typeof message === 'boolean' ||
                    message === '1' || message === 1 ||
                    message === 'true' || message === true ||
                    message === 'false' || message === false ||
                    message === 'null' || message === 'undefined') {
                    console.warn('🚫 BLOCKED unwanted alert:', message);
                    console.warn('Stack trace:', new Error().stack);
                    return; // KHÔNG hiển thị
                }

                // Alert bình thường vẫn hiển thị
                return originalAlert.call(window, message);
            };

            console.log('✅ Alert override installed');
        })();

        // Ensure Sanctum CSRF cookie is present so API calls using cookie auth work
        if (window.APIHelper && typeof window.APIHelper.ensureCsrf === 'function') {
            window.APIHelper.ensureCsrf().catch(err => console.warn('Failed to fetch CSRF cookie:', err));
        }

        // Admin dashboard sử dụng session authentication
        console.log('🔍 Admin dashboard loaded with session authentication');

        // Token interceptor cho API calls
        const originalFetch = window.fetch;
        window.fetch = function(...args) {
            const [url, options = {}] = args;

            // Chỉ thêm token cho API calls
            if (url.includes('/api/')) {
                const authToken = localStorage.getItem('authToken');
                if (authToken) {
                    options.headers = {
                        ...options.headers,
                        'Authorization': 'Bearer ' + authToken,
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    };
                }
            }

            return originalFetch.apply(this, args);
        };

        // Logout function
        async function logout() {
            if (confirm('Bạn có chắc muốn đăng xuất?')) {
                try {
                    await fetch('/api/logout', {
                        method: 'POST',
                        headers: {
                            'Authorization': 'Bearer ' + localStorage.getItem('authToken'),
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });
                } catch (error) {
                    console.error('Logout error:', error);
                }

                // Clear local storage
                localStorage.clear();

                // Redirect to login
                window.location.href = '{{ route("login") }}';
            }
        }

        // Mobile menu toggle
        function toggleSidebar() {
            const sidebar = document.querySelector('.admin-sidebar');
            sidebar.classList.toggle('show');
            // also prevent background scrolling when sidebar is open
            document.body.classList.toggle('sidebar-open');
        }

        // Responsive table helper: add data-label to each td from corresponding th
        // This avoids changing all Blade table templates; runs on small screens only.
        function initResponsiveTables() {
            const tables = document.querySelectorAll('.admin-table .table');
            tables.forEach(tbl => {
                const ths = Array.from(tbl.querySelectorAll('thead th')).map(th => th.textContent.trim());
                const rows = tbl.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const cells = Array.from(row.children);
                    cells.forEach((cell, idx) => {
                        if (!cell.hasAttribute('data-label')) {
                            const label = ths[idx] || '';
                            cell.setAttribute('data-label', label);
                        }
                    });
                });
            });
        }

        // Run on load and on orientation/resize (debounced)
        window.addEventListener('load', initResponsiveTables);
        let _rt;
        window.addEventListener('resize', () => {
            clearTimeout(_rt);
            _rt = setTimeout(initResponsiveTables, 250);
        });
    </script>

    <!-- Page-specific scripts stack -->
    @stack('scripts')
</body>

</html>