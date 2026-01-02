/**
 * AI Career Advisor Chat Widget
 * Tích hợp vào messaging system
 */

class AiCareerAdvisor {
    constructor() {
        this.conversationId = null;
        this.aiBotId = null;
        this.isLoading = false;
        this.init();
    }

    init() {
        this.createChatButton();
        this.createChatWindow();
        this.attachEventListeners();
    }

    /**
     * Tạo nút floating để mở chat
     */
    createChatButton() {
        const button = document.createElement('button');
        button.id = 'ai-chat-btn';
        button.innerHTML = `
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                <path d="M8 12h.01M12 12h.01M16 12h.01"></path>
            </svg>
            <span>AI Tư vấn</span>
        `;
        button.style.cssText = `
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 14px 24px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
            display: flex;
            align-items: center;
            gap: 8px;
            z-index: 9998;
            transition: all 0.3s ease;
        `;
        
        button.onmouseover = () => {
            button.style.transform = 'translateY(-2px)';
            button.style.boxShadow = '0 6px 25px rgba(102, 126, 234, 0.5)';
        };
        
        button.onmouseout = () => {
            button.style.transform = 'translateY(0)';
            button.style.boxShadow = '0 4px 20px rgba(102, 126, 234, 0.4)';
        };

        document.body.appendChild(button);
    }

    /**
     * Tạo cửa sổ chat
     */
    createChatWindow() {
        const chatWindow = document.createElement('div');
        chatWindow.id = 'ai-chat-window';
        chatWindow.style.cssText = `
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 400px;
            height: 600px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
            display: none;
            flex-direction: column;
            z-index: 9999;
            overflow: hidden;
        `;

        chatWindow.innerHTML = `
            <div class="chat-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3 style="margin: 0; font-size: 18px; font-weight: 600;">🤖 AI Career Advisor</h3>
                    <p style="margin: 4px 0 0 0; font-size: 12px; opacity: 0.9;">Trợ lý tư vấn nghề nghiệp của bạn</p>
                </div>
                <button id="close-chat" style="background: rgba(255,255,255,0.2); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 20px; display: flex; align-items: center; justify-content: center;">×</button>
            </div>
            
            <div id="chat-messages" style="flex: 1; overflow-y: auto; padding: 20px; background: #f8f9fa;">
                <div class="loading-init" style="text-align: center; color: #6c757d; padding: 40px 20px;">
                    <div class="spinner" style="width: 40px; height: 40px; border: 3px solid #f3f3f3; border-top: 3px solid #667eea; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 16px;"></div>
                    <p>Đang khởi tạo AI Advisor...</p>
                </div>
            </div>
            
            <div class="chat-input-container" style="padding: 16px; border-top: 1px solid #e9ecef; background: white;">
                <div style="display: flex; gap: 8px;">
                    <input type="text" id="chat-input" placeholder="Đặt câu hỏi về nghề nghiệp..." style="flex: 1; padding: 12px 16px; border: 1px solid #dee2e6; border-radius: 24px; font-size: 14px; outline: none;" />
                    <button id="send-btn" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 50%; width: 44px; height: 44px; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="22" y1="2" x2="11" y2="13"></line>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                    </button>
                </div>
                <div id="quick-questions" style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px;">
                    <!-- Quick questions will be added here -->
                </div>
            </div>
        `;

        document.body.appendChild(chatWindow);

        // Add spin animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            #chat-messages::-webkit-scrollbar {
                width: 6px;
            }
            
            #chat-messages::-webkit-scrollbar-track {
                background: #f1f1f1;
            }
            
            #chat-messages::-webkit-scrollbar-thumb {
                background: #888;
                border-radius: 3px;
            }
            
            #chat-messages::-webkit-scrollbar-thumb:hover {
                background: #555;
            }
        `;
        document.head.appendChild(style);
    }

    /**
     * Attach event listeners
     */
    attachEventListeners() {
        const btn = document.getElementById('ai-chat-btn');
        const chatWindow = document.getElementById('ai-chat-window');
        const closeBtn = document.getElementById('close-chat');
        const sendBtn = document.getElementById('send-btn');
        const input = document.getElementById('chat-input');

        btn.addEventListener('click', () => this.openChat());
        closeBtn.addEventListener('click', () => this.closeChat());
        sendBtn.addEventListener('click', () => this.sendMessage());
        
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !this.isLoading) {
                this.sendMessage();
            }
        });
    }

    /**
     * Mở chat window
     */
    async openChat() {
        const chatWindow = document.getElementById('ai-chat-window');
        chatWindow.style.display = 'flex';
        document.getElementById('ai-chat-btn').style.display = 'none';

        // Nếu chưa khởi tạo conversation, gọi API
        if (!this.conversationId) {
            await this.startConversation();
        }
    }

    /**
     * Đóng chat window
     */
    closeChat() {
        const chatWindow = document.getElementById('ai-chat-window');
        chatWindow.style.display = 'none';
        document.getElementById('ai-chat-btn').style.display = 'flex';
    }

    /**
     * Khởi tạo conversation với AI 
     */
    async startConversation() {
        try {
            const token = localStorage.getItem('authToken');
            
            if (!token) {
                this.showError('Vui lòng đăng nhập để sử dụng AI Advisor');
                return;
            }

            console.log('Starting AI conversation...');
            const response = await fetch('/api/chat/ai/start', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });

            console.log('Response status:', response.status);
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                console.error('API Error:', errorData);
                throw new Error(errorData.message || `HTTP ${response.status}`);
            }

            const data = await response.json();
            console.log('AI Chat started:', data);
            
            this.conversationId = data.conversation_id;
            this.aiBotId = data.ai_bot_id;

            // Hiển thị messages
            this.renderMessages(data.messages);
            this.showQuickQuestions();

        } catch (error) {
            console.error('Error starting conversation:', error);
            this.showError('Không thể kết nối với AI: ' + error.message + '<br><small>Kiểm tra console (F12) để biết chi tiết</small>');
        }
    }

    /**
     * Gửi message - VỚI RETRY LOGIC
     */
    async sendMessage(retryCount = 0) {
        const input = document.getElementById('chat-input');
        const message = input.value.trim();

        if (!message || this.isLoading) return;

        // Disable input
        this.isLoading = true;
        input.disabled = true;
        input.value = '';

        // Hiển thị user message
        this.addMessage(message, false);

        // Gọi API gửi message
        try {
            const token = localStorage.getItem('authToken');
            const response = await fetch('/api/chat/ai/message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + token,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    conversation_id: this.conversationId,
                    message: message
                })
            });

            if (!response.ok) {
                if (response.status >= 500 && retryCount < 1) {
                    console.log('Retrying API call...');
                    await new Promise(resolve => setTimeout(resolve, 1000));
                    this.sendMessageRetry(message, retryCount + 1);
                    return;
                }
                throw new Error('API returned error: ' + response.status);
            }

            const data = await response.json();
            
            // Hiển thị AI response
            this.addMessage(data.ai_message.content, true);

        } catch (error) {
            console.error('Error sending message:', error);
            const fallbackMsg = this.getSmartFallback(message);
            this.addMessage(fallbackMsg, true);
            
        } finally {
            this.isLoading = false;
            input.disabled = false;
            input.focus();
        }
    }

    /**
     * Retry helper
     */
    async sendMessageRetry(message, retryCount) {
        this.isLoading = true;
        
        try {
            const token = localStorage.getItem('authToken');
            const response = await fetch('/api/chat/ai/message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + token,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    conversation_id: this.conversationId,
                    message: message
                })
            });

            if (!response.ok) {
                throw new Error('Retry failed');
            }

            const data = await response.json();
            this.addMessage(data.ai_message.content, true);

        } catch (error) {
            const fallbackMsg = this.getSmartFallback(message);
            this.addMessage(fallbackMsg, true);
        } finally {
            this.isLoading = false;
            document.getElementById('chat-input').disabled = false;
            document.getElementById('chat-input').focus();
        }
    }

    /**
     * Smart fallback khi API fail
     */
    getSmartFallback(message) {
        const msg = message.toLowerCase();
        
        if (msg.includes('backend') || msg.includes('developer') || msg.includes('lập trình')) {
            return "**Lộ trình Backend Developer 2025:**\n\n" +
                   "✓ **Nền tảng:** PHP/Python/Node.js\n" +
                   "✓ **Framework:** Laravel (PHP), Django (Python), Express (Node)\n" +
                   "✓ **Database:** MySQL, PostgreSQL, MongoDB\n" +
                   "✓ **API:** RESTful, GraphQL\n" +
                   "✓ **DevOps:** Docker, Git, CI/CD basics\n\n" +
                   "Bạn muốn tìm hiểu sâu hơn về phần nào? 😊";
        }
        
        if (msg.includes('phỏng vấn')) {
            return "**Tips phỏng vấn thành công:**\n\n" +
                   "✓ Research kỹ về công ty trước\n" +
                   "✓ Chuẩn bị câu trả lời cho các câu hỏi phổ biến\n" +
                   "✓ Dress code phù hợp, đúng giờ\n" +
                   "✓ Tự tin nhưng khiêm tốn\n" +
                   "✓ Chuẩn bị câu hỏi ngược cho interviewer\n\n" +
                   "Chúc bạn phỏng vấn thành công! 🎯";
        }
        
        return "Xin lỗi, tôi đang gặp chút vấn đề kỹ thuật! 😅\n\n" +
               "Nhưng tôi vẫn có thể giúp bạn với:\n" +
               "✓ Lộ trình học tập các vị trí IT\n" +
               "✓ Tips phỏng vấn & viết CV\n" +
               "✓ Tư vấn chuyển đổi nghề nghiệp\n\n" +
               "Hãy thử hỏi lại hoặc hỏi cụ thể hơn nhé!";
    }

    /**
     * Render messages
     */
    renderMessages(messages) {
        const container = document.getElementById('chat-messages');
        container.innerHTML = '';

        messages.forEach(msg => {
            this.addMessage(msg.content, msg.is_ai, false, false);
        });
    }

    /**
     * Add message to chat - CẢI TIẾN FORMAT
     */
    addMessage(content, isAi = false, isError = false, scroll = true) {
        const container = document.getElementById('chat-messages');
        const messageDiv = document.createElement('div');
        
        messageDiv.style.cssText = `
            display: flex;
            justify-content: ${isAi ? 'flex-start' : 'flex-end'};
            margin-bottom: 16px;
            animation: fadeIn 0.3s ease;
        `;

        const bubble = document.createElement('div');
        bubble.style.cssText = `
            max-width: 75%;
            padding: 12px 16px;
            border-radius: 16px;
            ${isAi 
                ? 'background: white; color: #333; box-shadow: 0 2px 8px rgba(0,0,0,0.1);' 
                : 'background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;'
            }
            ${isError ? 'background: #dc3545; color: white;' : ''}
            font-size: 14px;
            line-height: 1.6;
            white-space: pre-wrap;
            word-wrap: break-word;
        `;
        
        // Format content nếu là AI response
        if (isAi && !isError) {
            bubble.innerHTML = this.formatAiResponse(content);
        } else {
            bubble.textContent = content;
        }
        
        messageDiv.appendChild(bubble);
        container.appendChild(messageDiv);

        if (scroll) {
            container.scrollTop = container.scrollHeight;
        }
    }

    /**
     * Format AI response với markdown-like syntax
     */
    formatAiResponse(text) {
        let html = text;
        
        // Bold text (**text**)
        html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        
        // Bullet points (✓)
        html = html.replace(/^✓\s+(.+)$/gm, '<div style="margin: 6px 0;"><span style="color: #10b981; margin-right: 8px;">✓</span><span>$1</span></div>');
        
        // Numbered lists
        html = html.replace(/^(\d+)\.\s+(.+)$/gm, '<div style="margin: 6px 0;"><strong style="color: #667eea; margin-right: 8px;">$1.</strong><span>$2</span></div>');
        
        // Money emoji format (💰)
        html = html.replace(/^💰\s+(.+)$/gm, '<div style="margin: 6px 0; color: #f59e0b;"><span style="margin-right: 8px;">💰</span><span>$1</span></div>');
        
        // Headings (###)
        html = html.replace(/^###\s+(.+)$/gm, '<h4 style="font-size: 15px; font-weight: 600; margin: 12px 0 8px 0; color: #667eea;">$1</h4>');
        
        // Line breaks
        html = html.replace(/\n\n/g, '<br/><br/>');
        html = html.replace(/\n/g, '<br/>');
        
        return html;
    }

    /**
     * Hiển thị quick questions
     */
    showQuickQuestions() {
        const container = document.getElementById('quick-questions');
        const questions = [
            'Lộ trình Backend Developer',
            'Skills cần học năm 2025',
            'Tips phỏng vấn',
            'Cách viết CV tốt'
        ];

        questions.forEach(q => {
            const btn = document.createElement('button');
            btn.textContent = q;
            btn.style.cssText = `
                padding: 6px 12px;
                border: 1px solid #dee2e6;
                border-radius: 16px;
                background: white;
                color: #667eea;
                font-size: 12px;
                cursor: pointer;
                transition: all 0.2s;
            `;
            
            btn.onmouseover = () => {
                btn.style.background = '#667eea';
                btn.style.color = 'white';
            };
            
            btn.onmouseout = () => {
                btn.style.background = 'white';
                btn.style.color = '#667eea';
            };
            
            btn.onclick = () => {
                document.getElementById('chat-input').value = q;
                this.sendMessage();
            };
            
            container.appendChild(btn);
        });
    }

    /**
     * Show error
     */
    showError(message) {
        const container = document.getElementById('chat-messages');
        container.innerHTML = `
            <div style="text-align: center; color: #dc3545; padding: 40px 20px;">
                <p>${message}</p>
            </div>
        `;
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        new AiCareerAdvisor();
    });
} else {
    new AiCareerAdvisor();
}
