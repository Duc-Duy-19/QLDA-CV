<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu - WebCV</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="{{ asset('css/components/login.css') }}" rel="stylesheet">
    <link href="{{ asset('css/components/forgot-password.css') }}" rel="stylesheet">
</head>
<body>
    <div class="auth-container">
        <div class="auth-left">
            <h1>WebCV</h1>
            <p>Khôi phục mật khẩu của bạn</p>
            <ul class="benefits">
                <li><i class="fas fa-key"></i> Reset mật khẩu an toàn</li>
                <li><i class="fas fa-envelope"></i> Nhận link qua email</li>
                <li><i class="fas fa-shield-alt"></i> Bảo mật tuyệt đối</li>
                <li><i class="fas fa-clock"></i> Xử lý nhanh chóng</li>
            </ul>
            
            
        </div>

        <div class="auth-right">
            <div class="auth-header">
                <h2>Quên mật khẩu</h2>
                <p>Nhập email để nhận link khôi phục mật khẩu</p>
            </div>

            <div class="error-message" id="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <span id="error-text"></span>
            </div>

            <div class="success-message" id="success-message">
                <i class="fas fa-check-circle"></i>
                <span id="success-text"></span>
            </div>

            <form id="forgot-password-form" onsubmit="return false;">
                @csrf
                <div class="form-group">
                    <label for="email">Email đã đăng ký</label>
                    <input type="email" id="email" name="email" required 
                           placeholder="Nhập email của bạn">
                    <div class="error" id="email-error"></div>
                </div>

                <button type="submit" class="btn-login" id="reset-btn">
                    <i class="fas fa-paper-plane"></i>
                    Gửi link khôi phục
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
        // Form validation
        function validateForm() {
            let isValid = true;
            const errors = {};

            // Clear previous errors
            document.querySelectorAll('.error').forEach(error => error.textContent = '');

            // Email validation
            const email = document.getElementById('email').value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!email) {
                errors.email = 'Vui lòng nhập email';
                isValid = false;
            } else if (!emailRegex.test(email)) {
                errors.email = 'Email không hợp lệ';
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
        document.getElementById('forgot-password-form').addEventListener('submit', function(e) {
            e.preventDefault();

            if (!validateForm()) {
                return;
            }

            handleForgotPassword();
        });

        async function handleForgotPassword() {
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
                const email = document.getElementById('email').value.trim();

                // Sử dụng API có sẵn của bạn
                const response = await fetch('/api/forgot-password', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ email: email })
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Có lỗi xảy ra');
                }

                // Show success - thông báo dựa trên email template
                const userName = email.split('@')[0]; 
                document.getElementById('success-text').innerHTML = 
                    `<strong>✅ Email đã được gửi!</strong><br>
                     📧 Xin chào <strong>${userName}</strong>, chúng tôi vừa gửi email với tiêu đề <strong>"Reset your password"</strong><br>
                     🔗 Hãy kiểm tra hộp thư và click vào link <strong>"Reset mật khẩu"</strong><br>
                     ⏰ Link có hiệu lực trong 60 phút<br>
                     ❌ Nếu bạn không yêu cầu, hãy bỏ qua email này`;
                successMessage.style.display = 'block';
                
                resetBtn.innerHTML = '<i class="fas fa-check"></i> Đã gửi email!';
                resetBtn.style.background = '#28a745';
                
                // Auto redirect after 8 seconds để user có thời gian đọc
                setTimeout(() => {
                    window.location.href = '{{ route("login") }}';
                }, 8000);

            } catch (error) {
                console.error('Forgot password error:', error);
                document.getElementById('error-text').textContent = error.message;
                errorMessage.style.display = 'block';
                
                // Reset button
                resetBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Gửi link khôi phục';
                resetBtn.style.background = '#28a745';
            } finally {
                resetBtn.disabled = false;
                loading.style.display = 'none';
            }
        }
    </script>

    
</body>
</html>