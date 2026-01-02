<!DOCTYPE html>
<html lang="vi">
<head>
    @include('layouts.head')
    <link href="{{ asset('css/shared/header.css') }}" rel="stylesheet">
    <link href="{{ asset('css/shared/footer.css') }}" rel="stylesheet">
    <link href="{{ asset('css/roles/candidate/chat.css') }}" rel="stylesheet">
    <title>Chat - WebCV</title>
</head>
<body>
    @include('layouts.header')
    
    <div class="chat-container">
        <div class="chat-sidebar">
            <div class="chat-sidebar-header">
                <h2>Tin nhắn</h2>
            </div>
            <div class="conversations-list" id="conversations-list">
                <div class="loading-state">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Đang tải...</p>
                </div>
            </div>
        </div>
        
        <div class="chat-main">
            <div class="chat-empty" id="chat-empty">
                <i class="fas fa-comments"></i>
                <h3>Chọn cuộc trò chuyện để bắt đầu</h3>
            </div>
            
            <div class="chat-window" id="chat-window" style="display: none;">
                <div class="chat-header" id="chat-header">
                    <div class="chat-header-info">
                        <div class="chat-avatar" id="chat-avatar">U</div>
                        <div>
                            <h3 id="chat-other-user-name">Tên người dùng</h3>
                            <span class="chat-status" id="chat-status">Đang hoạt động</span>
                        </div>
                    </div>
                </div>
                
                <div class="chat-messages" id="chat-messages">
                    <!-- Messages will be loaded here -->
                </div>
                
                <div class="chat-input-container">
                    <form id="chat-form">
                        <input 
                            type="text" 
                            id="chat-input" 
                            placeholder="Nhập tin nhắn..." 
                            autocomplete="off"
                        />
                        <button type="submit" class="send-btn">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Pusher for real-time chat -->
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.15.3/dist/echo.iife.min.js"></script>
    
    <script>
        window.CHAT_API_BASE = '{{ url("/api/chat") }}';
        window.CSRF_TOKEN = '{{ csrf_token() }}';
        
        // Pusher configuration (use config() instead of env() for cached config)
        window.PUSHER_APP_KEY = '{{ config("broadcasting.connections.pusher.key", "") }}';
        window.PUSHER_APP_CLUSTER = '{{ config("broadcasting.connections.pusher.options.cluster", "ap1") }}';
        window.BROADCAST_DRIVER = '{{ config("broadcasting.default", "null") }}';
        window.APP_URL = '{{ url("/") }}';
    </script>
    <script src="{{ asset('js/candidate/chat.js') }}"></script>
    
    @include('layouts.footer')
</body>
</html>

