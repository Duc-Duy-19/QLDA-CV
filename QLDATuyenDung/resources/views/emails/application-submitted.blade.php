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

        .success-icon {
            font-size: 60px;
            margin: 20px 0;
        }

        .job-info {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #667eea;
        }

        .timeline {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .timeline-item {
            display: flex;
            align-items: start;
            margin-bottom: 15px;
        }

        .timeline-icon {
            background: #667eea;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 14px;
        }

        .highlight {
            background: #fef3c7;
            padding: 2px 6px;
            border-radius: 3px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="success-icon">✅</div>
            <h1>Đã nhận hồ sơ của bạn!</h1>
            <p>Cảm ơn bạn đã ứng tuyển</p>
        </div>
        <div class="content">
            <p>Xin chào <strong>{{ $application->applicant_name ?? $application->user->name ?? 'Ứng viên' }}</strong>,</p>

            <p>Chúng tôi đã nhận được hồ sơ ứng tuyển của bạn cho vị trí:</p>

            <div class="job-info">
                <h3 style="margin-top: 0; color: #667eea;">{{ $application->job->title }}</h3>
                <p style="margin: 5px 0;"><strong>Công ty:</strong> {{ $application->job->company->company_name }}</p>
                <p style="margin: 5px 0;"><strong>Địa điểm:</strong> {{ $application->job->location ?? 'Chưa cập nhật' }}</p>
                <p style="margin: 5px 0;"><strong>Mức lương:</strong> {{ $application->job->salary_range ?? 'Thỏa thuận' }}</p>
                <p style="margin: 5px 0;"><strong>Ngày ứng tuyển:</strong> {{ $application->applied_at ? $application->applied_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i') }}</p>
            </div>

            <h3>📋 Quy trình tiếp theo</h3>
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-icon">✓</div>
                    <div>
                        <strong>Gửi hồ sơ</strong><br>
                        <span style="color: #10b981;">Hoàn tất</span>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-icon">2</div>
                    <div>
                        <strong>Nhà tuyển dụng xem xét</strong><br>
                        <span style="color: #666;">Thời gian: 3-7 ngày làm việc</span>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-icon">3</div>
                    <div>
                        <strong>Nhận thông báo kết quả</strong><br>
                        <span style="color: #666;">Chúng tôi sẽ gửi email cho bạn</span>
                    </div>
                </div>
            </div>

            <h3>💡 Lưu ý</h3>
            <ul>
                <li>Vui lòng <span class="highlight">kiểm tra email thường xuyên</span> để không bỏ lỡ thông báo</li>
                <li>Đảm bảo số điện thoại <strong>{{ $application->user->profile->phone ?? 'của bạn' }}</strong> luôn liên lạc được</li>
                <li>Bạn có thể theo dõi trạng thái đơn ứng tuyển trong tài khoản của mình</li>
            </ul>

            <p style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
                Chúc bạn may mắn!<br>
                <strong>Đội ngũ tuyển dụng</strong>
            </p>
        </div>
        <div class="footer">
            <p>© 2025 JobPortal. Mọi quyền được bảo lưu.</p>
            <p style="font-size: 12px; color: #999;">Email này được gửi tự động, vui lòng không trả lời.</p>
        </div>
    </div>
</body>

</html>