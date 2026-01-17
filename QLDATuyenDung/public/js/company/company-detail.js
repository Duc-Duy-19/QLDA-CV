/**
 * Company Detail Page JavaScript
 * Handles company information display and company jobs listing
 */

document.addEventListener('DOMContentLoaded', function() {
    // Get company ID safely from data-attribute instead of injecting Blade directly into JS
    const root = document.getElementById('company-detail-root');
    const rawId = root ? root.dataset.companyId : '';
    const companyId = rawId ? parseInt(rawId, 10) : null;

    if (!companyId) {
        root.innerHTML = '<div class="alert alert-danger">ID công ty không hợp lệ.</div>';
        return;
    }

    // Render skeleton while loading
    root.innerHTML = `
        <div class="skeleton-hero">
            <div class="skeleton-avatar skeleton"></div>
            <div style="flex:1">
                <div class="skeleton-rect skeleton" style="width:40%"></div>
                <div class="skeleton-rect skeleton" style="width:30%"></div>
            </div>
        </div>
        <div style="height:16px"></div>
        <div class="company-layout">
            <div class="company-main">
                <div class="card skeleton-card skeleton"></div>
            </div>
            <aside class="company-side">
                <div class="card skeleton-card skeleton"></div>
            </aside>
        </div>
    `;

    fetch(`/api/public/companies/${companyId}`)
        .then(resp => {
            if (!resp.ok) throw new Error('Không thể lấy dữ liệu công ty');
            return resp.json();
        })
        .then(data => {
            const company = data.company || data.data || data;
            root.innerHTML = `
            <div class="company-hero">
                <div class="company-logo"><img src="${company.logo || '/images/company-placeholder.svg'}" alt="logo"></div>
                <div class="hero-top" style="background-image:url('${company.cover_image || company.logo || ''}');"></div>
                <div class="hero-bottom">
                    <div class="hero-inner hero-container">
                        <div class="company-info-block">
                            <h1 class="company-name">${company.company_name || ''}</h1>
                            <div class="company-meta">
                                ${company.website ? `<span class="meta-item">🌐 <a href="${company.website}" target="_blank" style="color:inherit;opacity:0.95;text-decoration:underline">${company.website}</a></span>` : ''}
                                ${company.employee_count ? `<span class="meta-item">👥 ${company.employee_count} nhân viên</span>` : ''}
                                <span class="meta-item">⭐ ${company.followers || 0} theo dõi</span>
                            </div>
                        </div>
                        <div class="company-action">
                            <button class="btn-follow">+ Theo dõi công ty</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="container company-layout">
                <div class="company-main">
                    <div class="card">
                        <h3>Giới thiệu công ty</h3>
                        <div style="white-space:pre-wrap;color:#333">${company.description || 'Chưa có mô tả'}</div>
                    </div>
                    <div style="height:16px"></div>

                    <!-- Company jobs list (fetched from public jobs API filtered by company_id) -->
                    <div class="card" id="company-jobs-card">
                        <h3>Các việc làm tại công ty</h3>
                        <div id="company-jobs-container">
                            <div id="company-jobs-list" class="jobs-grid"></div>
                            <div id="company-jobs-loading" style="padding:16px;color:#666">Đang tải việc làm...</div>
                        </div>
                    </div>
                    <div style="height:16px"></div>
                </div>
                <aside class="company-side">
                    <div class="card">
                        <h4>Thông tin liên hệ</h4>
                        <div class="contact-item"><div>📍</div><div><strong>Địa chỉ</strong><div>${company.address || 'Chưa có địa chỉ'}</div></div></div>
                        <div class="contact-item"><div>✉️</div><div><strong>Email</strong><div>${company.email || '---'}</div></div></div>
                        <div class="contact-item"><div>📞</div><div><strong>Phone</strong><div>${company.phone || '---'}</div></div></div>
                        ${company.website ? `<div class="contact-item"><div>🔗</div><div><strong>Website</strong><div><a href="${company.website}" target="_blank">${company.website}</a></div></div></div>` : ''}
                    </div>
                </aside>
            </div>
        `;

            // Update breadcrumb with company name and reveal content
            const breadcrumb = document.getElementById('company-breadcrumb');
            if (breadcrumb) {
                breadcrumb.innerHTML = `Danh sách Công ty &nbsp;›&nbsp; Thông tin&nbsp; <strong>${company.company_name || ''}</strong>`;
            }

            // After inserting markup, reveal image with fade-in
            setTimeout(() => {
                const img = root.querySelector('.company-logo img');
                if (img) {
                    // If image already cached, add class immediately
                    if (img.complete) img.classList.add('revealed');
                    else img.addEventListener('load', () => img.classList.add('revealed'));
                }

                // Small interaction for follow button (visual only)
                const btn = root.querySelector('.btn-follow');
                if (btn) {
                    btn.addEventListener('click', (e) => {
                        btn.textContent = 'Đã theo dõi';
                        btn.disabled = true;
                        btn.style.opacity = 0.95;
                    });
                }
            }, 30);

            // Load company jobs using public jobs API (filtered by company_id)
            loadCompanyJobs(company);
        })
        .catch(err => {
            console.error(err);
            root.innerHTML = '<div class="alert alert-danger">Không thể tải thông tin công ty.</div>';
        });
});

/**
 * Load and display jobs for the current company
 */
async function loadCompanyJobs(company) {
    try {
        const jobsListEl = document.getElementById('company-jobs-list');
        const loadingEl = document.getElementById('company-jobs-loading');
        if (!jobsListEl) return;

        // Preload saved job IDs for current user
        let savedJobIds = new Set();
        
        async function preloadSavedJobs() {
            try {
                const currentUser = JSON.parse(localStorage.getItem('currentUser') || 'null');
                if (!currentUser) return;
                
                const token = localStorage.getItem('authToken');
                if (!token) return;

                const resp = await fetch('/api/saved-jobs', {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                
                if (!resp.ok) return;
                const data = await resp.json();
                const list = data?.data || [];
                list.forEach(item => {
                    const jobId = Number(item.job_id || item.jobId || (item.job && item.job.id));
                    if (jobId) savedJobIds.add(jobId);
                });
            } catch (e) {
                console.warn('Failed to preload saved jobs', e);
            }
        }

        await preloadSavedJobs();

        /**
         * Toggle save/favorite job
         */
        window.toggleSaveJob = async function(jobId, button) {
            const currentUser = JSON.parse(localStorage.getItem('currentUser') || 'null');
            if (!currentUser) {
                alert('Vui lòng đăng nhập để lưu việc làm yêu thích');
                window.location.href = '/login';
                return;
            }

            const token = localStorage.getItem('authToken');
            if (!token) {
                alert('Vui lòng đăng nhập để lưu việc làm yêu thích');
                window.location.href = '/login';
                return;
            }

            const isSaved = savedJobIds.has(Number(jobId));
            const icon = button.querySelector('i');

            try {
                if (isSaved) {
                    // Unsave
                    const resp = await fetch(`/api/saved-jobs/${jobId}`, {
                        method: 'DELETE',
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Accept': 'application/json'
                        }
                    });

                    if (resp.ok) {
                        savedJobIds.delete(Number(jobId));
                        button.classList.remove('active');
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                    } else {
                        throw new Error('Failed to unsave job');
                    }
                } else {
                    // Save
                    const resp = await fetch('/api/saved-jobs', {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ job_id: jobId })
                    });

                    if (resp.ok) {
                        savedJobIds.add(Number(jobId));
                        button.classList.add('active');
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                    } else {
                        throw new Error('Failed to save job');
                    }
                }
            } catch (e) {
                console.error('Toggle save job error:', e);
                alert('Có lỗi xảy ra, vui lòng thử lại');
            }
        };

        /**
         * Create job card element
         */
        function createJobElement(job) {
            const div = document.createElement('div');
            div.className = 'job-item';
            div.setAttribute('data-job-id', job.id);

            const companyName = (job && job.company && (job.company.company_name || job.company.name)) 
                ? (job.company.company_name || job.company.name) 
                : (job.company_name || company.company_name || 'Công ty không xác định');
            const salaryText = job.salary_range || job.salary || '';
            const deadlineText = formatDateToDMY(job.expiration_date || job.deadline || job.posted_date || '');
            
            // Use company logo from the page context since all jobs are from the same company
            const logoUrl = normalizeLogoUrl(company.logo) 
                || normalizeLogoUrl((job.company && (job.company.logo || job.company.avatar)) || job.company_logo || job.logo);

            const isSaved = savedJobIds.has(Number(job.id));
            const savedClass = isSaved ? 'active' : '';
            const savedIcon = isSaved ? 'fas' : 'far';

            div.innerHTML = `
                <div class="job-header">
                    <div class="job-logo-wrap">
                        <img src="${logoUrl}" alt="${escapeHtml(companyName)}" onerror="this.onerror=null;this.src='/images/company-placeholder.svg'" loading="lazy">
                    </div>
                    <div style="flex:1">
                        <h4 class="job-title"><a href="/jobs/${job.id}" class="job-title-link" onclick="event.stopPropagation();">${escapeHtml(job.title || '')}</a></h4>
                        <div style="color:#6b7280;margin-top:6px;font-size:13px"><i class="fas fa-building"></i> ${escapeHtml(companyName)}</div>
                        <div class="job-meta-row" style="margin-top:8px">
                            <div class="meta-location"><i class="fas fa-map-marker-alt"></i> <span class="meta-text">${escapeHtml(job.location || '')}</span></div>
                            <div class="meta-deadline"><i class="fas fa-calendar"></i> <span>Hạn nộp: ${escapeHtml(deadlineText)}</span></div>
                        </div>
                    </div>
                </div>
                <div class="job-actions" style="display:flex;align-items:center;justify-content:space-between;margin-top:10px">
                    <div class="job-salary">${escapeHtml(salaryText)}</div>
                    <div style="display:flex;align-items:center;gap:8px">
                        <button class="btn-save ${savedClass}" data-job-id="${job.id}" onclick="toggleSaveJob(${job.id}, this)" title="Lưu việc làm">
                            <i class="${savedIcon} fa-heart"></i>
                        </button>
                    </div>
                </div>
            `;

            return div;
        }

        // Fetch jobs from API
        const url = `/api/public/jobs?company_id=${company.id}&per_page=12`;
        const resp = await fetch(url);
        if (!resp.ok) throw new Error('Không thể tải danh sách việc làm');
        const json = await resp.json().catch(() => null);
        const list = (json && json.data) ? json.data : json;
        const jobs = Array.isArray(list) ? list : (Array.isArray(list?.data) ? list.data : []);

        if (loadingEl) loadingEl.style.display = 'none';
        if (!jobs || jobs.length === 0) {
            jobsListEl.innerHTML = '<div style="padding:16px;color:#666">Chưa có việc làm nào từ công ty này.</div>';
            return;
        }

        jobsListEl.innerHTML = '';
        jobs.forEach(j => jobsListEl.appendChild(createJobElement(j)));
    } catch (e) {
        console.error(e);
        const loadingEl = document.getElementById('company-jobs-loading');
        if (loadingEl) loadingEl.textContent = 'Không thể tải việc làm.';
    }
}

/**
 * Utility Functions
 */

function escapeHtml(str) {
    return String(str || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

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
    } catch (e) { 
        return placeholder; 
    }
}

function formatDateToDMY(value) {
    if (!value) return '';
    let d;
    try { 
        d = new Date(value); 
        if (isNaN(d.getTime())) { 
            const m = String(value).match(/(\d{4}-\d{2}-\d{2})/); 
            if (m) d = new Date(m[1] + 'T00:00:00'); 
        } 
    } catch (e) { 
        return ''; 
    }
    if (!d || isNaN(d.getTime())) return '';
    const dd = String(d.getDate()).padStart(2, '0');
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const yyyy = d.getFullYear();
    return `${dd}-${mm}-${yyyy}`;
}
