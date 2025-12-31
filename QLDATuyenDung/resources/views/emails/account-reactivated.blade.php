<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Tài khoản đã được kích hoạt lại</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
        }
        .header {
            background-color: #28a745;
            color: white;
            padding: 15px;
            border-radius: 5px 5px 0 0;
            margin: -20px -20px 20px -20px;
        }
        .content {
            padding: 10px 0;
        }
        .success-box {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 15px 0;
        }
        .footer {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>✅ Tài khoản của bạn đã được kích hoạt lại</h2>
        </div>
        <div class="content">
            <p>Xin chào <strong>{{ $user->name ?? $user->email }}</strong>,</p>
            <div class="success-box">
                <p><strong>Tin vui!</strong> Tài khoản của bạn đã được kích hoạt lại và bạn có thể sử dụng tất cả các tính năng của hệ thống.</p>
            </div>
            <p>Bạn có thể đăng nhập và tiếp tục sử dụng dịch vụ như bình thường.</p>
            <p>Nếu có bất kỳ thắc mắc nào, vui lòng liên hệ với bộ phận hỗ trợ.</p>
            <p>Chúc bạn có trải nghiệm tốt với dịch vụ của chúng tôi!</p>
        </div>
        <div class="footer">
            <p>Email này được gửi tự động từ hệ thống. Vui lòng không trả lời email này.</p>
            <p>&copy; {{ date('Y') }} - Website Tuyển Dụng Thực Tế</p>
        </div>
    </div>
</body>
</html>

