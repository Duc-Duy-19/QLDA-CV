    (function () {
    const API_BASE_URL = '';
        let employerMap = {};
        let favoriteJobIdSet = new Set();
        let promoIndex = 0;

        const qs = (s, r = document) => r.querySelector(s);
        const qsa = (s, r = document) => Array.from(r.querySelectorAll(s));

        function escapeHtml(str) {
            if (str === null || str === undefined) return '';
            return String(str)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // Format ISO/DateTime string to DD-MM-YYYY
        function formatDateToDMY(value) {
            if (!value) return '';
            try {
                let d = new Date(value);
                if (isNaN(d.getTime())) {
                    const m = String(value).match(/(\d{4}-\d{2}-\d{2})/);
                    if (m) d = new Date(m[1] + 'T00:00:00');
                }
                if (!d || isNaN(d.getTime())) return '';
                const dd = String(d.getDate()).padStart(2, '0');
                const mm = String(d.getMonth() + 1).padStart(2, '0');
                const yyyy = d.getFullYear();
                return `${dd}-${mm}-${yyyy}`;
            } catch (e) { return ''; }
        }

        // Normalize logo URL: convert relative paths to absolute; fallback to placeholder
        function normalizeLogoUrl(url) {
            const placeholder = '/images/company-placeholder.svg';
            if (!url) return placeholder;
            try {
                const raw = String(url || '').trim();
                if (!raw.match(/^https?:\/\//i) && !raw.startsWith('data:')) {
                    if (raw.startsWith('/storage/')) return window.location.origin + raw;
                    if (raw.startsWith('/')) return window.location.origin + raw;
                    return window.location.origin + '/storage/' + raw.replace(/^\/+/, '');
                }
                return new URL(raw, window.location.origin).href;
            } catch (e) { return placeholder; }
        }

        // --- Auth ---
        function showAuthButtons() {
            const a = qs('#user-actions');
            const m = qs('#user-menu');
            if (a) a.style.display = 'flex';
            if (m) m.style.display = 'none';
        }

        function showUserMenu(user) {
            const a = qs('#user-actions');
            const m = qs('#user-menu');
            const n = qs('#user-name');
            const r = qs('#user-role');
            if (a) a.style.display = 'none';
            if (m) m.style.display = 'block';
            if (n) n.textContent = user.name || user.companyName || 'User';
            let roleText = user.role === 'admin' ? 'Quản trị viên' : user.role === 'employer' ? 'Nhà tuyển dụng' : 'Ứng viên';
            if (r) r.textContent = roleText;

            if (user.role === 'candidate' && m) {
                m.style.cursor = 'pointer';
                m.onclick = () => { window.location.href = candidateDashboardUrl; };
            }
        }

        function checkAuthStatus() {
            const isLoggedIn = localStorage.getItem('isLoggedIn');
            const currentUserRaw = localStorage.getItem('currentUser');
            if (isLoggedIn === 'true' && currentUserRaw) {
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
                const token = localStorage.getItem('authToken') || localStorage.getItem('token') || localStorage.getItem('access_token');
                if (token) {
                    try {
                        await fetch('/api/logout', {
                            method: 'POST',
                            headers: {
                                'Authorization': token.startsWith('Bearer ') ? token : ('Bearer ' + token),
                                'Accept': 'application/json',
                                'Content-Type': 'application/json'
                            }
                        });
                    } catch (e) { console.warn('API logout failed', e); }
                }

                // Clear client-side storage
                localStorage.removeItem('isLoggedIn');
                localStorage.removeItem('currentUser');
                localStorage.removeItem('authToken');
                localStorage.removeItem('token');
                localStorage.removeItem('access_token');

                showAuthButtons();
                // Redirect to server logout route to ensure session is invalidated and homepage is rendered unauthenticated
                window.location.href = '/logout';
        }
        window.logout = logout;

        // --- Favorites ---
        async function preloadFavoriteIds() {
            favoriteJobIdSet.clear();
            try {
                const currentUser = JSON.parse(localStorage.getItem('currentUser') || 'null');
                if (!currentUser) return;
                const res = await fetch(`${API_BASE_URL}/favoriteJobs?userId=${currentUser.id}`);
                const list = await res.json();
                list.forEach(f => favoriteJobIdSet.add(Number(f.jobId)));
            } catch (e) { console.warn(e); }
        }

        async function updateFavoriteCount() {
            try {
                const currentUser = JSON.parse(localStorage.getItem('currentUser') || 'null');
                const btn = qs('#favorite-btn');
                const c = btn ? btn.querySelector('.fab-count') : null;
                if (!c) return;
                if (!currentUser) { c.textContent = '0'; return; }
                const res = await fetch(`${API_BASE_URL}/favoriteJobs?userId=${currentUser.id}`);
                const list = await res.json();
                c.textContent = list.length;
            } catch (e) { console.warn(e); }
        }

        async function toggleFavorite(jobId, el) {
            const saved = el.classList.contains('active');
            if (saved) {
                await removeFavorite(jobId, el);
            } else {
                await saveJob(jobId, el);
            }
        }
        window.toggleFavorite = toggleFavorite;

        // --- Jobs ---
        function createJobElement(job) {
            const div = document.createElement('div'); 
            div.className = 'job-item';
            const savedClass = favoriteJobIdSet.has(Number(job.id)) ? 'active' : '';
            const savedIcon = favoriteJobIdSet.has(Number(job.id)) ? 'fas' : 'far';
            const companyName = (job && job.company && (job.company.company_name || job.company.name)) ? (job.company.company_name || job.company.name) : (employerMap[job.companyId] || 'Công ty không xác định');
            const salaryText = job.salary_range || job.salary || '';
            const deadlineText = formatDateToDMY(job.expiration_date || job.deadline || '');
            const rawLogo = (job && job.company && (job.company.logo || job.company.avatar || job.company.logo_url)) ? (job.company.logo || job.company.avatar || job.company.logo_url) : null;
            const logoUrl = normalizeLogoUrl(rawLogo);
            const location = job.location || job.work_location || '';
            const isHot = job.is_hot || job.featured || false;
            
            // Debug: show raw logo value and resolved URL for troubleshooting
            try { console.debug('Job logo debug', { jobId: job.id, rawLogo: rawLogo, resolvedLogo: logoUrl }); } catch(e) {}
            
                div.innerHTML = `
                    <div class="job-card-inner" style="display:flex;gap:16px;align-items:flex-start">
                        <div class="job-left" style="flex:0 0 auto">
                            <div class="job-logo-wrap" style="width:88px;height:88px;flex:0 0 88px;border-radius:12px;overflow:hidden;background:#fff;display:flex;align-items:center;justify-content:center;border:1px solid rgba(2,6,23,0.04)">
                                <img src="${logoUrl}" alt="${escapeHtml(companyName)}" onerror="this.onerror=null;this.src='/images/company-placeholder.svg'" loading="lazy" style="width:72px;height:72px;object-fit:contain;display:block">
                            </div>
                        </div>
                        <div class="job-right" style="flex:1;position:relative;min-width:0">
                            <div class="job-top" style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px">
                                <div style="min-width:0">
                                    <h4 class="job-title" style="margin:0 0 4px 0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${escapeHtml(job.title || '')}</h4>
                                    <div class="job-company" style="color:#6b7280;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${escapeHtml(companyName)}</div>
                                </div>
                                <div class="job-location-top" style="text-align:right;color:#16a34a;font-size:13px;white-space:nowrap">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <div style="display:inline-block;margin-left:6px;vertical-align:middle">${escapeHtml(location)}</div>
                                </div>
                            </div>

                            <div class="job-desc" style="color:#6b7280;margin-top:8px;font-size:13px;min-height:30px;overflow:hidden">${escapeHtml(job.short_description || job.description || '')}</div>

                            <div class="job-bottom" style="display:flex;justify-content:space-between;align-items:center;margin-top:12px">
                                <div class="job-salary" style="color:#059669;font-weight:700;font-size:13px;background:rgba(5,150,105,0.06);padding:6px 10px;border-radius:14px">${escapeHtml(salaryText)}</div>
                                <div class="job-controls" style="display:flex;align-items:center;gap:8px">
                                    <div class="job-deadline" style="color:#6b7280;font-size:13px"><i class="fas fa-calendar"></i> ${escapeHtml(deadlineText)}</div>
                                    <button class="btn-save ${savedClass}" data-job-id="${Number(job.id)}" onclick="toggleFavorite(${Number(job.id)}, this)" title="Lưu việc làm" style="background:#fff;border:1px solid #e5e7eb;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center">
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
            const list = qs('#jobs-list'); if (!list) return; list.innerHTML = ''; jobs.forEach(job => list.appendChild(createJobElement(job)));
        }

        async function loadEmployers() {
            // Try to preload employers from a local API endpoint if available, otherwise leave map empty
            try {
                const res = await fetch(`${API_BASE_URL}/api/companies`);
                if (!res.ok) return;
                const companies = await res.json();
                // support both array and {data: array} paginated responses
                const list = (companies && companies.data) ? companies.data : companies;
                employerMap = (list || []).reduce((a, e) => { a[e.id] = e.company_name || e.companyName || ''; return a; }, {});
            } catch (e) { console.error(e); }
        }

        async function loadJobs() {
            try {
                await preloadFavoriteIds();
                // Use Laravel public API at /api/jobs which returns paginated results
                const res = await fetch('/api/jobs');
                if (!res.ok) { console.error('Failed to load jobs', res.status); return; }
                let json;
                try { json = await res.json(); } catch (e) { json = null; }
                const jobs = (json && json.data) ? json.data : json;
                // jobs might include company relation (job.company.company_name)
                displayJobs(jobs || []);
                await updateFavoriteCount();
            } catch (e) { console.error(e); }
        }

        function applyJob(jobId) {
            const isLoggedIn = localStorage.getItem('isLoggedIn');
            const currentUser = localStorage.getItem('currentUser');
            if (isLoggedIn !== 'true' || !currentUser) {
                alert('Vui lòng đăng nhập để ứng tuyển việc làm!');
                window.location.href = loginUrl + '?from=button';
                return;
            }
            const user = JSON.parse(currentUser);
            if (user.role === 'employer') { alert('Nhà tuyển dụng không thể ứng tuyển việc làm!'); return; }
            alert(`Ứng tuyển thành công cho việc làm ID: ${jobId}`);
        }
        window.applyJob = applyJob;

        // --- Promo Slider ---
        function showPromoSlide(idx) { qsa('.promo-slide').forEach((s, i) => s.classList.toggle('active', i === idx)); }
        function nextPromoSlide() { const s = qsa('.promo-slide'); if (!s.length) return; promoIndex = (promoIndex + 1) % s.length; showPromoSlide(promoIndex); }
        function prevPromoSlide() { const s = qsa('.promo-slide'); if (!s.length) return; promoIndex = (promoIndex - 1 + s.length) % s.length; showPromoSlide(promoIndex); }

        function wireEvents() {
            const searchBtn = qs('#search-btn');
            if (searchBtn) searchBtn.addEventListener('click', () => {
                const t = qs('#job-search') ? qs('#job-search').value : '';
                const l = qs('#location-select') ? qs('#location-select').value : '';
                if (t || l) searchJobs(t, l); else loadJobs();
            });

            qsa('.filter-tag').forEach(tag => tag.addEventListener('click', function () {
                qsa('.filter-tag').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                const loc = this.dataset.location || '';
                if (loc) searchJobs('', loc); else loadJobs();
            }));

            qsa('#category-list .category-link').forEach(l => l.addEventListener('click', function (e) {
                e.preventDefault();
                qsa('#category-list .category-link').forEach(lk => lk.classList.remove('active'));
                this.classList.add('active');
                const cat = this.dataset.category || '';
                filterByCategory(cat);
            }));

            const promoNext = qs('.promo-next'); const promoPrev = qs('.promo-prev');
            if (promoNext) promoNext.addEventListener('click', nextPromoSlide);
            if (promoPrev) promoPrev.addEventListener('click', prevPromoSlide);

            window.addEventListener('storage', (e) => {
                if (e.key === 'favorite:updated') {
                    preloadFavoriteIds().then(updateFavoriteCount).then(() => loadJobs());
                }
            });
        }

        document.addEventListener('DOMContentLoaded', async function () {
            checkAuthStatus();
            wireEvents();
            await loadEmployers();
            await loadJobs();
            const slides = qsa('.promo-slide');
            if (slides.length) setInterval(nextPromoSlide, 8000);
        });

        

    })();

    