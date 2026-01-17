<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Tài khoản bị khóa</title>
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
            background-color: #dc3545;
            color: white;
            padding: 15px;
            border-radius: 5px 5px 0 0;
            margin: -20px -20px 20px -20px;
        }
        .content {
            padding: 10px 0;
        }
        .reason-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
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
            <h2>⚠️ Tài khoản của bạn đã bị khóa</h2>
        </div>
        <div class="content">
            <p>Xin chào <strong>{{ $user->name ?? $user->email }}</strong>,</p>
            <p>Chúng tôi thông báo rằng tài khoản của bạn đã bị khóa và không thể sử dụng các tính năng của hệ thống.</p>
            
            @if($reason)
            <div class="reason-box">
                <strong>Lý do khóa:</strong><br>
                {{ $reason }}
            </div>
            @endif
            
            <p><strong>Thời gian khóa:</strong> {{ $suspendedAt->format('d/m/Y H:i:s') }}</p>
            
            <p>Nếu bạn cho rằng đây là sự nhầm lẫn hoặc có bất kỳ câu hỏi nào, vui lòng liên hệ với bộ phận hỗ trợ để được giải quyết.</p>
        </div>
        <div class="footer">
            <p>Email này được gửi tự động từ hệ thống. Vui lòng không trả lời email này.</p>
            <p>&copy; {{ date('Y') }} - Website Tuyển Dụng Thực Tế</p>
        </div>
    </div>
</body>
</html>

