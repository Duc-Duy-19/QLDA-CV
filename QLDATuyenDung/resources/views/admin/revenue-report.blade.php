@extends('layouts.admin')

@section('title', 'Báo cáo doanh thu')
@section('page-title', 'Báo cáo doanh thu')

@section('content')
<div class="revenue-report-container">
    <!-- Filters Section -->
    <div class="filter-section" style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
            <!-- Period Selector -->
            <div style="flex: 1; min-width: 150px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #333;">Kỳ báo cáo:</label>
                <select id="periodSelect" class="form-control" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
                    <option value="day">Theo ngày</option>
                    <option value="month">Theo tháng</option>
                    <option value="year">Theo năm</option>
                    <option value="custom">Tùy chọn</option>
                </select>
            </div>

            <!-- Date Input (for day) -->
            <div id="dateInputGroup" style="flex: 1; min-width: 150px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #333;">Chọn ngày:</label>
                <input type="date" id="dateInput" class="form-control" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px;" value="{{ date('Y-m-d') }}">
            </div>

            <!-- Month Input (for month) -->
            <div id="monthInputGroup" style="flex: 1; min-width: 150px; display: none;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #333;">Chọn tháng:</label>
                <input type="month" id="monthInput" class="form-control" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px;" value="{{ date('Y-m') }}">
            </div>

            <!-- Year Input (for year) -->
            <div id="yearInputGroup" style="flex: 1; min-width: 150px; display: none;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #333;">Chọn năm:</label>
                <input type="number" id="yearInput" class="form-control" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px;" value="{{ date('Y') }}" min="2020" max="2100">
            </div>

            <!-- Custom Range (for custom) -->
            <div id="customRangeGroup" style="flex: 1; min-width: 150px; display: none;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #333;">Từ ngày:</label>
                <input type="date" id="startDateInput" class="form-control" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
            </div>
            <div id="customRangeGroup2" style="flex: 1; min-width: 150px; display: none;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #333;">Đến ngày:</label>
                <input type="date" id="endDateInput" class="form-control" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
            </div>

            <!-- Search Input -->
            <div style="flex: 1; min-width: 200px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #333;">Tìm kiếm:</label>
                <input type="text" id="searchInput" class="form-control" placeholder="Tên công ty, email, mã đơn..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
            </div>

            <!-- Action Buttons -->
            <div style="display: flex; gap: 10px;">
                <button onclick="loadRevenueData()" class="btn btn-primary" style="padding: 8px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
                    <i class="fas fa-search"></i> Tìm kiếm
                </button>
                <button onclick="resetFilters()" class="btn btn-secondary" style="padding: 8px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer;">
                    <i class="fas fa-redo"></i> Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-cards" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px;">
        <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Tổng doanh thu</div>
                    <div id="totalRevenue" style="font-size: 28px; font-weight: bold;">0 VND</div>
                </div>
                <i class="fas fa-money-bill-wave" style="font-size: 40px; opacity: 0.3;"></i>
            </div>
        </div>

        <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Tổng giao dịch</div>
                    <div id="totalTransactions" style="font-size: 28px; font-weight: bold;">0</div>
                </div>
                <i class="fas fa-receipt" style="font-size: 40px; opacity: 0.3;"></i>
            </div>
        </div>

        <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Trung bình/đơn</div>
                    <div id="averageAmount" style="font-size: 28px; font-weight: bold;">0 VND</div>
                </div>
                <i class="fas fa-chart-bar" style="font-size: 40px; opacity: 0.3;"></i>
            </div>
        </div>
    </div>

    <!-- Chart Section -->
    <div class="chart-section" style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h3 style="margin-bottom: 20px; color: #333;">Biểu đồ doanh thu</h3>
        <canvas id="revenueChart" style="max-height: 400px;"></canvas>
    </div>

    <!-- Payments Table -->
    <div class="payments-table-section" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #333;">Chi tiết giao dịch</h3>
            <div id="paginationInfo" style="color: #666; font-size: 14px;"></div>
        </div>

        <div style="overflow-x: auto;">
            <table class="table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                        <th style="padding: 12px; text-align: left; font-weight: 600; color: #333;">ID</th>
                        <th style="padding: 12px; text-align: left; font-weight: 600; color: #333;">Công ty</th>
                        <th style="padding: 12px; text-align: left; font-weight: 600; color: #333;">Người thanh toán</th>
                        <th style="padding: 12px; text-align: right; font-weight: 600; color: #333;">Số tiền</th>
                        <th style="padding: 12px; text-align: left; font-weight: 600; color: #333;">Mã đơn</th>
                        <th style="padding: 12px; text-align: center; font-weight: 600; color: #333;">Ngày thanh toán</th>
                    </tr>
                </thead>
                <tbody id="paymentsTableBody">
                    <tr>
                        <td colspan="6" style="padding: 40px; text-align: center; color: #666;">
                            <div class="spinner-border" role="status">
                                <span class="sr-only">Đang tải...</span>
                            </div>
                            <p style="margin-top: 10px;">Đang tải dữ liệu...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div id="pagination" style="margin-top: 20px; display: flex; justify-content: center; gap: 10px;"></div>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<script>
let revenueChart = null;
let currentPage = 1;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadRevenueData();
    
    // Handle period change
    document.getElementById('periodSelect').addEventListener('change', function() {
        const period = this.value;
        document.getElementById('dateInputGroup').style.display = period === 'day' ? 'block' : 'none';
        document.getElementById('monthInputGroup').style.display = period === 'month' ? 'block' : 'none';
        document.getElementById('yearInputGroup').style.display = period === 'year' ? 'block' : 'none';
        document.getElementById('customRangeGroup').style.display = period === 'custom' ? 'block' : 'none';
        document.getElementById('customRangeGroup2').style.display = period === 'custom' ? 'block' : 'none';
    });

    // Handle Enter key in search
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            loadRevenueData();
        }
    });
});

// Load revenue data
async function loadRevenueData(page = 1) {
    try {
        currentPage = page;
        const period = document.getElementById('periodSelect').value;
        const search = document.getElementById('searchInput').value;
        
        const params = new URLSearchParams({
            period: period,
            per_page: 20,
            page: page,
        });

        if (search) {
            params.append('search', search);
        }

        if (period === 'day') {
            params.append('date', document.getElementById('dateInput').value);
        } else if (period === 'month') {
            params.append('month', document.getElementById('monthInput').value);
        } else if (period === 'year') {
            params.append('year', document.getElementById('yearInput').value);
        } else if (period === 'custom') {
            const startDate = document.getElementById('startDateInput').value;
            const endDate = document.getElementById('endDateInput').value;
            if (startDate) params.append('start_date', startDate);
            if (endDate) params.append('end_date', endDate);
        }

        // Get auth token
        const authToken = localStorage.getItem('authToken');
        if (!authToken) {
            alert('Vui lòng đăng nhập lại');
            return;
        }

        // Show loading
        document.getElementById('paymentsTableBody').innerHTML = `
            <tr>
                <td colspan="6" style="padding: 40px; text-align: center; color: #666;">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">Đang tải...</span>
                    </div>
                    <p style="margin-top: 10px;">Đang tải dữ liệu...</p>
                </td>
            </tr>
        `;

        const response = await fetch(`/api/admin/revenue/data?${params.toString()}`, {
            headers: {
                'Authorization': `Bearer ${authToken}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error('Không thể tải dữ liệu');
        }

        const result = await response.json();

        if (result.success) {
            // Update statistics
            const stats = result.data.statistics;
            document.getElementById('totalRevenue').textContent = formatCurrency(stats.total_revenue);
            document.getElementById('totalTransactions').textContent = stats.total_transactions.toLocaleString('vi-VN');
            document.getElementById('averageAmount').textContent = formatCurrency(stats.average_amount);

            // Update chart
            updateChart(result.data.breakdown);

            // Update table
            updateTable(result.data.payments);

            // Update pagination
            updatePagination(result.data.payments);
        } else {
            throw new Error(result.error || 'Có lỗi xảy ra');
        }
    } catch (error) {
        console.error('Error loading revenue data:', error);
        document.getElementById('paymentsTableBody').innerHTML = `
            <tr>
                <td colspan="6" style="padding: 40px; text-align: center; color: #dc3545;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p style="margin-top: 10px;">${error.message}</p>
                </td>
            </tr>
        `;
    }
}

// Update chart
function updateChart(breakdown) {
    const ctx = document.getElementById('revenueChart').getContext('2d');
    
    if (revenueChart) {
        revenueChart.destroy();
    }

    const labels = breakdown.map(item => item.period);
    const revenues = breakdown.map(item => item.revenue);
    const transactions = breakdown.map(item => item.transactions);

    revenueChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Doanh thu (VND)',
                    data: revenues,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1,
                    yAxisID: 'y',
                },
                {
                    label: 'Số giao dịch',
                    data: transactions,
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    tension: 0.1,
                    yAxisID: 'y1',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Doanh thu (VND)'
                    },
                    ticks: {
                        callback: function(value) {
                            return formatCurrency(value);
                        }
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Số giao dịch'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            if (context.datasetIndex === 0) {
                                return 'Doanh thu: ' + formatCurrency(context.parsed.y);
                            } else {
                                return 'Giao dịch: ' + context.parsed.y;
                            }
                        }
                    }
                }
            }
        }
    });
}

// Update table
function updateTable(paymentsData) {
    const tbody = document.getElementById('paymentsTableBody');
    
    if (!paymentsData.data || paymentsData.data.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" style="padding: 40px; text-align: center; color: #666;">
                    <i class="fas fa-inbox"></i>
                    <p style="margin-top: 10px;">Không có dữ liệu</p>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = paymentsData.data.map(payment => {
        const paidAt = new Date(payment.paid_at);
        return `
            <tr style="border-bottom: 1px solid #dee2e6;">
                <td style="padding: 12px;">#${payment.id}</td>
                <td style="padding: 12px;">${payment.company?.company_name || 'N/A'}</td>
                <td style="padding: 12px;">${payment.user?.email || 'N/A'}</td>
                <td style="padding: 12px; text-align: right; font-weight: 600; color: #28a745;">${formatCurrency(payment.amount)} ${payment.currency}</td>
                <td style="padding: 12px; font-family: monospace; font-size: 12px;">${payment.sepay_order_id || 'N/A'}</td>
                <td style="padding: 12px; text-align: center;">${paidAt.toLocaleDateString('vi-VN')} ${paidAt.toLocaleTimeString('vi-VN', {hour: '2-digit', minute: '2-digit'})}</td>
            </tr>
        `;
    }).join('');
}

// Update pagination
function updatePagination(paymentsData) {
    const paginationDiv = document.getElementById('pagination');
    const paginationInfo = document.getElementById('paginationInfo');
    
    if (!paymentsData || !paymentsData.data) {
        paginationDiv.innerHTML = '';
        paginationInfo.textContent = '';
        return;
    }

    const currentPage = paymentsData.current_page || 1;
    const lastPage = paymentsData.last_page || 1;
    const total = paymentsData.total || 0;
    const from = paymentsData.from || 0;
    const to = paymentsData.to || 0;

    paginationInfo.textContent = `Hiển thị ${from}-${to} trong tổng số ${total} giao dịch`;

    if (lastPage <= 1) {
        paginationDiv.innerHTML = '';
        return;
    }

    let paginationHTML = '';

    // Previous button
    if (currentPage > 1) {
        paginationHTML += `<button onclick="loadRevenueData(${currentPage - 1})" class="btn btn-sm" style="padding: 6px 12px; border: 1px solid #ddd; background: white; border-radius: 4px; cursor: pointer;">
            <i class="fas fa-chevron-left"></i> Trước
        </button>`;
    }

    // Page numbers
    for (let i = 1; i <= lastPage; i++) {
        if (i === 1 || i === lastPage || (i >= currentPage - 2 && i <= currentPage + 2)) {
            paginationHTML += `<button onclick="loadRevenueData(${i})" class="btn btn-sm ${i === currentPage ? 'active' : ''}" style="padding: 6px 12px; border: 1px solid #ddd; background: ${i === currentPage ? '#007bff' : 'white'}; color: ${i === currentPage ? 'white' : '#333'}; border-radius: 4px; cursor: pointer; margin: 0 2px;">
                ${i}
            </button>`;
        } else if (i === currentPage - 3 || i === currentPage + 3) {
            paginationHTML += `<span style="padding: 6px;">...</span>`;
        }
    }

    // Next button
    if (currentPage < lastPage) {
        paginationHTML += `<button onclick="loadRevenueData(${currentPage + 1})" class="btn btn-sm" style="padding: 6px 12px; border: 1px solid #ddd; background: white; border-radius: 4px; cursor: pointer;">
            Sau <i class="fas fa-chevron-right"></i>
        </button>`;
    }

    paginationDiv.innerHTML = paginationHTML;
}

// Reset filters
function resetFilters() {
    document.getElementById('periodSelect').value = 'day';
    document.getElementById('dateInput').value = new Date().toISOString().split('T')[0];
    document.getElementById('monthInput').value = new Date().toISOString().slice(0, 7);
    document.getElementById('yearInput').value = new Date().getFullYear();
    document.getElementById('startDateInput').value = '';
    document.getElementById('endDateInput').value = '';
    document.getElementById('searchInput').value = '';
    
    // Trigger period change to show/hide inputs
    document.getElementById('periodSelect').dispatchEvent(new Event('change'));
    
    loadRevenueData(1);
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount);
}
</script>

<style>
.spinner-border {
    display: inline-block;
    width: 2rem;
    height: 2rem;
    vertical-align: text-bottom;
    border: 0.25em solid currentColor;
    border-right-color: transparent;
    border-radius: 50%;
    animation: spinner-border 0.75s linear infinite;
}

@keyframes spinner-border {
    to { transform: rotate(360deg); }
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border-width: 0;
}
</style>
@endsection

