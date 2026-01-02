<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - WebCV</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="{{ asset('css/components/register.css') }}" rel="stylesheet">
    <style>
        /* Field-level error styling */
        .error { color: #d9534f; margin-top: 6px; font-size: 0.95em; }
        .error-message { display: none; }
        .success-message { display: none; }
        .loading { display: none; }
        /* Input invalid visual */
        .is-invalid { border-color: #d9534f !important; box-shadow: 0 0 0 0.2rem rgba(217,83,79,0.08); }
    </style>
</head>
<body>
    

    <div class="auth-container">
        <div class="auth-left">
            <h1>WebCV</h1>
            <p>Tham gia cộng đồng tìm việc làm lớn nhất Việt Nam</p>
            <ul class="features">
                <li><i class="fas fa-check"></i> Tìm việc làm phù hợp</li>
                <li><i class="fas fa-check"></i> Tạo CV chuyên nghiệp</li>
                <li><i class="fas fa-check"></i> Kết nối với nhà tuyển dụng</li>
                <li><i class="fas fa-check"></i> Cơ hội nghề nghiệp tốt nhất</li>
            </ul>
        </div>

        <div class="auth-right">
            <div class="auth-header">
                <h2>Đăng ký tài khoản</h2>
                <p>Tạo tài khoản miễn phí để bắt đầu tìm việc</p>
            </div>

            <div class="success-message" id="success-message">
                <i class="fas fa-check-circle"></i>
                Đăng ký thành công!
            </div>

            <div class="error-message" id="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <span id="error-text"></span>
            </div>

            <form id="register-form" novalidate>
                <div class="form-group">
                    <label for="name">Họ và tên *</label>
                    <input type="text" id="name" name="name">
                    <div class="error" id="name-error"></div>
                </div>


                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email">
                    <div class="error" id="email-error"></div>
                </div>

                <!-- Phone field removed as requested -->

                <div class="form-group">
                    <label for="password">Mật khẩu *</label>
                    <input type="password" id="password" name="password">
                    <div class="error" id="password-error"></div>
                </div>

                <div class="form-group">
                    <label for="confirm-password">Xác nhận mật khẩu *</label>
                    <input type="password" id="confirm-password" name="confirm-password">
                    <div class="error" id="confirm-password-error"></div>
                </div>

                <button type="submit" class="btn-register" id="register-btn">
                    <i class="fas fa-user-plus"></i>
                    Đăng ký ngay
                </button>

                <div class="loading" id="loading">
                    <i class="fas fa-spinner"></i>
                    Đang xử lý...
                </div>
            </form>

            <div class="auth-footer">
                <p>Đã có tài khoản? <a href="{{ route('login') }}">Đăng nhập ngay</a></p>
            </div>
        </div>
    </div>

    <script>
        const API_BASE_URL = '{{ url("/api") }}';
        let selectedRole = 'candidate';

        selectedRole = 'candidate';

        function validateForm() {
            let isValid = true;
            const errors = {};
            document.querySelectorAll('.error').forEach(error => error.textContent = '');
            const name = document.getElementById('name').value.trim();
            if (!name) {
                errors.name = 'Vui lòng nhập họ và tên.';
                isValid = false;
            }

            const email = document.getElementById('email').value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!email) {
                errors.email = 'Vui lòng nhập email.';
                isValid = false;
            } else if (!emailRegex.test(email)) {
                errors.email = 'Email không hợp lệ.';
                isValid = false;
            }
            // Phone removed from form; no client-side phone validation

            const password = document.getElementById('password').value;
            if (!password) {
                errors.password = 'Vui lòng nhập mật khẩu.';
                isValid = false;
            } else if (password.length < 8) {
                errors.password = 'Mật khẩu phải có ít nhất 8 ký tự.';
                isValid = false;
            }

            const confirmPassword = document.getElementById('confirm-password').value;
            if (!confirmPassword) {
                errors['confirm-password'] = 'Vui lòng nhập mật khẩu.';
                isValid = false;
            } else if (password !== confirmPassword) {
                errors['confirm-password'] = 'Mật khẩu xác nhận không khớp.';
                isValid = false;
            }

                    Object.keys(errors).forEach(key => {
                        const id = (key.indexOf('-') !== -1) ? key : key;
                        setFieldError(id, errors[key]);
                    });

            return isValid;
        }

        document.getElementById('register-form').addEventListener('submit', async function(e) {
            e.preventDefault();

            if (!validateForm()) {
                return;
            }

            const registerBtn = document.getElementById('register-btn');
            const loading = document.getElementById('loading');
            const successMessage = document.getElementById('success-message');
            const errorMessage = document.getElementById('error-message');

            registerBtn.disabled = true;
            loading.style.display = 'block';
            errorMessage.style.display = 'none';

            try {
                const userData = {
                    name: document.getElementById('name').value.trim(),
                    email: document.getElementById('email').value.trim(),
                    password: document.getElementById('password').value,
                    password_confirmation: document.getElementById('confirm-password').value
                };

                console.log('🔍 User data to send:', userData);
                console.log('🔍 API URL:', `${API_BASE_URL}/register`);
                const response = await fetch(`${API_BASE_URL}/register`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(userData)
                });

                console.log('🔍 Response status:', response.status);
                const data = await response.json();
                console.log('🔍 Response data:', data);

                if (!response.ok) {
                    if (data.errors) {
                        const shown = displayFieldErrors(data.errors);
                        if (shown) {
                            registerBtn.disabled = false;
                            loading.style.display = 'none';
                            return;
                        }
                        let fallback = '';
                        Object.keys(data.errors).forEach(key => {
                            fallback += data.errors[key].join(', ') + ' ';
                        });
                        throw new Error(fallback.trim());
                    }

                    // Một số backend trả về message (string) thay vì errors array
                    if (data.message) {
                        const mapped = displayMessageToField(data.message);
                        if (mapped) {
                            registerBtn.disabled = false;
                            loading.style.display = 'none';
                            return;
                        }
                        throw new Error(data.message || 'Đăng ký thất bại');
                    }

                    throw new Error('Đăng ký thất bại');
                }

                // Show success message
                successMessage.style.display = 'block';
                
                // Store user data and token in localStorage
                localStorage.setItem('authToken', data.token);
                localStorage.setItem('currentUser', JSON.stringify(data.user));
                localStorage.setItem('isLoggedIn', 'true');

                // Redirect to home page immediately
                window.location.href = '{{ route("home") }}';

            } catch (error) {
                console.error('Registration error:', error);
                document.getElementById('error-text').textContent = error.message;
                errorMessage.style.display = 'block';
            } finally {
                registerBtn.disabled = false;
                loading.style.display = 'none';
            }
        });

        // Helpers: set/clear field errors (with visual invalid class)
        function setFieldError(fieldKey, message) {
            // normalize key: support email, user_email, email_address, username
            const map = {
                'user_email': 'email',
                'email_address': 'email',
                'username': 'email',
                'confirmPassword': 'confirm-password'
            };
            const raw = (map[fieldKey] || fieldKey).split('.')[0];
            const id = raw.indexOf('-') !== -1 ? raw : raw; // keep hyphen keys
            const errorEl = document.getElementById(id + '-error');
            const inputEl = document.getElementById(id);
            const text = Array.isArray(message) ? message.join(', ') : message;
            if (errorEl) {
                errorEl.textContent = translateMessage(text, raw);
            }
            if (inputEl) {
                inputEl.classList.add('is-invalid');
            }
            // show banner if no specific error element
            if (!errorEl) {
                const errorText = document.getElementById('error-text');
                if (errorText) {
                    errorText.textContent = translateMessage(text);
                    document.getElementById('error-message').style.display = 'block';
                }
            }
        }

        function clearFieldError(fieldKey) {
            const map = {
                'user_email': 'email',
                'email_address': 'email',
                'username': 'email',
                'confirmPassword': 'confirm-password'
            };
            const raw = (map[fieldKey] || fieldKey).split('.')[0];
            const id = raw.indexOf('-') !== -1 ? raw : raw;
            const errorEl = document.getElementById(id + '-error');
            const inputEl = document.getElementById(id);
            if (errorEl) errorEl.textContent = '';
            if (inputEl) inputEl.classList.remove('is-invalid');
        }

        // Clear field errors when user edits inputs
        ['name','email','password','confirm-password'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('input', function() {
                    clearFieldError(id);
                });
            }
        });

            // Helper: hiển thị lỗi theo-field từ backend
            // Trả về true nếu có ít nhất một lỗi được map tới field cụ thể
            function displayFieldErrors(errors) {
                // Clear existing field errors
                document.querySelectorAll('.error').forEach(el => el.textContent = '');

                const fieldMap = {
                    'name': 'name-error',
                    'email': 'email-error',
                    'password': 'password-error',
                    'password_confirmation': 'confirm-password-error'
                };

                let any = false;
                Object.keys(errors).forEach(key => {
                    const raw = key.split('.')[0];
                    const mappedId = fieldMap[raw] || (raw + '-error');
                    const el = document.getElementById(mappedId);
                    if (el) {
                        // Use backend message directly (RegisterRequest returns Vietnamese messages)
                        el.textContent = errors[key].join(', ');
                        any = true;
                    } else {
                        // Không có element tương ứng — sẽ append vào banner sau
                        const errorText = document.getElementById('error-text');
                        if (errorText) {
                            errorText.textContent = (errorText.textContent ? errorText.textContent + ' ' : '') + errors[key].join(', ');
                            document.getElementById('error-message').style.display = 'block';
                        }
                    }
                });

                return any;
            }

            // Helper: nếu backend chỉ trả message (string), thử map message vào field dựa trên từ khóa
            function displayMessageToField(message) {
                const lower = message.toLowerCase();
                const hasEmail = lower.includes('email') || lower.includes('e-mail');
                const hasPassword = lower.includes('password') || lower.includes('mật khẩu');
                const hasPhone = lower.includes('phone') || lower.includes('số điện thoại');

                    // Specific phrase mapping from RegisterRequest
                    if (lower.includes('đã được sử dụng') || lower.includes('đã được đăng ký')) {
                        const el = document.getElementById('email-error');
                        if (el) el.textContent = message;
                        return true;
                    }

                    if (hasEmail && hasPassword) {
                    const elEmail = document.getElementById('email-error');
                    const elPass = document.getElementById('password-error');
                        if (elEmail) elEmail.textContent = translateMessage(message, 'email');
                        if (elPass) elPass.textContent = translateMessage(message, 'password');
                    return true;
                }

                if (hasEmail) {
                    const el = document.getElementById('email-error');
                    if (el) el.textContent = translateMessage(message, 'email');
                    return true;
                }
                if (hasPassword) {
                    const el = document.getElementById('password-error');
                    if (el) el.textContent = translateMessage(message, 'password');
                    return true;
                }
                if (hasPhone) {
                    const el = document.getElementById('phone-error');
                    if (el) el.textContent = translateMessage(message, 'phone');
                    return true;
                }
                return false;
            }

            // Translate common backend messages (English) to Vietnamese.
            // field (optional) helps choosing phrasing.
            function translateMessage(message, field) {
                if (!message) return '';
                const m = message.toString();
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
                        if (map[i].en.includes('must be at least')) {
                            const num = m.match(/\d+/);
                            return num ? map[i].vi + ' ' + num[0] + ' ký tự' : map[i].vi;
                        }
                        return map[i].vi;
                    }
                }

                return message;
            }
    </script>
</body>
</html>
