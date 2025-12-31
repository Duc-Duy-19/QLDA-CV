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
            background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%);
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

        .info-badge {
            background: #f59e0b;
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
            border-left: 4px solid #f59e0b;
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
            <h1>📧 Thông báo kết quả</h1>
            <p>Về hồ sơ ứng tuyển của bạn</p>
        </div>
        <div class="content">
            <p>Xin chào <strong>{{ $application->applicant_name ?? $application->user->name ?? 'Ứng viên' }}</strong>,</p>

            <p>Cảm ơn bạn đã quan tâm và ứng tuyển vị trí:</p>

            <div class="job-info">
                <h3 style="margin-top: 0; color: #f59e0b;">{{ $application->job->title }}</h3>
                <p style="margin: 5px 0;"><strong>Công ty:</strong> {{ $application->job->company->company_name }}</p>
                <p style="margin: 5px 0;"><strong>Ngày ứng tuyển:</strong> {{ $application->applied_at ? $application->applied_at->format('d/m/Y') : 'N/A' }}</p>
            </div>

            <p>Sau khi xem xét kỹ lưỡng, chúng tôi nhận thấy hồ sơ của bạn chưa phù hợp với yêu cầu của vị trí này tại thời điểm hiện tại.</p>

            <h3>💡 Gợi ý cho bạn</h3>
            <ul>
                <li>Tiếp tục hoàn thiện hồ sơ và kỹ năng của mình</li>
                <li>Theo dõi các cơ hội việc làm khác phù hợp hơn</li>
                <li>Ứng tuyển lại khi có kinh nghiệm và kỹ năng tương xứng</li>
            </ul>

            <p>Chúng tôi mong bạn không nản lòng và tiếp tục theo đuổi mục tiêu nghề nghiệp của mình. Chúc bạn sớm tìm được công việc phù hợp!</p>

            <a href="{{ config('app.url') }}/jobs" class="btn">Tìm việc làm khác</a>

            <p style="margin-top: 30px;">Trân trọng,<br><strong>Đội ngũ tuyển dụng</strong></p>
        </div>
        <div class="footer">
            <p>© 2025 JobPortal. Mọi quyền được bảo lưu.</p>
            <p style="font-size: 12px; color: #999;">Email này được gửi tự động, vui lòng không trả lời.</p>
        </div>
    </div>
</body>

</html>