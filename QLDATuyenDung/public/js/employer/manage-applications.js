(function () {
    // Simple employer applications manager
    const listEl = document.getElementById("applications-list");
    const filterStatus = document.getElementById("filter-status");
    const btnRefresh = document.getElementById("btn-refresh");
    const modal = document.getElementById("application-modal");
    const modalBody = document.getElementById("application-modal-body");
    const modalTitle = document.getElementById("application-modal-title");
    const modalClose = document.getElementById("application-modal-close");
    const modalCloseBtn = document.getElementById(
        "application-modal-close-btn"
    );

    let currentPage = 1;

    function formatDate(iso) {
        try {
            return new Date(iso).toLocaleString();
        } catch (e) {
            return iso;
        }
    }

    // Return localized status text based on page language (falls back to raw key)
    function getStatusText(statusKey) {
        const lang = (document.documentElement && document.documentElement.lang) || (navigator.language || 'vi');
        const isEn = String(lang).toLowerCase().startsWith('en');
        const mapVi = {
            pending: 'Chờ xử lý',
            reviewing: 'Đang xem xét',
            reviewed: 'Đã xem',
            approved: 'Phù hợp',
            rejected: 'Từ chối',
        };
        const mapEn = {
            pending: 'Pending',
            reviewing: 'Reviewing',
            reviewed: 'Reviewed',
            approved: 'Approved',
            rejected: 'Rejected',
        };

        if (!statusKey) return isEn ? 'Pending' : 'Chờ xử lý';
        const key = String(statusKey);
        return isEn ? (mapEn[key] || key) : (mapVi[key] || key);
    }

    // NOTE: CV files should be served via the backend route `/api/cv/application/{id}`
    // which checks permissions and returns the file. We avoid trying to guess/normalize
    // direct storage URLs on the frontend.

    async function loadApplications() {
        const status = filterStatus.value;
        listEl.innerHTML = '<div class="empty-state">Đang tải...</div>';
        try {
            // Build query params safely so we don't produce a malformed URL like
            // `/employer/applications&per_page=50` when there is no existing ?
            const params = new URLSearchParams();
            if (status) params.set("status", status);
            params.set("per_page", "50");
            const qs = params.toString() ? `?${params.toString()}` : "";
            const data = await APIHelper.request(`/employer/applications${qs}`);

            // Handle paginated shapes: data.data or array
            const apps = Array.isArray(data) ? data : data.data || data;

            if (!apps || apps.length === 0) {
                listEl.innerHTML =
                    '<div class="empty-state">Không tìm thấy hồ sơ ứng tuyển.</div>';
                return;
            }

            const html = apps
                .map((app) => {
                    const jobTitle =
                        app.job?.title ||
                        app.job_title ||
                        (app.job && app.job.name) ||
                        "Không xác định";
                    const candidateName =
                        app.candidate?.user?.name ||
                        app.candidate?.name ||
                        app.candidate?.email ||
                        "Ứng viên";
                    const appliedAt =
                        app.applied_at ||
                        app.appliedAt ||
                        app.created_at ||
                        "";
                    const cover = app.cover_letter || app.coverLetter || "";
                    const statusLabel = (app.status || "pending").toString();
                    // pick logo prioritizing the job's own logo fields, then company, then application-level logos
                    return `
                    <div class="application-card">
                        <div class="app-left">
                            <div class="app-meta">
                                <div class="job-title">${escapeHtml(jobTitle)}</div>
                                <div class="candidate-name">${escapeHtml(candidateName)} • ${escapeHtml(
                                    formatDate(appliedAt)
                                )}</div>
                            </div>
                        </div>
                        <div class="app-right">
                            <div class="status-badge status-${escapeHtml(statusLabel)}">${escapeHtml(
                                getStatusText(statusLabel)
                            )}</div>
                            <div class="app-actions">
                                <button class="action-link" data-action="view" data-id="${app.id}">Xem</button>
                                ${statusLabel !== "pending" 
                                    ? `<button class="action-link message-btn" data-action="message" data-id="${app.id}" title="Nhắn tin với ứng viên">
                                        <i class="fas fa-comment-dots"></i> Nhắn tin
                                       </button>`
                                    : ""
                                }
                            </div>
                        </div>
                    </div>
                `;
                })
                .join("\n");

            listEl.innerHTML = html;

            // attach view handlers
            [...listEl.querySelectorAll('button[data-action="view"]')].forEach(
                (btn) => {
                    btn.addEventListener("click", (e) => {
                        const id = btn.getAttribute("data-id");
                        openDetail(id);
                    });
                }
            );

            // attach message handlers
            [...listEl.querySelectorAll('button[data-action="message"]')].forEach(
                (btn) => {
                    btn.addEventListener("click", (e) => {
                        const id = btn.getAttribute("data-id");
                        openChat(id);
                    });
                }
            );
        } catch (err) {
            console.error("Load applications failed", err);
            listEl.innerHTML = `<div class="empty-state" style="color:#dc2626">Không thể tải dữ liệu. ${escapeHtml(
                err.message || ""
            )}</div>`;
        }
    }

    function escapeHtml(s) {
        if (!s && s !== 0) return "";
        return String(s)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    async function openDetail(id) {
        modalBody.innerHTML = "Đang tải...";
        modalTitle.textContent = "Chi tiết ứng tuyển";
        showModal();
        try {
            const app = await APIHelper.request(`/employer/applications/${id}`);

            const jobTitle =
                app.job?.title || app.job_title || "Không xác định";
            const candidateName =
                app.candidate?.user?.name ||
                app.candidate?.name ||
                app.candidate?.email ||
                "Ứng viên";
            const appliedAt =
                app.applied_at || app.appliedAt || app.created_at || "";
            const cover = app.cover_letter || app.coverLetter || "";
            const hasCvFile =
                app.resume?.file_url ||
                app.cv_file_url ||
                app.resume?.file ||
                null;
            // Use onclick handler to fetch the protected CV with auth instead of a direct anchor
            const resumeUrl = hasCvFile
                ? `/api/cv/application/${app.id}`
                : null;
            const status = app.status || "pending";

            modalBody.innerHTML = `
                <div style="margin-bottom:8px"><strong>Công việc:</strong> ${escapeHtml(
                    jobTitle
                )}</div>
                <div style="margin-bottom:8px"><strong>Ứng viên:</strong> ${escapeHtml(
                    candidateName
                )}</div>
                <div style="margin-bottom:8px"><strong>Ngày ứng tuyển:</strong> ${escapeHtml(
                    formatDate(appliedAt)
                )}</div>
                <div style="margin-bottom:8px"><strong>Trạng thái hiện tại:</strong> <span id="current-status">${escapeHtml(
                    getStatusText(status)
                )}</span></div>
                <div style="margin-bottom:8px"><strong>Thư ứng tuyển:</strong><div style="white-space:pre-wrap; margin-top:6px; padding:8px; background:#f8fafc">${escapeHtml(
                    cover
                )}</div></div>
                <div style="margin-bottom:8px"><strong>CV:</strong> ${
                    resumeUrl
                        ? `<a href="#" class="open-cv" data-id="${app.id}">Tải/ Xem CV</a>`
                        : "Không có"
                }</div>
                <div style="margin-top:12px; display:flex; gap:8px; align-items:center">
                    <label for="status-select"><strong>Đổi trạng thái:</strong></label>
                    <select id="status-select">
                        <option value="pending">Chờ xử lý</option>
                        <option value="reviewed">Đã xem</option>
                        <option value="approved">Phù hợp</option>
                        <option value="rejected">Từ chối</option>
                    </select>
                    <button class="btn btn-primary" id="btn-update-status">Cập nhật</button>
                </div>
            `;

            // attach click handler for CV link - use authenticated fetch to avoid
            // redirect issues on hosted environments. We fetch the CV as a blob
            // using APIHelper headers/credentials and open it in a new tab.
            modalBody.querySelectorAll("a.open-cv").forEach((a) => {
                a.addEventListener("click", function (e) {
                    e.preventDefault();
                    const id = this.dataset.id;
                    if (!id) return;
                    downloadAndOpenCv(id);
                });
            });

            const statusSelect = document.getElementById("status-select");
            statusSelect.value = status;
            document
                .getElementById("btn-update-status")
                .addEventListener("click", async () => {
                    const newStatus = statusSelect.value;
                    try {
                        await APIHelper.request(
                            `/employer/applications/${id}/status`,
                            {
                                method: "PUT",
                                body: JSON.stringify({ status: newStatus }),
                            }
                        );
                        // optimistic UI (show localized label)
                        document.getElementById("current-status").textContent =
                            getStatusText(newStatus);
                        // refresh list
                        await loadApplications();
                    } catch (err) {
                        console.error("Update status failed", err);
                        alert(
                            "Không thể cập nhật trạng thái: " +
                                (err.message || "")
                        );
                    }
                });
        } catch (err) {
            console.error("Failed to load application detail", err);
            modalBody.innerHTML = `<div style="color:#dc2626">Không thể tải chi tiết: ${escapeHtml(
                err.message || ""
            )}</div>`;
        }
    }

    // Open chat with candidate
    async function openChat(applicationId) {
        try {
            // Kiểm tra xem có thể nhắn tin không
            const checkResponse = await fetch(`${APIHelper.getBaseURL()}/chat/applications/${applicationId}/can-chat`, {
                headers: APIHelper.getAuthHeaders(),
                credentials: "include"
            });

            const checkData = await checkResponse.json();

            if (!checkData.can_chat) {
                alert(checkData.message || 'Chỉ có thể nhắn tin khi đã xem xét hồ sơ ứng viên.');
                return;
            }

            // Tạo hoặc lấy conversation
            const response = await fetch(`${APIHelper.getBaseURL()}/chat/conversations`, {
                method: 'POST',
                headers: {
                    ...APIHelper.getAuthHeaders(),
                    'Content-Type': 'application/json'
                },
                credentials: "include",
                body: JSON.stringify({
                    application_id: applicationId
                })
            });

            const data = await response.json();

            if (data.success) {
                // Chuyển đến trang chat
                window.location.href = `/chat?conversation=${data.conversation.id}`;
            } else {
                alert(data.message || data.error || 'Không thể tạo cuộc trò chuyện');
            }
        } catch (error) {
            console.error('Error opening chat:', error);
            alert('Có lỗi xảy ra khi mở chat');
        }
    }

    function showModal() {
        modal?.classList.add("open");
    }
    function hideModal() {
        modal?.classList.remove("open");
    }

    modalClose?.addEventListener("click", hideModal);
    modalCloseBtn?.addEventListener("click", hideModal);

    btnRefresh?.addEventListener("click", () => loadApplications());
    filterStatus?.addEventListener("change", () => loadApplications());

    // initial load
    document.addEventListener("DOMContentLoaded", () => {
        // If APIHelper isn't ready yet, wait a tick
        if (typeof APIHelper === "undefined") {
            console.warn("APIHelper not found on page; waiting briefly...");
            setTimeout(loadApplications, 200);
        } else {
            loadApplications();
        }
    });
})();

// fallback: nếu openCv chưa được định nghĩa (OpenCV.js chưa nạp), mở CV bằng route bảo vệ
if (typeof window.openCv !== "function") {
    window.openCv = function (applicationId) {
        if (!applicationId) {
            console.warn("openCv called without application id");
            return;
        }
        // route backend trả file CV có kiểm tra quyền: /api/cv/application/{id}
        // Use authenticated fetch instead of direct window.open to include
        // Authorization / CSRF cookies so hosted instances don't redirect to home.
        downloadAndOpenCv(applicationId);
    };
}

// event delegation cho link Xem CV
document.addEventListener("click", function (e) {
    const el = e.target.closest && e.target.closest("a.open-cv");
    if (!el) return;
    e.preventDefault();
    const appId = el.dataset.id;
    if (!appId) return;
    try {
        // Prefer authenticated download helper which includes Authorization header
        // and credentials. This avoids HTML redirects (to homepage/login) on host.
        downloadAndOpenCv(appId);
    } catch (err) {
        console.error("openCv error, fallback open file", err);
        // last-resort: open direct URL (may redirect if unauthenticated)
        window.open(`/api/cv/application/${appId}`, "_blank");
    }
});


// Helper: fetch protected CV endpoint with API auth headers and open blob
async function downloadAndOpenCv(applicationId) {
    if (!applicationId) return;
    try {
        // Build URL and headers from APIHelper
        const base = (typeof APIHelper !== 'undefined' && APIHelper.getBaseURL)
            ? APIHelper.getBaseURL()
            : (window.location.origin + '/api');
        const url = `${base}/cv/application/${applicationId}`;

        // Prepare headers: clone and remove Content-Type so browser can accept binary
        const headers = (typeof APIHelper !== 'undefined' && APIHelper.getAuthHeaders)
            ? { ...APIHelper.getAuthHeaders() }
            : { 'Accept': 'application/json' };
        delete headers['Content-Type'];

        const resp = await fetch(url, {
            method: 'GET',
            headers: headers,
            credentials: 'include'
        });

        if (!resp.ok) {
            // try to read JSON error if present
            const ct = resp.headers.get('content-type') || '';
            let msg = `HTTP error ${resp.status}`;
            try {
                if (ct.includes('application/json')) {
                    const j = await resp.json();
                    msg = j.message || JSON.stringify(j);
                } else {
                    const t = await resp.text();
                    msg = t || msg;
                }
            } catch (e) {
                // ignore parse error
            }
            throw new Error(msg);
        }

        const blob = await resp.blob();
        const objectUrl = URL.createObjectURL(blob);

        // Try to open in a new window/tab
        const newWin = window.open();
        if (newWin) {
            newWin.location = objectUrl;
        } else {
            // fallback: create anchor and click
            const a = document.createElement('a');
            a.href = objectUrl;
            a.target = '_blank';
            document.body.appendChild(a);
            a.click();
            a.remove();
        }

        // revoke after some time
        setTimeout(() => URL.revokeObjectURL(objectUrl), 60000);
    } catch (err) {
        console.error('Failed to download/open CV', err);
        alert('Không thể tải CV: ' + (err.message || 'Lỗi kết nối hoặc không có quyền truy cập'));
    }
}
