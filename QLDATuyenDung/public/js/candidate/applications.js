const API_BASE_URL = "/api";
let applications = [];
let filteredApplications = [];

function getAuthHeaders() {
    const token =
        localStorage.getItem("authToken") || localStorage.getItem("token");
    const headers = { Accept: "application/json" };
    if (token) headers["Authorization"] = "Bearer " + token;
    else {
        try {
            const meta = document.querySelector('meta[name="csrf-token"]');
            if (meta && meta.content) headers["X-CSRF-TOKEN"] = meta.content;
        } catch (e) {}
    }
    return headers;
}

// NOTE: We no longer normalize direct resume URLs here. Use secure backend route
// `/api/cv/application/{id}` to serve protected CV files.

// Load applications
async function loadApplications() {
    try {
        const currentUser = JSON.parse(localStorage.getItem("currentUser"));
        if (!currentUser) {
            window.location.href = "/login";
            return;
        }

        // Show loading state
        document.getElementById("applications-list").innerHTML = `
            <div class="empty-state">
                <i>⏳</i>
                <h3>Đang tải dữ liệu...</h3>
                <p>Vui lòng chờ trong giây lát</p>
            </div>
        `;

        console.log("Loading applications for user:", currentUser);
        const response = await fetch(`${API_BASE_URL}/applications`, {
            headers: getAuthHeaders(),
            credentials: "include",
        });
        const data = await response.json().catch(() => null);
        console.log("API response:", data);

        // Handle paginated response { data: [...] } or plain array
        let appsFromApi = [];
        if (!response.ok) {
            throw new Error(`API returned status ${response.status}`);
        }
        if (data) {
            if (Array.isArray(data)) appsFromApi = data;
            else if (Array.isArray(data.data)) appsFromApi = data.data;
            else if (Array.isArray(data.items)) appsFromApi = data.items;
        }

        if (appsFromApi.length > 0) {
            console.log("Enriching applications with job details...");
            // Enrich applications with job details (but prefer job relation if present)
            applications = await enrichApplicationsWithJobDetails(appsFromApi);
        } else {
            console.log(
                "No applications returned from API — showing empty list"
            );
            // Do not inject fake/sample applications in production; show the real empty state
            applications = [];
        }
        filteredApplications = [...applications];

        updateStats();
        renderApplications();
    } catch (error) {
        console.error("Lỗi khi tải ứng tuyển:", error);
        // Show error message to user
        document.getElementById("applications-list").innerHTML = `
            <div class="empty-state">
                <i>⚠️</i>
                <h3>Lỗi khi tải dữ liệu</h3>
                <p>Có lỗi xảy ra khi tải danh sách ứng tuyển. Vui lòng thử lại sau.</p>
                <button onclick="loadApplications()" class="btn btn-primary">Thử lại</button>
            </div>
        `;
    }
}

// Enrich applications with job details
async function enrichApplicationsWithJobDetails(applications) {
    const enrichedApplications = [];

    for (const app of applications) {
        try {
            // Prefer job relation from API if present
            let jobData = app.job || null;
            // some APIs may return job_id or jobId on the application record
            const jobId = jobData
                ? jobData.id || jobData.job_id || jobData.jobId
                : app.jobId || app.job_id || app.jobId;
            if (!jobData && jobId) {
                try {
                    // Use the public jobs endpoint without auth to match job-detail behavior
                    const jobResponse = await fetch(
                        `${API_BASE_URL}/public/jobs/${jobId}`
                    );
                    let jr = await jobResponse.json().catch(() => null);
                    if (jr && jr.data) jr = jr.data;
                    jobData = jr || null;
                } catch (e) {
                    console.warn("Could not fetch job data for application", e);
                }
            }

            // Get company details (try multiple sources: application object, job relation, then fetch by company id)
            let companyName =
                app.companyName || app.company_name || "Không xác định";

            // Prefer job relation embedded company name when available
            if (
                jobData &&
                jobData.company &&
                (jobData.company.company_name || jobData.company.name)
            ) {
                companyName =
                    jobData.company.company_name || jobData.company.name;
            }

            // Fallback to application-level company object if present
            if (
                app.company &&
                (app.company.company_name || app.company.name) &&
                companyName === "Không xác định"
            ) {
                companyName = app.company.company_name || app.company.name;
            }

            // If still unknown, attempt to fetch company by id from jobData or app
            const companyId =
                jobData?.company_id ||
                jobData?.companyId ||
                jobData?.company?.id ||
                app.company_id ||
                app.company?.id ||
                app.companyId;
            if (companyName === "Không xác định" && companyId) {
                try {
                    // Prefer the public companies endpoint first (matches job-detail data)
                    let companyData = null;
                    try {
                        const pub = await fetch(
                            `${API_BASE_URL}/public/companies/${companyId}`
                        );
                        if (pub.ok)
                            companyData = await pub.json().catch(() => null);
                    } catch (e) {
                        /* ignore */
                    }

                    // If public didn't return useful data, try the authenticated endpoint
                    if (!companyData) {
                        try {
                            const companyResponse = await fetch(
                                `${API_BASE_URL}/companies/${companyId}`,
                                { headers: getAuthHeaders() }
                            );
                            if (companyResponse.ok)
                                companyData = await companyResponse
                                    .json()
                                    .catch(() => null);
                        } catch (e) {
                            /* ignore */
                        }
                    }

                    if (companyData) {
                        // public controller returns { company: { ... } }
                        const cd =
                            companyData.company ||
                            companyData.data ||
                            companyData;
                        companyName =
                            (cd &&
                                (cd.company_name ||
                                    cd.name ||
                                    cd.companyName)) ||
                            companyName;
                    }
                } catch (e) {
                    console.warn("Could not fetch company data", e);
                }
            }

            // Create enriched application
            const enrichedApp = {
                ...app,
                jobTitle:
                    (jobData && (jobData.title || jobData.name)) ||
                    app.job_title ||
                    "Không xác định",
                companyName: companyName,
                salary:
                    (jobData && (jobData.salary || jobData.salary_range)) ||
                    app.salary ||
                    "Thỏa thuận",
                // Normalize cover letter property so modal can read it consistently
                coverLetter:
                    app.cover_letter ||
                    app.coverLetter ||
                    app.coverLetterText ||
                    "",
                appliedDate:
                    app.applied_at ||
                    app.appliedAt ||
                    app.appliedDate ||
                    new Date().toISOString(),
            };

            enrichedApplications.push(enrichedApp);
            console.log("Enriched application:", enrichedApp);
        } catch (error) {
            console.error("Error enriching application:", error);
            // Add application with fallback data
            enrichedApplications.push({
                ...app,
                jobTitle: "Không xác định",
                companyName: "Không xác định",
                salary: "Thỏa thuận",
                coverLetter: app.cover_letter || app.coverLetter || "",
                appliedDate:
                    app.appliedAt ||
                    app.appliedDate ||
                    new Date().toISOString(),
            });
        }
    }

    return enrichedApplications;
}

// Update statistics
function updateStats() {
    const total = applications.length;
    const pending = applications.filter(
        (app) => app.status === "pending"
    ).length;
    const reviewed = applications.filter(
        (app) => app.status === "reviewed"
    ).length;
    const approved = applications.filter(
        (app) => app.status === "approved"
    ).length;

    document.getElementById("total-applications").textContent = total;
    document.getElementById("pending-applications").textContent = pending;
    document.getElementById("interview-applications").textContent = reviewed;
    document.getElementById("accepted-applications").textContent = approved;
}

// Render applications
function renderApplications() {
    const container = document.getElementById("applications-list");

    console.log("Rendering applications:", filteredApplications);

    if (filteredApplications.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i>📋</i>
                <h3>Không tìm thấy ứng tuyển nào</h3>
                <p>Hãy thử thay đổi bộ lọc hoặc ứng tuyển thêm việc làm mới!</p>
            </div>
        `;
        return;
    }

    container.innerHTML = filteredApplications
        .map((app) => {
            console.log("Rendering application:", app);

            // Use enriched data directly
            const jobTitle = app.jobTitle || "Không xác định";
            const companyName = app.companyName || "Không xác định";
            const appliedDate = app.appliedDate || new Date().toISOString();
            const salary = app.salary || "Thỏa thuận";
            const status = app.status || "pending";
            const appId = app.id || app.applicationId || 0;

            // Ensure appId is properly escaped for HTML
            const safeAppId = JSON.stringify(appId);

            // Format date safely
            let formattedDate = "Không xác định";
            try {
                formattedDate = new Date(appliedDate).toLocaleDateString(
                    "vi-VN"
                );
            } catch (e) {
                console.error("Error formatting date:", e);
            }

            return `
        <div class="application-item">
            <div class="job-info">
                <h3>${jobTitle}</h3>
                <p><strong>Công ty:</strong> ${companyName}</p>
                <p><strong>Ngày ứng tuyển:</strong> ${formattedDate}</p>
                <!-- Salary hidden in list view; shown in detail modal -->
            </div>
            <div class="application-details">
                <span class="application-status status-${status}">
                    ${getStatusText(status)}
                </span>
                <div class="application-actions">
                    <button class="btn btn-primary view-detail-btn" data-app-id="${appId}" title="Xem chi tiết ứng tuyển">
                        <i class="fas fa-eye"></i>
                        Xem chi tiết
                    </button>
                    ${
                        status === "pending"
                            ? `<button class="btn btn-danger cancel-app-btn" data-app-id="${appId}" title="Hủy ứng tuyển này">
                            <i class="fas fa-times"></i>
                            Hủy ứng tuyển
                        </button>`
                            : `<button class="btn btn-success message-btn" data-app-id="${appId}" title="Nhắn tin với nhà tuyển dụng">
                            <i class="fas fa-comment-dots"></i>
                            Nhắn tin
                        </button>`
                    }
                </div>
            </div>
        </div>
        `;
        })
        .join("");

    // Add event listeners after rendering
    setTimeout(() => {
        addEventListeners();
    }, 100);
}

// Get status text
function getStatusText(status) {
    const statusMap = {
        pending: "Chờ duyệt",
        reviewed: "Đã xem",
        approved: "Phù hợp",
        rejected: "Không phù hợp",
        cancelled: "Đã hủy",
    };
    return statusMap[status] || status;
}

// Apply filters
function applyFilters() {
    const statusFilter = document.getElementById("status-filter").value;
    const dateFrom = document.getElementById("date-from").value;
    const dateTo = document.getElementById("date-to").value;

    filteredApplications = applications.filter((app) => {
        let matches = true;

        if (statusFilter && app.status !== statusFilter) {
            matches = false;
        }

        if (dateFrom && new Date(app.appliedDate) < new Date(dateFrom)) {
            matches = false;
        }

        if (dateTo && new Date(app.appliedDate) > new Date(dateTo)) {
            matches = false;
        }

        return matches;
    });

    renderApplications();
}

// View application details
function viewApplication(applicationId) {
    console.log(
        "Viewing application with ID:",
        applicationId,
        "Type:",
        typeof applicationId
    );

    // Find application by ID (handle both string and number)
    const app = applications.find(
        (a) => a.id == applicationId || String(a.id) === String(applicationId)
    );

    console.log("Found application:", app);

    if (!app) {
        console.error("Application not found for ID:", applicationId);
        showNotification("Không tìm thấy thông tin ứng tuyển!", "error");
        return;
    }

    // If present, normalize the resume/CV URL so we open it on the current origin/port
    // Build a secure URL that points to the backend route which serves the CV
    // The backend route will check permissions and return the file (or 404).
    const hasCvFile =
        app.resume?.file_url || app.cv_file_url || app.resume?.file || null;
    const resumeUrl = hasCvFile
        ? `${API_BASE_URL}/cv/application/${app.id}`
        : null;

    // Format date
    let formattedDate = "Không xác định";
    try {
        formattedDate = new Date(app.appliedDate).toLocaleDateString("vi-VN");
    } catch (e) {
        console.error("Error formatting date:", e);
    }

    // Create modal content
    const modalContent = `
        <div class="detail-section">
            <h3>📋 Thông tin ứng tuyển</h3>
            <div class="detail-item">
                <span class="detail-label">Vị trí:</span>
                <span class="detail-value">${
                    app.jobTitle || "Không xác định"
                }</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Công ty:</span>
                <span class="detail-value">${
                    app.companyName || "Không xác định"
                }</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Trạng thái:</span>
                <span class="detail-value">
                    <span class="status-badge status-${
                        app.status
                    }">${getStatusText(app.status)}</span>
                </span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Ngày ứng tuyển:</span>
                <span class="detail-value">${formattedDate}</span>
            </div>
            <!-- Salary removed from detail modal as requested -->
        </div>

        <div class="detail-section">
            <h3>📝 Thư xin việc</h3>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #28a745;">
                <p style="margin: 0; line-height: 1.6; color: #333;">
                    ${app.coverLetter || "Không có thư xin việc"}
                </p>
            </div>
        </div>

        <div class="detail-section">
            <div class="detail-item">
                <span class="detail-label"><strong>CV:</strong></span>
                <span class="detail-value">${
                    resumeUrl
                        ? `<a href="#" onclick="openCv(${app.id}); return false;">Tải/ Xem CV</a>`
                        : "Không có"
                }</span>
            </div>
        </div>

        <!-- Removed 'Thông tin bổ sung' section as requested -->
    `;

    // Show modal
    document.getElementById("modalBody").innerHTML = modalContent;
    document.getElementById("applicationModal").style.display = "block";
}

// Open CV via secure backend route. This fetches the PDF using auth headers or cookies
// and opens it as a blob in a new tab. If unauthorized, redirect user to login.
window.openCv = async function (applicationId) {
    try {
        const token =
            localStorage.getItem("authToken") || localStorage.getItem("token");
        const headers = token ? { Authorization: "Bearer " + token } : {};
        const res = await fetch(
            `${API_BASE_URL}/cv/application/${applicationId}`,
            { headers, credentials: "include" }
        );

        // If backend redirected to login or returned unauthorized, navigate to login
        if (res.status === 401 || res.redirected) {
            // preserve current location so user can return after login
            window.location.href =
                "/login?from=" +
                encodeURIComponent(
                    window.location.pathname + window.location.search
                );
            return;
        }

        if (!res.ok) {
            const txt = await res.text().catch(() => null);
            console.error("Failed to fetch CV", res.status, txt);
            alert(
                "Không thể tải CV. Vui lòng thử lại hoặc liên hệ quản trị viên."
            );
            return;
        }

        const blob = await res.blob();
        const blobUrl = URL.createObjectURL(blob);
        window.open(blobUrl, "_blank");
        // revoke after 1 minute
        setTimeout(() => URL.revokeObjectURL(blobUrl), 60000);
    } catch (e) {
        console.error("Error opening CV", e);
        alert("Lỗi khi tải CV. Vui lòng thử lại.");
    }
};

// Cancel application
function cancelApplication(applicationId) {
    console.log(
        "Canceling application with ID:",
        applicationId,
        "Type:",
        typeof applicationId
    );

    // Find application by ID (handle both string and number)
    const app = applications.find(
        (a) => a.id == applicationId || String(a.id) === String(applicationId)
    );

    console.log("Found application for cancellation:", app);

    if (!app) {
        console.error("Application not found for ID:", applicationId);
        showNotification("Không tìm thấy thông tin ứng tuyển!", "error");
        return;
    }

    // Store application ID for confirmation
    window.pendingCancelId = applicationId;

    // Update confirmation message
    document.getElementById("confirmationMessage").innerHTML = `
        Bạn có chắc chắn muốn hủy ứng tuyển cho vị trí <strong>"${app.jobTitle}"</strong> tại <strong>"${app.companyName}"</strong>?<br><br>
        <small style="color: #dc3545;">⚠️ Hành động này không thể hoàn tác.</small>
    `;

    // Show confirmation dialog
    document.getElementById("confirmationDialog").style.display = "block";
}

// Confirm cancel application
async function confirmCancelApplication() {
    const applicationId = window.pendingCancelId;
    if (!applicationId) return;

    console.log("Confirming cancellation for ID:", applicationId);

    const confirmBtn = document.getElementById("confirmCancelBtn");
    const originalText = confirmBtn.innerHTML;

    try {
        // Show loading state
        confirmBtn.innerHTML =
            '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
        confirmBtn.disabled = true;
        confirmBtn.classList.add("btn-loading");

        // Call API to cancel application
        const token =
            localStorage.getItem("authToken") || localStorage.getItem("token");
        const headers = getAuthHeaders();
        const fetchOpts = { method: "DELETE", headers: headers };

        // If we don't have a Bearer token, send cookies (Sanctum/session) with the request
        // If we DO have a token, avoid sending credentials so the backend uses the token guard
        if (!token) {
            fetchOpts.credentials = "include";
        } else {
            fetchOpts.credentials = "omit";
        }

        const response = await fetch(
            `${API_BASE_URL}/applications/${applicationId}`,
            fetchOpts
        );

        if (!response.ok) {
            // try to read error detail from response for better debug
            let errMsg = null;
            try {
                const body = await response.json().catch(() => null);
                if (body) errMsg = body.message || JSON.stringify(body);
            } catch (e) {}
            console.error("Cancel API failed", response.status, errMsg);
            throw new Error(
                errMsg ||
                    `Failed to cancel application (status ${response.status})`
            );
        }

        // Remove from local array - use flexible comparison
        applications = applications.filter(
            (app) =>
                app.id != applicationId &&
                String(app.id) !== String(applicationId)
        );
        filteredApplications = [...applications];

        updateStats();
        renderApplications();

        // Close confirmation dialog
        closeConfirmation();

        // Show success message
        showNotification("Đã hủy ứng tuyển thành công!", "success");
    } catch (error) {
        console.error("Lỗi khi hủy ứng tuyển:", error);
        showNotification("Có lỗi xảy ra khi hủy ứng tuyển!", "error");
    } finally {
        // Reset button
        confirmBtn.innerHTML = originalText;
        confirmBtn.disabled = false;
        confirmBtn.classList.remove("btn-loading");
        window.pendingCancelId = null;
    }
}

// Note: sample application generation removed — the UI will show a real empty state when
// the backend returns no applications. If you need local mock data for development,
// re-enable a dedicated dev-only helper or seed the database instead.

// Modal functions
function closeModal() {
    document.getElementById("applicationModal").style.display = "none";
}

function closeConfirmation() {
    document.getElementById("confirmationDialog").style.display = "none";
    window.pendingCancelId = null;
}

// Close modals when clicking outside
window.onclick = function (event) {
    const modal = document.getElementById("applicationModal");
    const confirmation = document.getElementById("confirmationDialog");

    if (event.target === modal) {
        closeModal();
    }
    if (event.target === confirmation) {
        closeConfirmation();
    }
};

// Notification system
function showNotification(message, type = "info") {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll(".notification");
    existingNotifications.forEach((notification) => notification.remove());

    // Create notification element
    const notification = document.createElement("div");
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 1002;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        animation: slideInRight 0.3s ease;
        max-width: 300px;
        word-wrap: break-word;
    `;

    // Set background color based on type
    const colors = {
        success: "linear-gradient(135deg, #28a745 0%, #20c997 100%)",
        error: "linear-gradient(135deg, #dc3545 0%, #c82333 100%)",
        warning: "linear-gradient(135deg, #ffc107 0%, #e0a800 100%)",
        info: "linear-gradient(135deg, #17a2b8 0%, #138496 100%)",
    };

    notification.style.background = colors[type] || colors.info;
    notification.innerHTML = `
        <div style="display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-${
                type === "success"
                    ? "check-circle"
                    : type === "error"
                    ? "exclamation-circle"
                    : type === "warning"
                    ? "exclamation-triangle"
                    : "info-circle"
            }"></i>
            <span>${message}</span>
        </div>
    `;

    // Add to page
    document.body.appendChild(notification);

    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = "slideOutRight 0.3s ease";
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

// Add CSS for notification animations
const style = document.createElement("style");
style.textContent = `
    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(100%);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes slideOutRight {
        from {
            opacity: 1;
            transform: translateX(0);
        }
        to {
            opacity: 0;
            transform: translateX(100%);
        }
    }
`;
document.head.appendChild(style);

// Open chat with employer
async function openChat(applicationId) {
    try {
        // Kiểm tra xem có thể nhắn tin không
        const checkResponse = await fetch(
            `${API_BASE_URL}/chat/applications/${applicationId}/can-chat`,
            {
                headers: getAuthHeaders(),
                credentials: "include",
            }
        );

        const checkData = await checkResponse.json();

        if (!checkData.can_chat) {
            showNotification(
                checkData.message ||
                    "Chỉ có thể nhắn tin khi nhà tuyển dụng đã xem xét hồ sơ của bạn.",
                "warning"
            );
            return;
        }

        // Tạo hoặc lấy conversation
        const response = await fetch(`${API_BASE_URL}/chat/conversations`, {
            method: "POST",
            headers: {
                ...getAuthHeaders(),
                "Content-Type": "application/json",
            },
            credentials: "include",
            body: JSON.stringify({
                application_id: applicationId,
            }),
        });

        const data = await response.json();

        if (data.success) {
            // Chuyển đến trang chat
            window.location.href = `/chat?conversation=${data.conversation.id}`;
        } else {
            showNotification(
                data.message || data.error || "Không thể tạo cuộc trò chuyện",
                "error"
            );
        }
    } catch (error) {
        console.error("Error opening chat:", error);
        showNotification("Có lỗi xảy ra khi mở chat", "error");
    }
}

// Add event listeners for buttons
function addEventListeners() {
    // View detail buttons
    document.querySelectorAll(".view-detail-btn").forEach((btn) => {
        btn.addEventListener("click", function () {
            const appId = this.getAttribute("data-app-id");
            viewApplication(appId);
        });
    });

    // Cancel application buttons
    document.querySelectorAll(".cancel-app-btn").forEach((btn) => {
        btn.addEventListener("click", function () {
            const appId = this.getAttribute("data-app-id");
            cancelApplication(appId);
        });
    });

    // Message buttons
    document.querySelectorAll(".message-btn").forEach((btn) => {
        btn.addEventListener("click", function () {
            const appId = this.getAttribute("data-app-id");
            openChat(appId);
        });
    });
}

// Initialize page
document.addEventListener("DOMContentLoaded", function () {
    loadApplications();
});
