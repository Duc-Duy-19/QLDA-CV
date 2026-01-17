<!-- Company Sidebar Component -->
@php
    // Server-side detection
    $serverIsEmployer = auth()->check() && auth()->user()->role === 'employer';
@endphp

<nav class="sidebar">
    <ul class="sidebar-menu">
        <li style="display: none;"><a href="{{ route('candidate.dashboard') }}">
            <i class="fas fa-tachometer-alt"></i>
            Account
        </a></li>

        <!-- Menu dành cho Recruiter/Employer -->
        <!-- Render recruiter items always but hide by default when server doesn't confirm employer.
             Client-side JS will reveal them if localStorage indicates role=employer. -->
        <li class="menu-recruiter" style="{{ $serverIsEmployer ? '' : 'display: none;' }}"><a href="{{ route('candidate.profile') }}">
            <i class="fas fa-user"></i>
            Thông tin cá nhân
        </a></li>
        <li class="menu-recruiter" style="{{ $serverIsEmployer ? '' : 'display: none;' }}"><a href="{{ route('company.info') }}" @if(request()->routeIs('company.info')) class="active" @endif>
            <i class="fas fa-building"></i>
            Thông tin công ty
        </a></li>
        <li class="menu-recruiter" style="{{ $serverIsEmployer ? '' : 'display: none;' }}"><a href="{{ route('employer.jobs') }}" @if(request()->routeIs('employer.jobs')) class="active" @endif>
            <i class="fas fa-briefcase"></i>
            Quản lý việc làm
        </a></li>
        <li class="menu-recruiter" style="{{ $serverIsEmployer ? '' : 'display: none;' }}"><a href="{{ route('company.members') }}" @if(request()->routeIs('company.members')) class="active" @endif>
            <i class="fas fa-users"></i>
            Quản lý thành viên
        </a></li>
        <li class="menu-recruiter" style="{{ $serverIsEmployer ? '' : 'display: none;' }}"><a href="{{ route('employer.manage-applications') }}" @if(request()->routeIs('employer.manage-applications')) class="active" @endif>
            <i class="fas fa-file-alt"></i>
            Quản lý ứng tuyển
        </a></li>

        <!-- Menu chung -->
        <li><a href="{{ route('candidate.notifications') }}">
            <i class="fas fa-bell"></i>
            Thông báo
        </a></li>
    </ul>
</nav>

<!-- Client-side fallback: if server didn't detect employer but localStorage shows role=employer,
     reveal recruiter menu items so UI matches client state while server-side auth remains authoritative on actions. -->
<script>
    (function() {
        try {
            var serverIsEmployer = {{ $serverIsEmployer ? 'true' : 'false' }};
            if (!serverIsEmployer) {
                var current = localStorage.getItem('currentUser');
                if (current) {
                    var user = JSON.parse(current);
                    if (user && (user.role === 'employer' || user.role === 'recruiter')) {
                        document.querySelectorAll('.menu-recruiter').forEach(function(el) {
                            el.style.display = 'block';
                        });
                    }
                }
            }
        } catch (e) {
            // ignore
            console.warn('Sidebar client fallback error', e);
        }
    })();
</script>