const API_BASE_URL = '/api';
let notifications = [];
let filteredNotifications = [];
let companyInvites = [];

// Get authentication token
function getAuthToken() {
    return localStorage.getItem('authToken');
}

// Check authentication
function checkAuth() {
    const token = getAuthToken();
    const isLoggedIn = localStorage.getItem('isLoggedIn');
    
    // Nếu có token hoặc có flag isLoggedIn thì OK
    if (token || isLoggedIn === 'true') {
        return true;
    }
    
    // Nếu không có token, kiểm tra xem có user info không
    const currentUser = localStorage.getItem('currentUser');
    if (currentUser) {
        return true;
    }
    
    console.warn('No authentication found, user might need to login');
    return false; // Không redirect ngay, để trang tự quyết định
}

// Load notifications
async function loadNotifications() {
    console.log('🔄 loadNotifications() called at:', new Date().toISOString());
    
    try {
        // Show loading state
        document.getElementById('notifications-list').innerHTML = `
            <div class="empty-state">
                <i>⏳</i>
                <h3>Đang tải thông báo...</h3>
                <p>Vui lòng chờ trong giây lát</p>
            </div>
        `;

        // Check authentication
        if (!checkAuth()) {
            console.log('❌ Authentication failed');
            // Hiển thị thông báo chưa đăng nhập thay vì redirect
            document.getElementById('notifications-list').innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-lock"></i>
                    <h3>Vui lòng đăng nhập</h3>
                    <p>Bạn cần đăng nhập để xem thông báo</p>
                    <a href="/login" class="btn btn-primary">Đăng nhập</a>
                </div>
            `;
            return;
        }

        // Load notifications
        console.log('🔄 Resetting notifications array');
        notifications = []; // Start with empty array instead of sample data
        
        // Load company invites and convert to notifications
        await loadAndConvertCompanyInvites();
        
        // Load payment notifications
        await loadPaymentNotifications();
        
        // Sort all notifications by date
        filteredNotifications = [...notifications].sort((a, b) => new Date(b.date) - new Date(a.date));
        renderNotifications();
        
        // Update notification count
        updateNotificationBadge();
    } catch (error) {
        console.error('Lỗi khi tải thông báo:', error);
        // Show empty state instead of sample data when error occurs
        notifications = [];
        filteredNotifications = [];
        renderNotifications();
    }
}

// Load payment notifications
async function loadPaymentNotifications() {
    try {
        const token = getAuthToken();
        if (!token) return;

        const response = await fetch(`${API_BASE_URL}/payments/my-payments`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
            },
        });

        if (!response.ok) {
            return; // Silently fail if no payments
        }

        const data = await response.json();
        const payments = data.payments || [];

        // Convert payments to notifications
        payments.forEach(payment => {
            if (payment.status === 'pending' || payment.status === 'pending_verification') {
                const notificationId = `payment_${payment.id}`;
                
                // Check if notification already exists
                const existing = notifications.find(n => n.id === notificationId);
                if (existing) {
                    return; // Skip if already exists
                }

                const notification = {
                    id: notificationId,
                    type: 'payment_required',
                    title: payment.status === 'pending' 
                        ? 'Thanh toán để nâng cấp lên nhà tuyển dụng'
                        : 'Thanh toán đang chờ xác minh',
                    message: payment.status === 'pending'
                        ? `Công ty ${payment.company?.company_name || ''} đã được duyệt. Vui lòng thanh toán để hoàn tất nâng cấp.`
                        : `Thanh toán cho công ty ${payment.company?.company_name || ''} đang chờ admin xác minh.`,
                    date: payment.created_at || new Date().toISOString(),
                    read: false,
                    priority: 'high',
                    data: {
                        payment_id: payment.id,
                        company_id: payment.company_id,
                        company_name: payment.company?.company_name,
                        amount: payment.amount,
                        qr_code_url: payment.qr_code_url,
                        qr_code_data: payment.qr_code_data,
                        order_id: payment.sepay_order_id,
                    },
                };

                notifications.push(notification);
            }
        });

    } catch (error) {
        console.error('Lỗi khi tải payment notifications:', error);
    }
}

// Load company invites and convert to notifications
async function loadAndConvertCompanyInvites() {
    try {
        const token = getAuthToken();
        console.log('🔑 Auth token:', token ? 'Có token' : 'Không có token');
        
        // Check user role first
        const currentUser = localStorage.getItem('currentUser');
        if (currentUser) {
            const userData = JSON.parse(currentUser);
            console.log('👤 Current user role:', userData.role);
            
            // Only load company invites for candidates
            if (userData.role !== 'candidate') {
                console.log('⚠️ User is not candidate, skipping company invites API');
                return; // Don't load invites for non-candidates
            }
        }
        
        const response = await fetch(`${API_BASE_URL}/company-invites`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });

        console.log('📡 API Response status:', response.status);
        
        if (response.ok) {
            const data = await response.json();
            console.log('🔍 Raw API response:', data);
            
            const invites = data.invites || [];
            console.log('🔍 Company invites array:', invites);
            
            // Convert company invites to notifications
            invites.forEach(invite => {
                const company = invite.company || {};
                const companyName = company.company_name || 'Công ty không xác định';
                
                const inviteId = `invite_${invite.id}`;
                
                // Check if this invite notification already exists
                const existingInvite = notifications.find(n => n.id === inviteId);
                if (existingInvite) {
                    console.log('🔄 Invite notification already exists, skipping:', inviteId);
                    return; // Skip if already exists
                }
                
                const inviteNotification = {
                    id: inviteId,
                    type: 'invite',
                    title: 'Lời mời tham gia công ty',
                    message: `Bạn được mời tham gia ${companyName} với vai trò ${invite.role_in_company || 'Member'}`,
                    date: invite.created_at || new Date().toISOString(),
                    read: false,
                    priority: 'high',
                    invite_data: invite // Lưu data gốc để xử lý accept/decline
                };
                
                console.log('➕ Adding new invite notification:', inviteId);
                notifications.push(inviteNotification);
            });
        } else {
            console.error('Failed to load company invites:', response.status);
        }
    } catch (error) {
        console.error('Lỗi khi tải lời mời công ty:', error);
    }
}

// Render notifications
function renderNotifications() {
    const container = document.getElementById('notifications-list');
    
    if (filteredNotifications.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i>📭</i>
                <h3>Không có thông báo nào</h3>
                <p>Bạn chưa có thông báo nào phù hợp với bộ lọc</p>
            </div>
        `;
        return;
    }

    container.innerHTML = filteredNotifications.map(notification => {
        const isRead = notification.read;
        const priorityClass = notification.priority || 'medium';
        const typeIcon = getTypeIcon(notification.type);
        const formattedDate = new Date(notification.date).toLocaleDateString('vi-VN');
        
        // Special handling for invite notifications
        let actionButtons = '';
        let qrCodeSection = '';
        
        if (notification.type === 'invite' && notification.invite_data) {
            const invite = notification.invite_data;
            const companyId = invite.company_id;
            actionButtons = `
                <button class="btn btn-sm btn-success" onclick="acceptInviteFromNotification('${notification.id}', ${companyId})">
                    <i class="fas fa-check"></i> Chấp nhận
                </button>
                <button class="btn btn-sm btn-danger" onclick="declineInviteFromNotification('${notification.id}', ${companyId})">
                    <i class="fas fa-times"></i> Từ chối
                </button>
            `;
        } else if (notification.type === 'payment_required' && notification.data) {
            // Payment required notification
            const paymentId = notification.data.payment_id;
            const amount = notification.data.amount || 0;
            const formattedAmount = new Intl.NumberFormat('vi-VN').format(amount);
            const qrCodeUrl = notification.data.qr_code_url;
            const qrCodeData = notification.data.qr_code_data || notification.data.qr_code;
            
            // QR Code section
            if (qrCodeUrl || qrCodeData) {
                const qrCodeSrc = qrCodeUrl 
                    ? qrCodeUrl 
                    : (qrCodeData.startsWith('data:') ? qrCodeData : `data:image/png;base64,${qrCodeData}`);
                
                qrCodeSection = `
                    <div style="margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px; text-align: center; border: 2px solid #28a745;">
                        <p style="font-weight: 600; color: #28a745; margin-bottom: 10px; font-size: 16px;">
                            Số tiền: ${formattedAmount} VND
                        </p>
                        <img src="${qrCodeSrc}" 
                             alt="QR Code Thanh toán" 
                             style="max-width: 200px; width: 100%; border: 2px solid #28a745; border-radius: 8px; padding: 10px; background: white; margin: 10px 0;">
                        <p style="font-size: 12px; color: #666; margin-top: 10px;">
                            Quét mã QR để thanh toán
                        </p>
                    </div>
                `;
            }
            
            // Action buttons
            if (paymentId) {
                actionButtons = `
                    <a href="/payment/${paymentId}" class="btn btn-sm btn-success" style="text-decoration: none; display: inline-block;">
                        <i class="fas fa-credit-card"></i> Xem thanh toán
                    </a>
                    ${!isRead ? `
                        <button class="btn btn-sm btn-primary" onclick="markAsRead('${notification.id}')">
                            <i class="fas fa-check"></i> Đánh dấu đã đọc
                        </button>
                    ` : ''}
                    <button class="btn btn-sm btn-danger" onclick="deleteNotification('${notification.id}')">
                        <i class="fas fa-trash"></i> Xóa
                    </button>
                `;
            } else {
                actionButtons = `
                    ${!isRead ? `
                        <button class="btn btn-sm btn-primary" onclick="markAsRead('${notification.id}')">
                            <i class="fas fa-check"></i> Đánh dấu đã đọc
                        </button>
                    ` : ''}
                    <button class="btn btn-sm btn-danger" onclick="deleteNotification('${notification.id}')">
                        <i class="fas fa-trash"></i> Xóa
                    </button>
                `;
            }
        } else {
            actionButtons = `
                ${!isRead ? `
                    <button class="btn btn-sm btn-primary" onclick="markAsRead('${notification.id}')">
                        <i class="fas fa-check"></i> Đánh dấu đã đọc
                    </button>
                ` : ''}
                <button class="btn btn-sm btn-danger" onclick="deleteNotification('${notification.id}')">
                    <i class="fas fa-trash"></i> Xóa
                </button>
            `;
        }
        
        return `
        <div class="notification-item ${isRead ? 'read' : 'unread'}" data-id="${notification.id}">
            <div class="notification-icon ${priorityClass}">
                <i class="${typeIcon}"></i>
            </div>
            <div class="notification-content">
                <div class="notification-header">
                    <h3 class="notification-title">${notification.title}</h3>
                    <span class="notification-date">${formattedDate}</span>
                </div>
                <p class="notification-message">${notification.message}</p>
                ${qrCodeSection}
                <div class="notification-actions">
                    ${actionButtons}
                </div>
            </div>
        </div>
        `;
    }).join('');
}

// Get type icon
function getTypeIcon(type) {
    const icons = {
        'application': 'fas fa-paper-plane',
        'interview': 'fas fa-calendar-alt',
        'offer': 'fas fa-handshake',
        'invite': 'fas fa-envelope',
        'payment_required': 'fas fa-credit-card',
        'system': 'fas fa-cog'
    };
    return icons[type] || 'fas fa-bell';
}

// Format date for display
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

// Apply filters
function applyFilters() {
    const typeFilter = document.getElementById('type-filter').value;
    const statusFilter = document.getElementById('status-filter').value;
    const dateFrom = document.getElementById('date-from').value;

    filteredNotifications = notifications.filter(notification => {
        let matches = true;

        if (typeFilter && notification.type !== typeFilter) {
            matches = false;
        }

        if (statusFilter === 'read' && !notification.read) {
            matches = false;
        }

        if (statusFilter === 'unread' && notification.read) {
            matches = false;
        }

        if (dateFrom && new Date(notification.date) < new Date(dateFrom)) {
            matches = false;
        }

        return matches;
    });

    renderNotifications();
}

// Update notification badge count
function updateNotificationBadge() {
    const unreadCount = notifications.filter(n => !n.read).length;
    
    // Update badge in current page if exists
    const badge = document.getElementById('notification-count');
    if (badge) {
        if (unreadCount > 0) {
            badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
            badge.style.display = 'inline-block';
        } else {
            badge.style.display = 'none';
        }
    }
    
    // Try to update badge in parent window (if this page is in iframe or popup)
    if (window.parent && window.parent.updateNotificationCount) {
        window.parent.updateNotificationCount(unreadCount);
    }
    
    // Try to update badge in opener window (if this page was opened from another page)
    if (window.opener && window.opener.updateNotificationCount) {
        window.opener.updateNotificationCount(unreadCount);
    }
}

// Accept invite from notification
async function acceptInviteFromNotification(notificationId, companyId) {
    try {
        const token = getAuthToken();
        const response = await fetch(`${API_BASE_URL}/companies/${companyId}/acceptInvite`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });

        const data = await response.json();

        if (response.ok) {
            alert('Bạn đã tham gia công ty thành công!');
            
            // Update user role in localStorage
            await updateUserInfoAfterRoleChange();
            
            // Remove the invite notification and replace with success notification
            notifications = notifications.filter(n => n.id !== notificationId);
            
            const successNotification = {
                id: Date.now(),
                type: 'system',
                title: 'Tham gia công ty thành công',
                message: `Bạn đã tham gia công ty thành công với vai trò ${data.company_user.role_in_company}.`,
                date: new Date().toISOString(),
                read: false,
                priority: 'high'
            };
            notifications.unshift(successNotification);
            
            filteredNotifications = [...notifications];
            renderNotifications();
            updateNotificationBadge();
            
            // Reload notifications instead of reloading the entire page
            console.log('🔄 Reloading notifications after accepting invite...');
            setTimeout(async () => {
                await loadNotifications();
            }, 1000);
        } else {
            alert(data.error || 'Có lỗi xảy ra khi chấp nhận lời mời');
        }
    } catch (error) {
        console.error('Lỗi khi chấp nhận lời mời:', error);
        alert('Có lỗi xảy ra khi chấp nhận lời mời');
    }
}

// Update user info after role change
async function updateUserInfoAfterRoleChange() {
    try {
        const token = getAuthToken();
        const response = await fetch(`${API_BASE_URL}/profile`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });

        if (response.ok) {
            const userData = await response.json();
            // Merge profile into existing currentUser instead of overwriting the whole object
            // The /profile endpoint returns { profile: { ... } } so we should preserve
            // existing top-level user fields (like `role`) which are stored in `currentUser`.
            const existingStr = localStorage.getItem('currentUser');
            let existing = {};
            try {
                existing = existingStr ? JSON.parse(existingStr) : {};
            } catch (e) {
                existing = {};
            }

            // If API returned a nested profile, attach it to existing.currentUser.profile
            if (userData && userData.profile) {
                existing.profile = userData.profile;
            } else {
                // fallback: if API returns a user object directly, merge keys
                Object.assign(existing, userData || {});
            }

            localStorage.setItem('currentUser', JSON.stringify(existing));
            console.log('✅ User profile merged into currentUser (role preserved):', existing);
        }
    } catch (error) {
        console.error('Error updating user info:', error);
    }
}

// Decline invite from notification
async function declineInviteFromNotification(notificationId, companyId) {
    if (confirm('Bạn có chắc chắn muốn từ chối lời mời này?')) {
        // For now, just remove from notifications
        // In the future, you can implement API call to update status
        notifications = notifications.filter(n => n.id !== notificationId);
        filteredNotifications = [...notifications];
        renderNotifications();
        updateNotificationBadge();
        alert('Đã từ chối lời mời');
    }
}

// Mark as read
function markAsRead(notificationId) {
    const notification = notifications.find(n => n.id === notificationId);
    if (notification) {
        notification.read = true;
        filteredNotifications = [...notifications];
        renderNotifications();
        updateNotificationBadge();
    }
}

// Render company invites
function renderCompanyInvites() {
    const invitesList = document.getElementById('invites-list');
    
    console.log('🎨 Rendering company invites:', companyInvites);
    
    if (companyInvites.length === 0) {
        invitesList.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-envelope-open"></i>
                <h3>Không có lời mời nào</h3>
                <p>Bạn chưa có lời mời tham gia công ty nào</p>
            </div>
        `;
        return;
    }
    
    invitesList.innerHTML = companyInvites.map(invite => {
        console.log('🔍 Processing invite:', invite);
        console.log('🔍 Invite keys:', Object.keys(invite));
        
        // Laravel Eloquent relationship sẽ trả về company object
        const company = invite.company || {};
        console.log('🔍 Company data:', company);
        
        // Lấy từ trường company_name trong database
        const companyName = company.company_name || company.name || 'Công ty không xác định';
        const companyDesc = company.description || 'Không có mô tả';
        const roleName = invite.role_in_company || 'Member';
        const inviteDate = invite.created_at || new Date().toISOString();
        const companyId = invite.company_id;
        
        console.log('🏢 Company Name:', companyName);
        console.log('📝 Company Desc:', companyDesc);
        console.log('👤 Role:', roleName);
        console.log('🆔 Company ID:', companyId);
        
        return `
            <div class="invite-card" data-invite-id="${invite.id}">
                <div class="invite-header">
                    <div class="company-info">
                        <h3>${companyName}</h3>
                        <p class="company-desc">${companyDesc}</p>
                    </div>
                    <div class="invite-actions">
                        <button class="btn-accept" onclick="acceptInvite(${companyId})">
                            <i class="fas fa-check"></i> Chấp nhận
                        </button>
                        <button class="btn-decline" onclick="declineInvite(${companyId})">
                            <i class="fas fa-times"></i> Từ chối
                        </button>
                    </div>
                </div>
                <div class="invite-details">
                    <span class="role-badge role-${roleName.toLowerCase()}">${roleName}</span>
                    <span class="invite-date">${formatDate(inviteDate)}</span>
                </div>
            </div>
        `;
    }).join('');
}

// Accept company invite
async function acceptInvite(companyId) {
    try {
        const token = getAuthToken();
        const response = await fetch(`${API_BASE_URL}/companies/${companyId}/acceptInvite`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });

        const data = await response.json();

        if (response.ok) {
            alert('Bạn đã tham gia công ty thành công!');
            
            // Update user role in localStorage
            await updateUserInfoAfterRoleChange();
            
            // Remove the accepted invite from the list
            companyInvites = companyInvites.filter(invite => invite.company_id !== companyId);
            renderCompanyInvites();
            
            // Add notification about joining company
            const notification = {
                id: Date.now(),
                type: 'invite',
                title: 'Tham gia công ty thành công',
                message: `Bạn đã tham gia công ty ${data.company_user.company.name || 'thành công'}.`,
                date: new Date().toISOString(),
                read: false,
                priority: 'high'
            };
            notifications.unshift(notification);
            filteredNotifications = [...notifications];
            renderNotifications();
            
            // Reload notifications instead of reloading the entire page
            console.log('🔄 Reloading notifications after accepting invite...');
            setTimeout(async () => {
                await loadNotifications();
            }, 1000);
        } else {
            alert(data.error || 'Có lỗi xảy ra khi chấp nhận lời mời');
        }
    } catch (error) {
        console.error('Lỗi khi chấp nhận lời mời:', error);
        alert('Có lỗi xảy ra khi chấp nhận lời mời');
    }
}

// Decline company invite (optional - you can implement this later)
async function declineInvite(companyId) {
    if (confirm('Bạn có chắc chắn muốn từ chối lời mời này?')) {
        // For now, just remove from the UI
        // You can implement DELETE API later if needed
        companyInvites = companyInvites.filter(invite => invite.company_id !== companyId);
        renderCompanyInvites();
        alert('Đã từ chối lời mời');
    }
}

// Delete notification
function deleteNotification(notificationId) {
    if (confirm('Bạn có chắc chắn muốn xóa thông báo này?')) {
        notifications = notifications.filter(n => n.id !== notificationId);
        filteredNotifications = [...notifications];
        renderNotifications();
        updateNotificationBadge();
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadNotifications();
    
    // Poll for new notifications every 30 seconds
    setInterval(function() {
        console.log('🔄 Checking for new notifications...');
        
        // Remove old invite notifications before loading new ones
        notifications = notifications.filter(n => n.type !== 'invite');
        
        loadAndConvertCompanyInvites().then(() => {
            // Combine and sort all notifications by date
            filteredNotifications = [...notifications].sort((a, b) => new Date(b.date) - new Date(a.date));
            renderNotifications();
            updateNotificationBadge();
        });
    }, 30000);
});
