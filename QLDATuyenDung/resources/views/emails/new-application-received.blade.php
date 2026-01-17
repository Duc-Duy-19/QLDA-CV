<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ứng viên mới ứng tuyển</title>
</head>

<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <table role="presentation" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="padding: 0;">
                <table role="presentation" style="width: 100%; max-width: 600px; margin: 0 auto; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <tr>
                        <td style="padding: 40px 30px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: bold;">
                                🎉 Ứng viên mới ứng tuyển
                            </h1>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding: 0;">
                <table role="presentation" style="width: 100%; max-width: 600px; margin: 0 auto; background-color: #ffffff; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="padding: 40px 30px;">
                            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 10px; text-align: center; margin-bottom: 30px;">
                                <p style="margin: 0; color: #ffffff; font-size: 18px; font-weight: bold;">
                                    Bạn có ứng viên mới quan tâm đến vị trí tuyển dụng!
                                </p>
                            </div>

                            <h2 style="color: #333333; font-size: 20px; margin: 0 0 20px 0; padding-bottom: 15px; border-bottom: 2px solid #667eea;">
                                📋 Thông tin ứng tuyển
                            </h2>

                            <table role="presentation" style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
                                <tr>
                                    <td style="padding: 12px 0; border-bottom: 1px solid #eeeeee;">
                                        <strong style="color: #666666; display: inline-block; width: 140px;">Vị trí:</strong>
                                        <span style="color: #333333; font-weight: 600;">{{ $application->job->title ?? 'N/A' }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 0; border-bottom: 1px solid #eeeeee;">
                                        <strong style="color: #666666; display: inline-block; width: 140px;">Ứng viên:</strong>
                                        <span style="color: #333333;">{{ $application->applicant_name ?? 'N/A' }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 0; border-bottom: 1px solid #eeeeee;">
                                        <strong style="color: #666666; display: inline-block; width: 140px;">Email:</strong>
                                        <span style="color: #333333;">{{ $application->applicant_email ?? 'N/A' }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 0; border-bottom: 1px solid #eeeeee;">
                                        <strong style="color: #666666; display: inline-block; width: 140px;">Ngày ứng tuyển:</strong>
                                        <span style="color: #333333;">{{ $application->applied_at ? $application->applied_at->format('d/m/Y H:i') : 'N/A' }}</span>
                                    </td>
                                </tr>
                            </table>

                            @if($application->cover_letter)
                            <h2 style="color: #333333; font-size: 20px; margin: 0 0 15px 0; padding-bottom: 15px; border-bottom: 2px solid #667eea;">
                                💬 Thư xin việc
                            </h2>
                            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #667eea; margin-bottom: 30px;">
                                <p style="margin: 0; line-height: 1.6; color: #333333; white-space: pre-wrap;">{{ $application->cover_letter }}</p>
                            </div>
                            @endif

                            <div style="text-align: center; margin: 40px 0 30px 0;">
                                <a href="{{ config('app.url') }}/company/applications"
                                    style="display: inline-block; padding: 15px 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);">
                                    📊 Xem chi tiết hồ sơ
                                </a>
                            </div>

                            <div style="background: #fff3cd; padding: 20px; border-radius: 8px; border-left: 4px solid #ffc107; margin-top: 30px;">
                                <p style="margin: 0 0 10px 0; color: #856404; font-weight: bold;">
                                    💡 Gợi ý:
                                </p>
                                <ul style="margin: 0; padding-left: 20px; color: #856404; line-height: 1.8;">
                                    <li>Xem xét hồ sơ ứng viên càng sớm càng tốt</li>
                                    <li>Kiểm tra CV và thư xin việc kỹ lưỡng</li>
                                    <li>Liên hệ với ứng viên nếu hồ sơ phù hợp</li>
                                    <li>Cập nhật trạng thái hồ sơ để ứng viên biết được tiến độ</li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding: 0;">
                <table role="presentation" style="width: 100%; max-width: 600px; margin: 0 auto; background-color: #f8f9fa;">
                    <tr>
                        <td style="padding: 30px; text-align: center;">
                            <p style="margin: 0 0 10px 0; color: #6c757d; font-size: 14px;">
                                Email này được gửi tự động từ hệ thống WebCV
                            </p>
                            <p style="margin: 0; color: #6c757d; font-size: 12px;">
                                © {{ date('Y') }} WebCV. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>