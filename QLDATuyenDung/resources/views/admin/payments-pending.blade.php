@extends('layouts.admin')

@section('title', 'Thanh toán chờ duyệt')
@section('page-title', 'Thanh toán chờ duyệt')

@section('content')
<div class="admin-table">
    <div class="table-header">
        <h3 class="table-title">Danh sách thanh toán chờ duyệt (<span id="paymentsCount">0</span> thanh toán)</h3>
        <div style="display: flex; gap: 10px;">
            <button class="btn btn-info" onclick="loadPendingPayments()">
                <i class="fas fa-sync"></i> Làm mới
            </button>
        </div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Công ty</th>
                <th>Email Owner</th>
                <th>Số tiền</th>
                <th>Ngày tạo</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody id="paymentsTableBody">
            <tr>
                <td colspan="7" class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">Đang tải...</span>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<script>
    let pendingPayments = [];

    // Load pending payments
    async function loadPendingPayments() {
        try {
            const response = await fetch('/api/admin/payments/pending', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error('Không thể tải danh sách thanh toán');
            }

            const data = await response.json();
            pendingPayments = data.payments || [];

            document.getElementById('paymentsCount').textContent = pendingPayments.length;
            renderPayments();
        } catch (error) {
            console.error('Lỗi khi tải thanh toán:', error);
            document.getElementById('paymentsTableBody').innerHTML = `
            <tr>
                <td colspan="7" class="text-center text-danger">
                    Có lỗi xảy ra khi tải danh sách thanh toán
                </td>
            </tr>
        `;
        }
    }

    // Render payments table
    function renderPayments() {
        const tbody = document.getElementById('paymentsTableBody');

        if (pendingPayments.length === 0) {
            tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center">
                    <div class="empty-state">
                        <i class="fas fa-check-circle" style="font-size: 48px; color: #28a745; margin-bottom: 15px;"></i>
                        <h3>Không có thanh toán nào chờ duyệt</h3>
                        <p>Tất cả thanh toán đã được xử lý</p>
                    </div>
                </td>
            </tr>
        `;
            return;
        }

        tbody.innerHTML = pendingPayments.map(payment => {
            const formattedAmount = new Intl.NumberFormat('vi-VN').format(payment.amount);
            const createdDate = new Date(payment.created_at).toLocaleDateString('vi-VN');
            const createdTime = new Date(payment.created_at).toLocaleTimeString('vi-VN');

            return `
            <tr>
                <td>#${payment.id}</td>
                <td>
                    <strong>${payment.company?.company_name || 'N/A'}</strong>
                </td>
                <td>${payment.user?.email || 'N/A'}</td>
                <td>
                    <strong style="color: #28a745;">${formattedAmount} ${payment.currency}</strong>
                </td>
                <td>
                    <div style="font-size: 14px;">${createdDate}</div>
                    <div style="font-size: 12px; color: #7f8c8d;">${createdTime}</div>
                </td>
                <td>
                    <span class="badge badge-warning">Chờ xác minh</span>
                </td>
                <td>
                    <div style="display: flex; gap: 5px;">
                        <button class="btn btn-success btn-approve-payment" data-payment-id="${payment.id}" data-company-name="${(payment.company?.company_name || '').replace(/"/g, '&quot;')}" style="padding: 6px 12px; font-size: 12px;">
                            <i class="fas fa-check"></i> Duyệt
                        </button>
                        <button class="btn btn-danger btn-reject-payment" data-payment-id="${payment.id}" data-company-name="${(payment.company?.company_name || '').replace(/"/g, '&quot;')}" style="padding: 6px 12px; font-size: 12px;">
                            <i class="fas fa-times"></i> Từ chối
                        </button>
                    </div>
                </td>
            </tr>
        `;
        }).join('');
    }

    // Approve payment
    async function approvePayment(paymentId, companyName) {
        const confirmed = confirm(`Bạn có chắc chắn muốn duyệt thanh toán cho công ty "${companyName}"?`);
        if (!confirmed) {
            return;
        }

        try {
            const response = await fetch(`/api/admin/payments/${paymentId}/approve`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                credentials: 'same-origin'
            });

            const data = await response.json();

            if (response.ok && data.success) {
                alert('Duyệt thanh toán thành công!');
                loadPendingPayments();
            } else {
                alert('Có lỗi xảy ra: ' + (data.message || data.error || 'Không thể duyệt thanh toán'));
            }
        } catch (error) {
            console.error('Lỗi khi duyệt thanh toán:', error);
            alert('Có lỗi xảy ra khi duyệt thanh toán');
        }
    }

    // Reject payment
    async function rejectPayment(paymentId, companyName) {
        const confirmed = confirm(`Bạn có chắc chắn muốn từ chối thanh toán cho công ty "${companyName}"?`);
        if (!confirmed) {
            return;
        }

        try {
            const response = await fetch(`/api/admin/payments/${paymentId}/reject`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                credentials: 'same-origin'
            });

            const data = await response.json();

            if (response.ok && data.success) {
                alert('Đã từ chối thanh toán');
                loadPendingPayments();
            } else {
                alert('Có lỗi xảy ra: ' + (data.message || data.error || 'Không thể từ chối thanh toán'));
            }
        } catch (error) {
            console.error('Lỗi khi từ chối thanh toán:', error);
            alert('Có lỗi xảy ra khi từ chối thanh toán');
        }
    }

    // Load on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadPendingPayments();

        // Event delegation for buttons
        document.addEventListener('click', function(e) {
            // Approve payment button
            if (e.target.closest('.btn-approve-payment')) {
                const btn = e.target.closest('.btn-approve-payment');
                const paymentId = btn.dataset.paymentId;
                const companyName = btn.dataset.companyName;
                if (paymentId) approvePayment(paymentId, companyName);
            }

            // Reject payment button
            if (e.target.closest('.btn-reject-payment')) {
                const btn = e.target.closest('.btn-reject-payment');
                const paymentId = btn.dataset.paymentId;
                const companyName = btn.dataset.companyName;
                if (paymentId) rejectPayment(paymentId, companyName);
            }
        });
    });
</script>

<style>
    .empty-state {
        padding: 40px 20px;
        text-align: center;
    }

    .empty-state i {
        display: block;
        margin-bottom: 15px;
    }

    .empty-state h3 {
        margin: 15px 0 10px;
        color: #333;
    }

    .empty-state p {
        color: #7f8c8d;
        margin: 0;
    }
</style>
@endsection