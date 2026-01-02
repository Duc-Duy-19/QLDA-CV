<!DOCTYPE html>
<html lang="vi">
<head>
    @include('layouts.head')
    <link href="{{ asset('css/shared/header.css') }}" rel="stylesheet">
    <link href="{{ asset('css/shared/footer.css') }}" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>Thanh toán nâng cấp lên nhà tuyển dụng - WebCV</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    @include('layouts.header')
    
    <div class="container" style="max-width: 800px; margin: 40px auto; padding: 20px; min-height: calc(100vh - 200px);">
        <div class="card" style="border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px 10px 0 0;">
                <h2 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-credit-card"></i>
                    Thanh toán nâng cấp lên nhà tuyển dụng
                </h2>
            </div>
            
            <div class="card-body" style="padding: 30px;">
                <div id="paymentLoading" style="text-align: center; padding: 40px;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Đang tải...</span>
                    </div>
                    <p style="margin-top: 15px; color: #666;">Đang tải thông tin thanh toán...</p>
                </div>

                <div id="paymentContent" style="display: none;">
                    <!-- Payment Info -->
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                            <div>
                                <strong style="color: #333;">Công ty:</strong>
                                <span id="companyName" style="color: #666; margin-left: 10px;"></span>
                            </div>
                            <div>
                                <strong style="color: #333;">Số tiền:</strong>
                                <span id="paymentAmount" style="color: #28a745; font-size: 18px; font-weight: bold; margin-left: 10px;"></span>
                            </div>
                        </div>
                        <div>
                            <strong style="color: #333;">Trạng thái:</strong>
                            <span id="paymentStatus" style="margin-left: 10px;"></span>
                        </div>
                    </div>

                    <!-- QR Code Section -->
                    <div id="qrCodeSection" style="text-align: center; padding: 30px; background: #fff; border: 2px solid #e9ecef; border-radius: 8px; margin-bottom: 20px;">
                        <h4 style="color: #333; margin-bottom: 20px;">
                            <i class="fas fa-qrcode"></i> Quét mã QR để thanh toán
                        </h4>
                        <div id="qrCodeContainer" style="margin: 20px 0;">
                            <!-- QR code will be inserted here -->
                        </div>
                        <p style="color: #666; font-size: 14px; margin-top: 15px;">
                            Sử dụng ứng dụng ngân hàng để quét mã QR và chuyển khoản
                        </p>
                    </div>

                    <!-- Action Buttons -->
                    <div id="actionButtons" style="display: flex; gap: 10px; justify-content: center; margin-top: 20px;">
                        <!-- Buttons will be inserted here -->
                    </div>

                    <!-- Error Message -->
                    <div id="errorMessage" style="display: none; background: #fee; color: #c33; padding: 15px; border-radius: 5px; margin-top: 20px; text-align: center;">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span id="errorText"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
const paymentId = {{ $paymentId }};
let paymentData = null;

// Load payment data
async function loadPayment() {
    try {
        const token = localStorage.getItem('authToken') || localStorage.getItem('token');
        if (!token) {
            showError('Vui lòng đăng nhập để xem thanh toán');
            return;
        }

        const response = await fetch(`/api/payments/${paymentId}`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
            },
        });

        if (!response.ok) {
            if (response.status === 403) {
                showError('Bạn không có quyền xem thanh toán này');
            } else if (response.status === 404) {
                showError('Không tìm thấy thanh toán');
            } else {
                showError('Có lỗi xảy ra khi tải thông tin thanh toán');
            }
            return;
        }

        const data = await response.json();
        if (data.success && data.payment) {
            paymentData = data.payment;
            renderPayment();
        } else {
            showError('Không thể tải thông tin thanh toán');
        }
    } catch (error) {
        console.error('Lỗi khi tải thanh toán:', error);
        showError('Có lỗi xảy ra khi tải thông tin thanh toán');
    }
}

// Render payment details
function renderPayment() {
    document.getElementById('paymentLoading').style.display = 'none';
    document.getElementById('paymentContent').style.display = 'block';

    // Set payment info
    document.getElementById('companyName').textContent = paymentData.company?.company_name || 'N/A';
    const formattedAmount = new Intl.NumberFormat('vi-VN').format(paymentData.amount);
    document.getElementById('paymentAmount').textContent = `${formattedAmount} ${paymentData.currency}`;

    // Set status
    const statusBadge = getStatusBadge(paymentData.status);
    document.getElementById('paymentStatus').innerHTML = statusBadge;

    // Render QR code
    renderQRCode();

    // Render action buttons
    renderActionButtons();
}

// Render QR code
function renderQRCode() {
    const qrContainer = document.getElementById('qrCodeContainer');
    
    if (paymentData.qr_code_url) {
        qrContainer.innerHTML = `
            <img src="${paymentData.qr_code_url}" 
                 alt="QR Code Thanh toán" 
                 style="max-width: 300px; width: 100%; border: 2px solid #28a745; border-radius: 8px; padding: 15px; background: white;">
        `;
    } else if (paymentData.qr_code_data) {
        // Handle base64 QR code
        const qrData = paymentData.qr_code_data.startsWith('data:') 
            ? paymentData.qr_code_data 
            : `data:image/png;base64,${paymentData.qr_code_data}`;
        
        qrContainer.innerHTML = `
            <img src="${qrData}" 
                 alt="QR Code Thanh toán" 
                 style="max-width: 300px; width: 100%; border: 2px solid #28a745; border-radius: 8px; padding: 15px; background: white;">
        `;
    } else {
        qrContainer.innerHTML = `
            <div style="padding: 40px; background: #fff3cd; border-radius: 8px; color: #856404;">
                <i class="fas fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 15px;"></i>
                <p>QR code đang được tạo. Vui lòng thử lại sau.</p>
            </div>
        `;
    }
}

// Render action buttons based on status
function renderActionButtons() {
    const buttonsContainer = document.getElementById('actionButtons');
    let buttons = '';

    if (paymentData.status === 'pending') {
        buttons = `
            <button class="btn btn-success btn-lg" onclick="confirmPayment()" style="padding: 12px 30px; font-size: 16px;">
                <i class="fas fa-check-circle"></i> Xác nhận đã thanh toán
            </button>
        `;
    } else if (paymentData.status === 'pending_verification') {
        buttons = `
            <div style="text-align: center; padding: 20px; background: #d1ecf1; border-radius: 8px; color: #0c5460;">
                <i class="fas fa-clock" style="font-size: 48px; margin-bottom: 15px;"></i>
                <h4>Đang chờ admin xác minh</h4>
                <p>Thanh toán của bạn đã được xác nhận và đang chờ admin kiểm tra.</p>
            </div>
        `;
    } else if (paymentData.status === 'paid') {
        buttons = `
            <div style="text-align: center; padding: 20px; background: #d4edda; border-radius: 8px; color: #155724;">
                <i class="fas fa-check-circle" style="font-size: 48px; margin-bottom: 15px;"></i>
                <h4>Thanh toán thành công!</h4>
                <p>Công ty của bạn đã được kích hoạt.</p>
                <a href="/candidate/dashboard" class="btn btn-primary" style="margin-top: 15px;">
                    <i class="fas fa-arrow-left"></i> Quay lại Dashboard
                </a>
            </div>
        `;
    } else if (paymentData.status === 'expired') {
        buttons = `
            <div style="text-align: center; padding: 20px; background: #f8d7da; border-radius: 8px; color: #721c24;">
                <i class="fas fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 15px;"></i>
                <h4>Thanh toán đã hết hạn</h4>
                <p>Vui lòng liên hệ admin để được hỗ trợ.</p>
            </div>
        `;
    } else if (paymentData.status === 'failed') {
        buttons = `
            <div style="text-align: center; padding: 20px; background: #f8d7da; border-radius: 8px; color: #721c24;">
                <i class="fas fa-times-circle" style="font-size: 48px; margin-bottom: 15px;"></i>
                <h4>Thanh toán thất bại</h4>
                <p>Vui lòng liên hệ admin để được hỗ trợ.</p>
            </div>
        `;
    }

    buttonsContainer.innerHTML = buttons;
}

// Get status badge HTML
function getStatusBadge(status) {
    const badges = {
        'pending': '<span class="badge badge-warning">Chờ thanh toán</span>',
        'pending_verification': '<span class="badge badge-info">Chờ xác minh</span>',
        'paid': '<span class="badge badge-success">Đã thanh toán</span>',
        'failed': '<span class="badge badge-danger">Thất bại</span>',
        'expired': '<span class="badge badge-secondary">Hết hạn</span>',
    };
    return badges[status] || '<span class="badge badge-secondary">' + status + '</span>';
}

// Confirm payment
async function confirmPayment() {
    if (!confirm('Bạn đã quét QR code và chuyển khoản thành công?\n\nNhấn OK để xác nhận.')) {
        return;
    }

    try {
        const token = localStorage.getItem('authToken') || localStorage.getItem('token');
        if (!token) {
            showError('Vui lòng đăng nhập để xác nhận thanh toán');
            return;
        }

        const response = await fetch(`/api/payments/${paymentId}/confirm`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
        });

        const data = await response.json();

        if (response.ok && data.success) {
            alert('Xác nhận thanh toán thành công! Admin sẽ kiểm tra và duyệt.');
            // Reload payment data
            loadPayment();
        } else {
            showError(data.error || data.message || 'Không thể xác nhận thanh toán');
        }
    } catch (error) {
        console.error('Lỗi khi xác nhận thanh toán:', error);
        showError('Có lỗi xảy ra khi xác nhận thanh toán');
    }
}

// Show error message
function showError(message) {
    document.getElementById('paymentLoading').style.display = 'none';
    document.getElementById('paymentContent').style.display = 'block';
    document.getElementById('errorMessage').style.display = 'block';
    document.getElementById('errorText').textContent = message;
}

// Load payment on page load
document.addEventListener('DOMContentLoaded', function() {
    loadPayment();
});
</script>

<style>
.badge {
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.badge-warning {
    background: #ffc107;
    color: #000;
}

.badge-info {
    background: #17a2b8;
    color: #fff;
}

.badge-success {
    background: #28a745;
    color: #fff;
}

.badge-danger {
    background: #dc3545;
    color: #fff;
}

.badge-secondary {
    background: #6c757d;
    color: #fff;
}
</style>

    @include('layouts.footer')
</body>
</html>
