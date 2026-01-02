<!-- 
    AI Feedback Widget Component
    
    Usage: Thêm vào trang hiển thị kết quả AI
    
    Example:
    @include('shared.ai-feedback-widget', [
        'aiMatchResultId' => $matchResult->id,
        'matchScore' => $matchResult->match_score
    ])
-->

<div class="ai-feedback-widget card border-primary mt-4" id="aiFeedbackWidget-{{ $aiMatchResultId }}">
    <div class="card-header bg-primary bg-opacity-10 border-primary">
        <h6 class="mb-0 text-primary">
            <i class="fas fa-comments me-2"></i>Kết quả AI có chính xác không?
        </h6>
        <small class="text-muted">Phản hồi của bạn giúp cải thiện AI</small>
    </div>

    <div class="card-body">
        <!-- Step 1: Rating Buttons -->
        <div id="ratingButtons-{{ $aiMatchResultId }}" class="rating-buttons">
            <p class="mb-3">Đánh giá độ chính xác của điểm AI: <strong>{{ $matchScore }}/100</strong></p>

            <div class="btn-group w-100" role="group">
                <button type="button"
                    class="btn btn-outline-success btn-lg"
                    onclick="selectRating({{ $aiMatchResultId }}, 'accurate')">
                    <i class="fas fa-thumbs-up fa-2x d-block mb-2"></i>
                    <span>Chính xác</span>
                </button>

                <button type="button"
                    class="btn btn-outline-warning btn-lg"
                    onclick="selectRating({{ $aiMatchResultId }}, 'somewhat_accurate')">
                    <i class="fas fa-meh fa-2x d-block mb-2"></i>
                    <span>Khá chính xác</span>
                </button>

                <button type="button"
                    class="btn btn-outline-danger btn-lg"
                    onclick="selectRating({{ $aiMatchResultId }}, 'inaccurate')">
                    <i class="fas fa-thumbs-down fa-2x d-block mb-2"></i>
                    <span>Không chính xác</span>
                </button>
            </div>
        </div>

        <!-- Step 2: Detail Form (Show if inaccurate/somewhat) -->
        <div id="detailForm-{{ $aiMatchResultId }}" class="detail-form" style="display: none;">
            <form id="feedbackForm-{{ $aiMatchResultId }}" onsubmit="submitFeedback(event, {{ $aiMatchResultId }})">
                <input type="hidden" id="rating-{{ $aiMatchResultId }}" name="rating" value="">

                <div class="mb-3">
                    <label class="form-label fw-bold">Điểm bạn nghĩ AI nên cho:</label>
                    <div class="input-group">
                        <input type="number"
                            class="form-control form-control-lg"
                            id="expectedScore-{{ $aiMatchResultId }}"
                            name="expected_score"
                            min="0"
                            max="100"
                            placeholder="0-100">
                        <span class="input-group-text">/100</span>
                    </div>
                    <small class="text-muted">Bạn nghĩ điểm match phải là bao nhiêu?</small>
                </div>

                <!-- Vấn đề gặp phải UI đã bị xóa theo yêu cầu -->

                <div class="mb-3">
                    <label class="form-label fw-bold">Nhận xét chi tiết: (Optional)</label>
                    <textarea class="form-control"
                        id="comment-{{ $aiMatchResultId }}"
                        name="comment"
                        rows="3"
                        maxlength="1000"
                        placeholder="Ví dụ: AI cho điểm cao nhưng CV tôi không có kinh nghiệm Node.js như Job yêu cầu..."></textarea>
                    <small class="text-muted">Tối đa 1000 ký tự</small>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="fas fa-paper-plane me-2"></i>Gửi phản hồi
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="cancelFeedback({{ $aiMatchResultId }})">
                        Hủy
                    </button>
                </div>
            </form>
        </div>

        <!-- Step 3: Thank You Message -->
        <div id="thankYou-{{ $aiMatchResultId }}" class="thank-you" style="display: none;">
            <div class="text-center py-4">
                <i class="fas fa-check-circle text-success fa-4x mb-3"></i>
                <h5 class="text-success">Cảm ơn bạn đã đóng góp!</h5>
                <p class="text-muted mb-0">Phản hồi của bạn giúp chúng tôi cải thiện AI ngày càng chính xác hơn.</p>
            </div>
        </div>

        <!-- Loading State -->
        <div id="loading-{{ $aiMatchResultId }}" class="loading text-center py-4" style="display: none;">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="text-muted">Đang gửi phản hồi...</p>
        </div>
    </div>
</div>

<style>
    .ai-feedback-widget {
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .ai-feedback-widget .btn-group .btn {
        padding: 1.5rem 1rem;
        border-radius: 8px !important;
    }

    .ai-feedback-widget .btn-group .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        transition: all 0.3s ease;
    }

    .ai-feedback-widget .btn-outline-success:hover {
        background-color: #28a745;
        color: white;
    }

    .ai-feedback-widget .btn-outline-warning:hover {
        background-color: #ffc107;
        color: white;
    }

    .ai-feedback-widget .btn-outline-danger:hover {
        background-color: #dc3545;
        color: white;
    }

    .detail-form {
        animation: fadeIn 0.3s ease-in;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<script>
    function selectRating(resultId, rating) {
        document.getElementById('rating-' + resultId).value = rating;

        // Hide rating buttons
        document.getElementById('ratingButtons-' + resultId).style.display = 'none';

        if (rating === 'accurate') {
            // Nếu chọn "Chính xác", submit luôn không cần form
            submitFeedbackDirect(resultId, rating);
        } else {
            // Nếu chọn "Somewhat" hoặc "Inaccurate", show form
            document.getElementById('detailForm-' + resultId).style.display = 'block';
        }
    }

    function cancelFeedback(resultId) {
        // Reset form
        document.getElementById('feedbackForm-' + resultId).reset();

        // Show rating buttons again
        document.getElementById('ratingButtons-' + resultId).style.display = 'block';
        document.getElementById('detailForm-' + resultId).style.display = 'none';
    }

    async function submitFeedbackDirect(resultId, rating) {
        const widget = document.getElementById('aiFeedbackWidget-' + resultId);
        const loading = document.getElementById('loading-' + resultId);

        // Show loading
        widget.querySelector('.card-body').innerHTML = loading.outerHTML;
        loading.style.display = 'block';

        try {
            const response = await fetch('/api/ai/feedback', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    ai_match_result_id: resultId,
                    rating: rating
                })
            });

            const data = await response.json();

            if (response.ok) {
                showThankYou(resultId);
            } else {
                alert('Lỗi: ' + (data.error || 'Không thể gửi feedback'));
                location.reload();
            }
        } catch (error) {
            console.error('Error submitting feedback:', error);
            alert('Lỗi kết nối. Vui lòng thử lại.');
            location.reload();
        }
    }

    async function submitFeedback(event, resultId) {
        event.preventDefault();

        const form = document.getElementById('feedbackForm-' + resultId);
        const formData = new FormData(form);

        // Convert FormData to JSON (issues removed — UI no longer collects them)
        const data = {
            ai_match_result_id: resultId,
            rating: formData.get('rating'),
            expected_score: formData.get('expected_score') || null,
            comment: formData.get('comment') || null
        };

        // Show loading
        form.style.display = 'none';
        document.getElementById('loading-' + resultId).style.display = 'block';

        try {
            const response = await fetch('/api/ai/feedback', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (response.ok) {
                showThankYou(resultId);
            } else {
                alert('Lỗi: ' + (result.error || 'Không thể gửi feedback'));
                form.style.display = 'block';
                document.getElementById('loading-' + resultId).style.display = 'none';
            }
        } catch (error) {
            console.error('Error submitting feedback:', error);
            alert('Lỗi kết nối. Vui lòng thử lại.');
            form.style.display = 'block';
            document.getElementById('loading-' + resultId).style.display = 'none';
        }
    }

    function showThankYou(resultId) {
        const widget = document.getElementById('aiFeedbackWidget-' + resultId);
        widget.querySelector('.card-body').innerHTML = document.getElementById('thankYou-' + resultId).innerHTML;

        // Auto hide after 5 seconds
        setTimeout(() => {
            widget.style.display = 'none';
        }, 5000);
    }
</script>