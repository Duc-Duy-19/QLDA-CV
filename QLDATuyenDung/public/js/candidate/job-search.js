const API_BASE_URL = '';
let currentPage = 1;
let totalPages = 1;
let currentFilters = {};

// Load jobs
async function loadJobs(page = 1) {
    try {
        const params = new URLSearchParams({
            page: page,
            ...currentFilters
        });

        const response = await fetch(`${API_BASE_URL}/jobs?${params}`);
        const data = await response.json();
        
        displayJobs(data.jobs || data);
        updatePagination(data.totalPages || 1, page);
        currentPage = page;
    } catch (error) {
        console.error('Error loading jobs:', error);
        showEmptyState();
    }
}

// Display jobs
function displayJobs(jobs) {
    const container = document.getElementById('jobs-grid');
    
    if (jobs.length === 0) {
        showEmptyState();
        return;
    }
    
    container.innerHTML = jobs.map(job => `
        <div class="job-card" data-job-id="${job.id}">
            <div class="job-header">
                <h3 class="job-title">${job.title}</h3>
                <p class="company-name">${job.companyName || 'Công ty ABC'}</p>
                <p class="job-meta">${job.location} • ${job.salary || 'Thỏa thuận'}</p>
            </div>
            <div class="job-body">
                <p class="job-description">${job.description || 'Mô tả công việc...'}</p>
                <div class="job-tags">
                    ${(job.requirements || []).map(req => `<span class="job-tag">${req}</span>`).join('')}
                </div>
            </div>
            <div class="job-footer">
            <div class="job-actions">
                    <button class="btn btn-primary" onclick="applyJob(${job.id})">
                        <i class="fas fa-paper-plane"></i> Ứng tuyển
                </button>
                    <button class="favorite-btn" onclick="toggleFavorite(${job.id})" data-job-id="${job.id}">
                    <i class="fas fa-heart"></i>
                </button>
                </div>
            </div>
        </div>
    `).join('');
}
        
// Show empty state
function showEmptyState() {
    const container = document.getElementById('jobs-grid');
    container.innerHTML = `
        <div class="empty-state">
            <i class="fas fa-search"></i>
            <h3>Không tìm thấy việc làm nào</h3>
            <p>Hãy thử thay đổi từ khóa tìm kiếm hoặc bộ lọc</p>
        </div>
    `;
}

// Update pagination
function updatePagination(total, current) {
    const container = document.getElementById('pagination');
    totalPages = total;
    
    if (total <= 1) {
        container.innerHTML = '';
        return;
    }
    
    let paginationHTML = '';
    
    // Previous button
    paginationHTML += `
        <button ${current === 1 ? 'disabled' : ''} onclick="loadJobs(${current - 1})">
            <i class="fas fa-chevron-left"></i>
        </button>
    `;

    // Page numbers
    for (let i = 1; i <= total; i++) {
        if (i === current || i === 1 || i === total || (i >= current - 2 && i <= current + 2)) {
            paginationHTML += `
                <button class="${i === current ? 'active' : ''}" onclick="loadJobs(${i})">
                    ${i}
                </button>
            `;
        } else if (i === current - 3 || i === current + 3) {
            paginationHTML += '<span>...</span>';
        }
    }

    // Next button
    paginationHTML += `
        <button ${current === total ? 'disabled' : ''} onclick="loadJobs(${current + 1})">
            <i class="fas fa-chevron-right"></i>
        </button>
    `;

    container.innerHTML = paginationHTML;
}

// Search jobs
function searchJobs(event) {
    event.preventDefault();
    const searchInput = document.getElementById('search-input');
    const locationSelect = document.getElementById('location-select');
    const experienceSelect = document.getElementById('experience-select');
    
    currentFilters = {
        search: searchInput.value,
        location: locationSelect.value,
        experience: experienceSelect.value
    };
    
    loadJobs(1);
}

// Apply for job
async function applyJob(jobId) {
    const currentUser = JSON.parse(localStorage.getItem('currentUser'));
    if (!currentUser) {
        alert('Vui lòng đăng nhập để ứng tuyển!');
        return;
    }
    
    if (!confirm('Bạn có muốn ứng tuyển việc làm này?')) return;

    try {
        const response = await fetch(`${API_BASE_URL}/applications`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                jobId: jobId,
                candidateId: currentUser.id,
                status: 'pending',
                appliedAt: new Date().toISOString()
            })
        });

        if (response.ok) {
            alert('Đã ứng tuyển thành công!');
        } else {
            alert('Có lỗi xảy ra khi ứng tuyển!');
        }
    } catch (error) {
        console.error('Error applying job:', error);
        alert('Có lỗi xảy ra khi ứng tuyển!');
    }
}

// Toggle favorite
async function toggleFavorite(jobId) {
    const currentUser = JSON.parse(localStorage.getItem('currentUser'));
    if (!currentUser) {
        alert('Vui lòng đăng nhập để lưu việc làm!');
        return;
    }

    const favoriteBtn = document.querySelector(`[data-job-id="${jobId}"]`);
    const isFavorited = favoriteBtn.classList.contains('favorited');

    try {
        if (isFavorited) {
            // Remove from favorites
            await fetch(`${API_BASE_URL}/favoriteJobs?userId=${currentUser.id}&jobId=${jobId}`, {
                method: 'DELETE'
            });
            favoriteBtn.classList.remove('favorited');
        } else {
            // Add to favorites
            await fetch(`${API_BASE_URL}/favoriteJobs`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    userId: currentUser.id,
                    jobId: jobId,
                    savedAt: new Date().toISOString()
                })
            });
            favoriteBtn.classList.add('favorited');
        }
    } catch (error) {
        console.error('Error toggling favorite:', error);
        alert('Có lỗi xảy ra!');
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadJobs();
    
    // Check for category filter from URL
    const urlParams = new URLSearchParams(window.location.search);
    const category = urlParams.get('category');
    if (category) {
        currentFilters.category = category;
        // Update search input to show the category filter
        document.getElementById('search-input').value = category;
        loadJobs();
    }
});