@extends('layouts.admin')

@section('title', 'Cài đặt hệ thống')
@section('page-title', 'Cài đặt hệ thống')

@section('content')
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
    <!-- Cài đặt chung -->
    <div class="admin-table">
        <div class="table-header">
            <h3 class="table-title">Cài đặt chung</h3>
        </div>
        <div style="padding: 20px;">
            <form id="generalSettings">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2c3e50;">Tên website</label>
                    <input type="text" name="site_name" value="WebCV" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2c3e50;">Email liên hệ</label>
                    <input type="email" name="contact_email" value="admin@webcv.com" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2c3e50;">Số điện thoại</label>
                    <input type="tel" name="contact_phone" value="0123456789" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2c3e50;">Địa chỉ</label>
                    <textarea name="contact_address" rows="3" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">Hà Nội, Việt Nam</textarea>
                </div>
                
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Lưu cài đặt
                </button>
            </form>
        </div>
    </div>

    <!-- Cài đặt email -->
    <div class="admin-table">
        <div class="table-header">
            <h3 class="table-title">Cài đặt email</h3>
        </div>
        <div style="padding: 20px;">
            <form id="emailSettings">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2c3e50;">SMTP Host</label>
                    <input type="text" name="smtp_host" value="smtp.gmail.com" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2c3e50;">SMTP Port</label>
                    <input type="number" name="smtp_port" value="587" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2c3e50;">Email gửi</label>
                    <input type="email" name="mail_from" value="noreply@webcv.com" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2c3e50;">Tên người gửi</label>
                    <input type="text" name="mail_from_name" value="WebCV System" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                </div>
                
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Lưu cài đặt email
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Cài đặt validation -->
<div style="margin-top: 30px;">
    <div class="admin-table">
        <div class="table-header">
            <h3 class="table-title">Cài đặt validation công ty</h3>
        </div>
        <div style="padding: 20px;">
            <form id="validationSettings">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <h4 style="color: #2c3e50; margin-bottom: 15px;">Thông tin bắt buộc</h4>
                        
                        <div style="margin-bottom: 15px;">
                            <label style="display: flex; align-items: center; gap: 10px;">
                                <input type="checkbox" name="require_website" checked>
                                <span>Yêu cầu website</span>
                            </label>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <label style="display: flex; align-items: center; gap: 10px;">
                                <input type="checkbox" name="require_logo" checked>
                                <span>Yêu cầu logo</span>
                            </label>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <label style="display: flex; align-items: center; gap: 10px;">
                                <input type="checkbox" name="require_description" checked>
                                <span>Yêu cầu mô tả</span>
                            </label>
                        </div>
                    </div>
                    
                    <div>
                        <h4 style="color: #2c3e50; margin-bottom: 15px;">Giới hạn kích thước</h4>
                        
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2c3e50;">Kích thước logo tối đa (MB)</label>
                            <input type="number" name="max_logo_size" value="2" min="1" max="10" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2c3e50;">Độ dài mô tả tối đa (ký tự)</label>
                            <input type="number" name="max_description_length" value="1000" min="100" max="5000" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Lưu cài đặt validation
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Cài đặt tự động hóa -->
<div style="margin-top: 30px;">
    <div class="admin-table">
        <div class="table-header">
            <h3 class="table-title">Cài đặt tự động hóa</h3>
        </div>
        <div style="padding: 20px;">
            <form id="automationSettings">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <h4 style="color: #2c3e50; margin-bottom: 15px;">Tự động duyệt</h4>
                        
                        <div style="margin-bottom: 15px;">
                            <label style="display: flex; align-items: center; gap: 10px;">
                                <input type="checkbox" name="auto_approve_verified_domains">
                                <span>Tự động duyệt domain đã xác thực</span>
                            </label>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2c3e50;">Thời gian tự động từ chối (ngày)</label>
                            <input type="number" name="auto_reject_days" value="7" min="1" max="30" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                            <small style="color: #7f8c8d;">Tự động từ chối nếu không duyệt trong X ngày</small>
                        </div>
                    </div>
                    
                    <div>
                        <h4 style="color: #2c3e50; margin-bottom: 15px;">Thông báo</h4>
                        
                        <div style="margin-bottom: 15px;">
                            <label style="display: flex; align-items: center; gap: 10px;">
                                <input type="checkbox" name="email_notifications" checked>
                                <span>Gửi email thông báo</span>
                            </label>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <label style="display: flex; align-items: center; gap: 10px;">
                                <input type="checkbox" name="daily_summary" checked>
                                <span>Báo cáo hàng ngày</span>
                            </label>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2c3e50;">Email nhận báo cáo</label>
                            <input type="email" name="admin_email" value="admin@webcv.com" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Lưu cài đặt tự động hóa
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Thống kê hệ thống -->
<div style="margin-top: 30px;">
    <div class="admin-table">
        <div class="table-header">
            <h3 class="table-title">Thống kê hệ thống</h3>
        </div>
        <div style="padding: 20px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 10px;">
                    <div style="font-size: 24px; font-weight: bold; color: #27ae60; margin-bottom: 5px;">
                        {{ $systemStats['totalUsers'] ?? 0 }}
                    </div>
                    <div style="color: #7f8c8d;">Tổng user</div>
                </div>
                <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 10px;">
                    <div style="font-size: 24px; font-weight: bold; color: #3498db; margin-bottom: 5px;">
                        {{ $systemStats['totalCompanies'] ?? 0 }}
                    </div>
                    <div style="color: #7f8c8d;">Tổng công ty</div>
                </div>
                <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 10px;">
                    <div style="font-size: 24px; font-weight: bold; color: #f39c12; margin-bottom: 5px;">
                        {{ $systemStats['pendingApprovals'] ?? 0 }}
                    </div>
                    <div style="color: #7f8c8d;">Chờ duyệt</div>
                </div>
                <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 10px;">
                    <div style="font-size: 24px; font-weight: bold; color: #9b59b6; margin-bottom: 5px;">
                        {{ $systemStats['diskUsage'] ?? '0' }}MB
                    </div>
                    <div style="color: #7f8c8d;">Dung lượng sử dụng</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Handle form submissions
document.getElementById('generalSettings').addEventListener('submit', function(e) {
    e.preventDefault();
    console.log('Saving general settings...');
    // Implement save functionality
    alert('Đã lưu cài đặt chung!');
});

document.getElementById('emailSettings').addEventListener('submit', function(e) {
    e.preventDefault();
    console.log('Saving email settings...');
    // Implement save functionality
    alert('Đã lưu cài đặt email!');
});

document.getElementById('validationSettings').addEventListener('submit', function(e) {
    e.preventDefault();
    console.log('Saving validation settings...');
    // Implement save functionality
    alert('Đã lưu cài đặt validation!');
});

document.getElementById('automationSettings').addEventListener('submit', function(e) {
    e.preventDefault();
    console.log('Saving automation settings...');
    // Implement save functionality
    alert('Đã lưu cài đặt tự động hóa!');
});
</script>
@endsection
