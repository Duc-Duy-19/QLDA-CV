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
            const date = new Date(iso);
            const day = date.getDate();
            const month = date.getMonth() + 1;
            const year = date.getFullYear();
            return `${day} tháng ${month}, ${year}`;
        } catch (e) {
            return iso;
        }
    }

    // Return localized status text based on page language (falls back to raw key)
    function getStatusText(statusKey) {
        const lang =
            (document.documentElement && document.documentElement.lang) ||
            navigator.language ||
            "vi";
        const isEn = String(lang).toLowerCase().startsWith("en");
        const mapVi = {
            pending: "Chờ xử lý",
            reviewing: "Đang xem xét",
            reviewed: "Đã xem",
            approved: "Phù hợp",
            rejected: "Từ chối",
        };
        const mapEn = {
            pending: "Pending",
            reviewing: "Reviewing",
            reviewed: "Reviewed",
            approved: "Approved",
            rejected: "Rejected",
        };

        if (!statusKey) return isEn ? "Pending" : "Chờ xử lý";
        const key = String(statusKey);
        return isEn ? mapEn[key] || key : mapVi[key] || key;
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
                        app.applied_at || app.appliedAt || app.created_at || "";
                    const cover = app.cover_letter || app.coverLetter || "";
                    const statusLabel = (app.status || "pending").toString();
                    // pick logo prioritizing the job's own logo fields, then company, then application-level logos
                    return `
                    <div class="application-card">
                        <div class="app-left">
                            <div class="app-meta">
                                <div class="job-title">${escapeHtml(
                                    jobTitle
                                )}</div>
                                <div class="candidate-name">${escapeHtml(
                                    candidateName
                                )} • ${escapeHtml(formatDate(appliedAt))}</div>
                            </div>
                        </div>
                        <div class="app-right">
                            <div class="status-badge status-${escapeHtml(
                                statusLabel
                            )}">${escapeHtml(getStatusText(statusLabel))}</div>
                            <div class="app-actions">
                                <button class="action-link" data-action="view" data-id="${
                                    app.id
                                }">Xem</button>
                                ${
                                    statusLabel !== "pending"
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
            [
                ...listEl.querySelectorAll('button[data-action="message"]'),
            ].forEach((btn) => {
                btn.addEventListener("click", (e) => {
                    const id = btn.getAttribute("data-id");
                    openChat(id);
                });
            });
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

            // Kiểm tra nếu là resume được tạo trên web (không phải file upload)
            const isWebResume =
                app.resume_snapshot?.is_web_resume ||
                (app.resume_id && !hasCvFile) ||
                (app.cv_file_url &&
                    app.cv_file_url.includes("/api/candidate/resumes/"));

            // Use onclick handler to fetch the protected CV with auth instead of a direct anchor
            const resumeUrl =
                hasCvFile || app.resume_id
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
                    isWebResume
                        ? `<a href="#" class="view-resume" data-id="${app.id}" data-resume-id="${app.resume_id}">Xem CV</a>`
                        : resumeUrl
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

            // Handler cho resume được tạo trên web
            modalBody.querySelectorAll("a.view-resume").forEach((a) => {
                a.addEventListener("click", async function (e) {
                    e.preventDefault();
                    const appId = this.dataset.id;
                    if (!appId) return;

                    try {
                        // Load lại application để lấy resume_snapshot
                        // Vì employer không có quyền truy cập /candidate/resumes
                        // Nên sử dụng resume_snapshot đã được lưu trong application
                        const application = await APIHelper.request(
                            `/employer/applications/${appId}`
                        );

                        if (application.resume_snapshot) {
                            // Hiển thị resume từ snapshot
                            showResumeDetail(application.resume_snapshot);
                        } else {
                            alert("Không tìm thấy dữ liệu CV");
                        }
                    } catch (err) {
                        console.error("Failed to load resume", err);
                        alert("Không thể tải CV: " + (err.message || ""));
                    }
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
            const checkResponse = await fetch(
                `${APIHelper.getBaseURL()}/chat/applications/${applicationId}/can-chat`,
                {
                    headers: APIHelper.getAuthHeaders(),
                    credentials: "include",
                }
            );

            const checkData = await checkResponse.json();

            if (!checkData.can_chat) {
                alert(
                    checkData.message ||
                        "Chỉ có thể nhắn tin khi đã xem xét hồ sơ ứng viên."
                );
                return;
            }

            // Tạo hoặc lấy conversation
            const response = await fetch(
                `${APIHelper.getBaseURL()}/chat/conversations`,
                {
                    method: "POST",
                    headers: {
                        ...APIHelper.getAuthHeaders(),
                        "Content-Type": "application/json",
                    },
                    credentials: "include",
                    body: JSON.stringify({
                        application_id: applicationId,
                    }),
                }
            );

            const data = await response.json();

            if (data.success) {
                // Chuyển đến trang chat
                window.location.href = `/chat?conversation=${data.conversation.id}`;
            } else {
                alert(
                    data.message ||
                        data.error ||
                        "Không thể tạo cuộc trò chuyện"
                );
            }
        } catch (error) {
            console.error("Error opening chat:", error);
            alert("Có lỗi xảy ra khi mở chat");
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
        const base =
            typeof APIHelper !== "undefined" && APIHelper.getBaseURL
                ? APIHelper.getBaseURL()
                : window.location.origin + "/api";
        const url = `${base}/cv/application/${applicationId}`;

        // Prepare headers: clone and remove Content-Type so browser can accept binary
        const headers =
            typeof APIHelper !== "undefined" && APIHelper.getAuthHeaders
                ? { ...APIHelper.getAuthHeaders() }
                : { Accept: "application/json" };
        delete headers["Content-Type"];

        const resp = await fetch(url, {
            method: "GET",
            headers: headers,
            credentials: "include",
        });

        if (!resp.ok) {
            // try to read JSON error if present
            const ct = resp.headers.get("content-type") || "";
            let msg = `HTTP error ${resp.status}`;
            try {
                if (ct.includes("application/json")) {
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
            const a = document.createElement("a");
            a.href = objectUrl;
            a.target = "_blank";
            document.body.appendChild(a);
            a.click();
            a.remove();
        }

        // revoke after some time
        setTimeout(() => URL.revokeObjectURL(objectUrl), 60000);
    } catch (err) {
        console.error("Failed to download/open CV", err);
        alert(
            "Không thể tải CV: " +
                (err.message || "Lỗi kết nối hoặc không có quyền truy cập")
        );
    }
}

// Hàm hiển thị resume được tạo trên web
function showResumeDetail(resumeData) {
    // Tạo HTML để hiển thị resume
    const resume = resumeData.data || resumeData;

    console.log("=== RESUME DATA DEBUG ===");
    console.log("Full resume:", resume);
    console.log("Has header:", !!resume.header);
    console.log(
        "Has experiences:",
        !!resume.experiences,
        resume.experiences?.length
    );
    console.log(
        "Has educations:",
        !!resume.educations,
        resume.educations?.length
    );
    console.log("Has skills:", !!resume.skills, resume.skills?.length);
    console.log("========================");

    let html =
        '<div style="max-width:800px; margin:0 auto; padding:20px; background:white;">';

    // Header - Hiển thị title CV nếu không có header
    const title =
        resume.title ||
        (resume.header && resume.header.full_name) ||
        "Curriculum Vitae";

    // Header
    if (resume.header) {
        const h = resume.header;
        html +=
            '<div style="text-align:center; margin-bottom:30px; border-bottom:2px solid #16a34a; padding-bottom:20px;">';
        if (h.avatar) {
            html += `<img src="${escapeHtml(
                h.avatar
            )}" style="width:120px; height:120px; border-radius:50%; object-fit:cover; margin-bottom:15px;" />`;
        }
        html += `<h2 style="margin:10px 0; color:#16a34a;">${escapeHtml(
            h.Full_name || h.full_name || title
        )}</h2>`;
        if (h.title)
            html += `<p style="font-size:16px; color:#666;">${escapeHtml(
                h.title
            )}</p>`;
        html += '<div style="font-size:14px; color:#555; margin-top:10px;">';
        if (h.Email || h.email)
            html += `<div>📧 ${escapeHtml(h.Email || h.email)}</div>`;
        if (h.Phone || h.phone)
            html += `<div>📞 ${escapeHtml(h.Phone || h.phone)}</div>`;
        if (h.address) html += `<div>📍 ${escapeHtml(h.address)}</div>`;
        if (h.Website) html += `<div>🌐 ${escapeHtml(h.Website)}</div>`;
        if (h.BirthDay) {
            const birthday = new Date(h.BirthDay).toLocaleDateString("vi-VN");
            html += `<div>🎂 ${birthday}</div>`;
        }
        if (h.gender) {
            const genderText =
                h.gender === "male"
                    ? "Nam"
                    : h.gender === "female"
                    ? "Nữ"
                    : "Khác";
            html += `<div>👤 ${genderText}</div>`;
        }
        html += "</div></div>";
    } else {
        // Fallback khi không có header
        html += `<div style="text-align:center; margin-bottom:30px; border-bottom:2px solid #16a34a; padding-bottom:20px;">
            <h2 style="margin:10px 0; color:#16a34a;">${escapeHtml(title)}</h2>
        </div>`;
    }

    // Mục tiêu nghề nghiệp
    if (resume.skills_summary) {
        html += `<div style="margin-bottom:25px;">
            <h3 style="color:#16a34a; border-bottom:2px solid #16a34a; padding-bottom:8px; margin-bottom:12px;">Mục tiêu nghề nghiệp</h3>
            <p style="white-space:pre-wrap; line-height:1.6;">${escapeHtml(
                resume.skills_summary
            )}</p>
        </div>`;
    }

    // Kinh nghiệm
    if (resume.experiences && resume.experiences.length > 0) {
        html +=
            '<div style="margin-bottom:25px;"><h3 style="color:#16a34a; border-bottom:2px solid #16a34a; padding-bottom:8px; margin-bottom:12px;">Kinh nghiệm làm việc</h3>';
        resume.experiences.forEach((exp) => {
            const startDate = exp.start_date ? new Date(exp.start_date) : null;
            const endDate = exp.end_date ? new Date(exp.end_date) : null;
            const startDateStr = startDate
                ? `${startDate.getDate()} tháng ${
                      startDate.getMonth() + 1
                  }, ${startDate.getFullYear()}`
                : "";
            const endDateStr = endDate
                ? `${endDate.getDate()} tháng ${
                      endDate.getMonth() + 1
                  }, ${endDate.getFullYear()}`
                : "Hiện tại";

            html += `<div style="margin-bottom:15px;">
                <div style="font-weight:600; font-size:16px;">${escapeHtml(
                    exp.job_title || exp.title || "N/A"
                )}</div>
                <div style="color:#666; font-size:14px; margin:5px 0;">
                    ${escapeHtml(exp.company_name || exp.company || "N/A")} 
                    ${
                        startDateStr ? "• " + escapeHtml(startDateStr) : ""
                    } - ${escapeHtml(endDateStr)}
                </div>
                ${
                    exp.description
                        ? `<p style="margin-top:8px; line-height:1.6; white-space:pre-wrap;">${escapeHtml(
                              exp.description
                          )}</p>`
                        : ""
                }
            </div>`;
        });
        html += "</div>";
    }

    // Học vấn
    if (resume.educations && resume.educations.length > 0) {
        html +=
            '<div style="margin-bottom:25px;"><h3 style="color:#16a34a; border-bottom:2px solid #16a34a; padding-bottom:8px; margin-bottom:12px;">Học vấn</h3>';
        resume.educations.forEach((edu) => {
            const startDate = edu.start_date ? new Date(edu.start_date) : null;
            const endDate = edu.end_date ? new Date(edu.end_date) : null;
            const startDateStr = startDate
                ? `${startDate.getDate()} tháng ${
                      startDate.getMonth() + 1
                  }, ${startDate.getFullYear()}`
                : "";
            const endDateStr = endDate
                ? `${endDate.getDate()} tháng ${
                      endDate.getMonth() + 1
                  }, ${endDate.getFullYear()}`
                : "Hiện tại";

            html += `<div style="margin-bottom:15px;">
                <div style="font-weight:600; font-size:16px;">${escapeHtml(
                    edu.degree || edu.field_of_study || "N/A"
                )}</div>
                <div style="color:#666; font-size:14px; margin:5px 0;">
                    ${escapeHtml(edu.school_name || edu.institution || "N/A")}
                    ${
                        startDateStr ? "• " + escapeHtml(startDateStr) : ""
                    } - ${escapeHtml(endDateStr)}
                </div>
                ${
                    edu.description
                        ? `<p style="margin-top:8px; line-height:1.6;">${escapeHtml(
                              edu.description
                          )}</p>`
                        : ""
                }
            </div>`;
        });
        html += "</div>";
    }

    // Kỹ năng
    if (resume.skills && resume.skills.length > 0) {
        html +=
            '<div style="margin-bottom:25px;"><h3 style="color:#16a34a; border-bottom:2px solid #16a34a; padding-bottom:8px; margin-bottom:12px;">Kỹ năng</h3>';
        html += '<div style="display:flex; flex-wrap:wrap; gap:10px;">';
        resume.skills.forEach((skill) => {
            const level = skill.proficiency_level || skill.level || "";
            html += `<span style="background:#e8f5e9; color:#2e7d32; padding:6px 12px; border-radius:15px; font-size:14px;">
                ${escapeHtml(skill.skill_name || skill.name || "N/A")} ${
                level ? "(" + escapeHtml(level) + ")" : ""
            }
            </span>`;
        });
        html += "</div></div>";
    }

    // Chứng chỉ
    if (resume.certifications && resume.certifications.length > 0) {
        html +=
            '<div style="margin-bottom:25px;"><h3 style="color:#16a34a; border-bottom:2px solid #16a34a; padding-bottom:8px; margin-bottom:12px;">Chứng chỉ</h3>';
        resume.certifications.forEach((cert) => {
            html += `<div style="margin-bottom:10px;">
                <span style="font-weight:600;">${escapeHtml(
                    cert.name || cert.certificate_name || "N/A"
                )}</span>
                ${cert.issued_by ? " - " + escapeHtml(cert.issued_by) : ""}
                ${
                    cert.issued_date
                        ? " (" + escapeHtml(cert.issued_date) + ")"
                        : ""
                }
            </div>`;
        });
        html += "</div>";
    }

    html += "</div>";

    // Mở trong tab mới
    const newWindow = window.open("", "_blank");
    if (newWindow) {
        newWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>CV - ${escapeHtml(
                    resume.header?.full_name || resume.title || "Resume"
                )}</title>
                <style>
                    body { font-family: Arial, sans-serif; margin:0; padding:20px; background:#f5f5f5; }
                    @media print { body { background:white; } }
                </style>
            </head>
            <body>${html}</body>
            </html>
        `);
        newWindow.document.close();
    } else {
        alert("Vui lòng cho phép popup để xem CV");
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
