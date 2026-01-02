/**
 * AI Feedback Widget - JavaScript Helper
 * Dùng cho SPA/Modal khi hiển thị kết quả AI
 */

/**
 * Render feedback widget vào element
 * @param {string} containerId - ID của element chứa widget
 * @param {number} aiMatchResultId - ID của ai_match_result
 * @param {number} matchScore - Điểm match (0-100)
 */
function renderAiFeedbackWidget(containerId, aiMatchResultId, matchScore) {
    const container = document.getElementById(containerId);
    if (!container) {
        console.error("Container not found:", containerId);
        return;
    }

    const widgetHTML = `
        <div class="ai-feedback-widget card border-primary mt-4" id="aiFeedbackWidget-${aiMatchResultId}" style="border: 2px solid #0d6efd; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-top: 20px;">
            <div class="card-header" style="background: rgba(13, 110, 253, 0.1); border-bottom: 1px solid #0d6efd; padding: 15px;">
                <h6 style="margin: 0; color: #0d6efd; font-weight: 600;">
                    <i class="fas fa-comments" style="margin-right: 8px;"></i>Kết quả AI có chính xác không?
                </h6>
                <small style="color: #6c757d;">Phản hồi của bạn giúp cải thiện AI</small>
            </div>
            
            <div class="card-body" style="padding: 20px;">
                <!-- Step 1: Rating Buttons -->
                <div id="ratingButtons-${aiMatchResultId}" class="rating-buttons">
                    <p style="margin-bottom: 15px;">Đánh giá độ chính xác của điểm AI: <strong>${matchScore}/100</strong></p>
                    
                    <div style="display: flex; gap: 10px; width: 100%;">
                        <button type="button" 
                                class="btn-feedback btn-success" 
                                onclick="selectRatingWidget(${aiMatchResultId}, 'accurate')"
                                style="flex: 1; padding: 20px 15px; border: 2px solid #28a745; background: white; color: #28a745; border-radius: 8px; cursor: pointer; transition: all 0.3s;">
                            <i class="fas fa-thumbs-up fa-2x" style="display: block; margin-bottom: 10px;"></i>
                            <span>Chính xác</span>
                        </button>
                        
                        <button type="button" 
                                class="btn-feedback btn-warning" 
                                onclick="selectRatingWidget(${aiMatchResultId}, 'somewhat_accurate')"
                                style="flex: 1; padding: 20px 15px; border: 2px solid #ffc107; background: white; color: #ffc107; border-radius: 8px; cursor: pointer; transition: all 0.3s;">
                            <i class="fas fa-meh fa-2x" style="display: block; margin-bottom: 10px;"></i>
                            <span>Khá chính xác</span>
                        </button>
                        
                        <button type="button" 
                                class="btn-feedback btn-danger" 
                                onclick="selectRatingWidget(${aiMatchResultId}, 'inaccurate')"
                                style="flex: 1; padding: 20px 15px; border: 2px solid #dc3545; background: white; color: #dc3545; border-radius: 8px; cursor: pointer; transition: all 0.3s;">
                            <i class="fas fa-thumbs-down fa-2x" style="display: block; margin-bottom: 10px;"></i>
                            <span>Không chính xác</span>
                        </button>
                    </div>
                </div>

                <!-- Step 2: Detail Form -->
                <div id="detailForm-${aiMatchResultId}" class="detail-form" style="display: none;">
                    <form id="feedbackForm-${aiMatchResultId}" onsubmit="submitFeedbackWidget(event, ${aiMatchResultId})">
                        <input type="hidden" id="rating-${aiMatchResultId}" name="rating" value="">
                        
                        <div style="margin-bottom: 15px;">
                            <label style="font-weight: 600; margin-bottom: 5px; display: block;">Điểm bạn nghĩ AI nên cho:</label>
                            <div style="display: flex; gap: 10px;">
                                <input type="number" 
                                       id="expectedScore-${aiMatchResultId}"
                                       name="expected_score" 
                                       min="0" 
                                       max="100" 
                                       placeholder="0-100"
                                       style="flex: 1; padding: 10px; border: 1px solid #ced4da; border-radius: 6px; font-size: 16px;">
                                <span style="padding: 10px; background: #f8f9fa; border: 1px solid #ced4da; border-radius: 6px;">/100</span>
                            </div>
                            <small style="color: #6c757d;">Bạn nghĩ điểm match phải là bao nhiêu?</small>
                        </div>

                        <!-- Vấn đề gặp phải đã được xóa theo yêu cầu -->

                        <div style="margin-bottom: 15px;">
                            <label style="font-weight: 600; margin-bottom: 5px; display: block;">Nhận xét chi tiết: (Optional)</label>
                            <textarea 
                                id="comment-${aiMatchResultId}"
                                name="comment" 
                                rows="3" 
                                maxlength="1000"
                                placeholder="Ví dụ: AI cho điểm cao nhưng CV tôi không có kinh nghiệm Node.js như Job yêu cầu..."
                                style="width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 6px; font-family: inherit; resize: vertical;"></textarea>
                            <small style="color: #6c757d;">Tối đa 1000 ký tự</small>
                        </div>

                        <div style="display: flex; gap: 10px;">
                            <button type="submit" style="flex: 1; padding: 12px; background: #0d6efd; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                                <i class="fas fa-paper-plane" style="margin-right: 8px;"></i>Gửi phản hồi
                            </button>
                            <button type="button" onclick="cancelFeedbackWidget(${aiMatchResultId})" style="padding: 12px 20px; background: #6c757d; color: white; border: none; border-radius: 6px; cursor: pointer;">
                                Hủy
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Step 3: Thank You -->
                <div id="thankYou-${aiMatchResultId}" class="thank-you" style="display: none;">
                    <div style="text-align: center; padding: 30px 0;">
                        <i class="fas fa-check-circle" style="color: #28a745; font-size: 64px; margin-bottom: 20px;"></i>
                        <h5 style="color: #28a745; margin-bottom: 10px;">Cảm ơn bạn đã đóng góp!</h5>
                        <p style="color: #6c757d; margin: 0;">Phản hồi của bạn giúp chúng tôi cải thiện AI ngày càng chính xác hơn.</p>
                    </div>
                </div>

                <!-- Loading -->
                <div id="loading-${aiMatchResultId}" class="loading" style="display: none;">
                    <div style="text-align: center; padding: 30px 0;">
                        <div style="border: 4px solid #f3f3f3; border-top: 4px solid #0d6efd; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 20px;"></div>
                        <p style="color: #6c757d;">Đang gửi phản hồi...</p>
                    </div>
                </div>
            </div>
        </div>
    `;

    container.innerHTML = widgetHTML;

    // Add hover effects
    const style = document.createElement("style");
    style.textContent = `
        .btn-feedback:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .btn-feedback.btn-success:hover {
            background: #28a745 !important;
            color: white !important;
        }
        .btn-feedback.btn-warning:hover {
            background: #ffc107 !important;
            color: white !important;
        }
        .btn-feedback.btn-danger:hover {
            background: #dc3545 !important;
            color: white !important;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(style);
}

function selectRatingWidget(resultId, rating) {
    document.getElementById("rating-" + resultId).value = rating;
    document.getElementById("ratingButtons-" + resultId).style.display = "none";

    if (rating === "accurate") {
        submitFeedbackDirectWidget(resultId, rating);
    } else {
        document.getElementById("detailForm-" + resultId).style.display =
            "block";
    }
}

function cancelFeedbackWidget(resultId) {
    document.getElementById("feedbackForm-" + resultId).reset();
    document.getElementById("ratingButtons-" + resultId).style.display =
        "block";
    document.getElementById("detailForm-" + resultId).style.display = "none";
}

async function submitFeedbackDirectWidget(resultId, rating) {
    const widget = document.getElementById("aiFeedbackWidget-" + resultId);
    if (!widget) {
        console.error("Widget not found:", resultId);
        return;
    }
    
    const cardBody = widget.querySelector(".card-body");
    if (!cardBody) {
        console.error("Card body not found:", resultId);
        return;
    }

    // Show loading directly
    cardBody.innerHTML = `
        <div style="text-align: center; padding: 30px 0;">
            <div style="border: 4px solid #f3f3f3; border-top: 4px solid #0d6efd; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 20px;"></div>
            <p style="color: #6c757d;">Đang gửi phản hồi...</p>
        </div>
    `;

    try {
        const token = localStorage.getItem("token");
        const response = await fetch("/api/ai/feedback", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                Authorization: token ? "Bearer " + token : "",
            },
            body: JSON.stringify({
                ai_match_result_id: resultId,
                rating: rating,
            }),
        });

        const data = await response.json();

        if (response.ok) {
            showThankYouWidget(resultId);
        } else {
            alert("Lỗi: " + (data.error || "Không thể gửi feedback"));
            location.reload();
        }
    } catch (error) {
        console.error("Error submitting feedback:", error);
        alert("Lỗi kết nối. Vui lòng thử lại.");
    }
}

async function submitFeedbackWidget(event, resultId) {
    event.preventDefault();

    const form = document.getElementById("feedbackForm-" + resultId);
    const formData = new FormData(form);

    // Issues validation removed - no longer collecting issues data
    const data = {
        ai_match_result_id: resultId,
        rating: formData.get("rating"),
        expected_score: formData.get("expected_score") || null,
        comment: formData.get("comment") || null
    };

    form.style.display = "none";
    document.getElementById("loading-" + resultId).style.display = "block";

    try {
        const token = localStorage.getItem("token");
        const response = await fetch("/api/ai/feedback", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                Authorization: token ? "Bearer " + token : "",
            },
            body: JSON.stringify(data),
        });

        const result = await response.json();

        if (response.ok) {
            showThankYouWidget(resultId);
        } else {
            alert("Lỗi: " + (result.error || "Không thể gửi feedback"));
            form.style.display = "block";
            document.getElementById("loading-" + resultId).style.display =
                "none";
        }
    } catch (error) {
        console.error("Error submitting feedback:", error);
        alert("Lỗi kết nối. Vui lòng thử lại.");
        form.style.display = "block";
        document.getElementById("loading-" + resultId).style.display = "none";
    }
}

function showThankYouWidget(resultId) {
    const widget = document.getElementById("aiFeedbackWidget-" + resultId);
    if (!widget) {
        console.error("Widget not found:", resultId);
        return;
    }
    
    const cardBody = widget.querySelector(".card-body");
    if (!cardBody) {
        console.error("Card body not found:", resultId);
        return;
    }

    // Create thank you HTML directly
    cardBody.innerHTML = `
        <div style="text-align: center; padding: 30px 0;">
            <i class="fas fa-check-circle" style="color: #28a745; font-size: 64px; margin-bottom: 20px;"></i>
            <h5 style="color: #28a745; margin-bottom: 10px;">Cảm ơn bạn đã đóng góp!</h5>
            <p style="color: #6c757d; margin: 0;">Phản hồi của bạn giúp chúng tôi cải thiện AI ngày càng chính xác hơn.</p>
        </div>
    `;

    // Auto hide after 5 seconds
    setTimeout(() => {
        widget.style.display = "none";
    }, 5000);
}
