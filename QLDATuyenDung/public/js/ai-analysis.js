/**
 * AI Analysis Module
 * Handles CV analysis with AI for job matching
 */

let candidateResumes = null;

/**
 * Load user's resumes from API
 */
async function loadUserResumes() {
    const token = localStorage.getItem("authToken");
    if (!token) return null;

    try {
        const res = await fetch("/api/resumes", {
            headers: {
                Authorization: "Bearer " + token,
                Accept: "application/json",
            },
        });

        if (res.ok) {
            const data = await res.json();
            candidateResumes = data.data || data;
            return candidateResumes;
        }
    } catch (e) {
        console.error("Load resumes error:", e);
    }
    return null;
}

/**
 * Handle AI analysis button click
 * @param {number} jobId - The job ID to analyze
 */
async function handleAiAnalysisHome(jobId) {
    const token = localStorage.getItem("authToken");

    if (!token) {
        alert("Vui lòng đăng nhập để sử dụng tính năng phân tích AI");
        window.location.href = "/login";
        return;
    }

    try {
        // Load user resumes if not loaded
        if (!candidateResumes) {
            await loadUserResumes();
        }

        // Show CV selection modal
        showCvSelectionModalHome(jobId, token);
    } catch (e) {
        console.error("AI Analysis error:", e);
        alert("Có lỗi xảy ra. Vui lòng thử lại sau.");
    }
}

/**
 * Show modal for CV selection or upload
 * @param {number} jobId - The job ID
 * @param {string} token - Auth token
 */
function showCvSelectionModalHome(jobId, token) {
    const modal = document.createElement("div");
    modal.style.cssText =
        "position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:10000;";

    const modalContent = document.createElement("div");
    modalContent.style.cssText =
        "background:white;padding:30px;border-radius:12px;max-width:600px;width:90%;max-height:80vh;overflow-y:auto;";

    let html = `<h3 style="margin-bottom:20px;color:#1fae4f;">Chọn CV để phân tích</h3>`;

    // Show existing resumes if available
    if (candidateResumes && candidateResumes.length > 0) {
        html += `<div style="margin-bottom:20px;">`;
        candidateResumes.forEach((resume, index) => {
            const type = resume.parsed_content ? "Đã tải lên" : "Đã tạo";
            const title = resume.title || resume.name || "CV " + (index + 1);
            const updatedDate = resume.updated_at
                ? formatDateToDMY(resume.updated_at)
                : "";

            html += `
                <div class="cv-option" data-resume-id="${
                    resume.id
                }" style="padding:15px;margin-bottom:10px;border:2px solid #e0e0e0;border-radius:8px;cursor:pointer;transition:all 0.3s;">
                    <div style="font-weight:600;color:#333;margin-bottom:5px;">${escapeHtml(
                        title
                    )}</div>
                    <div style="font-size:13px;color:#888;">
                        <span style="background:#e8f5e9;color:#1fae4f;padding:2px 8px;border-radius:4px;margin-right:8px;">${type}</span>
                        ${updatedDate ? "Cập nhật: " + updatedDate : ""}
                    </div>
                </div>
            `;
        });
        html += `</div>`;
    }

    // Cancel button only
    html += `
        <div style="margin-top:25px;display:flex;gap:10px;justify-content:flex-end;">
            <button id="cancel-analysis-btn-home" style="padding:12px 24px;background:#ccc;color:#333;border:none;border-radius:6px;cursor:pointer;font-weight:600;">
                Đóng
            </button>
        </div>
    `;

    modalContent.innerHTML = html;
    modal.appendChild(modalContent);
    document.body.appendChild(modal);

    // Handle CV selection from existing resumes
    const cvOptions = modalContent.querySelectorAll(".cv-option");
    cvOptions.forEach((option) => {
        option.addEventListener("click", async function () {
            const resumeId = this.getAttribute("data-resume-id");
            modal.remove();
            await performAiAnalysisHome(jobId, resumeId, token);
        });

        // Hover effects
        option.addEventListener("mouseenter", function () {
            this.style.borderColor = "#1fae4f";
            this.style.background = "#f8fdf9";
        });
        option.addEventListener("mouseleave", function () {
            this.style.borderColor = "#e0e0e0";
            this.style.background = "white";
        });
    });

    // Handle cancel
    const cancelBtn = document.getElementById("cancel-analysis-btn-home");
    cancelBtn.addEventListener("click", () => modal.remove());

    // Close on backdrop click
    modal.addEventListener("click", (e) => {
        if (e.target === modal) modal.remove();
    });
}

/**
 * Perform AI analysis
 * @param {number} jobId - The job ID
 * @param {number} resumeId - The resume ID
 * @param {string} token - Auth token
 */
async function performAiAnalysisHome(jobId, resumeId, token) {
    const loadingModal = document.createElement("div");
    loadingModal.style.cssText =
        "position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);display:flex;align-items:center;justify-content:center;z-index:10001;";
    loadingModal.innerHTML = `
        <div style="background:white;padding:40px;border-radius:12px;text-align:center;">
            <div style="font-size:48px;margin-bottom:20px;">🤖</div>
            <div style="font-size:18px;font-weight:600;color:#1fae4f;margin-bottom:10px;">
                AI đang phân tích CV của bạn...
            </div>
            <div style="font-size:14px;color:#888;">
                Vui lòng đợi trong giây lát
            </div>
        </div>
    `;
    document.body.appendChild(loadingModal);

    try {
        const res = await fetch("/api/ai/check-match", {
            method: "POST",
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
                Authorization: "Bearer " + token,
            },
            body: JSON.stringify({
                job_id: jobId,
                resume_id: resumeId,
            }),
        });

        loadingModal.remove();

        // Handle authentication errors
        if (res.status === 401) {
            alert("Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.");
            localStorage.removeItem("authToken");
            localStorage.removeItem("currentUser");
            localStorage.setItem("isLoggedIn", "false");
            window.location.href = "/login";
            return;
        }

        if (!res.ok) {
            const errorData = await res.json().catch(() => ({}));
            throw new Error(
                errorData.message || errorData.error || "Không thể phân tích CV"
            );
        }

        const data = await res.json();
        showAnalysisResultsHome(data.analysis, data.ai_match_result_id || null);
    } catch (e) {
        loadingModal.remove();
        alert("Lỗi: " + e.message);
    }
}

/**
 * Show analysis results in modal
 * @param {object} analysis - Analysis data from API
 * @param {number|null} aiMatchResultId - ID của kết quả AI (để submit feedback)
 */
function showAnalysisResultsHome(analysis, aiMatchResultId = null) {
    const modal = document.createElement("div");
    modal.style.cssText =
        "position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:10000;overflow-y:auto;padding:20px;";

    const modalContent = document.createElement("div");
    modalContent.style.cssText =
        "background:white;padding:40px;border-radius:12px;max-width:700px;width:100%;max-height:90vh;overflow-y:auto;";

    const matchScore = analysis.match_score || 0;
    const scoreColor =
        matchScore >= 70 ? "#1fae4f" : matchScore >= 50 ? "#ff9800" : "#e03";

    let html = `
        <div style="text-align:center;margin-bottom:30px;">
            <h2 style="color:#1fae4f;margin-bottom:20px;">Kết quả phân tích AI</h2>
            <div style="position:relative;width:150px;height:150px;margin:0 auto 20px;">
                <svg width="150" height="150" style="transform:rotate(-90deg);">
                    <circle cx="75" cy="75" r="65" fill="none" stroke="#e0e0e0" stroke-width="10"/>
                    <circle cx="75" cy="75" r="65" fill="none" stroke="${scoreColor}" stroke-width="10" 
                            stroke-dasharray="${
                                (matchScore / 100) * 408.4
                            } 408.4" stroke-linecap="round"/>
                </svg>
                <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-size:36px;font-weight:700;color:${scoreColor};">
                    ${matchScore}%
                </div>
            </div>
            <div style="font-size:18px;font-weight:600;color:#333;">
                ${
                    matchScore >= 70
                        ? "Rất phù hợp"
                        : matchScore >= 50
                        ? "Khá phù hợp"
                        : "Cần cải thiện"
                }
            </div>
        </div>
    `;

    // Summary section
    if (analysis.summary) {
        html += `
            <div style="background:#f8f9fa;padding:20px;border-radius:8px;margin-bottom:20px;border-left:4px solid #1fae4f;">
                <h4 style="color:#1fae4f;margin-bottom:10px;font-size:16px;">📋 Tổng quan</h4>
                <div style="line-height:1.8;color:#333;">${escapeHtml(
                    analysis.summary
                )}</div>
            </div>
        `;
    }

    // Company Culture Fit section
    if (analysis.company_culture_fit) {
        const cultureFit = analysis.company_culture_fit;
        const cultureScore = cultureFit.score || 0;
        const cultureColor =
            cultureScore >= 70
                ? "#1fae4f"
                : cultureScore >= 50
                ? "#ff9800"
                : "#e03";

        html += `
            <div style="background:#fff8e1;padding:20px;border-radius:8px;margin-bottom:20px;border-left:4px solid ${cultureColor};">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                    <h4 style="color:${cultureColor};margin:0;font-size:16px;">🏢 Độ phù hợp với văn hóa công ty</h4>
                    <div style="font-size:24px;font-weight:700;color:${cultureColor};">${cultureScore}%</div>
                </div>
                <div style="line-height:1.8;color:#333;">${escapeHtml(
                    cultureFit.assessment || "Chưa có đánh giá"
                )}</div>
            </div>
        `;
    }

    // Strengths section
    if (analysis.strengths && analysis.strengths.length > 0) {
        html += `
            <div style="margin-bottom:20px;">
                <h4 style="color:#1fae4f;margin-bottom:15px;font-size:16px;">✅ Điểm mạnh</h4>
                <ul style="list-style:none;padding:0;">
        `;
        analysis.strengths.forEach((strength) => {
            html += `<li style="padding:10px;background:#e8f5e9;margin-bottom:8px;border-radius:6px;color:#333;">• ${escapeHtml(
                strength
            )}</li>`;
        });
        html += `</ul></div>`;
    }

    // Weaknesses section
    if (analysis.weaknesses && analysis.weaknesses.length > 0) {
        html += `
            <div style="margin-bottom:20px;">
                <h4 style="color:#ff9800;margin-bottom:15px;font-size:16px;">⚠️ Điểm cần cải thiện</h4>
                <ul style="list-style:none;padding:0;">
        `;
        analysis.weaknesses.forEach((weakness) => {
            html += `<li style="padding:10px;background:#fff3e0;margin-bottom:8px;border-radius:6px;color:#333;">• ${escapeHtml(
                weakness
            )}</li>`;
        });
        html += `</ul></div>`;
    }

    // Improvement tip section
    if (analysis.improvement_tip) {
        html += `
            <div style="background:#e3f2fd;padding:20px;border-radius:8px;border-left:4px solid #2196f3;">
                <h4 style="color:#2196f3;margin-bottom:10px;font-size:16px;">💡 Gợi ý cải thiện</h4>
                <div style="line-height:1.8;color:#333;">${escapeHtml(
                    analysis.improvement_tip
                )}</div>
            </div>
        `;
    }

    html += `
        <div style="margin-top:30px;text-align:center;">
            <button id="close-results-btn-home" style="padding:12px 40px;background:#1fae4f;color:white;border:none;border-radius:6px;cursor:pointer;font-weight:600;font-size:16px;">
                Đóng
            </button>
        </div>
    `;

    // Add feedback widget container
    if (aiMatchResultId) {
        html += `<div id="feedback-widget-container"></div>`;
    }

    modalContent.innerHTML = html;
    modal.appendChild(modalContent);
    document.body.appendChild(modal);

    // Render feedback widget if aiMatchResultId exists
    if (aiMatchResultId && typeof renderAiFeedbackWidget === "function") {
        setTimeout(() => {
            renderAiFeedbackWidget(
                "feedback-widget-container",
                aiMatchResultId,
                matchScore
            );
        }, 100);
    }

    // Handle close button
    const closeBtn = document.getElementById("close-results-btn-home");
    closeBtn.addEventListener("click", () => modal.remove());

    // Close on backdrop click
    modal.addEventListener("click", (e) => {
        if (e.target === modal) modal.remove();
    });
}

/**
 * Helper function to format date to DD-MM-YYYY
 * @param {string} value - Date string
 * @returns {string} Formatted date
 */
function formatDateToDMY(value) {
    if (!value) return "";
    try {
        let d = new Date(value);
        if (isNaN(d.getTime())) {
            const m = String(value).match(/(\d{4}-\d{2}-\d{2})/);
            if (m) d = new Date(m[1] + "T00:00:00");
        }
        if (!d || isNaN(d.getTime())) return "";
        const dd = String(d.getDate()).padStart(2, "0");
        const mm = String(d.getMonth() + 1).padStart(2, "0");
        const yyyy = d.getFullYear();
        return `${dd}-${mm}-${yyyy}`;
    } catch (e) {
        return "";
    }
}

/**
 * Helper function to escape HTML
 * @param {string} str - String to escape
 * @returns {string} Escaped string
 */
function escapeHtml(str) {
    if (str === null || str === undefined) return "";
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Export to window for global access
window.handleAiAnalysisHome = handleAiAnalysisHome;
