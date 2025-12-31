<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }

        .content {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }

        .success-badge {
            background: #10b981;
            color: white;
            padding: 10px 20px;
            border-radius: 20px;
            display: inline-block;
            margin: 20px 0;
        }

        .job-info {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #667eea;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 14px;
        }

        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Chúc mừng!</h1>
            <p>Hồ sơ của bạn đã được chấp nhận</p>
        </div>
        <div class="content">
            <p>Xin chào <strong>{{ $application->applicant_name ?? $application->user->name ?? 'Ứng viên' }}</strong>,</p>

            <div class="success-badge">✓ Hồ sơ phù hợp</div>

            <p>Chúng tôi rất vui mừng thông báo rằng hồ sơ của bạn đã được đánh giá <strong>PHÙ HỢP</strong> cho vị trí:</p>

            <div class="job-info">
                <h3 style="margin-top: 0; color: #667eea;">{{ $application->job->title }}</h3>
                <p style="margin: 5px 0;"><strong>Công ty:</strong> {{ $application->job->company->company_name }}</p>
                <p style="margin: 5px 0;"><strong>Địa điểm:</strong> {{ $application->job->location ?? 'Chưa cập nhật' }}</p>
                <p style="margin: 5px 0;"><strong>Mức lương:</strong> {{ $application->job->salary_range ?? 'Thỏa thuận' }}</p>
            </div>

            <h3>📞 Bước tiếp theo</h3>
            <p>Nhà tuyển dụng sẽ liên hệ với bạn qua:</p>
            <ul>
                <li>Email: <strong>{{ $application->applicant_email ?? $application->user->email }}</strong></li>
                <li>Điện thoại: <strong>{{ $application->user->profile->phone ?? 'Chưa cập nhật' }}</strong></li>
            </ul>

            <p>Vui lòng chú ý điện thoại và email để không bỏ lỡ cơ hội!</p>

            <p style="margin-top: 30px;">Chúc bạn thành công!</p>
        </div>
        <div class="footer">
            <p>© 2025 JobPortal. Mọi quyền được bảo lưu.</p>
            <p style="font-size: 12px; color: #999;">Email này được gửi tự động, vui lòng không trả lời.</p>
        </div>
    </div>
</body>

</html>