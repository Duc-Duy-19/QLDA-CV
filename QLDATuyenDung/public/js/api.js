// API Helper functions
class APIHelper {
    static getBaseURL() {
        return window.location.origin + '/api';
    }

    static getAuthHeaders() {
        const token = localStorage.getItem('authToken');
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        };
        
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }
        
        return headers;
    }

    static async request(endpoint, options = {}) {
        const url = `${this.getBaseURL()}${endpoint}`;
        const config = {
            headers: this.getAuthHeaders(),
            credentials: 'include', // include cookies for Sanctum cookie auth
            ...options
        };

        try {
            const response = await fetch(url, config);
            let data = null;
            const contentType = response.headers.get('content-type') || '';
            if (contentType.includes('application/json')) {
                data = await response.json();
            } else {
                const text = await response.text();
                data = { message: text };
            }

            if (!response.ok) {
                const err = new Error(data.message || `HTTP error! status: ${response.status}`);
                err.status = response.status;
                err.response = data;
                throw err;
            }

            return data;
        } catch (error) {
            console.error('API request failed:', error);
            throw error;
        }
    }

    // Ensure CSRF cookie is present for Sanctum (cookie based auth)
    static async ensureCsrf() {
        try {
            const url = window.location.origin + '/sanctum/csrf-cookie';
            await fetch(url, { credentials: 'include' });
        } catch (error) {
            console.warn('Failed to get CSRF cookie:', error);
        }
    }

    // Auth methods
    static async login(email, password) {
        return this.request('/login', {
            method: 'POST',
            body: JSON.stringify({ email, password })
        });
    }

    static async register(userData) {
        return this.request('/register', {
            method: 'POST',
            body: JSON.stringify(userData)
        });
    }

    static async logout() {
        return this.request('/logout', {
            method: 'POST'
        });
    }

    static async forgotPassword(email) {
        return this.request('/forgot-password', {
            method: 'POST',
            body: JSON.stringify({ email })
        });
    }

    static async resetPassword(email, token, password) {
        return this.request('/reset-password', {
            method: 'POST',
            body: JSON.stringify({ email, token, password })
        });
    }

    // Candidate profile routes
    static async getCandidateProfile() {
        return this.request('/profile');
    }

    static async updateCandidateProfile(profileData) {
        // Tạo FormData để hỗ trợ upload file avatar
        const formData = new FormData();
        
        // Thêm các trường dữ liệu
        Object.keys(profileData).forEach(key => {
            if (key === 'avatar' && profileData[key] instanceof File) {
                formData.append('avatar', profileData[key]);
            } else if (profileData[key] !== null && profileData[key] !== undefined) {
                formData.append(key, profileData[key]);
            }
        });

        // Gửi request với FormData
        const url = `${this.getBaseURL()}/profile`;
        const token = localStorage.getItem('authToken');
        const headers = {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        };
        
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: headers,
                credentials: 'include',
                body: formData
            });
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || `HTTP error! status: ${response.status}`);
            }

            return data;
        } catch (error) {
            console.error('API request failed:', error);
            throw error;
        }
    }
}

// Export for use in other scripts
window.APIHelper = APIHelper;
