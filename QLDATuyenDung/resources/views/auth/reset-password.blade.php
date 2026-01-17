<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lại mật khẩu - WebCV</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="{{ asset('css/components/login.css') }}" rel="stylesheet">
    <link href="{{ asset('css/components/reset-password.css') }}" rel="stylesheet">
</head>
<body>
    <div class="auth-container">
        <div class="auth-left">
            <h1>WebCV</h1>
            <p>Đặt lại mật khẩu mới</p>
            <ul class="benefits">
                <li><i class="fas fa-lock"></i> Mật khẩu mới an toàn</li>
                <li><i class="fas fa-shield-alt"></i> Mã hóa bảo mật</li>
                <li><i class="fas fa-check-circle"></i> Xác thực qua email</li>
                <li><i class="fas fa-key"></i> Quyền truy cập mới</li>
            </ul>
            
            <div class="reset-guide">
                <h4><i class="fas fa-info-circle"></i> Yêu cầu mật khẩu:</h4>
                <div class="guide-content">
                    <p>🔢 Ít nhất 8 ký tự</p>
                    <p>🔤 Có chữ hoa và chữ thường</p>
                    <p>🔢 Có ít nhất 1 số</p>
                    <p>🔣 Nên có ký tự đặc biệt</p>
                </div>
            </div>
        </div>

        <div class="auth-right">
            <div class="auth-header">
                <h2>Đặt lại mật khẩu</h2>
                <p>Nhập mật khẩu mới cho tài khoản <strong>{{ $email }}</strong></p>
            </div>

            <div class="error-message" id="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <span id="error-text"></span>
            </div>

            <div class="success-message" id="success-message">
                <i class="fas fa-check-circle"></i>
                <span id="success-text"></span>
            </div>

            <form id="reset-password-form" onsubmit="return false;">
                @csrf
                <input type="hidden" id="token" value="{{ $token }}">
                <input type="hidden" id="email" value="{{ $email }}">
                
                <div class="form-group">
                    <label for="password">Mật khẩu mới</label>
                    <div class="password-input-container">
                        <input type="password" id="password" name="password" required 
                               placeholder="Nhập mật khẩu mới" minlength="8">
                        <button type="button" class="toggle-password" onclick="togglePassword('password')">
                            <i class="fas fa-eye" id="password-eye"></i>
                        </button>
                    </div>
                    <div class="error" id="password-error"></div>
                    <div class="password-strength" id="password-strength"></div>
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Xác nhận mật khẩu</label>
                    <div class="password-input-container">
                        <input type="password" id="password_confirmation" name="password_confirmation" required 
                               placeholder="Nhập lại mật khẩu mới" minlength="8">
                        <button type="button" class="toggle-password" onclick="togglePassword('password_confirmation')">
                            <i class="fas fa-eye" id="password_confirmation-eye"></i>
                        </button>
                    </div>
                    <div class="error" id="password_confirmation-error"></div>
                </div>

                <button type="submit" class="btn-login" id="reset-btn">
                    <i class="fas fa-save"></i>
                    Cập nhật mật khẩu
                </button>

                <div class="loading" id="loading">
                    <i class="fas fa-spinner"></i>
                    Đang xử lý...
                </div>
            </form>

            <div class="auth-footer">
                <p>Nhớ lại mật khẩu? <a href="{{ route('login') }}">Đăng nhập ngay</a></p>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const eye = document.getElementById(fieldId + '-eye');
            
            if (field.type === 'password') {
                field.type = 'text';
                eye.classList.remove('fa-eye');
                eye.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                eye.classList.remove('fa-eye-slash');
                eye.classList.add('fa-eye');
            }
        }

        // Password strength checker
        function checkPasswordStrength(password) {
            let strength = 0;
            let feedback = [];

            if (password.length >= 8) strength++;
            else feedback.push('Ít nhất 8 ký tự');

            if (/[a-z]/.test(password)) strength++;
            else feedback.push('Chữ thường');

            if (/[A-Z]/.test(password)) strength++;
            else feedback.push('Chữ hoa');

            if (/[0-9]/.test(password)) strength++;
            else feedback.push('Số');

            if (/[^A-Za-z0-9]/.test(password)) strength++;
            else feedback.push('Ký tự đặc biệt');

            return { strength, feedback };
        }

        // Real-time password strength
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.getElementById('password-strength');
            
            if (password.length === 0) {
                strengthDiv.innerHTML = '';
                return;
            }

            const { strength, feedback } = checkPasswordStrength(password);
            
            let strengthText = '';
            let strengthClass = '';
            
            if (strength <= 2) {
                strengthText = 'Yếu';
                strengthClass = 'weak';
            } else if (strength <= 3) {
                strengthText = 'Trung bình';
                strengthClass = 'medium';
            } else if (strength <= 4) {
                strengthText = 'Mạnh';
                strengthClass = 'strong';
            } else {
                strengthText = 'Rất mạnh';
                strengthClass = 'very-strong';
            }

            strengthDiv.innerHTML = `
                <div class="strength-meter ${strengthClass}">
                    <div class="strength-bar"></div>
                </div>
                <span class="strength-text ${strengthClass}">Độ mạnh: ${strengthText}</span>
                ${feedback.length > 0 ? '<div class="strength-feedback">Thiếu: ' + feedback.join(', ') + '</div>' : ''}
            `;
        });

        // Form validation
        function validateForm() {
            let isValid = true;
            const errors = {};

            // Clear previous errors
            document.querySelectorAll('.error').forEach(error => error.textContent = '');

            const password = document.getElementById('password').value;
            const passwordConfirmation = document.getElementById('password_confirmation').value;

            // Password validation
            if (!password) {
                errors.password = 'Vui lòng nhập mật khẩu mới';
                isValid = false;
            } else if (password.length < 8) {
                errors.password = 'Mật khẩu phải có ít nhất 8 ký tự';
                isValid = false;
            }

            // Password confirmation validation
            if (!passwordConfirmation) {
                errors.password_confirmation = 'Vui lòng xác nhận mật khẩu';
                isValid = false;
            } else if (password !== passwordConfirmation) {
                errors.password_confirmation = 'Mật khẩu xác nhận không khớp';
                isValid = false;
            }

            // Display errors
            Object.keys(errors).forEach(key => {
                const errorElement = document.getElementById(key + '-error');
                if (errorElement) {
                    errorElement.textContent = errors[key];
                }
            });

            return isValid;
        }

        // Form submission
        document.getElementById('reset-password-form').addEventListener('submit', function(e) {
            e.preventDefault();

            if (!validateForm()) {
                return;
            }

            handleResetPassword();
        });

        async function handleResetPassword() {
            const resetBtn = document.getElementById('reset-btn');
            const loading = document.getElementById('loading');
            const errorMessage = document.getElementById('error-message');
            const successMessage = document.getElementById('success-message');

            // Show loading
            resetBtn.disabled = true;
            loading.style.display = 'block';
            errorMessage.style.display = 'none';
            successMessage.style.display = 'none';

            try {
                const token = document.getElementById('token').value;
                const email = document.getElementById('email').value;
                const password = document.getElementById('password').value;
                const passwordConfirmation = document.getElementById('password_confirmation').value;

                // Gọi API reset password
                const response = await fetch('/api/reset-password', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        token: token,
                        email: email,
                        password: password,
                        password_confirmation: passwordConfirmation
                    })
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Có lỗi xảy ra');
                }

                // Show success
                document.getElementById('success-text').innerHTML = 
                    `<strong>✅ Đặt lại mật khẩu thành công!</strong><br>
                     Bạn có thể đăng nhập bằng mật khẩu mới ngay bây giờ.`;
                successMessage.style.display = 'block';
                
                resetBtn.innerHTML = '<i class="fas fa-check"></i> Hoàn thành!';
                resetBtn.style.background = '#28a745';
                
                // Auto redirect to login after 3 seconds
                setTimeout(() => {
                    window.location.href = '{{ route("login") }}';
                }, 3000);

            } catch (error) {
                console.error('Reset password error:', error);
                document.getElementById('error-text').textContent = error.message;
                errorMessage.style.display = 'block';
                
                // Reset button
                resetBtn.innerHTML = '<i class="fas fa-save"></i> Cập nhật mật khẩu';
                resetBtn.style.background = '#28a745';
            } finally {
                resetBtn.disabled = false;
                loading.style.display = 'none';
            }
        }

        // Auto-fill email info on load
        window.addEventListener('load', function() {
            const email = '{{ $email }}';
            const token = '{{ $token }}';
            
            console.log('Reset page loaded for email:', email);
            console.log('Token:', token.substring(0, 10) + '...');
        });
    </script>
</body>
</html>