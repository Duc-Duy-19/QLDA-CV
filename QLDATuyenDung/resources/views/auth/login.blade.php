<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - WebCV</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="{{ asset('css/components/login.css') }}" rel="stylesheet">
    <style>
        .error { color: #d9534f; margin-top: 6px; font-size: 0.95em; }
        .error-message { display: none; }
        .loading { display: none; }
        .is-invalid { border-color: #d9534f !important; box-shadow: 0 0 0 0.2rem rgba(217,83,79,0.08); }
    </style>
</head>
<body>
    

    <div class="auth-container">
        <div class="auth-left">
            <h1>WebCV</h1>
            <p>Chào mừng bạn quay trở lại!</p>
            <ul class="benefits">
                <li><i class="fas fa-briefcase"></i> Truy cập hàng nghìn việc làm</li>
                <li><i class="fas fa-chart-line"></i> Theo dõi ứng tuyển</li>
                <li><i class="fas fa-bell"></i> Nhận thông báo việc làm mới</li>
                <li><i class="fas fa-arrow-up"></i> Nâng cấp lên nhà tuyển dụng</li>
            </ul>
        </div>

        <div class="auth-right">
            <div class="auth-header">
                <h2>Đăng nhập</h2>
                <p>Đăng nhập vào tài khoản của bạn</p>
            </div>

            <div class="error-message" id="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <span id="error-text"></span>
            </div>


            <form id="login-form" onsubmit="return false;" novalidate>
                @csrf
                <div class="form-group">
                    <label for="email">Email</label>
                    <!-- remove native 'required' so custom JS validation messages are used -->
                    <input type="email" id="email" name="email">
                    <div class="error" id="email-error"></div>
                </div>

                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <!-- remove native 'required' so custom JS validation messages are used -->
                    <input type="password" id="password" name="password">
                    <div class="error" id="password-error"></div>
                </div>

                <div class="form-options">
                    <a href="{{ route('password.request') }}" class="forgot-password">Quên mật khẩu?</a>
                </div>

                <button type="submit" class="btn-login" id="login-btn">
                    <i class="fas fa-sign-in-alt"></i>
                    Đăng nhập
                </button>

                <div class="loading" id="loading">
                    <i class="fas fa-spinner"></i>
                    Đang xử lý...
                </div>
            </form>

            <div class="auth-footer">
                <p>Chưa có tài khoản? <a href="{{ route('register') }}">Đăng ký ngay</a></p>
            </div>
        </div>
    </div>

    <script>
        const API_BASE_URL = '{{ url("/api") }}';


        // Form validation
        function validateForm() {
            let isValid = true;
            const errors = {};

            // Clear previous errors and visuals
            document.querySelectorAll('.error').forEach(error => error.textContent = '');
            ['email','password'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.classList.remove('is-invalid');
            });

            // Email validation
            const email = document.getElementById('email').value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!email) {
                // match LoginRequest message
                errors.email = 'Vui lòng nhập email.';
                isValid = false;
            } else if (!emailRegex.test(email)) {
                // match LoginRequest message
                errors.email = 'Email không hợp lệ.';
                isValid = false;
            }

            // Password validation (enforce min:8 to match LoginRequest)
            const password = document.getElementById('password').value;
            if (!password) {
                errors.password = 'Vui lòng nhập mật khẩu.';
                isValid = false;
            } else if (password.length > 0 && password.length < 8) {
                errors.password = 'Mật khẩu phải có ít nhất 8 ký tự.';
                isValid = false;
            }

            // Display client-side validation errors
            Object.keys(errors).forEach(key => {
                setFieldError(key, errors[key]);
            });

            return isValid;
        }

        // Form submission - sử dụng JavaScript API
        document.getElementById('login-form').addEventListener('submit', function(e) {
            e.preventDefault(); // Ngăn form submit mặc định

            if (!validateForm()) {
                return;
            }

            // Gọi async function
            handleLogin();
        });

        async function handleLogin() {
            const loginBtn = document.getElementById('login-btn');
            const loading = document.getElementById('loading');
            const errorMessage = document.getElementById('error-message');

            // Show loading
            loginBtn.disabled = true;
            loading.style.display = 'block';
            errorMessage.style.display = 'none';

            try {
                const email = document.getElementById('email').value.trim();
                const password = document.getElementById('password').value;

                console.log('🔍 Bắt đầu đăng nhập với email:', email);
                console.log('🔍 API URL:', `${API_BASE_URL}/login`);

                // Gọi API đăng nhập (sử dụng web-login để tạo session trên server cho admin)
                const response = await fetch('/api/web-login', {
                    method: 'POST',
                    credentials: 'same-origin', // allow session cookie to be set for server-side auth
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        email: email,
                        password: password
                    })
                });

                console.log('🔍 Response status:', response.status);
                const data = await response.json();
                console.log('🔍 Response data:', data);

                if (!response.ok) {
                    // Nếu backend trả errors (validation), hiển thị theo trường
                    if (data.errors) {
                        const shown = displayFieldErrors(data.errors);
                        if (shown) {
                            loginBtn.disabled = false;
                            loading.style.display = 'none';
                            return;
                        }

                        let fallback = '';
                        Object.keys(data.errors).forEach(key => {
                            fallback += data.errors[key].join(', ') + ' ';
                        });
                        throw new Error(fallback.trim());
                    }

                    if (data.message) {
                        const mapped = displayMessageToField(data.message);
                        if (mapped) {
                            loginBtn.disabled = false;
                            loading.style.display = 'none';
                            return;
                        }
                        throw new Error(data.message || 'Đăng nhập thất bại');
                    }

                    throw new Error('Đăng nhập thất bại');
                }

                // Clear any previous auth keys to avoid stale token/currentUser conflicts
                try {
                    ['token','authToken','auth_token','bearer_token','currentUser','isLoggedIn'].forEach(k => localStorage.removeItem(k));
                } catch(e) { console.warn('Could not clear localStorage auth keys', e); }

                // Lưu token và thông tin user vào localStorage
                if (data.token) {
                    localStorage.setItem('authToken', data.token);
                    console.log('✅ Token đã lưu:', (data.token || '').substring(0, 30) + '...');
                } else {
                    console.warn('⚠️ Backend không trả token!');
                }
                localStorage.setItem('currentUser', JSON.stringify(data.user));
                localStorage.setItem('isLoggedIn', 'true');
                
                console.log('🔍 User data saved:', data.user);

                // Show success
                loginBtn.innerHTML = '<i class="fas fa-check"></i> Đăng nhập thành công!';
                loginBtn.style.background = '#28a745';

                // Frontend redirect (fallback) based on role
                // If admin -> go to admin dashboard; otherwise go to home.
                try {
                    if (data.user && data.user.role === 'admin') {
                        // Prefer backend session creation, but always redirect client-side as fallback
                        console.log('🔍 Redirecting admin user to admin dashboard (frontend)');
                        window.location.href = '{{ route("admin.dashboard") }}';
                        return;
                    }
                } catch (e) {
                    console.error('Error during role redirect check:', e);
                }

                // Default: non-admin user -> home
                const redirectUrl = '{{ route("home") }}';
                console.log('🔍 Redirecting to:', redirectUrl);
                setTimeout(() => { window.location.href = redirectUrl; }, 600);

            } catch (error) {
                console.error('Login error:', error);
                document.getElementById('error-text').textContent = error.message;
                errorMessage.style.display = 'block';
                
                // Reset button
                loginBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Đăng nhập';
                loginBtn.style.background = '#28a745';
            } finally {
                loginBtn.disabled = false;
                loading.style.display = 'none';
            }
        }

        // Helper: show field-level errors from backend
        // Returns true if at least one field-specific error was displayed
        function displayFieldErrors(errors) {
            // Clear existing field errors
            document.querySelectorAll('.error').forEach(el => el.textContent = '');

            let any = false;
            Object.keys(errors).forEach(key => {
                const raw = key.split('.')[0];
                const mapped = {
                    'email': 'email-error',
                    'password': 'password-error',
                    'password_confirmation': 'password-error'
                }[raw] || (raw + '-error');
                const el = document.getElementById(mapped);
                if (el) {
                    // Translate message(s) to Vietnamese before showing
                    const msg = errors[key].join(', ');
                    el.textContent = translateMessage(msg, raw);
                    any = true;
                } else {
                    const errorText = document.getElementById('error-text');
                    if (errorText) {
                        const msg = errors[key].join(', ');
                        errorText.textContent = (errorText.textContent ? errorText.textContent + ' ' : '') + translateMessage(msg);
                        document.getElementById('error-message').style.display = 'block';
                    }
                }
            });
            return any;
        }

        // Try to map generic message to a field by keyword
        // - If message mentions both email and password (e.g. "Email hoặc mật khẩu không đúng")
        //   show it under both fields.
        // - Supports Vietnamese and English keywords.
        function displayMessageToField(message) {
            const lower = message.toLowerCase();
            const hasEmail = lower.includes('email') || lower.includes('e-mail');
            const hasPassword = lower.includes('password') || lower.includes('mật khẩu');

            // If backend returns the common generic auth failure message, show it under BOTH fields
            // e.g. backend often returns: "Email hoặc mật khẩu không đúng" — map to email+password so
            // users see the error near both inputs instead of only the password field.
            if (message && message.toLowerCase().includes('email hoặc mật khẩu không đúng')) {
                setFieldError('email', message);
                setFieldError('password', message);
                return true;
            }

            if (hasEmail && hasPassword) {
                // Backend returned a generic message mentioning both fields; show under both.
                setFieldError('email', message);
                setFieldError('password', message);
                return true;
            }

            if (hasEmail) {
                setFieldError('email', message);
                return true;
            }
            if (hasPassword) {
                setFieldError('password', message);
                return true;
            }

            return false;
        }

        // set/clear helpers and mapping
        function setFieldError(fieldKey, message) {
            const map = { 'user_email': 'email', 'email_address': 'email', 'username': 'email', 'password_confirmation': 'password' };
            const raw = (map[fieldKey] || fieldKey).split('.')[0];
            const id = raw.indexOf('-') !== -1 ? raw : raw;
            const errorEl = document.getElementById(id + '-error');
            const inputEl = document.getElementById(id);
            const text = Array.isArray(message) ? message.join(', ') : message;
            if (errorEl) errorEl.textContent = translateMessage(text, raw);
            if (inputEl) inputEl.classList.add('is-invalid');
            if (!errorEl) {
                const errorText = document.getElementById('error-text');
                if (errorText) {
                    errorText.textContent = translateMessage(text);
                    document.getElementById('error-message').style.display = 'block';
                }
            }
        }

        function clearFieldError(fieldKey) {
            const map = { 'user_email': 'email', 'email_address': 'email', 'username': 'email', 'password_confirmation': 'password' };
            const raw = (map[fieldKey] || fieldKey).split('.')[0];
            const id = raw.indexOf('-') !== -1 ? raw : raw;
            const errorEl = document.getElementById(id + '-error');
            const inputEl = document.getElementById(id);
            if (errorEl) errorEl.textContent = '';
            if (inputEl) inputEl.classList.remove('is-invalid');
        }

        // Add listeners to clear errors on edit
        ['email','password'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('input', () => clearFieldError(id));
        });

        // Translate common backend messages (English) to Vietnamese.
        // field (optional) helps choosing phrasing.
        function translateMessage(message, field) {
            if (!message) return '';
            const m = message.toString();
            // common English -> Vietnamese mappings
            const map = [
                { en: 'The email has already been taken', vi: 'Email đã được đăng ký' },
                { en: 'The email has already been taken.', vi: 'Email đã được đăng ký' },
                { en: 'The password must be at least', vi: 'Mật khẩu phải có ít nhất' },
                { en: 'The password confirmation does not match', vi: 'Xác nhận mật khẩu không khớp' },
                { en: 'The given data was invalid', vi: 'Dữ liệu nhập không hợp lệ' },
                { en: 'The email field is required', vi: 'Vui lòng nhập email' },
                { en: 'The password field is required', vi: 'Vui lòng nhập mật khẩu' }
            ];

            for (let i = 0; i < map.length; i++) {
                if (m.toLowerCase().includes(map[i].en.toLowerCase())) {
                    // If password length message, keep the number
                    if (map[i].en.includes('must be at least')) {
                        // extract number
                        const num = m.match(/\d+/);
                        return num ? map[i].vi + ' ' + num[0] + ' ký tự' : map[i].vi;
                    }
                    return map[i].vi;
                }
            }

            // Vietnamese messages pass-through
            return message;
        }

        // Check if user is already logged in - TỰ ĐỘNG REDIRECT
        window.addEventListener('load', function() {
            const isLoggedIn = localStorage.getItem('isLoggedIn');
            const currentUser = localStorage.getItem('currentUser');
            
            if (isLoggedIn === 'true' && currentUser) {
                try {
                    const user = JSON.parse(currentUser);
                    console.log('🔍 User already logged in:', user);
                    console.log('🔍 User role:', user.role);
                    
                    // TỰ ĐỘNG REDIRECT dựa trên role
                    if (user.role === 'admin') {
                        console.log('🔍 Admin already logged in, redirecting to admin dashboard...');
                        window.location.href = '{{ route("admin.dashboard") }}';
                    } else {
                        console.log('🔍 User already logged in, redirecting to home...');
                        window.location.href = '{{ route("home") }}';
                    }
                } catch (error) {
                    console.error('Error parsing user data:', error);
                    // Clear invalid data
                    localStorage.removeItem('isLoggedIn');
                    localStorage.removeItem('currentUser');
                    localStorage.removeItem('authToken');
                }
            }
        });

        // Debug: Test localStorage
        console.log('🔍 Login page loaded');
        console.log('🔍 API_BASE_URL:', API_BASE_URL);
        console.log('🔍 Current localStorage:', localStorage);
    </script>
</body>
</html>
