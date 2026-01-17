/**
 * Job Detail Page JavaScript
 * Handles job detail display, company information, and job application
 */

const apiBase = '/api';

function formatDate(d) {
    if (!d) return '';
    try {
        const dt = new Date(d);
        return dt.toLocaleDateString('vi-VN');
    } catch (e) {
        return d;
    }
}

function safeHTML(text) {
    if (!text) return '';
    return String(text).replace(/\n/g, '<br/>');
}

async function loadJob(jobId) {
    try {
        console.log('Fetching job with ID:', jobId);
        const res = await fetch(`${apiBase}/public/jobs/${jobId}`);
        console.log('Response status:', res.status);
        if (!res.ok) throw new Error('Không thể tải dữ liệu việc làm - Status: ' + res.status);
        const job = await res.json();
        console.log('Job data received:', job);

        const jobTitleEl = document.getElementById('job-title');
        if (jobTitleEl) jobTitleEl.textContent = job.title || 'N/A';

        const breadcrumbEl = document.getElementById('breadcrumb-title');
        if (breadcrumbEl) breadcrumbEl.textContent = job.title || 'Chi tiết';

        const companyName = (job.company && job.company.company_name) || job.company_name || '';
        const jobCompanySubEl = document.getElementById('job-company-sub');
        if (jobCompanySubEl) jobCompanySubEl.textContent = companyName + (job.location ? ' • ' + job.location : '');

        // meta pills
        const meta = [];
        if (job.salary_range || job.salary) meta.push({ text: job.salary_range || job.salary });
        if (job.location) meta.push({ text: job.location });
        if (job.experience || job.experience_level) meta.push({ text: job.experience || job.experience_level });
        if (job.expiration_date || job.deadline) meta.push({ text: 'Hạn nộp: ' + formatDate(job.expiration_date || job.deadline) });

        const metaEl = document.getElementById('job-meta');
        if (metaEl) {
            metaEl.innerHTML = '';
            meta.forEach(m => {
                const d = document.createElement('div');
                d.className = 'meta-item';
                d.textContent = m.text;
                metaEl.appendChild(d);
            });
        }

        // description - display job details properly structured
        const jobDescEl = document.getElementById('job-description');
        console.log('Job description element:', jobDescEl);
        console.log('Job data for description:', {
            description: job.description,
            requirements: job.requirements,
            content: job.content
        });

        if (jobDescEl) {
            let detailedHTML = '';

            // Mô tả công việc
            if (job.description) {
                detailedHTML += `<div style="margin-bottom:24px;background:#f8fdf9;padding:20px;border-radius:8px;border:1px solid #d1f0db;">
                    <h4 style="color:#16a34a;font-weight:700;margin-bottom:12px;font-size:18px;">Mô tả công việc</h4>
                    <div style="line-height:1.8;color:#1a1a1a;white-space:pre-wrap;font-size:15px;">${safeHTML(job.description)}</div>
                </div>`;
            }

            // Yêu cầu công việc
            if (job.requirements) {
                detailedHTML += `<div style="margin-bottom:24px;background:#f0f9ff;padding:20px;border-radius:8px;border:1px solid #bae6fd;">
                    <h4 style="color:#16a34a;font-weight:700;margin-bottom:12px;font-size:18px;">Yêu cầu công việc</h4>
                    <div style="line-height:1.8;color:#1a1a1a;white-space:pre-wrap;font-size:15px;">${safeHTML(job.requirements)}</div>
                </div>`;
            }

            // Nếu không có gì thì hiển thị thông báo rõ ràng
            if (!detailedHTML) {
                detailedHTML = '<div style="background:#fee;padding:30px;border:2px solid #f00;border-radius:8px;margin:20px 0;"><p style="color:#c00;font-size:18px;font-weight:bold;text-align:center;">⚠️ KHÔNG CÓ DỮ LIỆU MÔ TẢ</p></div>';
            }

            jobDescEl.innerHTML = detailedHTML;
            jobDescEl.style.display = 'block';
            jobDescEl.style.minHeight = '200px';
            console.log('Description HTML set successfully', detailedHTML.substring(0, 100));
        } else {
            console.error('job-description element not found!');
        }

        // sidebar - company logo handling
        function normalizeLogoUrl(raw) {
            try {
                if (!raw) return null;
                raw = String(raw).trim();
                if (raw === '') return null;
                if (/^https?:\/\//i.test(raw)) return raw;
                if (/^\/\//.test(raw)) return window.location.protocol + raw;
                if (raw.startsWith('/')) return window.location.origin + raw;
                // assume storage relative path -> map to /storage/<path>
                return window.location.origin + '/storage/' + raw.replace(/^\/+/, '');
            } catch (e) {
                return null;
            }
        }

        let resolvedLogo = null;
        let logoSource = 'placeholder';
        if (job.company && job.company.logo) {
            resolvedLogo = normalizeLogoUrl(job.company.logo) || null;
            logoSource = 'job.company.logo';
        }
        if (!resolvedLogo && job.company_logo) {
            resolvedLogo = normalizeLogoUrl(job.company_logo) || null;
            logoSource = 'job.company_logo';
        }
        // If job data doesn't include a logo, try to fetch the company record
        if ((!resolvedLogo || resolvedLogo === null) && job.company && job.company.id) {
            try {
                const cRes = await fetch(`${apiBase}/public/companies/${job.company.id}`);
                if (cRes.ok) {
                    const cData = await cRes.json().catch(() => null) || {};
                    const cand = cData.logo || cData.logo_url || (cData.data && cData.data.attributes && cData.data.attributes.logo) || (cData.data && cData.data.attributes && cData.data.attributes.logo_url) || null;
                    if (cand) {
                        const n = normalizeLogoUrl(cand);
                        if (n) {
                            resolvedLogo = n;
                            logoSource = 'company.endpoint';
                        }
                    }
                }
            } catch (e) {
                /* ignore company endpoint errors */
            }
        }
        if (!resolvedLogo) {
            resolvedLogo = window.location.origin + '/images/company-placeholder.svg';
            logoSource = 'placeholder';
        }

        const logoEl = document.getElementById('company-logo');
        const logoLinkEl = document.getElementById('company-logo-link');

        if (logoEl) {
            logoEl.src = resolvedLogo;
            logoEl.onerror = function () {
                this.onerror = null;
                this.src = window.location.origin + '/images/company-placeholder.svg';
            };
            if (logoLinkEl) logoLinkEl.href = resolvedLogo;
        }

        // Try fallback logos if placeholder
        (function tryFallbacks() {
            if (!logoEl) return;
            const placeholderSrc = window.location.origin + '/images/company-placeholder.svg';
            if (logoEl.src && logoEl.src !== placeholderSrc && logoEl.src.trim() !== '') return;

            const company = job.company || {};
            const candidateUrls = [];

            // Collect candidate URLs
            const rawCandidates = [];
            if (job.company && job.company.logo) rawCandidates.push(job.company.logo);
            if (job.company_logo) rawCandidates.push(job.company_logo);
            if (job.logo) rawCandidates.push(job.logo);
            rawCandidates.forEach(rc => {
                if (rc) {
                    const s = String(rc).trim();
                    if (s) candidateUrls.push(s);
                }
            });

            // Map relative paths to /storage/
            rawCandidates.forEach(rc => {
                try {
                    const s = String(rc).trim();
                    if (s && !/^https?:\/\//i.test(s)) {
                        if (s.startsWith('/')) candidateUrls.push(window.location.origin + s);
                        else candidateUrls.push(window.location.origin + '/storage/' + s.replace(/^\/+/, ''));
                    }
                } catch (e) { }
            });

            // Try cached logos
            try {
                if (company.id) {
                    const key = 'company_logo_cache_' + company.id;
                    const cached = localStorage.getItem(key) || localStorage.getItem('company_cached_' + company.id) || localStorage.getItem('company_logo_' + company.id);
                    if (cached) candidateUrls.push(cached);
                }
            } catch (e) { }

            // Try website-based guesses
            if (company.website) {
                try {
                    const url = new URL(company.website);
                    const base = url.protocol + '//' + url.hostname;
                    candidateUrls.push(`https://logo.clearbit.com/${url.hostname}`);
                    candidateUrls.push(`https://logo.clearbit.com/${url.hostname}?size=128`);
                    candidateUrls.push(`https://www.google.com/s2/favicons?domain=${url.hostname}&sz=64`);
                    ['/logo.png', '/logo.svg', '/favicon.ico', '/assets/logo.png', '/images/logo.png'].forEach(p => candidateUrls.push(base + p));
                } catch (e) { }
            }

            // Remove duplicates and placeholder
            const seen = new Set();
            const urls = candidateUrls.filter(u => {
                if (!u) return false;
                const s = String(u);
                if (s.includes('company-placeholder.svg')) return false;
                if (seen.has(s)) return false;
                seen.add(s);
                return true;
            });
            if (urls.length === 0) return;

            let idx = 0;
            function loadNext() {
                if (idx >= urls.length) return;
                const next = urls[idx++];
                const img = new Image();
                let done = false;
                img.onload = function () {
                    if (done) return;
                    done = true;
                    logoEl.src = next;
                    if (logoLinkEl) logoLinkEl.href = next;
                };
                img.onerror = function () {
                    if (done) return;
                    done = true;
                    setTimeout(loadNext, 120);
                };
                img.src = next;
            }
            loadNext();
        })();

        const companyNameEl = document.getElementById('company-name');
        if (companyNameEl) companyNameEl.textContent = companyName || 'Công ty';

        const companyMetaEl = document.getElementById('company-meta');
        if (companyMetaEl) companyMetaEl.textContent = job.company && job.company.address ? job.company.address : '';

        const companyLinkEl = document.getElementById('company-page-link');
        if (companyLinkEl) companyLinkEl.href = job.company && job.company.id ? '/companies/' + job.company.id : '#';

    } catch (err) {
        console.error(err);
        const jobTitleEl = document.getElementById('job-title');
        if (jobTitleEl) jobTitleEl.textContent = 'Lỗi khi tải tin tuyển dụng';

        const jobDescEl = document.getElementById('job-description');
        if (jobDescEl) jobDescEl.textContent = err.message || 'Vui lòng thử lại sau.';
    }
}

let candidateResumes = null;
let hasApplied = false;

/**
 * Check if user has already applied to this job
 */
async function checkApplicationStatus(jobId, token) {
    try {
        const res = await fetch(`${apiBase}/applications`, {
            headers: {
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + token
            }
        });
        if (!res.ok) return false;
        
        let data = await res.json().catch(() => null);
        if (!data) return false;
        
        // Handle different response formats
        const applications = data.data || data;
        if (!Array.isArray(applications)) return false;
        
        // Check if any application matches this job
        const applied = applications.some(app => {
            const appJobId = app.job_id || (app.job && app.job.id);
            return String(appJobId) === String(jobId);
        });
        
        return applied;
    } catch (e) {
        console.warn('Could not check application status', e);
        return false;
    }
}

/**
 * Update apply button based on application status
 */
function updateApplyButton(hasApplied) {
    const btn = document.getElementById('apply-btn-fixed');
    if (!btn) return;
    
    if (hasApplied) {
        btn.textContent = '⟲ Ứng tuyển lại';
        btn.style.backgroundColor = '#f59e0b';
        btn.style.borderColor = '#f59e0b';
        btn.title = 'Bạn đã ứng tuyển công việc này';
    } else {
        btn.textContent = 'Ứng tuyển';
        btn.style.backgroundColor = '#16a34a';
        btn.style.borderColor = '#16a34a';
        btn.title = '';
    }
}

async function fetchAndPopulateResumes(token, cvBuilderRoute) {
    try {
        const sel = document.getElementById('resume-select');
        sel.style.display = 'none';
        sel.innerHTML = '';

        const res = await fetch(`${apiBase}/resumes`, {
            headers: { 'Accept': 'application/json', 'Authorization': 'Bearer ' + token }
        });
        if (!res.ok) {
            console.warn('Could not fetch resumes, status', res.status);
            candidateResumes = [];
            const note = document.getElementById('resume-note');
            if (note) {
                note.style.display = 'block';
                note.innerHTML = `Nếu chưa có CV, vui lòng <a href="${cvBuilderRoute}">tạo CV tại đây</a>.`;
            }
            return [];
        }
        let data = await res.json().catch(() => null);
        if (!data) {
            return null;
        }
        if (data.data && Array.isArray(data.data)) data = data.data;
        if (!Array.isArray(data) || data.length === 0) {
            candidateResumes = [];
            return [];
        }

        candidateResumes = data;
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.text = '-- Chọn CV --';
        sel.appendChild(placeholder);
        data.forEach(r => {
            const opt = document.createElement('option');
            const idVal = (r.id !== undefined) ? r.id : (r.resume_id !== undefined ? r.resume_id : '');
            opt.value = String(idVal);
            opt.text = (r.title || r.name || ('CV ' + (idVal || '')));
            sel.appendChild(opt);
        });
        sel.style.display = 'inline-block';
        return data;
    } catch (e) {
        console.warn('Could not load resumes', e);
        candidateResumes = [];
        const note = document.getElementById('resume-note');
        if (note) {
            note.style.display = 'block';
            note.innerHTML = `Nếu chưa có CV, vui lòng <a href="${cvBuilderRoute}">tạo CV tại đây</a>.`;
        }
        return [];
    }
}

async function handleApply(jobId, loginRoute, cvBuilderRoute, applicationsRoute) {
    const isLoggedIn = localStorage.getItem('isLoggedIn') === 'true';
    const currentUser = localStorage.getItem('currentUser');
    const token = localStorage.getItem('authToken') || localStorage.getItem('token');

    if (!isLoggedIn || !currentUser || !token) {
        window.location.href = `${loginRoute}?from=job-detail&jobId=${jobId}`;
        return;
    }

    let user = null;
    try {
        user = JSON.parse(currentUser);
    } catch (e) {
        user = null;
    }
    if (user && user.role === 'employer') {
        alert('Nhà tuyển dụng không thể ứng tuyển việc làm!');
        return;
    }

    const btn = document.getElementById('apply-btn-fixed');
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Đang gửi...';
    }

    const messagesEl = document.getElementById('job-messages-fixed');
    if (messagesEl) messagesEl.innerHTML = '';

    try {
        if (candidateResumes === null) {
            await fetchAndPopulateResumes(token, cvBuilderRoute);
        }

        const fileInput = document.getElementById('resume-file');
        const file = fileInput && fileInput.files && fileInput.files.length ? fileInput.files[0] : null;
        const coverLetterEl = document.getElementById('cover-letter');
        const coverLetter = coverLetterEl ? String(coverLetterEl.value).trim() : '';
        const sel = document.getElementById('resume-select');
        const selectedResumeId = (sel && sel.style.display !== 'none') ? (sel.value || null) : null;

        if (file) {
            const form = new FormData();
            form.append('job_id', jobId);
            if (coverLetter) form.append('cover_letter', coverLetter);
            form.append('cv', file);
            form.append('resume', file);
            if (selectedResumeId) form.append('resume_id', selectedResumeId);

            console.log('Applying with multipart form, keys:', Array.from(form.keys()));
            const res = await fetch(`${apiBase}/applications`, {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Accept': 'application/json'
                },
                body: form
            });

            const data = await res.json().catch(() => null);
            console.log('Apply (multipart) response', res.status, data);
            if (!res.ok) {
                if (res.status === 403) {
                    messagesEl.innerHTML = (data && (data.message || data.error)) ? (data.message || data.error) : 'Bạn chưa có hồ sơ ứng viên hoặc không có quyền ứng tuyển.';
                    const note = document.getElementById('resume-note');
                    if (note) {
                        note.style.display = 'block';
                        note.innerHTML = `Nếu chưa có CV, vui lòng <a href="${cvBuilderRoute}">tạo CV tại đây</a>.`;
                    }
                } else if (res.status === 429) {
                    const msg = (data && (data.message || data.error)) ? (data.message || data.error) : `Bạn đã ứng tuyển công việc này gần đây. Vui lòng thử lại sau.`;
                    messagesEl.innerHTML = msg;
                    messagesEl.style.color = '#e03';
                } else {
                    if (data && (data.message || data.errors || data.error)) {
                        const errs = data.errors ? Object.values(data.errors).flat().join(' - ') : (data.message || data.error);
                        messagesEl.innerHTML = errs;
                    } else {
                        if (messagesEl) messagesEl.innerHTML = `Ứng tuyển thất bại (status ${res.status})`;
                    }
                }
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Ứng tuyển';
                }
                return;
            }

            if (btn) btn.textContent = 'Đã ứng tuyển';
            if (messagesEl) {
                messagesEl.style.color = '#2a8b49';
                messagesEl.innerHTML = 'Ứng tuyển thành công!';
            }
            setTimeout(() => window.location.href = applicationsRoute, 1100);
            return;
        }

        let resumeId = selectedResumeId;
        if (!resumeId && Array.isArray(candidateResumes) && candidateResumes.length > 0) {
            resumeId = candidateResumes[0].id || candidateResumes[0].resume_id || null;
        }

        if (!resumeId) {
            if (messagesEl) messagesEl.innerHTML = `Nếu chưa có CV, vui lòng <a href="${cvBuilderRoute}">tạo CV tại đây</a> trước khi ứng tuyển, hoặc tải lên file ở trên.`;
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Ứng tuyển';
            }
            return;
        }

        console.log('Applying with resume_id', resumeId);
        const payload = { job_id: jobId, resume_id: resumeId };
        if (coverLetter) payload.cover_letter = coverLetter;

        const res2 = await fetch(`${apiBase}/applications`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + token
            },
            body: JSON.stringify(payload)
        });

        const data2 = await res2.json().catch(() => null);
        console.log('Apply (json) response', res2.status, data2);
        if (!res2.ok) {
            if (res2.status === 403) {
                messagesEl.innerHTML = (data2 && (data2.message || data2.error)) ? (data2.message || data2.error) : 'Bạn chưa có hồ sơ ứng viên hoặc không có quyền ứng tuyển.';
                const note = document.getElementById('resume-note');
                if (note) {
                    note.style.display = 'block';
                    note.innerHTML = `Nếu chưa có CV, vui lòng <a href="${cvBuilderRoute}">tạo CV tại đây</a>.`;
                }
            } else if (res2.status === 429) {
                const msg = (data2 && (data2.message || data2.error)) ? (data2.message || data2.error) : `Bạn đã ứng tuyển công việc này gần đây. Vui lòng thử lại sau.`;
                if (messagesEl) {
                    messagesEl.innerHTML = msg;
                    messagesEl.style.color = '#e03';
                }
            } else if (data2 && (data2.message || data2.errors || data2.error)) {
                const errs = data2.errors ? Object.values(data2.errors).flat().join(' - ') : (data2.message || data2.error);
                if (messagesEl) messagesEl.innerHTML = errs;
            } else {
                if (messagesEl) messagesEl.innerHTML = `Ứng tuyển thất bại (status ${res2.status})`;
            }
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Ứng tuyển';
            }
            return;
        }

        if (btn) btn.textContent = 'Đã ứng tuyển';
        if (messagesEl) {
            messagesEl.style.color = '#2a8b49';
            messagesEl.innerHTML = 'Ứng tuyển thành công!';
        }
        setTimeout(() => window.location.href = applicationsRoute, 1100);

    } catch (e) {
        console.error(e);
        if (messagesEl) messagesEl.innerHTML = 'Có lỗi xảy ra. Vui lòng thử lại.';
        if (btn) {
            btn.disabled = false;
            btn.textContent = 'Ứng tuyển';
        }
    }
}

// Initialize when DOM is ready
function initJobDetailPage(jobId, routes) {
    document.addEventListener('DOMContentLoaded', async function () {
        loadJob(jobId);

        // Preload candidate resumes and check application status if logged in
        try {
            const token = localStorage.getItem('authToken') || localStorage.getItem('token');
            if (token) {
                // Check if already applied
                hasApplied = await checkApplicationStatus(jobId, token);
                updateApplyButton(hasApplied);
                
                // Load resumes
                fetchAndPopulateResumes(token, routes.cvBuilder);
            }
        } catch (e) {
            /* ignore */
        }

        // Wire up the sidebar apply button
        const applyBtnFixed = document.getElementById('apply-btn-fixed');
        if (applyBtnFixed) {
            applyBtnFixed.addEventListener('click', () => {
                handleApply(jobId, routes.login, routes.cvBuilder, routes.applications);
            });
        }
    });
}
