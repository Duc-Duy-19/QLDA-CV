@extends('layouts.admin')

@section('title', 'AI Feedback & Accuracy')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 15px; box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3); margin-bottom: 20px;">
                <h2 class="fw-bold text-white mb-2">
                    <i class="fas fa-chart-line me-3"></i>AI Feedback & Accuracy Monitoring
                </h2>
                <p class="text-white mb-0" style="opacity: 0.9; font-size: 1.05rem;">
                    <i class="fas fa-info-circle me-2"></i>Theo dõi độ chính xác AI và cải thiện dựa trên phản hồi user
                </p>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="stat-card-revenue" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="revenue-icon">
                    <i class="fas fa-bullseye"></i>
                </div>
                <div class="revenue-content">
                    <p class="revenue-title">Độ chính xác AI</p>
                    <h2 class="revenue-value" id="accuracyRate">
                        <span class="spinner-border spinner-border-sm"></span>
                    </h2>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="stat-card-revenue" style="background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);">
                <div class="revenue-icon">
                    <i class="fas fa-comment-dots"></i>
                </div>
                <div class="revenue-content">
                    <p class="revenue-title">Tổng phản hồi</p>
                    <h2 class="revenue-value" id="totalFeedbacks">
                        <span class="spinner-border spinner-border-sm"></span>
                    </h2>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="stat-card-revenue" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                <div class="revenue-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="revenue-content">
                    <p class="revenue-title">Không chính xác</p>
                    <h2 class="revenue-value" id="inaccurateCount">
                        <span class="spinner-border spinner-border-sm"></span>
                    </h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-12 mb-3">
            <div class="card" style="height: 100%;">
                <div class="card-header bg-light" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;">
                    <h5 class="mb-0" style="color: #495057; font-weight: 600;">
                        <i class="fas fa-chart-pie me-2" style="color: #667eea;"></i>Rating Breakdown
                    </h5>
                    <small class="text-muted">Phân bố đánh giá từ users</small>
                </div>
                <div class="card-body" style="padding: 25px;">
                    <canvas id="ratingChart" height="150"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Problematic Feedbacks Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); border: none;">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-list fa-2x me-3" style="opacity: 0.9; color: #2c3e50;"></i>
                        <div>
                            <h5 class="mb-1" style="font-weight: 700; font-size: 1.25rem; color: #2c3e50;">
                                Tất cả Feedbacks
                            </h5>
                            <small style="color: #495057; font-weight: 500;">50 feedbacks gần nhất - ưu tiên "Không chính xác"</small>
                        </div>
                    </div>
                </div>
                <div class="card-body" style="padding: 25px;">
                    <div class="table-responsive">
                        <table class="table table-hover" id="problematicTable" style="margin-bottom: 0;">
                            <thead>
                                <tr>
                                    <th style="width: 50px;"><i class="fas fa-hashtag"></i></th>
                                    <th><i class="fas fa-calendar me-1"></i>Ngày</th>
                                    <th><i class="fas fa-user me-1"></i>Người dùng</th>
                                    <th><i class="fas fa-briefcase me-1"></i>Công việc</th>
                                    <th class="text-center"><i class="fas fa-smile me-1"></i>Đánh giá</th>
                                    <th class="text-center"><i class="fas fa-robot me-1"></i>AI</th>
                                    <th class="text-center"><i class="fas fa-star me-1"></i>Mong đợi</th>
                                    <th class="text-center"><i class="fas fa-exchange-alt me-1"></i>Chênh lệch</th>
                                    <th class="text-center"><i class="fas fa-info-circle me-1"></i>Chi tiết</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="9" class="text-center py-5">
                                        <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="text-muted mb-0" style="font-size: 1rem;">Đang tải dữ liệu...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="alert alert-info mt-4" style="border-left: 5px solid #00838f;">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-lightbulb fa-2x me-3" style="color: #00838f; margin-top: 3px;"></i>
                            <div>
                                <strong style="font-size: 1.1rem; color: #00838f;">💡 Cách cải thiện AI:</strong>
                                <ol class="mb-0 mt-3" style="line-height: 2;">
                                    <li>Đọc nhận xét từ user để hiểu <strong>vấn đề thực tế</strong></li>
                                    <li>Vào <a href="{{ route('admin.industry-contexts.index') }}" class="alert-link">
                                            <i class="fas fa-brain me-1"></i>Industry Contexts
                                        </a> để chỉnh sửa</li>
                                    <li>Sửa <strong>red_flags</strong> và <strong>focus_areas</strong> cho ngành bị lỗi nhiều</li>
                                    <li>Chạy lại test: <code>php artisan ai:test-accuracy</code> để verify kết quả</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0"></script>
<script>
    let ratingChart;

    document.addEventListener('DOMContentLoaded', function() {
        console.log('🚀 Page loaded, starting data fetch...');

        // Check if Chart.js is loaded
        if (typeof Chart === 'undefined') {
            console.error('❌ Chart.js not loaded!');
            return;
        }
        console.log('✅ Chart.js loaded, version:', Chart.version);

        loadStatistics();
        loadProblematicFeedbacks();
    });

    async function loadStatistics() {
        console.log('📊 Loading statistics...');
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            console.log('CSRF Token found:', csrfToken ? 'YES' : 'NO');

            const response = await fetch('/api/admin/ai/feedback/stats', {
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken ? csrfToken.getAttribute('content') : ''
                }
            });

            console.log('Response status:', response.status);

            if (!response.ok) {
                const text = await response.text();
                console.error('Response not OK:', text.substring(0, 200));
                throw new Error('Failed to load stats: ' + response.status);
            }

            const data = await response.json();
            console.log('✅ Stats data received:', data); // Debug log

            // Update cards with animation
            updateCardValue('accuracyRate', data.accuracy_rate);
            updateCardValue('totalFeedbacks', data.total_feedbacks);
            updateCardValue('inaccurateCount', data.rating_breakdown.inaccurate);

            // Rating breakdown chart
            const ratingCtx = document.getElementById('ratingChart').getContext('2d');
            if (ratingChart) ratingChart.destroy();

            ratingChart = new Chart(ratingCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Chính xác', 'Khá chính xác', 'Không chính xác'],
                    datasets: [{
                        data: [
                            data.rating_breakdown.accurate,
                            data.rating_breakdown.somewhat_accurate,
                            data.rating_breakdown.inaccurate
                        ],
                        backgroundColor: [
                            '#28a745',
                            '#ffc107',
                            '#dc3545'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                font: {
                                    size: 13,
                                    family: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif"
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.parsed / total) * 100).toFixed(1);
                                    return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });

        } catch (error) {
            console.error('❌ Error loading statistics:', error);
            showError('Không thể tải dữ liệu statistics. Error: ' + error.message);

            // Show error in cards
            ['accuracyRate', 'totalFeedbacks', 'inaccurateCount'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.innerHTML = '<span class="text-danger">Error</span>';
            });
        }
    }

    function updateCardValue(elementId, value) {
        const element = document.getElementById(elementId);
        if (element) {
            // Add animation
            element.style.opacity = '0';
            setTimeout(() => {
                element.textContent = value;
                element.style.opacity = '1';
                element.style.transition = 'opacity 0.3s ease';
            }, 100);
        }
    }

    function showError(message) {
        const container = document.querySelector('.container-fluid');
        const alert = document.createElement('div');
        alert.className = 'alert alert-danger alert-dismissible fade show';
        alert.innerHTML = `
            <i class="fas fa-exclamation-triangle me-2"></i>${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        container.insertBefore(alert, container.firstChild);
    }

    async function loadProblematicFeedbacks() {
        console.log('🔍 Loading problematic feedbacks...');
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]');

            const response = await fetch('/api/admin/ai/feedback/problematic', {
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken ? csrfToken.getAttribute('content') : ''
                }
            });

            console.log('Problematic feedbacks response:', response.status);

            if (!response.ok) {
                const text = await response.text();
                console.error('Problematic response not OK:', text.substring(0, 200));
                throw new Error('Failed to load problematic feedbacks');
            }

            const data = await response.json();
            console.log('✅ Problematic feedbacks data:', data);
            const tbody = document.querySelector('#problematicTable tbody');

            if (data.problematic_feedbacks.length === 0) {
                tbody.innerHTML = `
                <tr>
                    <td colspan="9" class="text-center py-4 text-success">
                        <i class="fas fa-check-circle fa-3x mb-3"></i>
                        <p class="mb-0">Chưa có feedback nào! 🎉</p>
                    </td>
                </tr>
            `;
                return;
            }

            tbody.innerHTML = data.problematic_feedbacks.map(feedback => {
                const aiScore = feedback.ai_match_result?.match_score || 'N/A';
                const expectedScore = feedback.expected_score || 'N/A';
                const diff = (expectedScore !== 'N/A' && aiScore !== 'N/A') ?
                    (expectedScore - aiScore) :
                    'N/A';

                const diffColor = diff > 0 ? 'text-danger' : 'text-success';
                const diffIcon = diff > 0 ? '↑' : '↓';

                // Rating badges
                const ratingBadges = {
                    'accurate': '<span class="badge bg-success">👍 Chính xác</span>',
                    'somewhat_accurate': '<span class="badge bg-warning">🤔 Khá chính xác</span>',
                    'inaccurate': '<span class="badge bg-danger">👎 Không chính xác</span>'
                };
                const ratingBadge = ratingBadges[feedback.rating] || feedback.rating;

                return `
                <tr>
                    <td>${feedback.id}</td>
                    <td>${new Date(feedback.created_at).toLocaleDateString('vi-VN')}</td>
                    <td>${feedback.user?.name || 'Guest'}</td>
                    <td>
                        <a href="/jobs/${feedback.job_id}" target="_blank" class="text-primary">
                            ${feedback.job?.title || 'Job #' + feedback.job_id}
                        </a>
                    </td>
                    <td class="text-center">${ratingBadge}</td>
                    <td class="text-center"><span class="badge bg-primary">${aiScore}</span></td>
                    <td class="text-center"><span class="badge bg-info">${expectedScore}</span></td>
                    <td class="text-center ${diffColor} fw-bold">${diff !== 'N/A' ? diffIcon + Math.abs(diff) : 'N/A'}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-info" onclick="viewFeedbackDetail(${feedback.id}, '${(feedback.comment || '').replace(/'/g, "\\'").replace(/\n/g, ' ')}', '${feedback.user?.name || 'Guest'}', '${new Date(feedback.created_at).toLocaleDateString('vi-VN')}')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
            `;
            }).join('');

        } catch (error) {
            console.error('❌ Error loading problematic feedbacks:', error);
            const tbody = document.querySelector('#problematicTable tbody');
            tbody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center text-danger py-4">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Lỗi: ${error.message}
                </td>
            </tr>
        `;
        }
    }

    function viewFeedbackDetail(feedbackId, comment, userName, date) {
        // Remove existing modals
        document.querySelectorAll('.feedback-modal').forEach(m => m.remove());

        const modal = document.createElement('div');
        modal.className = 'feedback-modal';
        modal.style.cssText = `
            display: flex;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.6);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 20px;
        `;

        modal.innerHTML = `
            <div style="
                background: white;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                max-width: 600px;
                width: 100%;
                animation: modalSlideIn 0.3s ease-out;
                overflow: hidden;
            ">
                <!-- Header -->
                <div style="
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    padding: 25px 30px;
                    color: white;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                ">
                    <h5 style="margin: 0; font-weight: 700; font-size: 1.25rem;">
                        <i class="fas fa-comment-dots me-2"></i>Chi tiết phản hồi #${feedbackId}
                    </h5>
                    <button onclick="this.closest('.feedback-modal').remove()" style="
                        background: rgba(255,255,255,0.2);
                        border: none;
                        color: white;
                        width: 35px;
                        height: 35px;
                        border-radius: 50%;
                        cursor: pointer;
                        font-size: 20px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        transition: all 0.2s;
                    " onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Body -->
                <div style="padding: 30px;">
                    <!-- User Info -->
                    <div style="
                        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                        border-radius: 12px;
                        padding: 20px;
                        margin-bottom: 20px;
                        border-left: 4px solid #667eea;
                    ">
                        <div style="margin-bottom: 12px;">
                            <i class="fas fa-user-circle" style="color: #667eea; font-size: 18px; margin-right: 10px;"></i>
                            <strong style="color: #495057;">Người dùng:</strong> 
                            <span style="color: #212529; font-weight: 500;">${userName}</span>
                        </div>
                        <div>
                            <i class="fas fa-calendar-alt" style="color: #667eea; font-size: 18px; margin-right: 10px;"></i>
                            <strong style="color: #495057;">Ngày gửi:</strong> 
                            <span style="color: #212529; font-weight: 500;">${date}</span>
                        </div>
                    </div>

                    <!-- Comment Section -->
                    <div style="margin-bottom: 0;">
                        <div style="
                            display: flex;
                            align-items: center;
                            margin-bottom: 15px;
                            padding-bottom: 10px;
                            border-bottom: 2px solid #e9ecef;
                        ">
                            <i class="fas fa-comment-alt" style="color: #667eea; font-size: 20px; margin-right: 10px;"></i>
                            <strong style="color: #212529; font-size: 1.1rem;">Nội dung nhận xét:</strong>
                        </div>
                        <div style="
                            background: #f8f9fa;
                            border-radius: 12px;
                            padding: 20px;
                            color: #495057;
                            line-height: 1.6;
                            white-space: pre-wrap;
                            border: 1px solid #e9ecef;
                            min-height: 80px;
                            font-size: 0.95rem;
                        ">
                            ${comment ? comment : '<em style="color: #adb5bd;">Người dùng không để lại nhận xét nào</em>'}
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div style="
                    background: #f8f9fa;
                    padding: 20px 30px;
                    display: flex;
                    justify-content: flex-end;
                    border-top: 1px solid #e9ecef;
                ">
                    <button onclick="this.closest('.feedback-modal').remove()" style="
                        background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
                        color: white;
                        border: none;
                        padding: 10px 25px;
                        border-radius: 8px;
                        cursor: pointer;
                        font-weight: 500;
                        font-size: 0.95rem;
                        transition: all 0.2s;
                        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                    " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.2)'" 
                       onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'">
                        <i class="fas fa-times me-2"></i>Đóng
                    </button>
                </div>
            </div>
        `;

        // Click outside to close
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.remove();
            }
        });

        document.body.appendChild(modal);
    }

    // Auto refresh every 5 minutes
    setInterval(() => {
        loadStatistics();
        loadProblematicFeedbacks();
    }, 300000);
</script>
@endpush

@push('styles')
<style>
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Enhanced Card Styles */
    .card {
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        border: none;
        border-radius: 15px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        overflow: hidden;
    }

    .card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    /* Statistics Cards - Revenue Style */
    .stat-card-revenue {
        border-radius: 15px;
        padding: 25px 30px;
        position: relative;
        overflow: hidden;
        color: white;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        transition: all 0.3s ease;
        min-height: 140px;
    }

    .stat-card-revenue:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
    }

    .stat-card-revenue .revenue-icon {
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 80px;
        opacity: 0.2;
    }

    .stat-card-revenue .revenue-content {
        position: relative;
        z-index: 1;
    }

    .stat-card-revenue .revenue-title {
        font-size: 14px;
        font-weight: 500;
        margin: 0 0 10px 0;
        opacity: 0.9;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-card-revenue .revenue-value {
        font-size: 36px;
        font-weight: 700;
        margin: 0;
        line-height: 1;
    }

    .card-title {
        letter-spacing: 1.5px;
        font-weight: 600;
        opacity: 0.85;
        font-size: 0.85rem;
    }

    /* Chart Cards */
    .chart-card .card-header {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-bottom: 3px solid #e0e0e0;
        padding: 20px;
    }

    .chart-card .card-header h5 {
        font-weight: 700;
        color: #2c3e50;
        margin: 0;
    }

    /* Enhanced Table */
    #problematicTable {
        font-size: 0.9rem;
    }

    #problematicTable thead {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    #problematicTable th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        color: #2c3e50 !important;
        letter-spacing: 0.5px;
        padding: 15px 10px;
        border: none;
    }

    #problematicTable tbody tr {
        transition: all 0.2s ease;
        border-bottom: 1px solid #f0f0f0;
    }

    #problematicTable tbody tr:hover {
        background: linear-gradient(90deg, rgba(102, 126, 234, 0.05) 0%, rgba(102, 126, 234, 0.1) 100%);
        transform: scale(1.002);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    #problematicTable td {
        padding: 15px 10px;
        vertical-align: middle;
    }

    /* Enhanced Badges */
    .badge {
        font-weight: 500;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        letter-spacing: 0.3px;
    }

    /* Alert Box */
    .alert-info {
        background: linear-gradient(135deg, #e0f7fa 0%, #b2ebf2 100%);
        border: 2px solid #4dd0e1;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(77, 208, 225, 0.2);
    }

    .alert-info a {
        color: #00838f;
        font-weight: 600;
        text-decoration: none;
        border-bottom: 2px solid #00838f;
        transition: all 0.2s ease;
    }

    .alert-info a:hover {
        color: #006064;
        border-bottom-color: #006064;
        transform: translateX(3px);
    }

    .alert-info code {
        background: rgba(255, 255, 255, 0.9);
        padding: 4px 10px;
        border-radius: 6px;
        color: #d32f2f;
        font-weight: 600;
        font-family: 'Courier New', monospace;
    }

    /* Loading Spinner */
    .spinner-border {
        animation: spinner-grow 0.75s linear infinite;
    }

    /* Buttons */
    .btn-sm {
        padding: 6px 14px;
        border-radius: 8px;
        transition: all 0.2s ease;
        font-weight: 500;
    }

    .btn-outline-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
    }

    /* Problematic Table Header */
    .card-header.problematic-header {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        border: none;
        padding: 20px;
    }

    /* Fade In Animation */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes pulse {

        0%,
        100% {
            transform: scale(1);
            opacity: 0.5;
        }

        50% {
            transform: scale(1.05);
            opacity: 0.8;
        }
    }

    /* Responsive */
    @media (max-width: 768px) {
        .stat-card-revenue {
            padding: 20px;
            min-height: 120px;
        }

        .stat-card-revenue .revenue-icon {
            font-size: 60px;
            right: 15px;
        }

        .stat-card-revenue .revenue-title {
            font-size: 12px;
        }

        .stat-card-revenue .revenue-value {
            font-size: 28px;
        }

        #problematicTable {
            font-size: 0.8rem;
        }

        .badge {
            font-size: 0.7rem;
            padding: 4px 8px;
        }
    }

    /* Modal Styles */
    .feedback-modal {
        display: flex !important;
        align-items: center;
        justify-content: center;
    }

    .feedback-modal .modal-dialog {
        margin: auto;
        max-width: 600px;
    }

    /* Modal Animation */
    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-50px) scale(0.9);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    /* Page animations */
    .row.mb-4:nth-child(1) {
        animation: fadeInUp 0.5s ease-out;
    }

    .row.mb-4:nth-child(2) {
        animation: fadeInUp 0.5s ease-out 0.1s both;
    }

    .row.mb-4:nth-child(3) {
        animation: fadeInUp 0.5s ease-out 0.2s both;
    }

    .row:last-child {
        animation: fadeInUp 0.5s ease-out 0.3s both;
    }
</style>
@endpush
@endsection