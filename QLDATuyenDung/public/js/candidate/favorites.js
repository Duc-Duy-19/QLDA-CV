// Legacy mock json-server base (disabled to avoid contacting localhost:3000)
const API_BASE_URL_JSON = '';
// Backwards-compatible alias (keep empty so accidental direct fetches don't hit localhost)
const API_BASE_URL = API_BASE_URL_JSON;
let favoriteJobs = [];
let favoriteCompanies = [];
let appliedJobIdSet = new Set();

// Small helper to prefer the Laravel API (window.APIHelper) when available,
// otherwise fall back to the JSON-server endpoints.
const hasApiHelper = typeof window !== 'undefined' && window.APIHelper && typeof window.APIHelper.request === 'function';

async function apiGet(path) {
    if (hasApiHelper) {
        // APIHelper.request expects an endpoint relative to /api, e.g. '/saved-jobs'
        return window.APIHelper.request(path);
    }

    // Fallback: call Laravel API directly (same-origin) instead of json-server.
    const url = `${window.location.origin}/api${path}`;
    const headers = { 'Accept': 'application/json' };
    const res = await fetch(url, { headers, credentials: 'include' });
    const contentType = res.headers.get('content-type') || '';
    if (contentType.includes('application/json')) {
        const data = await res.json();
        if (!res.ok) throw new Error(data?.message || `HTTP ${res.status}`);
        return data;
    }
    const text = await res.text();
    if (!res.ok) throw new Error(text || `HTTP ${res.status}`);
    return text;
}

async function apiPost(path, body) {
    if (hasApiHelper) {
        await window.APIHelper.ensureCsrf();
        return window.APIHelper.request(path, { method: 'POST', body: JSON.stringify(body) });
    }

    // Fallback: call Laravel API directly
    // Ensure CSRF cookie is present for Sanctum if used
    try { await fetch(window.location.origin + '/sanctum/csrf-cookie', { credentials: 'include' }); } catch (e) { /* ignore */ }
    const url = `${window.location.origin}/api${path}`;
    const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        credentials: 'include',
        body: JSON.stringify(body)
    });
    const data = await res.json().catch(() => null);
    if (!res.ok) throw new Error(data?.message || `HTTP ${res.status}`);
    return data;
}

async function apiDelete(path) {
    if (hasApiHelper) {
        await window.APIHelper.ensureCsrf();
        return window.APIHelper.request(path, { method: 'DELETE' });
    }

    // Fallback: call Laravel API directly
    try { await fetch(window.location.origin + '/sanctum/csrf-cookie', { credentials: 'include' }); } catch (e) { /* ignore */ }
    const url = `${window.location.origin}/api${path}`;
    const res = await fetch(url, { method: 'DELETE', credentials: 'include', headers: { 'Accept': 'application/json' } });
    const data = await res.json().catch(() => null);
    if (!res.ok) throw new Error(data?.message || `HTTP ${res.status}`);
    return data;
}

// Load favorites and enrich with job + company data
async function loadFavorites() {
    try {
        const currentUser = JSON.parse(localStorage.getItem('currentUser'));
        if (!currentUser) {
            window.location.href = '/login';
            return;
        }

        // preload applied job ids so we can show 'Ứng tuyển lại' where appropriate
        try {
            const respApps = await (hasApiHelper ? window.APIHelper.request('/applications') : apiGet('/applications'));
            const apps = respApps?.data || respApps || [];
            appliedJobIdSet = new Set((apps || []).map(a => Number(a.job_id || a.jobId || (a.job && a.job.id) || 0)).filter(Boolean));
        } catch (e) {
            console.warn('Could not preload applied job ids', e);
            appliedJobIdSet = new Set();
        }

        // Load favorite jobs (basic list) via backend API
        let favs = [];
        try {
            const resp = await apiGet('/saved-jobs');
            // SavedJobController returns a paginator -> { data: [...] }
            favs = resp?.data || resp || [];
        } catch (e) {
            console.warn('Không lấy được danh sách yêu thích từ server:', e);
            favs = [];
        }

        // Enrich each favorite with job details (use backend public job endpoints when possible)
        favoriteJobs = await Promise.all(favs.map(async (f) => {
            // SavedJob API may already include job via relation `job`
            const jobFromSaved = f.job || f.job_id || f.jobId || null;
            let job = null;
            try {
                if (jobFromSaved && typeof jobFromSaved === 'object') {
                    job = jobFromSaved;
                } else {
                    const jobId = jobFromSaved || f.jobId || f.job_id;
                    if (jobId) {
                        // Prefer backend public job endpoint
                        try {
                            job = await apiGet(`/public/jobs/${jobId}`);
                        } catch (err) {
                            console.warn('Không lấy được job từ API:', err);
                            job = null;
                        }
                    }
                }
            } catch (err) { console.warn('Không lấy được job:', err); }

            const companyId = job?.company_id || job?.companyId || job?.company || null;
            const companyName = companyId ? await getCompanyName(companyId) : 'Công ty không xác định';

            // Normalize tags/requirements into an array (backend may store as JSON array or comma-separated string)
            let rawReq = job?.requirements;
            let tagsArray = [];
            if (Array.isArray(rawReq)) {
                tagsArray = rawReq;
            } else if (typeof rawReq === 'string') {
                const trimmed = rawReq.trim();
                // Try JSON parse first (in case it's stored as JSON string)
                if ((trimmed.startsWith('[') && trimmed.endsWith(']')) || (trimmed.startsWith('{') && trimmed.endsWith('}'))) {
                    try { tagsArray = JSON.parse(trimmed); } catch (e) { tagsArray = [trimmed]; }
                } else {
                    // Fallback: split by comma
                    tagsArray = trimmed.length ? trimmed.split(',').map(s => s.trim()).filter(Boolean) : [];
                }
            } else {
                tagsArray = [];
            }

            return {
                id: f.id || f._id || null,
                job_id: job?.id || f.jobId || f.job_id || null,
                title: job?.title || 'Vị trí chưa xác định',
                company: companyName,
                location: job?.location || 'Chưa cập nhật',
                salary: job?.salary || job?.salary_range || 'Thỏa thuận',
                description: job?.description || '',
                tags: tagsArray,
                favoriteDate: f.saved_at || f.savedAt || f.savedAt || f.created_at || null
            };
        }));
        

        // Load favorite companies (if backend supports it). If not, keep empty.
        try {
            const companiesResp = await apiGet('/favorite-companies');
            favoriteCompanies = companiesResp?.data || companiesResp || [];
        } catch (e) {
            // Fallback: leave empty and continue silently
            favoriteCompanies = [];
            console.warn('Không lấy được danh sách công ty yêu thích (fallback):', e);
        }
        renderJobs();
        renderCompanies();
    } catch (error) {
        console.error('Lỗi khi tải yêu thích:', error);
    }
}

async function getCompanyName(companyId) {
    try {
        // Prefer backend public company endpoint
        try {
            const company = await apiGet(`/public/companies/${companyId}`);
            return company?.name || company?.companyName || '';
        } catch (e) {
            // Fallback: try users? query (some legacy setups stored companies in users)
            try {
                const list = await apiGet(`/users?id=${companyId}`);
                const arr = list?.data || list || [];
                return arr[0]?.companyName || '';
            } catch (_) { return ''; }
        }
    } catch { return ''; }
}

// Switch tabs
function switchTab(tabName) {
    // Update tab buttons
    document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
    event.target.classList.add('active');

    // Update tab content
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    document.getElementById(`${tabName}-tab`).classList.add('active');
}

// Render favorite jobs
function renderJobs() {
    const container = document.getElementById('jobs-list');
    
    if (favoriteJobs.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i>❤️</i>
                <h3>Không tìm thấy việc làm yêu thích nào</h3>
                <p>Hãy thử thay đổi bộ lọc hoặc lưu thêm việc làm mới!</p>
            </div>
        `;
        return;
    }

    container.innerHTML = favoriteJobs.map(job => `
        <div class="favorite-card">
            <div class="card-header">
                    <div class="card-top">
                        <div class="card-top-left">
                            <h3 class="job-title">${job.title}</h3>
                            <div class="job-sub">${job.company} • ${job.location}</div>
                        </div>
                        <div class="card-top-right">${job.salary || 'Thỏa thuận'}</div>
                    </div>
            </div>
            <div class="card-body">
                <p class="job-description">${job.description}</p>
                <div class="job-tags">
                    ${Array.isArray(job.tags) ? job.tags.map(tag => `<span class="tag">${tag}</span>`).join('') : ''}
                </div>
            </div>
            <div class="card-footer">
                <span class="favorite-date">Đã lưu: ${new Date(job.favoriteDate).toLocaleDateString('vi-VN')}</span>
                <div class="card-actions">
                    ${appliedJobIdSet.has(Number(job.job_id)) ? `<a href="${job.job_id ? `/jobs/${job.job_id}` : '/candidate/job-search'}" class="btn btn-primary"><i class="fas fa-sync-alt fa-spin" style="margin-right:6px"></i>Ứng tuyển lại</a>` : `<a href="${job.job_id ? `/jobs/${job.job_id}` : '/candidate/job-search'}" class="btn btn-primary">Ứng tuyển</a>`}
                    <button class="btn btn-danger" onclick="removeFavoriteJob(${job.id}, ${job.job_id})">Bỏ lưu</button>
                </div>
            </div>
        </div>
    `).join('');
}

// Render favorite companies
function renderCompanies() {
    const container = document.getElementById('companies-list');
    
    if (favoriteCompanies.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i>🏢</i>
                <h3>Không tìm thấy công ty nào</h3>
                <p>Hãy thử thay đổi bộ lọc hoặc theo dõi thêm công ty mới!</p>
            </div>
        `;
        return;
    }

    container.innerHTML = favoriteCompanies.map(company => `
        <div class="company-card">
            <div class="company-logo">${company.name.charAt(0)}</div>
            <h3 class="company-name">${company.name}</h3>
            <p class="company-info">${company.industry} • ${company.location}</p>
            <div class="company-stats">
                <div class="company-stat">
                    <div class="company-stat-number">${company.jobCount || 0}</div>
                    <div class="company-stat-label">Việc làm</div>
                </div>
                <div class="company-stat">
                    <div class="company-stat-number">${company.employeeCount || 0}</div>
                    <div class="company-stat-label">Nhân viên</div>
                </div>
            </div>
            <div class="card-actions">
                <a href="#" class="btn btn-primary" onclick="viewCompanyJobs(${company.id})">Xem việc làm</a>
                <button class="btn btn-danger" onclick="unfollowCompany(${company.id})">Bỏ theo dõi</button>
            </div>
        </div>
    `).join('');
}

// Apply for job -> lưu vào theo dõi ứng tuyển (applications)
async function applyJob(jobId) {
    const currentUser = JSON.parse(localStorage.getItem('currentUser'));
    if (!currentUser) {
        alert('Vui lòng đăng nhập để ứng tuyển!');
        return;
    }

    if (!confirm('Bạn có muốn ứng tuyển việc làm này?')) return;

        try {
            // kiểm tra trùng via backend
            try {
                const existsResp = await apiGet(`/applications?jobId=${jobId}&candidateId=${currentUser.id}`);
                const exists = existsResp?.data || existsResp || [];
                if (exists && exists.length > 0) {
                    alert('Bạn đã ứng tuyển công việc này trước đó.');
                    return;
                }
            } catch (e) { /* ignore check errors, continue to attempt apply */ }

            // Submit application using backend expected field names
            await apiPost('/applications', {
                job_id: jobId
            });

            alert('Đã ứng tuyển thành công.');
    } catch (e) {
        console.error('Error applying job:', e);
        alert('Có lỗi xảy ra khi ứng tuyển.');
    }
}

// Remove favorite job
async function removeFavoriteJob(favoriteId, jobId) {
    if (confirm('Bạn có chắc chắn muốn bỏ lưu việc làm này?')) {
        try {
            let deleted = false;
            try {
                // Prefer backend delete by job id (SavedJobController)
                await apiDelete(`/saved-jobs/${jobId}`);
                deleted = true;
            } catch (inner) {
                console.warn('Primary delete via API failed:', inner);
            }

            if (!deleted) {
                // Fallback: xóa theo userId + jobId
                const currentUser = JSON.parse(localStorage.getItem('currentUser') || 'null');
                if (currentUser && jobId != null) {
                    if (hasApiHelper) {
                        // Try finding saved record(s) via /saved-jobs and delete by job id (delete endpoint uses jobId)
                        try {
                            await apiDelete(`/saved-jobs/${jobId}`);
                            deleted = true;
                        } catch (e) { console.warn('Fallback delete via API failed', e); }
                    } else {
                        // Try finding saved record(s) via backend and delete each by id
                        try {
                            let list = await apiGet(`/saved-jobs?userId=${currentUser.id}&jobId=${jobId}`);
                            list = list?.data || list || [];
                            await Promise.all(list.map(item => apiDelete(`/saved-jobs/${item.id}`)));
                            deleted = list.length > 0;
                        } catch (e) {
                            console.warn('Fallback delete via API failed', e);
                        }
                    }
                }
            }

            if (!deleted) {
                alert('Không thể bỏ lưu việc làm. Vui lòng thử lại.');
                return;
            }

            // Làm mới dữ liệu từ server để đảm bảo đồng bộ
            await loadFavorites();

            // Cập nhật badge góc và phát tín hiệu đồng bộ
            try {
                const currentUser = JSON.parse(localStorage.getItem('currentUser') || 'null');
                if (currentUser) {
                    let list = [];
                    if (hasApiHelper) {
                        const resp = await apiGet('/saved-jobs');
                        list = resp?.data || resp || [];
                    } else {
                        const resp = await apiGet(`/saved-jobs?userId=${currentUser.id}`);
                        list = resp?.data || resp || [];
                    }
                    const btn = parent.window?.document?.getElementById('favorite-btn');
                    if (btn) btn.querySelector('.fab-count').textContent = list.length;
                    localStorage.setItem('favorite:updated', String(Date.now()));
                }
            } catch (e) { console.warn('Không cập nhật được badge yêu thích', e); }

            alert('Đã bỏ lưu việc làm thành công!');
        } catch (error) {
            console.error('Lỗi khi bỏ lưu việc làm:', error);
            alert('Có lỗi xảy ra khi bỏ lưu việc làm!');
        }
    }
}

// View company jobs
function viewCompanyJobs(companyId) {
    window.location.href = `/candidate/job-search?company=${companyId}`;
}

// Unfollow company
async function unfollowCompany(companyId) {
    if (confirm('Bạn có chắc chắn muốn bỏ theo dõi công ty này?')) {
        try {
                // Prefer backend endpoint to unfollow company
                try {
                    await apiDelete(`/favorite-companies/${companyId}`);
                } catch (e) {
                    // Best-effort: try another common pattern
                    try { await apiDelete(`/companies/${companyId}/follow`); } catch (_) { /* ignore */ }
                }
            
            favoriteCompanies = favoriteCompanies.filter(company => company.id !== companyId);
            renderCompanies();
            
            alert('Đã bỏ theo dõi công ty thành công!');
        } catch (error) {
            console.error('Lỗi khi bỏ theo dõi công ty:', error);
            alert('Có lỗi xảy ra khi bỏ theo dõi công ty!');
        }
    }
}

// Initialize page and render immediately
document.addEventListener('DOMContentLoaded', async function() {
    await loadFavorites();
    renderJobs();
    renderCompanies();

    // Lắng nghe tín hiệu cập nhật từ trang khác
    window.addEventListener('storage', async (evt) => {
        if (evt.key === 'favorite:updated') {
            await loadFavorites();
        }
    });
});