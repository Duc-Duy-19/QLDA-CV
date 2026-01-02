// Simple job management frontend
(function () {
    const API_BASE = "/api";

    const qs = (s) => document.querySelector(s);
    const qsa = (s) => Array.from(document.querySelectorAll(s));

    let editingJobId = null;
    let myCompanyCache = undefined; // undefined = not loaded, null = no company, object = company
    let categoriesCache = undefined; // undefined = not loaded, null = none, array = categories
    let publicCompaniesCache = {}; // cache for public company info by id

    async function fetchMyCompany() {
        if (typeof myCompanyCache !== "undefined") return myCompanyCache;
        const token = localStorage.getItem("authToken");
        try {
            const headers = Object.assign(
                { Accept: "application/json" },
                token ? { Authorization: `Bearer ${token}` } : {}
            );
            const res = await fetch(`${API_BASE}/my-company`, {
                headers,
                credentials: "include",
            });
            if (!res.ok) {
                myCompanyCache = null;
                return null;
            }
            let json;
            try {
                json = await res.json();
            } catch (e) {
                json = null;
            }
            // backend returns { company: {...}, user_role: 'Owner' }
            let company = null;
            if (json) {
                if (json.company) company = json.company;
                else if (json.data) company = json.data;
                else company = json;
            }
            myCompanyCache = company || null;
            try {
                console.debug("fetchMyCompany ->", myCompanyCache);
            } catch (e) {}
            return myCompanyCache;
        } catch (err) {
            console.error("Failed to fetch my company", err);
            myCompanyCache = null;
            return null;
        }
    }

    async function fetchCategories() {
        if (typeof categoriesCache !== "undefined") return categoriesCache;
        const token = localStorage.getItem("authToken");
        try {
            const headers = Object.assign(
                { Accept: "application/json" },
                token ? { Authorization: `Bearer ${token}` } : {}
            );
            const res = await fetch(`${API_BASE}/public/categories`, {
                headers,
                credentials: "include",
            });
            if (!res.ok) {
                categoriesCache = null;
                return null;
            }
            let json;
            try {
                json = await res.json();
            } catch (e) {
                json = null;
            }
            const data =
                json &&
                (Array.isArray(json)
                    ? json
                    : json.data && Array.isArray(json.data)
                    ? json.data
                    : null);
            categoriesCache = data || [];
            try {
                console.debug("fetchCategories ->", categoriesCache);
            } catch (e) {}
            populateCategorySelect(categoriesCache);
            return categoriesCache;
        } catch (err) {
            console.error("Failed to fetch categories", err);
            categoriesCache = null;
            return null;
        }
    }

    function populateCategorySelect(categories) {
        try {
            // render categories as a checkbox list (mobile friendly, multi-select)
            let listWrapper = qs("#job-category-list");
            if (!listWrapper) {
                const expInput = qs("#job-expiration-date");
                const wrapper = document.createElement("div");
                wrapper.className = "form-row";
                wrapper.style.marginTop = "0";
                wrapper.innerHTML = `\n                    <label>Danh mục</label>\n                    <div id="job-category-list" style="display:flex;flex-direction:column;gap:6px;max-height:160px;overflow:auto;padding-top:6px"></div>\n                `;
                if (expInput) {
                    const expRow = expInput.closest(".form-row");
                    if (expRow && expRow.parentNode) {
                        expRow.parentNode.insertBefore(wrapper, expRow);
                    } else {
                        const titleEl = qs("#job-title");
                        if (titleEl && titleEl.parentNode)
                            titleEl.parentNode.insertBefore(
                                wrapper,
                                titleEl.nextSibling
                            );
                        else return;
                    }
                } else {
                    const titleEl = qs("#job-title");
                    if (titleEl && titleEl.parentNode)
                        titleEl.parentNode.insertBefore(
                            wrapper,
                            titleEl.nextSibling
                        );
                    else return;
                }
                listWrapper = qs("#job-category-list");
            }

            // clear existing list
            listWrapper.innerHTML = "";
            if (
                !categories ||
                !Array.isArray(categories) ||
                categories.length === 0
            )
                return;
            categories.forEach((c) => {
                const id = c.id;
                const label = document.createElement("label");
                label.style.display = "flex";
                label.style.alignItems = "center";
                label.style.gap = "8px";
                label.innerHTML = `<input type="checkbox" name="job-category[]" value="${id}" data-cat-id="${id}"> <span>${
                    c.name || c.title || "Danh mục " + id
                }</span>`;
                listWrapper.appendChild(label);
            });
            // ensure there's an error container for category
            if (!qs("#job-category-error")) {
                const err = document.createElement("div");
                err.id = "job-category-error";
                err.className = "field-error";
                err.style.cssText =
                    "color:#dc2626;margin-top:6px;font-size:13px";
                listWrapper.parentNode &&
                    listWrapper.parentNode.appendChild(err);
            }
        } catch (e) {
            console.error("populateCategorySelect error", e);
        }
    }

    function normalizeLogoUrl(url) {
        const placeholder = "/images/company-placeholder.svg";
        if (!url) return placeholder;
        try {
            const raw = String(url || "").trim();
            if (!raw.match(/^https?:\/\//i) && !raw.startsWith("data:")) {
                if (raw.startsWith("/storage/"))
                    return window.location.origin + raw;
                if (raw.startsWith("/")) return window.location.origin + raw;
                return (
                    window.location.origin +
                    "/storage/" +
                    raw.replace(/^\/+/, "")
                );
            }
            return new URL(raw, window.location.origin).href;
        } catch (e) {
            return placeholder;
        }
    }

    async function fetchPublicCompany(id) {
        if (!id) return null;
        if (publicCompaniesCache[id]) return publicCompaniesCache[id];
        try {
            const res = await fetch(`${API_BASE}/public/companies/${id}`, {
                headers: { Accept: "application/json" },
                credentials: "include",
            });
            if (!res.ok) return null;
            let json;
            try {
                json = await res.json();
            } catch (e) {
                json = null;
            }
            // public controller may return { company: {...} } or { data: {...} } or direct company object
            const company =
                json && json.company
                    ? json.company
                    : json && json.data
                    ? json.data
                    : json;
            publicCompaniesCache[id] = company || null;
            return publicCompaniesCache[id];
        } catch (e) {
            console.error("fetchPublicCompany error", e);
            publicCompaniesCache[id] = null;
            return null;
        }
    }

    async function fetchJobs() {
        // Try cookie-based session (include) or token if present
        const token = localStorage.getItem("authToken");
        try {
            const headers = Object.assign(
                { Accept: "application/json" },
                token ? { Authorization: `Bearer ${token}` } : {}
            );
            const res = await fetch(`${API_BASE}/jobs`, {
                headers,
                credentials: "include",
            });

            if (!res.ok) {
                // Try to read response text for debugging
                const txt = await res.text();
                console.error("Failed to load jobs:", res.status, txt);
                return [];
            }

            // Parse JSON safely
            let data;
            try {
                data = await res.json();
            } catch (e) {
                const txt = await res.text();
                console.error("Invalid JSON response when loading jobs:", txt);
                return [];
            }

            return data.data || data; // paginator returns {data, ...}
        } catch (err) {
            console.error(err);
            return [];
        }
    }

    function renderJobs(jobs) {
        const container = qs("#jobs-list");
        if (!jobs || jobs.length === 0) {
            container.innerHTML =
                '<div class="empty-state">Chưa có tin tuyển dụng.</div>';
            return;
        }

        container.innerHTML = "";
        jobs.forEach((job) => {
            const div = document.createElement("div");
            div.className = "job-item";
            let companyName =
                job.company && (job.company.company_name || job.company.name)
                    ? job.company.company_name || job.company.name
                    : job.companyName || "Công ty không xác định";
            let rawLogo =
                job.company && job.company.logo ? job.company.logo : null;
            let logoUrl = normalizeLogoUrl(rawLogo);
            try {
                console.debug("Employer job logo debug", {
                    jobId: job.id,
                    rawLogo: rawLogo,
                    resolvedLogo: logoUrl,
                });
            } catch (e) {}
            div.innerHTML = `
                <div class="job-card-inner" style="display:flex;gap:16px;align-items:flex-start">
                    <div class="job-left" style="flex:0 0 auto">
                        <div class="job-logo-wrap" style="width:88px;height:88px;border-radius:12px;overflow:hidden;background:#f3f4f6;flex:0 0 88px;display:flex;align-items:center;justify-content:center">
                            <img src="${logoUrl}" alt="${escapeHtml(
                companyName
            )}" onerror="this.onerror=null;this.src='/images/company-placeholder.svg'" style="width:72px;height:72px;object-fit:contain">
                        </div>
                    </div>
                    <div class="job-right" style="flex:1;position:relative;min-width:0">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px">
                            <div style="min-width:0">
                                <h3 style="margin:0">${escapeHtml(
                                    job.title
                                )}</h3>
                                <div class="job-meta">${escapeHtml(
                                    companyName
                                )}</div>
                            </div>
                            <div style="text-align:right;color:#16a34a;font-size:13px">${escapeHtml(
                                job.location || ""
                            )}</div>
                        </div>
                        <div style="margin-top:8px;color:#6b7280">${escapeHtml(
                            job.short_description || ""
                        )}</div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:12px">
                            <div class="job-salary">${escapeHtml(
                                job.salary_range || ""
                            )}</div>
                            <div class="actions">
                                <button class="btn btn-ghost btn-view" data-id="${
                                    job.id
                                }">Xem</button>
                                <button class="btn btn-ghost btn-toggle" data-id="${
                                    job.id
                                }">${
                job.status === "open" ? "Đóng" : "Mở"
            }</button>
                                <button class="btn btn-primary btn-edit" data-id="${
                                    job.id
                                }">Sửa</button>
                                <button class="btn btn-danger btn-delete" data-id="${
                                    job.id
                                }">Xóa</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(div);

            // If company info (or logo) missing on job, try to fetch public company info by id
            if (
                !job.company ||
                !(
                    job.company.company_name ||
                    job.company.name ||
                    job.company.logo
                )
            ) {
                (async function () {
                    try {
                        // try job.company.id or job.company_id or job.companyId
                        const cid =
                            (job.company &&
                                (job.company.id || job.company.company_id)) ||
                            job.company_id ||
                            job.companyId ||
                            job.companyIdRaw ||
                            null;
                        let companyFromApi = null;
                        if (cid) {
                            console.debug(
                                "Attempting fetchPublicCompany for id:",
                                cid
                            );
                            companyFromApi = await fetchPublicCompany(cid);
                            console.debug(
                                "fetchPublicCompany result for",
                                cid,
                                companyFromApi
                            );
                        }

                        if (companyFromApi) {
                            const nameEl = div.querySelector(".job-meta");
                            if (
                                nameEl &&
                                (companyFromApi.company_name ||
                                    companyFromApi.name)
                            )
                                nameEl.textContent =
                                    companyFromApi.company_name ||
                                    companyFromApi.name;
                            const img = div.querySelector(".job-logo-wrap img");
                            if (
                                img &&
                                (companyFromApi.logo || companyFromApi.avatar)
                            ) {
                                img.src = normalizeLogoUrl(
                                    companyFromApi.logo || companyFromApi.avatar
                                );
                                img.alt =
                                    companyFromApi.company_name ||
                                    companyFromApi.name ||
                                    img.alt;
                            }
                            return;
                        }

                        // fallback to my-company (session) if public endpoint didn't help
                        console.debug("Falling back to fetchMyCompany()");
                        const myCompany = await fetchMyCompany();
                        console.debug("fetchMyCompany result", myCompany);
                        if (!myCompany) return;
                        const nameEl = div.querySelector(".job-meta");
                        if (
                            nameEl &&
                            (myCompany.company_name || myCompany.name)
                        )
                            nameEl.textContent =
                                myCompany.company_name || myCompany.name;
                        const img = div.querySelector(".job-logo-wrap img");
                        if (img && myCompany.logo) {
                            img.src = normalizeLogoUrl(myCompany.logo);
                            img.alt =
                                myCompany.company_name ||
                                myCompany.name ||
                                img.alt;
                        }
                    } catch (e) {
                        console.error(
                            "Error applying company info to job DOM",
                            e
                        );
                    }
                })();
            }
        });

        // Attach handlers
        qsa(".btn-edit").forEach((b) => b.addEventListener("click", onEdit));
        qsa(".btn-delete").forEach((b) =>
            b.addEventListener("click", onDelete)
        );
        qsa(".btn-toggle").forEach((b) =>
            b.addEventListener("click", onToggle)
        );
        qsa(".btn-view").forEach((b) => b.addEventListener("click", onView));
    }

    function escapeHtml(text) {
        if (!text) return "";
        return ("" + text).replace(/[&<>"']/g, function (c) {
            return {
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;",
                '"': "&quot;",
                "'": "&#39;",
            }[c];
        });
    }

    async function loadAndRender() {
        qs("#jobs-list").innerHTML =
            '<div class="empty-state">Đang tải...</div>';
        const jobs = await fetchJobs();
        renderJobs(jobs);
    }

    function openModal(mode = "create") {
        // Use inline !important so this wins over any stylesheet rules that use !important
        qs("#job-modal").style.setProperty("display", "flex", "important");
        qs("#modal-title").textContent =
            mode === "create" ? "Tạo việc làm" : "Chỉnh sửa việc làm";
        if (mode === "create") {
            // set default expiration to 30 days from now
            const d = new Date();
            d.setDate(d.getDate() + 30);
            const yyyy = d.getFullYear();
            const mm = String(d.getMonth() + 1).padStart(2, "0");
            const dd = String(d.getDate()).padStart(2, "0");
            qs("#job-expiration-date").value = `${yyyy}-${mm}-${dd}`;
        }
        // Ensure category select populated when modal opens.
        // Only fetch if categories not loaded. If categories are loaded already,
        // avoid re-populating the select if it already exists to prevent
        // overwriting a selection set by onEdit().
        (async function () {
            try {
                if (categoriesCache === undefined) {
                    await fetchCategories();
                } else {
                    // if category list missing, populate it; do not re-populate if it exists
                    if (!qs("#job-category-list"))
                        populateCategorySelect(categoriesCache || []);
                }
            } catch (e) {
                console.error("Error ensuring categories on openModal", e);
            }
        })();
    }
    function closeModal() {
        // Hide using inline !important to reliably override stylesheet rules
        qs("#job-modal").style.setProperty("display", "none", "important");
        editingJobId = null;
        clearForm();
    }

    function clearForm() {
        qs("#job-title").value = "";
        qs("#job-description").value = "";
        qs("#job-requirements").value = "";
        qs("#job-salary").value = "";
        qs("#job-location").value = "";
        qs("#job-employment-type").value = "";
        qs("#job-expiration-date").value = "";

        // Clear AI Context fields
        const aiFields = [
            "#job-must-have-skills",
            "#job-nice-to-have-skills",
            "#job-min-experience",
            "#job-weight-skills",
            "#job-evaluation-focus",
        ];
        aiFields.forEach((selector) => {
            const el = qs(selector);
            if (el) el.value = "";
        });

        // uncheck any category checkboxes
        const catChecks = document.querySelectorAll(
            'input[name="job-category[]"]'
        );
        if (catChecks && catChecks.length)
            catChecks.forEach((c) => (c.checked = false));
        const cat = qs("#job-category");
        if (cat) cat.value = "";
        // clear errors as well
        clearJobFormErrors();
    }

    // Clear inline field errors and top-level form errors
    function clearJobFormErrors() {
        const ids = [
            "#job-title-error",
            "#job-description-error",
            "#job-requirements-error",
            "#job-salary-error",
            "#job-location-error",
            "#job-expiration-date-error",
            "#job-employment-type-error",
            "#job-category-error",
        ];
        ids.forEach((id) => {
            const el = qs(id);
            if (el) el.textContent = "";
        });
        const top = qs("#job-form-errors");
        if (top) top.textContent = "";
    }

    // Display per-field errors returned by server (errors is an object of arrays)
    function displayJobFieldErrors(errors) {
        if (!errors || typeof errors !== "object") return;
        const map = {
            title: "#job-title-error",
            description: "#job-description-error",
            requirements: "#job-requirements-error",
            salary_range: "#job-salary-error",
            salary: "#job-salary-error",
            location: "#job-location-error",
            expiration_date: "#job-expiration-date-error",
            employment_type: "#job-employment-type-error",
            category_id: "#job-category-error",
            category_ids: "#job-category-error",
        };
        let first = false;
        const unmapped = [];
        for (const key in errors) {
            const arr = Array.isArray(errors[key])
                ? errors[key]
                : [errors[key]];
            // Try exact key, otherwise try base key before any dot (e.g. 'category_ids.0' -> 'category_ids')
            let target = map[key];
            if (!target && key.indexOf(".") !== -1) {
                const base = key.split(".")[0];
                target = map[base];
            }
            if (target) {
                const el = qs(target);
                if (el) {
                    el.textContent = arr.join(" ");
                    if (!first) {
                        const input = qs(target.replace("-error", ""));
                        if (input) input.focus();
                        first = true;
                    }
                    continue;
                }
            }
            unmapped.push(...arr);
        }
        if (unmapped.length) {
            const top = qs("#job-form-errors");
            if (top)
                top.innerHTML = unmapped.map((m) => `<div>${m}</div>`).join("");
            else console.warn("Job unmapped errors", unmapped);
        }
    }

    async function onSave() {
        const token = localStorage.getItem("authToken");
        const payload = {
            title: qs("#job-title").value.trim(),
            description: qs("#job-description").value.trim(),
            requirements: qs("#job-requirements").value.trim(),
            salary_range: qs("#job-salary").value.trim(),
            location: qs("#job-location").value.trim(),
            employment_type: qs("#job-employment-type").value,
            expiration_date: qs("#job-expiration-date").value, // YYYY-MM-DD
        };

        // collect selected categories from checkboxes (multi-select)
        const checkedCats = Array.from(
            document.querySelectorAll('input[name="job-category[]"]:checked')
        ).map((i) => String(i.value));
        payload.category_ids = checkedCats.length ? checkedCats : [];

        // Collect AI Context data
        const mustHaveSkillsText =
            qs("#job-must-have-skills")?.value?.trim() || "";
        const niceToHaveSkillsText =
            qs("#job-nice-to-have-skills")?.value?.trim() || "";
        const minExperience = qs("#job-min-experience")?.value?.trim() || "";
        const weightSkills = qs("#job-weight-skills")?.value?.trim() || "";
        const evaluationFocus =
            qs("#job-evaluation-focus")?.value?.trim() || "";

        const aiContext = {};
        if (mustHaveSkillsText) {
            aiContext.must_have_skills = mustHaveSkillsText
                .split("\n")
                .map((s) => s.trim())
                .filter((s) => s);
        }
        if (niceToHaveSkillsText) {
            aiContext.nice_to_have_skills = niceToHaveSkillsText
                .split("\n")
                .map((s) => s.trim())
                .filter((s) => s);
        }
        if (minExperience) {
            aiContext.min_experience_years = parseInt(minExperience);
        }
        if (weightSkills) {
            aiContext.weights = {
                skills: parseInt(weightSkills),
                experience: 100 - parseInt(weightSkills), // Simple split for now
            };
        }
        if (evaluationFocus) {
            aiContext.evaluation_focus = evaluationFocus;
        }

        // Only include ai_context if it has data
        if (Object.keys(aiContext).length > 0) {
            payload.ai_context = aiContext;
        }

        // Clear previous inline errors; send to server and let StoreJobRequest return authoritative messages.
        clearJobFormErrors();

        try {
            const headers = Object.assign(
                {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                },
                token ? { Authorization: `Bearer ${token}` } : {}
            );
            let res;
            if (editingJobId) {
                res = await fetch(`${API_BASE}/jobs/${editingJobId}`, {
                    method: "PUT",
                    headers,
                    credentials: "include",
                    body: JSON.stringify(payload),
                });
            } else {
                res = await fetch(`${API_BASE}/jobs`, {
                    method: "POST",
                    headers,
                    credentials: "include",
                    body: JSON.stringify(payload),
                });
            }

            // Parse JSON safely
            let data;
            try {
                data = await res.json();
            } catch (e) {
                data = null;
            }

            if (!res.ok) {
                // If validation error (422) show per-field messages inline
                if (res.status === 422 && data && data.errors) {
                    try {
                        displayJobFieldErrors(data.errors);
                    } catch (e) {
                        console.error("displayJobFieldErrors failed", e);
                    }
                    return;
                }

                // Non-validation error: try to show message or fallback
                if (data && data.message) throw new Error(data.message);
                // if server returned HTML, give informative message
                const txt = await res.text();
                if (typeof txt === "string" && txt.trim().startsWith("<")) {
                    throw new Error(
                        "Server returned HTML (có thể là trang đăng nhập). Vui lòng kiểm tra authentication."
                    );
                }
                throw new Error("HTTP " + res.status);
            }

            closeModal();
            await loadAndRender();
            alert(data.message || "Lưu thành công");
        } catch (err) {
            console.error(err);
            alert("Lỗi khi lưu: " + (err.message || err));
        }
    }

    async function onEdit(e) {
        const id = e.currentTarget.dataset.id;
        const token = localStorage.getItem("authToken");
        try {
            const headers = Object.assign(
                { Accept: "application/json" },
                token ? { Authorization: `Bearer ${token}` } : {}
            );
            const res = await fetch(`${API_BASE}/jobs/${id}`, {
                headers,
                credentials: "include",
            });
            if (!res.ok) {
                const txt = await res.text();
                console.error("Failed to fetch job detail:", res.status, txt);
                throw new Error("Không lấy được job");
            }
            let json;
            try {
                json = await res.json();
            } catch (e) {
                json = null;
            }
            const job = json && json.data ? json.data : json;
            editingJobId = id;
            qs("#job-title").value = job && job.title ? job.title : "";
            qs("#job-description").value =
                job && job.description ? job.description : "";
            qs("#job-requirements").value =
                job && job.requirements ? job.requirements : "";
            qs("#job-salary").value =
                job && job.salary_range ? job.salary_range : "";
            qs("#job-location").value = job && job.location ? job.location : "";
            qs("#job-employment-type").value =
                job && job.employment_type ? job.employment_type : "Full-time";
            // set expiration date if available (normalize formats like "YYYY-MM-DD HH:MM:SS" or ISO with T)
            if (job && job.expiration_date) {
                const m = String(job.expiration_date).match(
                    /(\d{4}-\d{2}-\d{2})/
                );
                if (m) qs("#job-expiration-date").value = m[1];
            }
            // set category if available (ensure categories/select exists first)
            try {
                console.debug(
                    "onEdit job payload",
                    job,
                    "categoriesCache",
                    categoriesCache
                );
                if (categoriesCache === undefined) await fetchCategories();
                else populateCategorySelect(categoriesCache || []);
                // set checkbox selections if available
                const list = qs("#job-category-list");
                if (list && job) {
                    // clear existing checks
                    const allChecks = Array.from(
                        list.querySelectorAll('input[type="checkbox"]')
                    );
                    allChecks.forEach((c) => (c.checked = false));
                    // job may return categories as array relation
                    if (
                        Array.isArray(job.categories) &&
                        job.categories.length
                    ) {
                        const ids = job.categories.map((x) => String(x.id));
                        allChecks.forEach((c) => {
                            if (ids.includes(String(c.value))) c.checked = true;
                        });
                    } else if (job.category_id) {
                        allChecks.forEach((c) => {
                            if (String(c.value) === String(job.category_id))
                                c.checked = true;
                        });
                    } else if (job.category && job.category.id) {
                        allChecks.forEach((c) => {
                            if (String(c.value) === String(job.category.id))
                                c.checked = true;
                        });
                    }
                }
            } catch (e) {
                console.error("error setting category on edit", e);
            }

            // Populate AI Context if available
            if (job && job.ai_context) {
                const ctx = job.ai_context;
                if (
                    ctx.must_have_skills &&
                    Array.isArray(ctx.must_have_skills)
                ) {
                    qs("#job-must-have-skills").value =
                        ctx.must_have_skills.join("\n");
                }
                if (
                    ctx.nice_to_have_skills &&
                    Array.isArray(ctx.nice_to_have_skills)
                ) {
                    qs("#job-nice-to-have-skills").value =
                        ctx.nice_to_have_skills.join("\n");
                }
                if (ctx.min_experience_years) {
                    qs("#job-min-experience").value = ctx.min_experience_years;
                }
                if (ctx.weights && ctx.weights.skills) {
                    qs("#job-weight-skills").value = ctx.weights.skills;
                }
                if (ctx.evaluation_focus) {
                    qs("#job-evaluation-focus").value = ctx.evaluation_focus;
                }
            }

            openModal("edit");
        } catch (err) {
            console.error(err);
            alert("Lỗi khi tải dữ liệu việc làm: " + (err.message || err));
        }
    }

    async function onDelete(e) {
        if (!confirm("Bạn có chắc muốn xóa tin tuyển dụng này?")) return;
        const id = e.currentTarget.dataset.id;
        const token = localStorage.getItem("authToken");
        try {
            const headers = Object.assign(
                { Accept: "application/json" },
                token ? { Authorization: `Bearer ${token}` } : {}
            );
            const res = await fetch(`${API_BASE}/jobs/${id}`, {
                method: "DELETE",
                headers,
                credentials: "include",
            });
            let data;
            try {
                data = await res.json();
            } catch (e) {
                data = null;
            }
            if (!res.ok)
                throw new Error(
                    data && data.message ? data.message : "HTTP " + res.status
                );
            await loadAndRender();
            alert(data && data.message ? data.message : "Xóa thành công");
        } catch (err) {
            console.error(err);
            alert("Lỗi khi xóa: " + (err.message || err));
        }
    }

    async function onToggle(e) {
        const id = e.currentTarget.dataset.id;
        const token = localStorage.getItem("authToken");
        try {
            const headers = Object.assign(
                { Accept: "application/json" },
                token ? { Authorization: `Bearer ${token}` } : {}
            );
            const res = await fetch(`${API_BASE}/jobs/${id}/toggle-status`, {
                method: "POST",
                headers,
                credentials: "include",
            });
            let data;
            try {
                data = await res.json();
            } catch (e) {
                data = null;
            }
            if (!res.ok)
                throw new Error(
                    data && data.message ? data.message : "HTTP " + res.status
                );
            await loadAndRender();
        } catch (err) {
            console.error(err);
            alert("Lỗi khi cập nhật trạng thái: " + (err.message || err));
        }
    }

    function onView(e) {
        const id = e.currentTarget.dataset.id;
        const token = localStorage.getItem("authToken");
        (async function () {
            try {
                const headers = Object.assign(
                    { Accept: "application/json" },
                    token ? { Authorization: `Bearer ${token}` } : {}
                );
                const res = await fetch(`${API_BASE}/jobs/${id}`, {
                    headers,
                    credentials: "include",
                });
                if (!res.ok) {
                    const txt = await res.text();
                    console.error(
                        "Failed to fetch job detail:",
                        res.status,
                        txt
                    );
                    alert(
                        `Không tải được chi tiết công việc (Mã lỗi: ${res.status})\nVui lòng kiểm tra:\n1. Bạn có quyền xem công việc này\n2. Công việc có tồn tại\n3. Đăng nhập vẫn hợp lệ`
                    );
                    return;
                }
                let json;
                try {
                    json = await res.json();
                } catch (e) {
                    console.error("Failed to parse job detail JSON:", e);
                    alert("Lỗi phân tích dữ liệu công việc");
                    return;
                }
                const job = json && json.data ? json.data : json;
                if (!job || !job.id) {
                    alert("Dữ liệu công việc không hợp lệ");
                    return;
                }
                // render into detail modal
                const body = document.getElementById("detail-body");
                body.innerHTML = `
                    <h4 style="margin:0">${escapeHtml(job.title)}</h4>
                    <div class="job-meta">${escapeHtml(
                        job.location || ""
                    )} • ${escapeHtml(
                    job.employment_type || ""
                )} • <strong>${escapeHtml(job.status || "")}</strong></div>
                    <hr>
                    <div class="detail-section"><strong>Mô tả</strong><div>${
                        job.description
                            ? job.description.replace(/\n/g, "<br>")
                            : "Không có mô tả"
                    }</div></div>
                    <div class="detail-section" style="margin-top:8px"><strong>Yêu cầu</strong><div>${
                        job.requirements
                            ? job.requirements.replace(/\n/g, "<br>")
                            : "Không có"
                    }</div></div>
                    <div class="detail-section" style="margin-top:8px"><strong>Mức lương</strong> <span class="salary-badge">${escapeHtml(
                        job.salary_range || "Thỏa thuận"
                    )}</span></div>
                `;
                const modal = document.getElementById("job-detail-modal");
                if (!modal) {
                    console.error("Modal element not found");
                    alert("Không tìm thấy modal hiển thị");
                    return;
                }
                // Ensure shown even if stylesheet used !important
                modal.style.setProperty("display", "flex", "important");
            } catch (err) {
                console.error("Error in onView:", err);
                alert("Lỗi khi tải chi tiết: " + (err.message || err));
            }
        })();
    }

    // Init
    document.addEventListener("DOMContentLoaded", function () {
        // Load categories upfront so the create/edit form can show them
        fetchCategories();
        qs("#btn-new-job").addEventListener("click", function () {
            openModal("create");
        });
        qs("#btn-cancel").addEventListener("click", function () {
            closeModal();
        });
        qs("#btn-save-job").addEventListener("click", function () {
            onSave();
        });
        qs("#job-modal").addEventListener("click", function (e) {
            if (e.target === qs("#job-modal")) closeModal();
        });
        // detail modal close
        const detailModal = qs("#job-detail-modal");
        const detailClose = qs("#detail-close");
        const detailCloseFloat = qs("#detail-close-float");
        if (detailClose)
            detailClose.addEventListener("click", function () {
                if (detailModal)
                    detailModal.style.setProperty(
                        "display",
                        "none",
                        "important"
                    );
            });
        if (detailCloseFloat)
            detailCloseFloat.addEventListener("click", function () {
                if (detailModal)
                    detailModal.style.setProperty(
                        "display",
                        "none",
                        "important"
                    );
            });
        if (detailModal)
            detailModal.addEventListener("click", function (e) {
                if (e.target === detailModal)
                    detailModal.style.setProperty(
                        "display",
                        "none",
                        "important"
                    );
            });

        loadAndRender();
    });
})();
