const API_BASE = window.CHAT_API_BASE || "/api/chat";
let currentConversationId = null;
let currentOtherUser = null;
let messages = [];
let isLoadingMessages = false;
let echo = null;
let currentChannel = null;
let echoInitialized = false;
let echoInitializing = false;
let pollingInterval = null; // For fallback polling when realtime is not available

// Get auth headers
function getAuthHeaders() {
    const token =
        localStorage.getItem("authToken") || localStorage.getItem("token");
    const csrfToken =
        window.CSRF_TOKEN ||
        document.querySelector('meta[name="csrf-token"]')?.content;

    // Debug: Log authentication info
    console.log("🔐 Auth Headers Debug:", {
        token: token ? token.substring(0, 20) + "..." : "Missing",
        csrfToken: csrfToken ? csrfToken.substring(0, 10) + "..." : "Missing",
        cookies: document.cookie ? "Present" : "Missing",
        cookieCount: document.cookie.split(";").filter((c) => c.trim()).length,
        user: localStorage.getItem("currentUser")
            ? "Logged in"
            : "Not logged in",
    });

    // Get all cookies for debugging
    const cookies = document.cookie.split(";").reduce((acc, cookie) => {
        const [key, value] = cookie.trim().split("=");
        if (key) acc[key] = value ? value.substring(0, 20) + "..." : "empty";
        return acc;
    }, {});
    console.log("🍪 Cookies:", cookies);

    const headers = {
        Accept: "application/json",
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
    };

    // ✅ CRITICAL: Add Authorization header
    if (token) {
        headers["Authorization"] = "Bearer " + token;
        console.log(
            "✅ Authorization header added:",
            "Bearer " + token.substring(0, 20) + "..."
        );
    } else {
        console.warn("⚠️ No token found in localStorage!");
    }

    if (csrfToken) {
        headers["X-CSRF-TOKEN"] = csrfToken;
    } else {
        console.warn("⚠️ No CSRF token found!");
    }

    console.log("📤 Final headers being sent:", Object.keys(headers));
    return headers;
}

// Initialize Laravel Echo for real-time chat
function initializeEcho() {
    if (window.BROADCAST_DRIVER === "pusher" && window.PUSHER_APP_KEY) {
        if (echoInitializing) {
            console.log("⏳ Echo is already initializing...");
            return;
        }
        echoInitializing = true;

        try {
            // Initialize Pusher
            window.Pusher = Pusher;

            // Initialize Laravel Echo
            // ✅ FIX: Ensure authEndpoint uses full URL with protocol
            const authEndpoint = window.APP_URL.startsWith("http")
                ? window.APP_URL + "/broadcasting/auth"
                : window.location.origin + "/broadcasting/auth";

            window.Echo = new Echo({
                broadcaster: "pusher",
                key: window.PUSHER_APP_KEY,
                cluster: window.PUSHER_APP_CLUSTER || "ap1",
                encrypted: true,
                forceTLS: true,
                authEndpoint: authEndpoint,
                auth: {
                    headers: function () {
                        // Call getAuthHeaders() each time to get fresh token/CSRF
                        const headers = getAuthHeaders();
                        console.log(
                            "📤 Echo auth headers:",
                            Object.keys(headers)
                        );
                        return headers;
                    },
                },
                // CRITICAL: Enable credentials to send cookies (laravel_session + XSRF)
                withCredentials: true,
                enabledTransports: ["ws", "wss"],
                disableStats: false,
            });

            echo = window.Echo;
            console.log("✅ Real-time chat initialized with Pusher");
            console.log("📡 Pusher config:", {
                key: window.PUSHER_APP_KEY
                    ? window.PUSHER_APP_KEY.substring(0, 10) + "..."
                    : "not set",
                cluster: window.PUSHER_APP_CLUSTER,
                driver: window.BROADCAST_DRIVER,
                authEndpoint: window.APP_URL + "/broadcasting/auth",
                withCredentials: true,
            });

            // Log connection events
            echo.connector.pusher.connection.bind("connected", () => {
                echoInitialized = true;
                console.log("✅ Pusher connected successfully!");

                // Stop polling since realtime is now available
                stopPollingMessages();

                // Retry subscription if there's a pending conversation
                if (currentConversationId && !currentChannel) {
                    console.log("🔄 Retrying subscription after connection...");
                    setTimeout(() => {
                        subscribeToConversation(currentConversationId);
                    }, 500);
                }
            });
            echo.connector.pusher.connection.bind("disconnected", () => {
                echoInitialized = false;
                console.warn("⚠️ Pusher disconnected!");
            });
            echo.connector.pusher.connection.bind("error", (err) => {
                console.error("❌ Pusher connection error:", err);
            });
            echo.connector.pusher.connection.bind("state_change", (states) => {
                console.log(
                    "🔄 Pusher state change:",
                    states.previous,
                    "->",
                    states.current
                );
                if (states.current === "connected") {
                    echoInitialized = true;
                }
            });
        } catch (error) {
            console.error("Error initializing Echo:", error);
            echoInitializing = false;
        }
    } else {
        console.log(
            "Real-time chat disabled (BROADCAST_DRIVER is not pusher or PUSHER_APP_KEY not set)",
            {
                driver: window.BROADCAST_DRIVER,
                hasKey: !!window.PUSHER_APP_KEY,
            }
        );
    }
}

// Leave current channel and join new one
function subscribeToConversation(conversationId) {
    // Check if Echo is ready
    if (!echo) {
        console.warn("⚠️ Echo not initialized yet, waiting...");
        // Wait for Echo to initialize
        setTimeout(() => {
            if (echo) {
                subscribeToConversation(conversationId);
            } else {
                console.error("❌ Echo failed to initialize, cannot subscribe");
            }
        }, 1000);
        return;
    }

    // Check if Pusher is connected
    if (
        !echoInitialized &&
        echo.connector.pusher.connection.state !== "connected"
    ) {
        console.warn("⚠️ Pusher not connected yet, waiting...");
        // Wait for connection
        const checkConnection = setInterval(() => {
            if (
                echoInitialized ||
                echo.connector.pusher.connection.state === "connected"
            ) {
                clearInterval(checkConnection);
                subscribeToConversation(conversationId);
            }
        }, 500);

        // Timeout after 10 seconds
        setTimeout(() => {
            clearInterval(checkConnection);
            if (!echoInitialized) {
                console.error("❌ Pusher connection timeout, cannot subscribe");
            }
        }, 10000);
        return;
    }

    // Leave previous channel if exists
    if (currentChannel && echo) {
        echo.leave(`conversation.${currentConversationId}`);
        currentChannel = null;
    }

    // Stop polling for previous conversation
    stopPollingMessages();

    // Join new conversation channel
    if (echo && conversationId) {
        try {
            console.log(`🔄 Subscribing to conversation.${conversationId}...`);

            currentChannel = echo
                .private(`conversation.${conversationId}`)
                .listen(".message.sent", (e) => {
                    console.log("✅ New message received via real-time:", e);

                    // Get current user ID
                    const currentUser = JSON.parse(
                        localStorage.getItem("currentUser") || "{}"
                    );
                    const isOwnMessage =
                        e.sender && e.sender.id === currentUser.id;

                    // ✅ FIX: Improved messageExists check to handle different ID formats
                    const messageExists = messages.some((m) => {
                        // Compare by ID (handle different ID formats: id, id__message, id_message)
                        const msgId = m.id || m.id__message || m.id_message;
                        const eventId = e.id || e.id__message || e.id_message;
                        if (msgId && eventId) {
                            return msgId.toString() === eventId.toString();
                        }
                        // Fallback: compare by content and timestamp if IDs don't match
                        const msgTime = m.send_at
                            ? new Date(m.send_at).getTime()
                            : 0;
                        const eventTime = e.send_at
                            ? new Date(e.send_at).getTime()
                            : 0;
                        return (
                            m.content === e.content &&
                            Math.abs(msgTime - eventTime) < 1000
                        ); // Within 1 second
                    });

                    if (!messageExists) {
                        messages.push({
                            id: e.id || e.id__message || e.id_message,
                            content: e.content,
                            sender: e.sender,
                            send_at: e.send_at,
                            is_own: isOwnMessage,
                        });
                        renderMessages(messages);
                        scrollToBottom();

                        // Update conversations list if not in current conversation
                        if (currentConversationId !== conversationId) {
                            loadConversations();
                        }
                    } else {
                        console.log(
                            "✅ Message already exists, skipping real-time event"
                        );
                    }
                });

            console.log(`✅ Subscribed to conversation.${conversationId}`);

            // Add subscription success handler
            currentChannel.subscribed(() => {
                console.log(
                    `✅ Successfully subscribed to conversation.${conversationId}`
                );
            });

            // Add error handler for channel subscription
            currentChannel.error((error) => {
                console.error("❌ Channel subscription error:", error);
                if (error.status === 403) {
                    console.error(
                        "❌ Authentication failed (403). Debug info:"
                    );
                    console.error(
                        "   - Token:",
                        localStorage.getItem("authToken") ||
                            localStorage.getItem("token")
                            ? "Exists"
                            : "Missing"
                    );
                    console.error(
                        "   - CSRF Token:",
                        window.CSRF_TOKEN ? "Exists" : "Missing"
                    );
                    console.error(
                        "   - User logged in:",
                        localStorage.getItem("currentUser") ? "Yes" : "No"
                    );
                    console.error(
                        "   - Cookies:",
                        document.cookie
                            ? "Present (" +
                                  document.cookie.split(";").length +
                                  " cookies)"
                            : "Missing"
                    );
                    console.error(
                        "   - Session cookie:",
                        document.cookie.includes("laravel_session") ||
                            document.cookie.includes("_session")
                            ? "Found"
                            : "Not found"
                    );
                    console.error(
                        "   - XSRF cookie:",
                        document.cookie.includes("XSRF-TOKEN")
                            ? "Found"
                            : "Not found"
                    );
                    console.error("   - Full error:", error);

                    // Retry after 2 seconds
                    console.log("🔄 Retrying subscription in 2 seconds...");
                    setTimeout(() => {
                        subscribeToConversation(conversationId);
                    }, 2000);

                    // Try to fetch auth status from server
                    fetch(window.APP_URL + "/api/chat/conversations", {
                        method: "GET",
                        headers: getAuthHeaders(),
                        credentials: "include",
                    })
                        .then((res) => {
                            console.log(
                                "🔍 Server auth check response status:",
                                res.status
                            );
                            return res.json();
                        })
                        .then((data) => {
                            console.log("🔍 Server auth status:", data);
                        })
                        .catch((err) => {
                            console.error(
                                "❌ Failed to check server auth:",
                                err
                            );
                        });
                } else if (error.status === 401) {
                    console.error("❌ Unauthorized (401). Please login again.");
                } else {
                    console.error("❌ Unknown error:", error);
                }
            });

            // Add connection status listeners
            echo.connector.pusher.connection.bind("connected", () => {
                console.log("Pusher connected");
            });

            echo.connector.pusher.connection.bind("disconnected", () => {
                console.log("Pusher disconnected");
            });

            echo.connector.pusher.connection.bind("error", (error) => {
                console.error("Pusher connection error:", error);
            });
        } catch (error) {
            console.error("Error subscribing to conversation:", error);
        }
    }
}

// Load conversations list
async function loadConversations() {
    try {
        const response = await fetch(`${API_BASE}/conversations`, {
            headers: getAuthHeaders(),
            credentials: "include",
        });

        if (!response.ok) {
            // Handle specific error cases
            if (response.status === 401) {
                alert("Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.");
                window.location.href = "/login";
                return;
            }

            if (response.status === 403) {
                const errorData = await response.json().catch(() => ({}));
                if (errorData.status === "suspended") {
                    alert(
                        "Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên."
                    );
                    return;
                }
                alert("Bạn không có quyền truy cập. Vui lòng đăng nhập lại.");
                window.location.href = "/login";
                return;
            }

            throw new Error(`Failed to load conversations: ${response.status}`);
        }

        const data = await response.json();
        renderConversations(data.conversations || []);
    } catch (error) {
        console.error("Error loading conversations:", error);
        document.getElementById("conversations-list").innerHTML = `
            <div class="loading-state">
                <i class="fas fa-exclamation-circle"></i>
                <p>Lỗi khi tải danh sách: ${error.message}</p>
                <button onclick="loadConversations()" style="margin-top: 10px; padding: 8px 16px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    Thử lại
                </button>
            </div>
        `;
    }
}

// Render conversations list
function renderConversations(conversations) {
    const container = document.getElementById("conversations-list");

    if (conversations.length === 0) {
        container.innerHTML = `
            <div class="loading-state">
                <i class="fas fa-comments"></i>
                <p>Chưa có cuộc trò chuyện nào</p>
            </div>
        `;
        return;
    }

    container.innerHTML = conversations
        .map((conv) => {
            const lastMsg = conv.last_message;
            const time = lastMsg ? formatTime(lastMsg.send_at) : "";
            const unreadBadge =
                conv.unread_count > 0
                    ? `<span class="conversation-unread">${conv.unread_count}</span>`
                    : "";

            const otherUserJson = JSON.stringify(conv.other_user).replace(
                /'/g,
                "&#39;"
            );
            return `
            <div class="conversation-item" data-conversation-id="${
                conv.id
            }" data-other-user='${otherUserJson}' onclick="handleOpenConversation(${
                conv.id
            })">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div style="flex: 1;">
                        <div class="conversation-user-name">${escapeHtml(
                            conv.other_user.name
                        )}</div>
                        <div class="conversation-last-message">${
                            lastMsg
                                ? escapeHtml(lastMsg.content)
                                : "Chưa có tin nhắn"
                        }</div>
                        <div class="conversation-time">${time}</div>
                    </div>
                    ${unreadBadge}
                </div>
            </div>
        `;
        })
        .join("");
}

// Handle open conversation from click
function handleOpenConversation(conversationId) {
    const item = document.querySelector(
        `[data-conversation-id="${conversationId}"]`
    );
    if (item) {
        const otherUserJson = item.getAttribute("data-other-user");
        if (otherUserJson) {
            try {
                const otherUser = JSON.parse(
                    otherUserJson.replace(/&#39;/g, "'")
                );
                openConversation(conversationId, otherUser);
            } catch (e) {
                console.error("Error parsing other user:", e);
                // Fallback: load conversation without other user info
                openConversationById(conversationId);
            }
        } else {
            openConversationById(conversationId);
        }
    }
}

// Open conversation by ID only (fallback)
async function openConversationById(conversationId) {
    try {
        const response = await fetch(
            `${API_BASE}/conversations/${conversationId}/messages`,
            {
                headers: getAuthHeaders(),
                credentials: "include",
            }
        );

        if (response.ok) {
            const data = await response.json();
            if (data.messages && data.messages.length > 0) {
                // Tìm tin nhắn của user khác (không phải của mình)
                let otherUser = null;
                for (const msg of data.messages) {
                    if (!msg.is_own && msg.sender) {
                        otherUser = msg.sender;
                        break;
                    }
                }

                // Nếu không tìm thấy, thử tìm từ tin nhắn cuối cùng
                if (!otherUser) {
                    const lastMsg = data.messages[data.messages.length - 1];
                    if (!lastMsg.is_own && lastMsg.sender) {
                        otherUser = lastMsg.sender;
                    } else {
                        // Tìm ngược lại từ cuối
                        for (let i = data.messages.length - 1; i >= 0; i--) {
                            if (
                                !data.messages[i].is_own &&
                                data.messages[i].sender
                            ) {
                                otherUser = data.messages[i].sender;
                                break;
                            }
                        }
                    }
                }

                if (otherUser) {
                    openConversation(conversationId, otherUser);
                    return;
                }
            }
        }

        // If we can't get user info, still open conversation and subscribe
        currentConversationId = conversationId;
        document.getElementById("chat-empty").style.display = "none";
        document.getElementById("chat-window").style.display = "flex";
        document.getElementById("chat-other-user-name").textContent =
            "Người dùng";
        await loadMessages(conversationId);

        // ✅ CRITICAL: Subscribe even if we don't have otherUser
        if (
            !echo &&
            !echoInitializing &&
            window.BROADCAST_DRIVER === "pusher" &&
            window.PUSHER_APP_KEY
        ) {
            console.log("🔄 Initializing Echo before subscribing...");
            initializeEcho();
        }

        if (window.BROADCAST_DRIVER === "pusher" && window.PUSHER_APP_KEY) {
            subscribeToConversation(conversationId);
        } else {
            // Fallback: Poll for new messages
            startPollingMessages(conversationId);
        }
    } catch (error) {
        console.error("Error opening conversation by ID:", error);
    }
}

// Open conversation
async function openConversation(conversationId, otherUser) {
    // Stop polling for previous conversation
    stopPollingMessages();

    currentConversationId = conversationId;
    currentOtherUser = otherUser;

    // Update UI
    document.getElementById("chat-empty").style.display = "none";
    document.getElementById("chat-window").style.display = "flex";

    // Update header
    document.getElementById("chat-other-user-name").textContent =
        otherUser.name;
    document.getElementById("chat-avatar").textContent = otherUser.name
        .charAt(0)
        .toUpperCase();

    // Update active conversation
    document.querySelectorAll(".conversation-item").forEach((item) => {
        item.classList.remove("active");
        if (item.getAttribute("data-conversation-id") == conversationId) {
            item.classList.add("active");
        }
    });

    // Load messages
    await loadMessages(conversationId);

    // Ensure Echo is initialized before subscribing
    if (
        !echo &&
        !echoInitializing &&
        window.BROADCAST_DRIVER === "pusher" &&
        window.PUSHER_APP_KEY
    ) {
        console.log("🔄 Initializing Echo before subscribing...");
        initializeEcho();
    }

    // Subscribe to real-time updates (will wait if Echo not ready)
    if (window.BROADCAST_DRIVER === "pusher" && window.PUSHER_APP_KEY) {
        subscribeToConversation(conversationId);
    } else {
        // Fallback: Poll for new messages every 3 seconds if realtime is not available
        startPollingMessages(conversationId);
    }

    // Scroll to bottom
    scrollToBottom();
}

// Load messages
async function loadMessages(conversationId, page = 1) {
    if (isLoadingMessages) return;
    isLoadingMessages = true;

    try {
        const response = await fetch(
            `${API_BASE}/conversations/${conversationId}/messages?per_page=50&page=${page}`,
            {
                headers: getAuthHeaders(),
                credentials: "include",
            }
        );

        if (!response.ok) {
            throw new Error("Failed to load messages");
        }

        const data = await response.json();
        messages = (data.messages || []).reverse(); // Reverse để hiển thị từ cũ đến mới

        renderMessages(messages);
        isLoadingMessages = false;
    } catch (error) {
        console.error("Error loading messages:", error);
        isLoadingMessages = false;
    }
}

// Render messages
function renderMessages(messagesList) {
    const container = document.getElementById("chat-messages");

    container.innerHTML = messagesList
        .map((msg) => {
            const time = formatTime(msg.send_at);
            const avatar = msg.sender.name.charAt(0).toUpperCase();

            return `
            <div class="message ${msg.is_own ? "own" : ""}">
                <div class="message-avatar">${avatar}</div>
                <div class="message-content">
                    <p class="message-text">${escapeHtml(msg.content)}</p>
                    <div class="message-time">${time}</div>
                </div>
            </div>
        `;
        })
        .join("");
}

// Send message
async function sendMessage() {
    const input = document.getElementById("chat-input");
    const content = input.value.trim();

    if (!content || !currentConversationId) return;

    try {
        // Clear input immediately for better UX
        input.value = "";

        const response = await fetch(
            `${API_BASE}/conversations/${currentConversationId}/messages`,
            {
                method: "POST",
                headers: getAuthHeaders(),
                credentials: "include",
                body: JSON.stringify({ content }),
            }
        );

        if (!response.ok) {
            // Handle specific error cases
            if (response.status === 401) {
                alert("Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.");
                window.location.href = "/login";
                return;
            }

            if (response.status === 403) {
                const errorData = await response.json().catch(() => ({}));
                if (errorData.status === "suspended") {
                    alert(
                        "Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên."
                    );
                    return;
                }
                alert(
                    "Bạn không có quyền gửi tin nhắn. Vui lòng đăng nhập lại."
                );
                window.location.href = "/login";
                return;
            }

            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || "Failed to send message");
        }

        const data = await response.json();

        // ✅ FIX: Don't add message from API response if it's already added by real-time event
        // Real-time event will handle it to avoid duplication
        // Only add if real-time is not working (fallback)
        if (data.message) {
            const currentUser = JSON.parse(
                localStorage.getItem("currentUser") || "{}"
            );
            const isOwnMessage =
                data.message.is_own ||
                (data.message.sender &&
                    data.message.sender.id === currentUser.id);

            // Check if message already exists (from real-time event)
            const messageExists = messages.some((m) => {
                // Compare by ID (handle different ID formats)
                const msgId = m.id || m.id__message || m.id_message;
                const newMsgId =
                    data.message.id ||
                    data.message.id__message ||
                    data.message.id_message;
                if (msgId && newMsgId) {
                    return msgId.toString() === newMsgId.toString();
                }
                // Fallback: compare by content and timestamp if IDs don't match
                return (
                    m.content === data.message.content &&
                    Math.abs(
                        new Date(m.send_at) - new Date(data.message.send_at)
                    ) < 1000
                ); // Within 1 second
            });

            if (!messageExists) {
                messages.push(data.message);
                renderMessages(messages);
                scrollToBottom();
            } else {
                console.log(
                    "✅ Message already exists (from real-time), skipping API response"
                );
            }
        }

        // Reload conversations to update last message
        loadConversations();
    } catch (error) {
        console.error("Error sending message:", error);
        alert("Không thể gửi tin nhắn. Vui lòng thử lại.");
        // Restore input value on error
        input.value = content;
    }
}

// Event listeners
document.getElementById("chat-form").addEventListener("submit", (e) => {
    e.preventDefault();
    sendMessage();
});

// Utility functions
function formatTime(dateString) {
    // Server trả về time local nhưng có chữ Z (ví dụ: 2025-11-28T19:18:50.000000Z)
    // Cần xóa Z và parse như local time để tránh JS tự động chuyển đổi timezone
    let date;
    if (dateString.endsWith("Z")) {
        // Xóa Z và parse như local time
        const localTimeString = dateString.slice(0, -1);
        date = new Date(localTimeString);
    } else {
        date = new Date(dateString);
    }

    const now = new Date();
    const diff = now - date;
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);

    // Format đầy đủ: Thứ X, dd/mm/yyyy HH:mm
    const weekday = date.toLocaleDateString("vi-VN", { weekday: "long" });
    const day = String(date.getDate()).padStart(2, "0");
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const year = date.getFullYear();
    const h = String(date.getHours()).padStart(2, "0");
    const m = String(date.getMinutes()).padStart(2, "0");

    return `${weekday}, ${day}/${month}/${year} ${h}:${m}`;
}

function escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
}

function scrollToBottom() {
    const container = document.getElementById("chat-messages");
    if (container) {
        container.scrollTop = container.scrollHeight;
    }
}

// Polling fallback for when realtime is not available
function startPollingMessages(conversationId) {
    // Stop any existing polling
    stopPollingMessages();

    // Only poll if realtime is not available
    if (
        window.BROADCAST_DRIVER !== "pusher" ||
        !window.PUSHER_APP_KEY ||
        !echoInitialized
    ) {
        console.log(
            "🔄 Starting polling fallback for messages (realtime not available)"
        );

        let lastMessageCount = messages.length;

        pollingInterval = setInterval(async () => {
            if (
                !currentConversationId ||
                currentConversationId !== conversationId
            ) {
                stopPollingMessages();
                return;
            }

            try {
                const response = await fetch(
                    `${API_BASE}/conversations/${conversationId}/messages?per_page=50&page=1`,
                    {
                        headers: getAuthHeaders(),
                        credentials: "include",
                    }
                );

                if (response.ok) {
                    const data = await response.json();
                    const newMessages = (data.messages || []).reverse();

                    // Check if there are new messages
                    if (newMessages.length > lastMessageCount) {
                        // Reload all messages to get the latest
                        await loadMessages(conversationId);
                        scrollToBottom();
                        lastMessageCount = newMessages.length;
                    }
                }
            } catch (error) {
                console.error("Error polling messages:", error);
            }
        }, 3000); // Poll every 3 seconds
    }
}

function stopPollingMessages() {
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
        console.log("🛑 Stopped polling messages");
    }
}

// Check URL for conversation parameter
window.addEventListener("DOMContentLoaded", () => {
    // Initialize real-time chat first (only if Pusher is configured)
    if (window.BROADCAST_DRIVER === "pusher" && window.PUSHER_APP_KEY) {
        initializeEcho();
    } else {
        console.log(
            "ℹ️ Real-time chat disabled. Chat will work without real-time updates."
        );
        console.log(
            "To enable real-time chat, set BROADCAST_DRIVER=pusher and PUSHER_APP_KEY in .env"
        );
    }

    // Load conversations
    loadConversations();

    const urlParams = new URLSearchParams(window.location.search);
    const conversationId = urlParams.get("conversation");

    if (conversationId) {
        // Load conversation details and open it
        fetch(`${API_BASE}/conversations/${conversationId}/messages`, {
            headers: getAuthHeaders(),
            credentials: "include",
        })
            .then((res) => res.json())
            .then((data) => {
                if (data.messages && data.messages.length > 0) {
                    // Tìm tin nhắn của user khác (không phải của mình)
                    let otherUser = null;
                    for (const msg of data.messages) {
                        if (!msg.is_own && msg.sender) {
                            otherUser = msg.sender;
                            break;
                        }
                    }

                    // Nếu không tìm thấy, thử tìm từ tin nhắn cuối cùng
                    if (!otherUser) {
                        const lastMsg = data.messages[data.messages.length - 1];
                        if (!lastMsg.is_own && lastMsg.sender) {
                            otherUser = lastMsg.sender;
                        } else {
                            // Tìm ngược lại từ cuối
                            for (
                                let i = data.messages.length - 1;
                                i >= 0;
                                i--
                            ) {
                                if (
                                    !data.messages[i].is_own &&
                                    data.messages[i].sender
                                ) {
                                    otherUser = data.messages[i].sender;
                                    break;
                                }
                            }
                        }
                    }

                    if (otherUser) {
                        openConversation(conversationId, otherUser);
                    } else {
                        // Fallback: Dùng openConversationById() để tự động tìm otherUser và subscribe
                        console.warn(
                            "⚠️ Could not determine otherUser from messages, using openConversationById()"
                        );
                        openConversationById(conversationId);
                    }
                } else {
                    // Try to get conversation info from conversations list
                    loadConversations().then(() => {
                        const convItem = document.querySelector(
                            `[data-conversation-id="${conversationId}"]`
                        );
                        if (convItem) {
                            convItem.click();
                        } else {
                            // Fallback: Dùng openConversationById()
                            openConversationById(conversationId);
                        }
                    });
                }
            })
            .catch((err) => {
                console.error("Error loading conversation:", err);
                // Still load conversations list
                loadConversations();
                // Try to open conversation anyway
                openConversationById(conversationId);
            });
    }
});
