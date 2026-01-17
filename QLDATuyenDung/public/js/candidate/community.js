// Switch tabs
function switchTab(tabName) {
    // Update tab buttons
    document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
    event.target.classList.add('active');

    // Update tab content
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    document.getElementById(`${tabName}-tab`).classList.add('active');
}

// Create post
function createPost() {
    const title = prompt('Tiêu đề bài viết:');
    if (title) {
        const content = prompt('Nội dung bài viết:');
        if (content) {
            alert('Bài viết đã được tạo thành công!');
        }
    }
}

// Send message
function sendMessage() {
    const input = document.getElementById('chat-input');
    const message = input.value.trim();
    
    if (message) {
        const messagesContainer = document.getElementById('chat-messages');
        const currentTime = new Date().toLocaleTimeString('vi-VN', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
        
        const messageElement = document.createElement('div');
        messageElement.className = 'message own';
        messageElement.innerHTML = `
            <div class="message-avatar">A</div>
            <div class="message-content">
                ${message}
                <div class="message-time">${currentTime}</div>
            </div>
        `;
        
        messagesContainer.appendChild(messageElement);
        input.value = '';
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
        
        // Simulate HR response
        setTimeout(() => {
            const hrResponse = document.createElement('div');
            hrResponse.className = 'message';
            hrResponse.innerHTML = `
                <div class="message-avatar">HR</div>
                <div class="message-content">
                    Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi sớm nhất có thể.
                    <div class="message-time">${new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })}</div>
                </div>
            `;
            messagesContainer.appendChild(hrResponse);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }, 1000);
    }
}

// Handle key press in chat input
function handleKeyPress(event) {
    if (event.key === 'Enter') {
        sendMessage();
    }
}

// Write review
function writeReview() {
    const company = prompt('Tên công ty:');
    if (company) {
        const rating = prompt('Đánh giá (1-5 sao):');
        if (rating) {
            const content = prompt('Nội dung đánh giá:');
            if (content) {
                alert('Đánh giá đã được gửi thành công!');
            }
        }
    }
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // Check authentication
    const currentUser = JSON.parse(localStorage.getItem('currentUser'));
    if (!currentUser) {
        window.location.href = '/login';
        return;
    }
});
