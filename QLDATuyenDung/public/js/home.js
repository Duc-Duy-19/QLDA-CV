(function () {
    const API_BASE_URL = "";
    let employerMap = {};
    let favoriteJobIdSet = new Set();
    let promoIndex = 0;

    const qs = (s, r = document) => r.querySelector(s);
    const qsa = (s, r = document) => Array.from(r.querySelectorAll(s));

    function escapeHtml(str) {
        if (str === null || str === undefined) return "";
        return String(str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Format ISO/DateTime string to DD-MM-YYYY
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

    // Normalize logo URL: convert relative paths to absolute; fallback to placeholder
    function normalizeLogoUrl(url) {
        const placeholder = "/images/company-placeholder.svg";

        // Handle null, undefined, empty, or invalid string values
        if (
            !url ||
            url === "null" ||
            url === "undefined" ||
            url === "NULL" ||
            url === "UNDEFINED" ||
            String(url).trim() === ""
        ) {
            return placeholder;
        }

        try {
            const raw = String(url).trim();

            // Final check for string-literal null/undefined
            if (raw === "null" || raw === "undefined" || raw.length === 0) {
                return placeholder;
            }

            // Check if already absolute URL (http or https)
            if (raw.match(/^https?:\/\//i)) {
                return raw;
            }

            // Check if data URI
            if (raw.startsWith("data:")) {
                return raw;
            }

            // Handle relative paths starting with /storage/, /images/, /uploads/
            if (
                raw.startsWith("/storage/") ||
                raw.startsWith("/images/") ||
                raw.startsWith("/uploads/")
            ) {
                return window.location.origin + raw;
            }

            // Handle any other path starting with /
            if (raw.startsWith("/")) {
                return window.location.origin + raw;
            }

            // If not starting with /, assume it's in storage folder
            if (raw.length > 0 && !raw.includes("://")) {
                return (
                    window.location.origin +
                    "/storage/" +
                    raw.replace(/^\/+/, "")
                );
            }

            // Try to construct full URL
            return new URL(raw, window.location.origin).href;
        } catch (e) {
            console.warn(
                "⚠️ Logo URL normalization failed for:",
                url,
                "Error:",
                e.message
            );
            return placeholder;
        }
    }

    // --- Auth ---
    function showAuthButtons() {
        const a = qs("#user-actions");
        const m = qs("#user-menu");
        if (a) a.style.display = "flex";
        if (m) m.style.display = "none";
    }

    function showUserMenu(user) {
        const a = qs("#user-actions");
        const m = qs("#user-menu");
        const n = qs("#user-name");
        const r = qs("#user-role");
        if (a) a.style.display = "none";
        if (m) m.style.display = "block";
        if (n) n.textContent = user.name || user.companyName || "User";
        let roleText =
            user.role === "admin"
                ? "Quản trị viên"
                : user.role === "employer"
                ? "Nhà tuyển dụng"
                : "Ứng viên";
        if (r) r.textContent = roleText;

        if (user.role === "candidate" && m) {
            m.style.cursor = "pointer";
            m.onclick = () => {
                window.location.href = candidateDashboardUrl;
            };
        }
    }

    function checkAuthStatus() {
        const isLoggedIn = localStorage.getItem("isLoggedIn");
        const currentUserRaw = localStorage.getItem("currentUser");
        if (isLoggedIn === "true" && currentUserRaw) {
            try {
                const user = JSON.parse(currentUserRaw);
                showUserMenu(user);
            } catch (e) {
                console.error(e);
                showAuthButtons();
            }
        } else showAuthButtons();
    }

    async function logout() {
        const token =
            localStorage.getItem("authToken") ||
            localStorage.getItem("token") ||
            localStorage.getItem("access_token");
        if (token) {
            try {
                await fetch("/api/logout", {
                    method: "POST",
                    headers: {
                        Authorization: token.startsWith("Bearer ")
                            ? token
                            : "Bearer " + token,
                        Accept: "application/json",
                        "Content-Type": "application/json",
                    },
                });
            } catch (e) {
                console.warn("API logout failed", e);
            }
        }

        // Clear client-side storage
        localStorage.removeItem("isLoggedIn");
        localStorage.removeItem("currentUser");
        localStorage.removeItem("authToken");
        localStorage.removeItem("token");
        localStorage.removeItem("access_token");

        showAuthButtons();
        // Redirect to server logout route to ensure session is invalidated and homepage is rendered unauthenticated
        window.location.href = "/logout";
    }
    window.logout = logout;

    // --- Favorites ---
    async function preloadFavoriteIds() {
        favoriteJobIdSet.clear();
        try {
            const currentUser = JSON.parse(
                localStorage.getItem("currentUser") || "null"
            );
            if (!currentUser) return;
            const res = await fetch(
                `${API_BASE_URL}/favoriteJobs?userId=${currentUser.id}`
            );
            const list = await res.json();
            list.forEach((f) => favoriteJobIdSet.add(Number(f.jobId)));
        } catch (e) {
            console.warn(e);
        }
    }

    async function updateFavoriteCount() {
        try {
            const currentUser = JSON.parse(
                localStorage.getItem("currentUser") || "null"
            );
            const btn = qs("#favorite-btn");
            const c = btn ? btn.querySelector(".fab-count") : null;
            if (!c) return;
            if (!currentUser) {
                c.textContent = "0";
                return;
            }
            const res = await fetch(
                `${API_BASE_URL}/favoriteJobs?userId=${currentUser.id}`
            );
            const list = await res.json();
            c.textContent = list.length;
        } catch (e) {
            console.warn(e);
        }
    }

    async function toggleFavorite(jobId, el) {
        const saved = el.classList.contains("active");
        if (saved) {
            await removeFavorite(jobId, el);
        } else {
            await saveJob(jobId, el);
        }
    }
    window.toggleFavorite = toggleFavorite;

    // --- Jobs ---
    function createJobElement(job) {
        const div = document.createElement("div");
        div.className = "job-item";
        const savedClass = favoriteJobIdSet.has(Number(job.id)) ? "active" : "";
        const savedIcon = favoriteJobIdSet.has(Number(job.id)) ? "fas" : "far";
        const companyName =
            job && job.company && (job.company.company_name || job.company.name)
                ? job.company.company_name || job.company.name
                : employerMap[job.companyId] || "Công ty không xác định";
        const salaryText = job.salary_range || job.salary || "";
        const deadlineText = formatDateToDMY(
            job.expiration_date || job.deadline || ""
        );
        // Extract logo URL with proper validation
        let rawLogo = null;
        if (job && job.company) {
            rawLogo = job.company.logo || job.company.logo_url || null;
        }
        // Filter out invalid values
        if (rawLogo === "null" || rawLogo === "undefined" || rawLogo === "") {
            rawLogo = null;
        }
        const logoUrl = normalizeLogoUrl(rawLogo);
        const location = job.location || job.work_location || "";
        const isHot = job.is_hot || job.featured || false;

        // Debug: show raw logo value and resolved URL for troubleshooting
        try {
            console.debug("Job logo debug", {
                jobId: job.id,
                rawLogo: rawLogo,
                resolvedLogo: logoUrl,
            });
        } catch (e) {}

        div.innerHTML = `
                    <div class="job-card-inner" style="display:flex;gap:16px;align-items:flex-start">
                        <div class="job-left" style="flex:0 0 auto">
                            <div class="job-logo-wrap" style="width:88px;height:88px;flex:0 0 88px;border-radius:12px;overflow:hidden;background:#fff;display:flex;align-items:center;justify-content:center;border:1px solid rgba(2,6,23,0.04)">
                                <img src="${logoUrl}" alt="${escapeHtml(
            companyName
        )}" onerror="this.onerror=null;this.src='/images/company-placeholder.svg'" loading="lazy" style="width:72px;height:72px;object-fit:contain;display:block">
                            </div>
                        </div>
                        <div class="job-right" style="flex:1;position:relative;min-width:0">
                            <div class="job-top" style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px">
                                <div style="min-width:0">
                                    <h4 class="job-title" style="margin:0 0 4px 0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${escapeHtml(
                                        job.title || ""
                                    )}</h4>
                                    <div class="job-company" style="color:#6b7280;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${escapeHtml(
                                        companyName
                                    )}</div>
                                </div>
                                <div class="job-location-top" style="text-align:right;color:#16a34a;font-size:13px;white-space:nowrap">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <div style="display:inline-block;margin-left:6px;vertical-align:middle">${escapeHtml(
                                        location
                                    )}</div>
                                </div>
                            </div>

                            <div class="job-desc" style="color:#6b7280;margin-top:8px;font-size:13px;min-height:30px;overflow:hidden">${escapeHtml(
                                job.short_description || job.description || ""
                            )}</div>

                            <div class="job-bottom" style="display:flex;justify-content:space-between;align-items:center;margin-top:12px">
                                <div class="job-salary" style="color:#059669;font-weight:700;font-size:13px;background:rgba(5,150,105,0.06);padding:6px 10px;border-radius:14px">${escapeHtml(
                                    salaryText
                                )}</div>
                                <div class="job-controls" style="display:flex;align-items:center;gap:8px">
                                    <div class="job-deadline" style="color:#6b7280;font-size:13px"><i class="fas fa-calendar"></i> ${escapeHtml(
                                        deadlineText
                                    )}</div>
                                    <button class="btn-ai-analysis" data-job-id="${Number(
                                        job.id
                                    )}" onclick="handleAiAnalysisHome(${Number(
            job.id
        )})" title="Phân tích độ phù hợp với AI" style="background:#1fae4f;border:none;padding:8px 12px;border-radius:6px;display:flex;align-items:center;gap:6px;color:white;font-size:12px;font-weight:600;cursor:pointer;transition:background 0.3s" onmouseover="this.style.background='#16a34a'" onmouseout="this.style.background='#1fae4f'">
                                        <span>🤖</span>
                                        <span>AI</span>
                                    </button>
                                    <button class="btn-save ${savedClass}" data-job-id="${Number(
            job.id
        )}" onclick="toggleFavorite(${Number(
            job.id
        )}, this)" title="Lưu việc làm" style="background:#fff;border:1px solid #e5e7eb;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center">
                                        <i class="${savedIcon} fa-heart"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
        return div;
    }

    function displayJobs(jobs) {
        const list = qs("#jobs-list");
        if (!list) return;
        list.innerHTML = "";
        jobs.forEach((job) => list.appendChild(createJobElement(job)));
    }

    async function loadEmployers() {
        // Try to preload employers from a local API endpoint if available, otherwise leave map empty
        try {
            const res = await fetch(`${API_BASE_URL}/api/companies`);
            if (!res.ok) return;
            const companies = await res.json();
            // support both array and {data: array} paginated responses
            const list =
                companies && companies.data ? companies.data : companies;
            employerMap = (list || []).reduce((a, e) => {
                a[e.id] = e.company_name || e.companyName || "";
                return a;
            }, {});
        } catch (e) {
            console.error(e);
        }
    }

    async function loadJobs() {
        try {
            await preloadFavoriteIds();
            // Use Laravel public API at /api/jobs which returns paginated results
            const res = await fetch("/api/jobs");
            if (!res.ok) {
                console.error("Failed to load jobs", res.status);
                return;
            }
            let json;
            try {
                json = await res.json();
            } catch (e) {
                json = null;
            }
            const jobs = json && json.data ? json.data : json;
            // jobs might include company relation (job.company.company_name)
            displayJobs(jobs || []);
            await updateFavoriteCount();
        } catch (e) {
            console.error(e);
        }
    }

    function applyJob(jobId) {
        const isLoggedIn = localStorage.getItem("isLoggedIn");
        const currentUser = localStorage.getItem("currentUser");
        if (isLoggedIn !== "true" || !currentUser) {
            alert("Vui lòng đăng nhập để ứng tuyển việc làm!");
            window.location.href = loginUrl + "?from=button";
            return;
        }
        const user = JSON.parse(currentUser);
        if (user.role === "employer") {
            alert("Nhà tuyển dụng không thể ứng tuyển việc làm!");
            return;
        }
        alert(`Ứng tuyển thành công cho việc làm ID: ${jobId}`);
    }
    window.applyJob = applyJob;

    // --- Promo Slider ---
    function showPromoSlide(idx) {
        qsa(".promo-slide").forEach((s, i) =>
            s.classList.toggle("active", i === idx)
        );
    }
    function nextPromoSlide() {
        const s = qsa(".promo-slide");
        if (!s.length) return;
        promoIndex = (promoIndex + 1) % s.length;
        showPromoSlide(promoIndex);
    }
    function prevPromoSlide() {
        const s = qsa(".promo-slide");
        if (!s.length) return;
        promoIndex = (promoIndex - 1 + s.length) % s.length;
        showPromoSlide(promoIndex);
    }

    function wireEvents() {
        const searchBtn = qs("#search-btn");
        if (searchBtn)
            searchBtn.addEventListener("click", () => {
                const t = qs("#job-search") ? qs("#job-search").value : "";
                const l = qs("#location-select")
                    ? qs("#location-select").value
                    : "";
                if (t || l) searchJobs(t, l);
                else loadJobs();
            });

        qsa(".filter-tag").forEach((tag) =>
            tag.addEventListener("click", function () {
                qsa(".filter-tag").forEach((t) => t.classList.remove("active"));
                this.classList.add("active");
                const loc = this.dataset.location || "";
                if (loc) searchJobs("", loc);
                else loadJobs();
            })
        );

        qsa("#category-list .category-link").forEach((l) =>
            l.addEventListener("click", function (e) {
                e.preventDefault();
                qsa("#category-list .category-link").forEach((lk) =>
                    lk.classList.remove("active")
                );
                this.classList.add("active");
                const cat = this.dataset.category || "";
                filterByCategory(cat);
            })
        );

        const promoNext = qs(".promo-next");
        const promoPrev = qs(".promo-prev");
        if (promoNext) promoNext.addEventListener("click", nextPromoSlide);
        if (promoPrev) promoPrev.addEventListener("click", prevPromoSlide);

        window.addEventListener("storage", (e) => {
            if (e.key === "favorite:updated") {
                preloadFavoriteIds()
                    .then(updateFavoriteCount)
                    .then(() => loadJobs());
            }
        });
    }

    document.addEventListener("DOMContentLoaded", async function () {
        checkAuthStatus();
        wireEvents();
        await loadEmployers();
        await loadJobs();
        const slides = qsa(".promo-slide");
        if (slides.length) setInterval(nextPromoSlide, 8000);
    });

    // ===== AI ANALYSIS FUNCTIONS =====
    let candidateResumes = null;

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

    // handleAiAnalysisHome đã được định nghĩa trong ai-analysis.js

    function showCvSelectionModalHome(jobId, token) {
        const modal = document.createElement("div");
        modal.style.cssText =
            "position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:10000;";

        const modalContent = document.createElement("div");
        modalContent.style.cssText =
            "background:white;padding:30px;border-radius:12px;max-width:600px;width:90%;max-height:80vh;overflow-y:auto;";

        let html = `<h3 style="margin-bottom:20px;color:#1fae4f;">Chọn CV để phân tích</h3>`;

        if (candidateResumes && candidateResumes.length > 0) {
            html += `<div style="margin-bottom:20px;">`;
            candidateResumes.forEach((resume, index) => {
                const type = resume.parsed_content ? "Đã tải lên" : "Đã tạo";
                html += `
                        <div class="cv-option" data-resume-id="${
                            resume.id
                        }" style="padding:15px;margin-bottom:10px;border:2px solid #e0e0e0;border-radius:8px;cursor:pointer;transition:all 0.3s;">
                            <div style="font-weight:600;color:#333;margin-bottom:5px;">${escapeHtml(
                                resume.title ||
                                    resume.name ||
                                    "CV " + (index + 1)
                            )}</div>
                            <div style="font-size:13px;color:#888;">
                                <span style="background:#e8f5e9;color:#1fae4f;padding:2px 8px;border-radius:4px;margin-right:8px;">${type}</span>
                                Cập nhật: ${formatDateToDMY(resume.updated_at)}
                            </div>
                        </div>
                    `;
            });
            html += `</div>`;
        }

        html += `
                <div style="margin-top:20px;">
                    <label style="display:block;margin-bottom:10px;color:#666;font-weight:600;">
                        ${
                            candidateResumes && candidateResumes.length > 0
                                ? "Hoặc t"
                                : "T"
                        }ải CV mới lên (PDF)
                    </label>
                    <input type="file" id="ai-cv-upload-home" accept=".pdf" style="width:100%;padding:10px;border:2px dashed #1fae4f;border-radius:8px;cursor:pointer;">
                </div>
                <div id="modal-messages-home" style="margin-top:15px;color:#e03;font-size:14px;text-align:center;"></div>
                <div style="margin-top:25px;display:flex;gap:10px;justify-content:flex-end;">
                    <button id="cancel-analysis-btn-home" style="padding:12px 24px;background:#ccc;color:#333;border:none;border-radius:6px;cursor:pointer;font-weight:600;">
                        Hủy
                    </button>
                    <button id="upload-and-analyze-btn-home" style="padding:12px 24px;background:#1fae4f;color:white;border:none;border-radius:6px;cursor:pointer;font-weight:600;display:none;">
                        Tải lên và Phân tích
                    </button>
                </div>
            `;

        modalContent.innerHTML = html;
        modal.appendChild(modalContent);
        document.body.appendChild(modal);

        // Handle CV selection
        const cvOptions = modalContent.querySelectorAll(".cv-option");
        cvOptions.forEach((option) => {
            option.addEventListener("click", async function () {
                const resumeId = this.getAttribute("data-resume-id");
                modal.remove();
                await performAiAnalysisHome(jobId, resumeId, token);
            });

            option.addEventListener("mouseenter", function () {
                this.style.borderColor = "#1fae4f";
                this.style.background = "#f8fdf9";
            });
            option.addEventListener("mouseleave", function () {
                this.style.borderColor = "#e0e0e0";
                this.style.background = "white";
            });
        });

        // Handle file upload
        const uploadInput = document.getElementById("ai-cv-upload-home");
        const uploadBtn = document.getElementById(
            "upload-and-analyze-btn-home"
        );
        const messagesEl = document.getElementById("modal-messages-home");

        uploadInput.addEventListener("change", function () {
            if (this.files.length > 0) {
                uploadBtn.style.display = "block";
                messagesEl.textContent = "";
            }
        });

        uploadBtn.addEventListener("click", async function () {
            const file = uploadInput.files[0];
            if (!file) {
                messagesEl.textContent = "Vui lòng chọn file PDF";
                return;
            }

            if (file.type !== "application/pdf") {
                messagesEl.textContent = "Chỉ chấp nhận file PDF";
                return;
            }

            if (file.size > 5 * 1024 * 1024) {
                messagesEl.textContent = "File không được vượt quá 5MB";
                return;
            }

            try {
                messagesEl.textContent = "Đang tải lên CV...";
                messagesEl.style.color = "#1fae4f";

                const formData = new FormData();
                formData.append("file", file);

                const uploadRes = await fetch("/api/cv/upload-file", {
                    method: "POST",
                    headers: {
                        Authorization: "Bearer " + token,
                    },
                    body: formData,
                });

                if (!uploadRes.ok) {
                    const errorData = await uploadRes.json().catch(() => ({}));
                    throw new Error(
                        errorData.error ||
                            errorData.message ||
                            "Không thể tải lên CV"
                    );
                }

                const uploadData = await uploadRes.json();
                modal.remove();

                await performAiAnalysisHome(jobId, uploadData.resume_id, token);
            } catch (e) {
                messagesEl.textContent = "Lỗi: " + e.message;
                messagesEl.style.color = "#e03";
            }
        });

        // Handle cancel
        const cancelBtn = document.getElementById("cancel-analysis-btn-home");
        cancelBtn.addEventListener("click", () => modal.remove());

        // Close on backdrop click
        modal.addEventListener("click", (e) => {
            if (e.target === modal) modal.remove();
        });
    }

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
                    errorData.message ||
                        errorData.error ||
                        "Không thể phân tích CV"
                );
            }

            const data = await res.json();
            showAnalysisResultsHome(data.analysis);
        } catch (e) {
            loadingModal.remove();
            alert("Lỗi: " + e.message);
        }
    }

    function showAnalysisResultsHome(analysis) {
        const modal = document.createElement("div");
        modal.style.cssText =
            "position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:10000;overflow-y:auto;padding:20px;";

        const modalContent = document.createElement("div");
        modalContent.style.cssText =
            "background:white;padding:40px;border-radius:12px;max-width:700px;width:100%;max-height:90vh;overflow-y:auto;";

        const matchScore = analysis.match_score || 0;
        const scoreColor =
            matchScore >= 70
                ? "#1fae4f"
                : matchScore >= 50
                ? "#ff9800"
                : "#e03";

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

        modalContent.innerHTML = html;
        modal.appendChild(modalContent);
        document.body.appendChild(modal);

        const closeBtn = document.getElementById("close-results-btn-home");
        closeBtn.addEventListener("click", () => modal.remove());

        modal.addEventListener("click", (e) => {
            if (e.target === modal) modal.remove();
        });
    }
})();
