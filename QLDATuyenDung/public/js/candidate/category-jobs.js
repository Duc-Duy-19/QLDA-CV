const API_BASE_URL = '';
let CATEGORY = window.CATEGORY || 'IT/Phần mềm';
let currentPage = 1;
let totalPages = 1;
let allJobs = [];
let filteredJobs = [];
let favoriteJobIdSet = new Set();
let appliedJobIdSet = new Set();
const companyCache = new Map();
const hasApiHelper = (typeof window !== 'undefined' && window.APIHelper && typeof window.APIHelper.request === 'function');

async function preloadFavoriteIds() {
    try {
        const currentUser = JSON.parse(localStorage.getItem('currentUser'));
        if (!currentUser) return;
        // Prefer the global APIHelper (it handles CSRF/cookies/authorization the same way home page does)
        if (hasApiHelper) {
            try {
                // ensure CSRF cookie/session is initialized for Sanctum flows
                if (typeof window.APIHelper.ensureCsrf === 'function') {
                    try { await window.APIHelper.ensureCsrf(); } catch(e) { /* ignore */ }
                }
                const resp = await window.APIHelper.request('/saved-jobs');
                const favorites = resp?.data || resp || [];
                favoriteJobIdSet = new Set((favorites || []).map(fav => Number((fav.jobId || fav.job_id || (fav.job && fav.job.id) || fav.jobId) || 0)).filter(Boolean));
                return;
            } catch (e) {
                console.warn('preloadFavoriteIds: APIHelper request failed, falling back to fetch', e);
            }
        }

        // Fallback: call the API directly with credentials (cookie-based auth) or Authorization header if available
        const url = (window.location.origin || '') + `/api/saved-jobs` + (currentUser && currentUser.id ? `?userId=${currentUser.id}` : '');
        const headers = { 'Accept': 'application/json' };
        const authHeader = getAuthHeader();
        if (authHeader) headers['Authorization'] = authHeader;
        const resp = await fetch(url, { credentials: 'include', headers });
        if (!resp.ok) {
            const txt = await resp.text().catch(() => null);
            console.warn('preloadFavoriteIds: saved-jobs endpoint returned non-OK', resp.status, txt ? txt.slice(0,200) : null);
            favoriteJobIdSet = new Set();
            return;
        }

        // Try to parse JSON, but be defensive: some servers may return `{ data: [...] }` or plain array
        let parsed = null;
        try {
            parsed = await resp.json();
        } catch (e) {
            const raw = await resp.text().catch(() => null);
            console.warn('preloadFavoriteIds: failed to parse JSON from saved-jobs response', raw && raw.slice ? raw.slice(0,200) : raw);
            parsed = null;
        }

        const favorites = (parsed && parsed.data) ? parsed.data : (parsed || []);
        favoriteJobIdSet = new Set((favorites || []).map(fav => Number((fav.jobId || fav.job_id || (fav.job && fav.job.id) || fav.jobId) || 0)).filter(Boolean));
    } catch (error) {
        console.error('Error loading favorites:', error);
    }
}

// Load IDs of jobs the current candidate has already applied to
async function preloadAppliedJobIds() {
    try {
        const currentUser = JSON.parse(localStorage.getItem('currentUser'));
        if (!currentUser) return;
        if (hasApiHelper) {
            try {
                if (typeof window.APIHelper.ensureCsrf === 'function') {
                    try { await window.APIHelper.ensureCsrf(); } catch(e) {}
                }
                const resp = await window.APIHelper.request('/applications');
                const apps = resp?.data || resp || [];
                appliedJobIdSet = new Set((apps || []).map(a => Number(a.job_id || a.jobId || (a.job && a.job.id) || 0)).filter(Boolean));
                return;
            } catch (e) {
                console.warn('preloadAppliedJobIds: APIHelper request failed, falling back to fetch', e);
            }
        }

        const url = (window.location.origin || '') + `/api/applications`;
        const headers = { 'Accept': 'application/json' };
        const auth = getAuthHeader(); if (auth) headers['Authorization'] = auth;
        const resp = await fetch(url, { credentials: 'include', headers });
        if (!resp.ok) { appliedJobIdSet = new Set(); return; }
        let parsed = null;
        try { parsed = await resp.json(); } catch (e) { parsed = null; }
        const apps = (parsed && parsed.data) ? parsed.data : (parsed || []);
        appliedJobIdSet = new Set((apps || []).map(a => Number(a.job_id || a.jobId || (a.job && a.job.id) || 0)).filter(Boolean));
    } catch (e) {
        console.warn('preloadAppliedJobIds failed', e);
        appliedJobIdSet = new Set();
    }
}

async function loadCategoryJobs() {
    try {
        await preloadFavoriteIds();
        await preloadAppliedJobIds();
        let categoriesRes = await fetch(`${API_BASE_URL}/api/public/categories`);
        if (!categoriesRes.ok) throw new Error('Failed to load categories');
        let categoriesJson = await categoriesRes.json();
        const categoriesList = (categoriesJson && categoriesJson.data) ? categoriesJson.data : categoriesJson;

        let categoryObj = (categoriesList || []).find(c => String(c.name || '').toLowerCase() === String(CATEGORY || '').toLowerCase());

        if (!categoryObj) {
            const decoded = decodeURIComponent(String(CATEGORY || ''));
            categoryObj = (categoriesList || []).find(c => String(c.name || '').toLowerCase() === decoded.toLowerCase())
                || (categoriesList || []).find(c => String(c.name || '').toLowerCase().includes(String(CATEGORY || '').toLowerCase()));
        }

        if (!categoryObj) {
            console.warn('Category not found in API for name:', CATEGORY);
            allJobs = [];
            filteredJobs = [];
            showEmptyState();
            updateJobsCount();
            return;
        }

    const jobsRes = await fetch(`${API_BASE_URL}/api/public/categories/${categoryObj.id}/jobs?per_page=100`);
        if (!jobsRes.ok) throw new Error('Failed to load jobs for category');
        const jobsJson = await jobsRes.json();

        let jobsData = null;
        if (jobsJson && jobsJson.data) {
            if (jobsJson.data.jobs) {
                jobsData = jobsJson.data.jobs.data || jobsJson.data.jobs;
            } else if (Array.isArray(jobsJson.data)) {
                jobsData = jobsJson.data;
            }
        } else if (Array.isArray(jobsJson)) {
            jobsData = jobsJson;
        }

        allJobs = (jobsData || []).map(j => {
            return Object.assign({}, j, {
                companyName: (j.company && (j.company.company_name || j.company.name)) || j.company_name || j.companyName,
                logo: (j.company && (j.company.logo || j.company.avatar || j.company.logo_url)) || j.logo || j.companyLogo,
                salary: j.salary_range || j.salary || j.salaryText,
                deadline: j.expiration_date || j.deadline || j.expiry,
                short_description: j.short_description || j.description || ''
            });
        });

        const companyIdsToFetch = new Set();
        allJobs.forEach(job => {
            const companyId = job.company_id || job.companyId || (job.company && job.company.id) || null;
            const hasLogo = job.logo && String(job.logo).trim() !== '';
            if (!hasLogo && companyId) companyIdsToFetch.add(companyId);
        });

        console.debug('companyIdsToFetch:', Array.from(companyIdsToFetch));

        if (companyIdsToFetch.size > 0) {
            await Promise.all(Array.from(companyIdsToFetch).map(id => fetchCompany(id)));
            allJobs = allJobs.map(job => {
                const companyId = job.company_id || job.companyId || (job.company && job.company.id) || null;
                if (companyId && companyCache.has(String(companyId))) {
                    const c = companyCache.get(String(companyId));
                    return Object.assign({}, job, {
                            companyName: job.companyName || c.company_name || c.name || job.companyName,
                            logo: job.logo || c.logo || c.avatar || c.logo_url || job.logo,
                            website: job.website || c.website || job.website
                        });
                }
                return job;
            });
        }

        filteredJobs = [...allJobs];
        displayJobs();
        updateJobsCount();
    } catch (error) {
        console.error('Error loading category jobs:', error);
        showEmptyState();
    }
}

function displayJobs() {
    const container = document.getElementById('jobs-list');
    const jobsPerPage = 12;
    const startIndex = (currentPage - 1) * jobsPerPage;
    const endIndex = startIndex + jobsPerPage;
    const currentJobs = filteredJobs.slice(startIndex, endIndex);
    
    if (currentJobs.length === 0) {
        showEmptyState();
        return;
    }
    
    container.innerHTML = currentJobs.map(job => createJobElement(job)).join('');
    updatePagination();

    // Hover/popup behavior is handled by CSS (.job-item-container:hover .job-detail-popup)
    // No JS mouseenter/mouseleave handlers are needed here to replicate Home behavior.
    // But we need to detect when an item is near the right edge and flip the popup to the left
    // so it won't be clipped. Do that on mouseenter for each container.
    container.querySelectorAll('.job-item-container').forEach(wrapper => {
        const popup = wrapper.querySelector('.job-detail-popup');
        if (!popup) return;
        wrapper.addEventListener('mouseenter', () => {
            try {
                popup.classList.remove('to-left');
                const rect = wrapper.getBoundingClientRect();
                const popupWidth = Math.min(360, window.innerWidth - 40);
                // if right edge + popup would overflow window, flip to left
                if (rect.right + popupWidth + 20 > window.innerWidth) {
                    popup.classList.add('to-left');
                } else {
                    popup.classList.remove('to-left');
                }
            } catch (e) { /* ignore */ }
        });
    });
}

let popupTimeout = null;
let activePopupJobId = null;
function showJobPopup(jobId, jobElement) {
    try {
        const jobsContainer = allJobs || [];
        const job = jobsContainer.find(j => String(j.id) === String(jobId));
        if (!job) return;
        const popup = document.getElementById('job-detail-popup');
        if (!popup) return;

        const detailDeadline = (job.expiration_date || job.deadline) ? (new Date(job.expiration_date || job.deadline)).toLocaleDateString('vi-VN') : '';
        popup.innerHTML = `
            <h3>${escapeHtml(job.title || '')}</h3>
            <p><strong>Công ty:</strong> ${escapeHtml(job.companyName || job.company_name || '')}</p>
            <p><strong>Lương:</strong> ${escapeHtml(job.salary || job.salary_range || '')}</p>
            <p><strong>Địa điểm:</strong> ${escapeHtml(job.location || '')}</p>
            <p><strong>Hạn nộp:</strong> ${escapeHtml(detailDeadline)}</p>
            <div><strong>Mô tả:</strong> ${escapeHtml(job.description || job.short_description || '')}</div>
        `;
        popup.style.display = 'block';
        const rect = jobElement.getBoundingClientRect();
        const popupWidth = Math.min(360, window.innerWidth - 40);
        popup.style.width = popupWidth + 'px';
        let left = rect.right + 12;
        let top = rect.top + window.scrollY;
        if (left + popupWidth + 20 > window.innerWidth) {
            left = rect.left - popupWidth - 12;
            if (left < 10) left = 10;
        }
        popup.style.left = left + 'px';
        popup.style.top = (top) + 'px';

        activePopupJobId = jobId;
    } catch (e) { console.warn(e); }
}

function hideJobPopup() {
    const popup = document.getElementById('job-detail-popup');
    if (popup) popup.style.display = 'none';
    activePopupJobId = null;
}

async function fetchCompany(companyId) {
    try {
        const key = String(companyId);
        if (companyCache.has(key)) return companyCache.get(key);
        const res = await fetch(`${API_BASE_URL}/api/public/companies/${companyId}`);
        const rawText = await res.text();
        let company = null;
        try {
            const parsed = JSON.parse(rawText);
            if (parsed && parsed.data) company = parsed.data;
            else if (parsed && parsed.company) company = parsed.company;
            else company = parsed;
        } catch (e) {
            console.debug('fetchCompany: response is not JSON for', companyId, 'status', res.status);
            console.debug('fetchCompany raw response snippet:', rawText && rawText.slice ? rawText.slice(0, 300) : rawText);
            if (!res.ok) return null;
        }
        if (company && company.company) company = company.company;
        console.debug('fetchCompany result for', companyId, company);
        if (company) {
            let logoCandidate = company.logo || company.avatar || company.logo_url || company.image || '';
            let normalized = '';
            try {
                if (logoCandidate) {
                    const raw = String(logoCandidate).trim();
                    if (/^https?:\/\//i.test(raw) || raw.startsWith('data:')) {
                        normalized = raw;
                    } else if (raw.startsWith('/')) {
                        normalized = window.location.origin + raw;
                    } else if (raw.startsWith('storage/') || raw.startsWith('uploads/') ) {
                        normalized = window.location.origin + '/' + raw.replace(/^\/+/, '');
                    } else if (raw.length > 0) {
                        normalized = window.location.origin + '/storage/' + raw.replace(/^\/+/, '');
                    }
                }
            } catch (e) { normalized = '' }
            let favicon = '';
            try {
                const website = company.website || company.website_url || company.site;
                if (website) {
                    const u = new URL(website, window.location.origin);
                    favicon = `https://logo.clearbit.com/${u.hostname}`;
                }
            } catch (e) { favicon = ''; }

            company._logo_url = normalized || '';
            company._favicon = favicon;
        }
        if (company) companyCache.set(key, company);
        return company;
    } catch (e) {
        console.warn('Failed to fetch company', companyId, e);
        return null;
    }
}

function createJobElement(job) {
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

    const savedClass = favoriteJobIdSet.has(Number(job.id)) ? 'active' : '';
    const savedIcon = favoriteJobIdSet.has(Number(job.id)) ? 'fas' : 'far';
    const companyName = escapeHtml(job.companyName || job.company_name || 'Công ty không xác định');
    let salaryText = job.salary || job.salary_range || 'Thỏa thuận';
    if (salaryText.length > 25) {
        salaryText = salaryText.substring(0, 22) + '...';
    }
    salaryText = escapeHtml(salaryText);
    const deadlineText = formatDateToDMY(job.deadline || job.expiration_date || job.expiry || '');
    const companyId = job.company_id || job.companyId || (job.company && job.company.id) || null;
    let logoRaw = job.logo || job.companyLogo || job.company_logo || null;
    let logoUrl = '/images/company-placeholder.svg';

    if (logoRaw && String(logoRaw).trim() !== '') {
        try {
            const raw = String(logoRaw).trim();
            if (/^https?:\/\//i.test(raw) || raw.startsWith('data:')) {
                logoUrl = raw;
            } else if (raw.startsWith('/')) {
                logoUrl = window.location.origin + raw;
            } else {
                logoUrl = window.location.origin + '/storage/' + raw.replace(/^\/+/, '');
            }
        } catch (e) { logoUrl = '/images/company-placeholder.svg'; }
    } else if (companyId && companyCache.has(String(companyId))) {
        const c = companyCache.get(String(companyId));
        if (c) {
            if (c._logo_url && String(c._logo_url).trim() !== '') logoUrl = c._logo_url;
            else if (c.logo || c.avatar || c.logo_url) {
                const raw = String(c.logo || c.avatar || c.logo_url).trim();
                if (/^https?:\/\//i.test(raw)) logoUrl = raw;
                else if (raw.startsWith('/')) logoUrl = window.location.origin + raw;
                else logoUrl = window.location.origin + '/storage/' + raw.replace(/^\/+/, '');
            } else if (c._favicon) {
                logoUrl = c._favicon;
            } else if (c.website) {
                try { const u = new URL(c.website); logoUrl = `https://logo.clearbit.com/${u.hostname}`; } catch(e){}
            }
        }
    } else if (job.company && job.company.website) {
        try { const u = new URL(job.company.website); logoUrl = `https://logo.clearbit.com/${u.hostname}`; } catch(e){}
    }

    const location = job.location || job.work_location || '';

    const fallbackUrls = [];
    if (companyId && companyCache.has(String(companyId))) {
        const cc = companyCache.get(String(companyId));
        if (cc && cc._logo_url) fallbackUrls.push(cc._logo_url);
        if (cc && cc._favicon) fallbackUrls.push(cc._favicon);
        if (cc && cc.website) {
            try { const uu = new URL(cc.website); fallbackUrls.push(`https://logo.clearbit.com/${uu.hostname}`); } catch(e){}
        }
    }
    if (logoRaw) {
        try {
            if (/^https?:\/\//i.test(logoRaw) || logoRaw.startsWith('data:')) fallbackUrls.unshift(logoRaw);
            else if (logoRaw.startsWith('/')) fallbackUrls.unshift(window.location.origin + logoRaw);
            else fallbackUrls.unshift(window.location.origin + '/storage/' + logoRaw.replace(/^\/+/, ''));
        } catch(e){}
    }
    try {
        const sanitized = encodeURIComponent(companyName.replace(/\s+/g, ''));
        fallbackUrls.push(`https://logo.clearbit.com/${sanitized}.com`);
    } catch(e){}
    if (companyId && companyCache.has(String(companyId))) {
        const cc = companyCache.get(String(companyId));
        if (cc && cc.website) {
            try { const uu = new URL(cc.website); fallbackUrls.push(`https://www.google.com/s2/favicons?domain=${uu.hostname}&sz=64`); } catch(e){}
        }
    }
    fallbackUrls.push('/images/company-placeholder.svg');
    const fallbackJson = JSON.stringify(fallbackUrls);
    const onerrorScript = `(function(img){ try{ img._fallbacks = ${fallbackJson}; img._idx = img._idx || 0; img._idx++; var next = img._fallbacks[img._idx]; console.debug('img onerror, trying fallback', img._idx, next, 'for', img.alt); if(next){ img.src = next; } else { console.debug('no more fallbacks, using placeholder for', img.alt); img.src='/images/company-placeholder.svg'; } }catch(e){ console.debug('onerror handler exception', e); img.src='/images/company-placeholder.svg'; } })(this)`;
    try{ console.debug('Rendering job', job.id, 'companyId', companyId, 'primary', logoUrl, 'fallbacks', fallbackUrls); }catch(e){}
    return `
        <div class="job-item-container">
            <div class="job-item" data-job-id="${job.id}">
                <div class="job-header">
                <div class="job-logo-wrap">
                    <img src="${logoUrl}" alt="${escapeHtml(companyName)}" data-website="${escapeHtml((job.website || (companyId && companyCache.has(String(companyId)) ? (companyCache.get(String(companyId)).website || companyCache.get(String(companyId)).website_url) : (job.company && job.company.website)) || ''))}" onerror="${onerrorScript}" onload="console.debug('Logo loaded for', this.alt, 'src=', this.src)">
                </div>
                <div class="job-info">
                    <h4 class="job-title"><a href="/jobs/${job.id}" class="job-title-link" onclick="event.stopPropagation();">${escapeHtml(job.title || '')}</a></h4>
                    <div class="job-company">${escapeHtml(companyName)}</div>
                    <div class="job-meta">
                        <span><i class="fas fa-map-marker-alt"></i> ${escapeHtml(location)}</span>
                        <span><i class="fas fa-calendar"></i> Hạn nộp: ${escapeHtml(deadlineText)}</span>
                    </div>
                </div>
            </div>
            
            <div class="job-actions">
                <div class="job-salary">${escapeHtml(salaryText)}</div>
                <button class="btn-save ${savedClass}" data-job-id="${Number(job.id)}" onclick="toggleFavorite(${Number(job.id)}, this)" title="Lưu việc làm">
                    <i class="${savedIcon} fa-heart"></i>
                </button>
            </div>
        </div>
            <div class="job-detail-popup">
                <h3>${escapeHtml(job.title || '')}</h3>
                <p><strong>Công ty:</strong> ${escapeHtml(job.companyName || job.company_name || '')}</p>
                <p><strong>Lương:</strong> ${escapeHtml(job.salary || job.salary_range || '')}</p>
                <p><strong>Địa điểm:</strong> ${escapeHtml(location)}</p>
                <p><strong>Hạn nộp:</strong> ${escapeHtml(deadlineText)}</p>
                <div><strong>Mô tả:</strong> ${escapeHtml(job.description || job.short_description || '')}</div>
                <div style="margin-top:12px; text-align:right;">
                    ${appliedJobIdSet.has(Number(job.id)) ?
                        `<a href="/jobs/${job.id}" class="btn-apply btn-apply-again" onclick="event.stopPropagation();"><i class="fas fa-sync-alt fa-spin" style="margin-right:8px"></i>Ứng tuyển lại</a>`
                        : `<a href="/jobs/${job.id}" class="btn-apply" onclick="event.stopPropagation();">Ứng tuyển</a>`
                    }
                </div>
            </div>
        </div>
    `;
}
function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Helper: return Authorization header value if authToken stored in localStorage
function getAuthHeader() {
    try {
        const token = localStorage.getItem('authToken') || localStorage.getItem('token') || null;
        if (token) return `Bearer ${token}`;
    } catch (e) {}
    return '';
}

function showEmptyState() {
    const container = document.getElementById('jobs-list');
    container.innerHTML = `
        <div class="empty-state">
            <i class="fas fa-search"></i>
            <h3>Không tìm thấy việc làm</h3>
            <p>Hiện tại chưa có việc làm nào trong danh mục ${CATEGORY}</p>
            <a href="/job-categories" class="btn btn-primary">Xem danh mục khác</a>
        </div>
    `;
}

function updateJobsCount() {
    const countElement = document.getElementById('jobs-count');
    countElement.textContent = `Tìm thấy ${filteredJobs.length} việc làm trong ${CATEGORY}`;
}

function updatePagination() {
    const jobsPerPage = 12;
    totalPages = Math.ceil(filteredJobs.length / jobsPerPage);
    const pagination = document.getElementById('pagination');
    
    if (totalPages <= 1) {
        pagination.innerHTML = '';
        return;
    }
    
    let paginationHTML = '';
    
    if (currentPage > 1) {
        paginationHTML += `<button class="page-btn" onclick="goToPage(${currentPage - 1})">
            <i class="fas fa-chevron-left"></i>
        </button>`;
    }
    
    for (let i = 1; i <= totalPages; i++) {
        if (i === currentPage) {
            paginationHTML += `<button class="page-btn active">${i}</button>`;
        } else if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
            paginationHTML += `<button class="page-btn" onclick="goToPage(${i})">${i}</button>`;
        } else if (i === currentPage - 3 || i === currentPage + 3) {
            paginationHTML += `<span class="page-ellipsis">...</span>`;
        }
    }
    
    if (currentPage < totalPages) {
        paginationHTML += `<button class="page-btn" onclick="goToPage(${currentPage + 1})">
            <i class="fas fa-chevron-right"></i>
        </button>`;
    }
    
    pagination.innerHTML = paginationHTML;
}

function goToPage(page) {
    currentPage = page;
    displayJobs();
}

function toggleSalaryFilter() {
    const panel = document.getElementById('salary-filter-panel');
    const button = document.querySelector('.filter-toggle-btn');
    const chevron = document.getElementById('salary-chevron');
    
    if (panel.style.display === 'none' || panel.style.display === '') {
        panel.style.display = 'block';
        button.classList.add('active');
        chevron.style.transform = 'rotate(180deg)';
    } else {
        panel.style.display = 'none';
        button.classList.remove('active');
        chevron.style.transform = 'rotate(0deg)';
    }
}

function initSalarySlider() {
    const salarySlider = document.getElementById('salary-slider');
    const salaryInput = document.getElementById('salary-value');
    const progress = document.getElementById('salary-progress');
    
    function updateSlider() {
        const salaryVal = parseInt(salarySlider.value);
        
        const salaryPercent = (salaryVal / 50) * 100;
        progress.style.left = '0%';
        progress.style.width = salaryPercent + '%';
        
        salaryInput.value = salaryVal;
        
        updateSalaryFilterText(salaryVal);
    }
    
    function updateSliderFromInput() {
        const salaryVal = parseInt(salaryInput.value) || 0;
        
        if (salaryVal < 0) salaryInput.value = 0;
        if (salaryVal > 50) salaryInput.value = 50;
        
        salarySlider.value = salaryVal;
        updateSlider();
    }
    
    salarySlider.addEventListener('input', updateSlider);
    
    salaryInput.addEventListener('input', updateSliderFromInput);
    
    updateSlider();
}

function updateSalaryFilterText(salary) {
    const textElement = document.getElementById('salary-filter-text');
    if (salary === 0) {
        textElement.textContent = 'Mức lương';
    } else {
        textElement.textContent = `Trên ${salary} triệu`;
    }
}

function applySalaryFilter() {
    searchJobs();
    toggleSalaryFilter(); 
}

function clearSalaryFilter() {
    document.getElementById('salary-slider').value = 0;
    document.getElementById('salary-value').value = 0;
    
    updateSalaryFilterText(0);
    searchJobs();
}

function searchJobs() {
    const searchTerm = document.getElementById('search-input').value.toLowerCase();
    const location = document.getElementById('location-filter').value;
    
    filteredJobs = allJobs.filter(job => {
        const title = (job.title || '').toLowerCase();
        const description = (job.description || '').toLowerCase();
        const companyName = (job.companyName || '').toLowerCase();
        
        const matchesSearch = !searchTerm || 
            title.includes(searchTerm) || 
            description.includes(searchTerm) || 
            companyName.includes(searchTerm);
        
        const matchesLocation = !location || job.location === location;
        
        const matchesSalary = checkCustomSalaryMatch(job.salary);
        
        return matchesSearch && matchesLocation && matchesSalary;
    });
    
    currentPage = 1;
    displayJobs();
    updateJobsCount();
}

function checkCustomSalaryMatch(jobSalary) {
    if (!jobSalary || jobSalary === 'Thỏa thuận') {
        return false;
    }
    
    const desiredSalary = parseInt(document.getElementById('salary-value').value) || 0;
    
    if (desiredSalary === 0) {
        return true;
    }
    
    const salaryMatch = jobSalary.match(/(\d+)-(\d+)/);
    if (!salaryMatch) {
        const singleMatch = jobSalary.match(/(\d+)/);
        if (!singleMatch) return false;
        
        const jobSalaryValue = parseInt(singleMatch[1]);
        return jobSalaryValue >= desiredSalary;
    }
    
    const jobMinSalary = parseInt(salaryMatch[1]);
    const jobMaxSalary = parseInt(salaryMatch[2]);
    
    return jobMaxSalary >= desiredSalary;
}

function checkSalaryMatch(jobSalary, salaryRange) {
    if (!jobSalary || jobSalary === 'Thỏa thuận') {
        return salaryRange === '';
    }
    
    const salaryMatch = jobSalary.match(/(\d+)-(\d+)/);
    if (!salaryMatch) {
        const singleMatch = jobSalary.match(/(\d+)/);
        if (!singleMatch) return false;
        
        const salary = parseInt(singleMatch[1]);
        switch (salaryRange) {
            case '0-5': return salary <= 5;
            case '5-10': return salary >= 5 && salary <= 10;
            case '10-15': return salary >= 10 && salary <= 15;
            case '15-20': return salary >= 15 && salary <= 20;
            case '20+': return salary >= 20;
            default: return true;
        }
    }
    
    const minSalary = parseInt(salaryMatch[1]);
    const maxSalary = parseInt(salaryMatch[2]);
    
    switch (salaryRange) {
        case '0-5': 
            return maxSalary <= 5;
        case '5-10': 
            return minSalary <= 10 && maxSalary >= 5;
        case '10-15': 
            return minSalary <= 15 && maxSalary >= 10;
        case '15-20': 
            return minSalary <= 20 && maxSalary >= 15;
        case '20+': 
            return maxSalary >= 20;
        default: 
            return true;
    }
}

async function applyJob(jobId) {
    const currentUser = JSON.parse(localStorage.getItem('currentUser'));
    if (!currentUser) {
        alert('Vui lòng đăng nhập để ứng tuyển!');
        return;
    }
    try {
        // Use APIHelper if available to handle CSRF/auth consistently
        if (typeof window !== 'undefined' && window.APIHelper && typeof window.APIHelper.request === 'function') {
            try {
                await window.APIHelper.ensureCsrf();
            } catch (e) { /* ignore */ }
            await window.APIHelper.request('/applications', {
                method: 'POST',
                body: JSON.stringify({ job_id: jobId })
            });
            alert('Ứng tuyển thành công!');
            return;
        }

        // Fallback: plain fetch with credentials
        const response = await fetch(`${API_BASE_URL}/api/applications`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ job_id: jobId })
        });

        if (response.ok) {
            alert('Ứng tuyển thành công!');
        } else {
            const txt = await response.text().catch(() => null);
            console.error('Apply job failed', response.status, txt);
            alert('Có lỗi xảy ra khi ứng tuyển!');
        }
    } catch (error) {
        console.error('Error applying job:', error);
        alert('Có lỗi xảy ra khi ứng tuyển!');
    }
}

async function toggleFavorite(jobId, button) {
    const currentUser = JSON.parse(localStorage.getItem('currentUser'));
    if (!currentUser) {
        alert('Vui lòng đăng nhập để lưu việc làm!');
        return;
    }

    const isFavorited = favoriteJobIdSet.has(Number(jobId));

    try {
        if (isFavorited) {
            if (hasApiHelper) {
                try {
                    if (window.APIHelper && typeof window.APIHelper.ensureCsrf === 'function') {
                        await window.APIHelper.ensureCsrf();
                    }
                    const resp = await window.APIHelper.request(`/saved-jobs/${jobId}`, { method: 'DELETE' });
                    // success if no exception thrown
                    favoriteJobIdSet.delete(Number(jobId));
                    button.classList.remove('active');
                    button.querySelector('i').className = 'far fa-heart';
                } catch (e) {
                    console.warn('APIHelper delete saved job failed', e);
                    alert('Không thể bỏ lưu việc làm. Vui lòng thử lại.');
                }
            } else {
                // fallback to fetch
                const delResp = await fetch(`${API_BASE_URL}/api/saved-jobs/${jobId}`, {
                    method: 'DELETE',
                    credentials: 'include',
                    headers: { 'Accept': 'application/json', 'Authorization': getAuthHeader() }
                });
                if (delResp.ok) {
                    favoriteJobIdSet.delete(Number(jobId));
                    button.classList.remove('active');
                    button.querySelector('i').className = 'far fa-heart';
                } else {
                    const txt = await delResp.text().catch(() => null);
                    console.warn('Failed to delete saved job', delResp.status, txt);
                    alert('Không thể bỏ lưu việc làm. Vui lòng thử lại.');
                }
            }
        } else {
            if (hasApiHelper) {
                try {
                    if (window.APIHelper && typeof window.APIHelper.ensureCsrf === 'function') {
                        await window.APIHelper.ensureCsrf();
                    }
                    const resp = await window.APIHelper.request('/saved-jobs', { method: 'POST', body: JSON.stringify({ job_id: jobId }) });
                    // treat success if no exception thrown
                    favoriteJobIdSet.add(Number(jobId));
                    button.classList.add('active');
                    button.querySelector('i').className = 'fas fa-heart';
                } catch (e) {
                    console.warn('APIHelper create saved job failed', e);
                    alert('Không thể lưu việc làm. Vui lòng thử lại.');
                }
            } else {
                const postResp = await fetch(`${API_BASE_URL}/api/saved-jobs`, {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': getAuthHeader()
                    },
                    body: JSON.stringify({ job_id: jobId })
                });
                if (postResp.ok) {
                    favoriteJobIdSet.add(Number(jobId));
                    button.classList.add('active');
                    button.querySelector('i').className = 'fas fa-heart';
                } else {
                    const txt = await postResp.text().catch(() => null);
                    console.warn('Failed to save job', postResp.status, txt);
                    alert('Không thể lưu việc làm. Vui lòng thử lại.');
                }
            }
        }
    } catch (error) {
        console.error('Error toggling favorite:', error);
        alert('Có lỗi xảy ra!');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    if (window.CATEGORY) {
        CATEGORY = window.CATEGORY;
    }
    
    loadCategoryJobs();
    initSalarySlider();
    
    document.getElementById('search-input').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            searchJobs();
        }
    });
    
    document.getElementById('location-filter').addEventListener('change', searchJobs);
});