let currentFilters = {
    search: '',
    location: '',
    industry: ''
};

// API base URL (use global if injected by Blade, otherwise relative /api)
const API_BASE_URL = window.API_BASE_URL || '/api';

function searchCompanies() {
    const searchTerm = document.getElementById('company-search').value;
    currentFilters.search = searchTerm;
    
    performSearch();
}

function performSearch() {
    const companiesGrid = document.getElementById('companies-grid');
    
    // Build query parameters for Laravel API
    const params = new URLSearchParams();
    if (currentFilters.search) {
        params.append('search', currentFilters.search);
    }
    // Add location filter (free-text)
    if (currentFilters.location) {
        params.append('location', currentFilters.location);
    }
    // Add industry if any
    if (currentFilters.industry) {
        params.append('industry', currentFilters.industry);
    }

    fetch(`${API_BASE_URL}/public/companies?${params.toString()}`)
    .then(response => response.json())
    .then(data => {
        let companies = Array.isArray(data) ? data : (data.data || []);
        // Apply client-side filtering as a fallback in case backend doesn't support locations[] param
        const filtered = applyClientFilters(companies);
        displayCompanies(filtered);
    })
    .catch(error => {
        console.error('Search error:', error);
        companiesGrid.innerHTML = '<div class="error">Có lỗi xảy ra khi tìm kiếm</div>';
    });
}

// Helper function to get size range
function getSizeRange(size) {
    switch (size) {
        case '25-99':
            return { min: 25, max: 99 };
        case '100-499':
            return { min: 100, max: 499 };
        case '500-999':
            return { min: 500, max: 999 };
        case '1000-4999':
            return { min: 1000, max: 4999 };
        case '5000-9999':
            return { min: 5000, max: 9999 };
        case '10000+':
            return { min: 10000, max: 999999 };
        default:
            return null;
    }
}

function displayCompanies(companies) {
    const companiesGrid = document.getElementById('companies-grid');
    
    if (companies.length === 0) {
        companiesGrid.innerHTML = '<div class="no-results">Không tìm thấy công ty nào</div>';
        return;
    }
    
    let html = '';
    companies.forEach(company => {
        const logoUrls = getCompanyLogoUrls(company);
        const hasWebsite = company.website && company.website.trim() !== '';
        const initials = getCompanyInitials(company);
        const hasCachedLogo = company.cached_logo;
        let logoHtml = '';
        if (company.logo) {
            // Ưu tiên logo từ backend, luôn hiển thị đồng đều
            logoHtml = `<img src="${company.logo}" alt="${company.company_name}" style="width:160px;height:160px;object-fit:contain;display:block;margin:auto;border-radius:20px;background:#fff;box-shadow:0 4px 16px rgba(0,0,0,0.10);padding:12px;" onload="hidePlaceholder(this); cacheLogo(${company.id}, '${company.logo}');">`;
        } else if (hasCachedLogo) {
            logoHtml = `<img src="${company.cached_logo}" alt="${company.company_name}" style="width:160px;height:160px;object-fit:contain;display:block;margin:auto;border-radius:20px;background:#fff;box-shadow:0 4px 16px rgba(0,0,0,0.10);padding:12px;" onload="hidePlaceholder(this); cacheLogo(${company.id}, '${company.cached_logo}');">`;
        } else if (hasWebsite && logoUrls.length > 0) {
            logoHtml = `<img src="${logoUrls[0]}" alt="${company.company_name}" style="width:160px;height:160px;object-fit:contain;display:block;margin:auto;border-radius:20px;background:#fff;box-shadow:0 4px 16px rgba(0,0,0,0.10);padding:12px;" onerror="tryNextLogo(this, '${company.company_name}', ${JSON.stringify(logoUrls).replace(/"/g, '&quot;')});" onload="hidePlaceholder(this); cacheLogo(${company.id}, this.src);">`;
        }
        html += `
            <div class="company-card" onclick="viewCompany(${company.id})">
                <div class="company-logo">
                    ${logoHtml}
                    <div class="logo-placeholder" style="display: ${(company.logo || hasCachedLogo || (hasWebsite && logoUrls.length > 0)) ? 'none' : 'flex'}; background-color: ${getCompanyColor(company)};">
                        ${initials}
                    </div>
                </div>
                <div class="company-info">
                    <h3>${company.company_name}</h3>
                    <div class="company-description" style="margin-top:10px;color:#444;font-size:15px;line-height:1.6;">
                        ${company.description ? company.description.substring(0, 220) + (company.description.length > 220 ? '...' : '') : 'Chưa có mô tả'}
                    </div>
                </div>
            </div>
        `;
    });
    
    companiesGrid.innerHTML = html;
    
    // Create canvas logos for companies without real logos
    setTimeout(() => {
        companies.forEach(company => {
            const logoElement = document.querySelector(`[alt="${company.company_name}"]`);
            const placeholderElement = logoElement ? logoElement.nextElementSibling : null;
            
            // Only create canvas logo if placeholder is still visible (no real logo loaded)
            if (placeholderElement && 
                placeholderElement.classList.contains('logo-placeholder') && 
                placeholderElement.style.display !== 'none') {
                
                const initials = getCompanyInitials(company);
                const canvasLogo = createTextLogo(company.company_name, initials);
                
                // Replace placeholder with canvas logo
                const img = document.createElement('img');
                img.src = canvasLogo;
                img.alt = company.company_name;
                img.style.width = '100%';
                img.style.height = '100%';
                img.style.borderRadius = '8px';
                img.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
                img.style.objectFit = 'cover';
                
                placeholderElement.parentNode.replaceChild(img, placeholderElement);
            }
        });
    }, 2000); // Wait 2 seconds for real logos to load
}

// Client-side filter helper: normalize strings and filter by selected locations
function normalizeText(str) {
    if (!str) return '';
    try {
        // remove diacritics, lowercase and collapse whitespace
        return str.normalize('NFD').replace(/\p{Diacritic}/gu, '').toLowerCase().replace(/[^a-z0-9 ]+/g, ' ').replace(/\s+/g, ' ').trim();
    } catch (e) {
        // Fallback if normalize or Unicode property escapes not supported
        return str.toLowerCase().replace(/[^a-z0-9 ]+/g, ' ').replace(/\s+/g, ' ').trim();
    }
}

function applyClientFilters(companies) {
    if (!currentFilters.location || currentFilters.location.trim() === '') return companies;

    const token = normalizeText(currentFilters.location);

    return companies.filter(company => {
        const candidates = [company.address, company.location, company.city, company.province, company.office_location, company.city_name];
        const combined = candidates.filter(Boolean).join(' ');
        const normalized = normalizeText(combined);

        return normalized.indexOf(token) !== -1;
    });
}

// Open company detail by fetching from backend and showing modal
function viewCompany(companyId) {
    // Redirect to company detail page (web route)
    window.location.href = `/companies/${companyId}`;
}

function showCompanyModal(company) {
    const modal = document.getElementById('companyDetailModal');
    if (!modal) return;

    // Populate modal fields
    const logoEl = document.getElementById('modal-company-logo');
    const nameEl = document.getElementById('modal-company-name');
    const websiteEl = document.getElementById('modal-company-website');
    const addressEl = document.getElementById('modal-company-address');
    const descEl = document.getElementById('modal-company-description');

    if (logoEl) logoEl.src = company.logo || '';
    if (nameEl) nameEl.textContent = company.company_name || '---';
    if (websiteEl) {
        websiteEl.innerHTML = company.website ? `<a href="${company.website}" target="_blank" rel="noopener">${company.website}</a>` : '';
    }
    if (addressEl) addressEl.textContent = company.address || '';
    if (descEl) descEl.textContent = company.description || '';

    // Show modal
    modal.style.display = 'block';

    // Close handlers
    const closeBtn = document.getElementById('modal-close-btn');
    if (closeBtn) {
        closeBtn.onclick = () => { modal.style.display = 'none'; };
    }
    const backdrop = modal.querySelector('.company-modal-backdrop');
    if (backdrop) backdrop.onclick = () => { modal.style.display = 'none'; };
}

// Handle logo loading errors
function handleLogoError(imgElement, companyName) {
    console.log('Logo failed to load for', companyName, ':', imgElement.src);
    imgElement.style.display = 'none';
    const placeholder = imgElement.nextElementSibling;
    if (placeholder) {
        placeholder.style.display = 'flex';
    }
}

// Function to get company logo URLs from website
function getCompanyLogoUrls(company) {
    const logoUrls = [];
    
    // Get logo from the company's website using multiple common paths
    if (company.website) {
        try {
            const url = new URL(company.website);
            const baseUrl = `${url.protocol}//${url.hostname}`;
            
            // Priority 1: Professional logo services (most reliable)
            logoUrls.push(`https://logo.clearbit.com/${url.hostname}`);
            logoUrls.push(`https://logo.clearbit.com/${url.hostname}?size=128`);
            
            // Priority 2: Google favicon service (good quality)
            logoUrls.push(`https://www.google.com/s2/favicons?domain=${url.hostname}&sz=64`);
            logoUrls.push(`https://www.google.com/s2/favicons?domain=${url.hostname}&sz=128`);
            
            // Priority 3: DuckDuckGo favicon service
            logoUrls.push(`https://icons.duckduckgo.com/ip3/${url.hostname}.ico`);
            
            // Priority 4: Direct website logos (may have CORS issues)
            const logoPaths = [
                '/logo.png',
                '/logo.svg', 
                '/assets/logo.png',
                '/assets/logo.svg',
                '/images/logo.png',
                '/images/logo.svg',
                '/static/logo.png',
                '/static/logo.svg',
                '/favicon.ico'
            ];
            
            logoPaths.forEach(path => {
                logoUrls.push(baseUrl + path);
            });
            
            // Priority 5: CORS proxy as last resort
            logoUrls.push(`https://api.allorigins.win/raw?url=${encodeURIComponent(baseUrl + '/logo.png')}`);
            logoUrls.push(`https://api.allorigins.win/raw?url=${encodeURIComponent(baseUrl + '/favicon.ico')}`);
            
            console.log('Getting logo URLs for', company.company_name, 'from website:', company.website);
            console.log('Logo URLs (ordered by priority):', logoUrls);
        } catch (e) {
            console.log('Invalid website URL for', company.company_name, ':', company.website);
        }
    } else {
        console.log('No website available for', company.company_name);
    }
    
    return logoUrls;
}

// Function to try next logo URL when current one fails
function tryNextLogo(imgElement, companyName, logoUrls) {
    const currentSrc = imgElement.src;
    const currentIndex = logoUrls.indexOf(currentSrc);
    
    if (currentIndex < logoUrls.length - 1) {
        // Try next URL
        const nextUrl = logoUrls[currentIndex + 1];
        console.log('Trying next logo for', companyName, ':', nextUrl);
        
        // Set up timeout and load handlers
        let timeoutId;
        
        const onLoad = () => {
            clearTimeout(timeoutId);
            console.log('Logo loaded successfully for', companyName, ':', nextUrl);
            hidePlaceholder(imgElement);
            cacheLogo(companyName, nextUrl);
            imgElement.removeEventListener('load', onLoad);
            imgElement.removeEventListener('error', onError);
        };
        
        const onError = () => {
            clearTimeout(timeoutId);
            console.log('Logo failed to load for', companyName, ':', nextUrl);
            imgElement.removeEventListener('load', onLoad);
            imgElement.removeEventListener('error', onError);
            tryNextLogo(imgElement, companyName, logoUrls);
        };
        
        // Set timeout
        timeoutId = setTimeout(() => {
            console.log('Logo load timeout for', companyName, ':', nextUrl);
            imgElement.removeEventListener('load', onLoad);
            imgElement.removeEventListener('error', onError);
            tryNextLogo(imgElement, companyName, logoUrls);
        }, 5000); // 5 second timeout
        
        // Add event listeners
        imgElement.addEventListener('load', onLoad);
        imgElement.addEventListener('error', onError);
        
        // Try to load the image
        imgElement.src = nextUrl;
    } else {
        // All URLs failed, show placeholder
        console.log('All logo URLs failed for', companyName, ', showing placeholder');
        imgElement.style.display = 'none';
        const placeholder = imgElement.nextElementSibling;
        if (placeholder) {
            placeholder.style.display = 'flex';
        }
    }
}

// Legacy function for backward compatibility
function getCompanyLogo(company) {
    const urls = getCompanyLogoUrls(company);
    return urls.length > 0 ? urls[0] : null;
}

// Function to get company initials (fallback)
function getCompanyInitials(company) {
    const name = (company && company.company_name) ? company.company_name.trim() : '';
    if (!name) return '';

    // Build a short initials string from up to the first 3 words.
    // If company has a single-word name, take the first 3 characters.
    const words = name.split(/\s+/).filter(Boolean);
    if (words.length === 1) {
        return words[0].substring(0, 3).toUpperCase();
    }

    const initials = words.slice(0, 3).map(w => w[0]).join('').toUpperCase();
    return initials;
}

// Function to create logo from text using canvas
function createTextLogo(companyName, initials) {
    const canvas = document.createElement('canvas');
    canvas.width = 120;
    canvas.height = 120;
    const ctx = canvas.getContext('2d');
    
    // Background
    const colors = ['#e74c3c', '#3498db', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#e91e63', '#8e44ad'];
    const colorIndex = companyName.length % colors.length;
    ctx.fillStyle = colors[colorIndex];
    ctx.fillRect(0, 0, 120, 120);
    
    // Text
    ctx.fillStyle = 'white';
    ctx.font = 'bold 36px Arial';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(initials, 60, 60);
    
    return canvas.toDataURL();
}

// Function to get company color
function getCompanyColor(company) {
    const name = company.company_name.toLowerCase();
    // Default colors based on company name hash
    const colors = [
        '#e74c3c', '#3498db', '#2ecc71', '#f39c12', '#9b59b6',
        '#1abc9c', '#e91e63', '#8e44ad', '#16a085', '#d35400',
        '#c0392b', '#27ae60', '#2980b9', '#34495e', '#f1c40f'
    ];
    
    let hash = 0;
    for (let i = 0; i < company.company_name.length; i++) {
        hash = company.company_name.charCodeAt(i) + ((hash << 5) - hash);
    }
    
    return colors[Math.abs(hash) % colors.length];
}

// Load companies on page load from JSON Server
function loadCompanies() {
    const companiesGrid = document.getElementById('companies-grid');
    
    fetch(`${API_BASE_URL}/public/companies`)
    .then(response => response.json())
    .then(data => {
        // Laravel API trả về dạng { data: [...], ... } hoặc { ...pagination, data: [...] }
        let companies = Array.isArray(data) ? data : (data.data || []);
        if (companies && companies.length > 0) {
            checkCachedLogos(companies).then(companiesWithLogos => {
                displayCompanies(companiesWithLogos);
            });
        } else {
            companiesGrid.innerHTML = '<div class="no-results">Không có công ty nào</div>';
        }
    })
    .catch(error => {
        console.error('Load companies error:', error);
        companiesGrid.innerHTML = '<div class="error">Có lỗi xảy ra khi tải danh sách công ty. Vui lòng kiểm tra API backend đã chạy chưa.</div>';
    });
}

// Check for cached logos in localStorage
function checkCachedLogos(companies) {
    return Promise.all(companies.map(company => {
        const cacheKey = `logo_${company.id}`;
        const cachedLogo = localStorage.getItem(cacheKey);
        
        if (cachedLogo) {
            company.cached_logo = cachedLogo;
            console.log('Using cached logo for', company.company_name);
        }
        
        return company;
    }));
}

// Cache successful logo URLs
function cacheLogo(companyId, logoUrl) {
    const cacheKey = `logo_${companyId}`;
    localStorage.setItem(cacheKey, logoUrl);
    console.log('Cached logo for company', companyId, ':', logoUrl);
}

// Hide placeholder when logo loads successfully
function hidePlaceholder(imgElement) {
    const placeholder = imgElement.nextElementSibling;
    if (placeholder && placeholder.classList.contains('logo-placeholder')) {
        placeholder.style.display = 'none';
        console.log('Hidden placeholder for logo:', imgElement.src);
    }
}

// Filter functionality
document.addEventListener('DOMContentLoaded', function() {
    // Load companies on page load
    loadCompanies();
    
    // Location input (free text) handling
    const locationInput = document.getElementById('location-input');
    const clearLocation = document.getElementById('clear-location');
    if (locationInput) {
        locationInput.addEventListener('input', function() {
            clearTimeout(this._debounce);
            const self = this;
            this._debounce = setTimeout(() => {
                currentFilters.location = self.value.trim();
                performSearch();
            }, 400);
        });
    }
    if (clearLocation) {
        clearLocation.addEventListener('click', function(e) {
            e.preventDefault();
            if (locationInput) locationInput.value = '';
            currentFilters.location = '';
            performSearch();
        });
    }
    // Note: company size filter removed. Filtering only by location (free text) and search for now.
    
    // Search on Enter key
    document.getElementById('company-search').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            searchCompanies();
        }
    });
    
    // Real-time search as user types
    document.getElementById('company-search').addEventListener('input', function() {
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => {
            searchCompanies();
        }, 500); // Debounce search by 500ms
    });
});
