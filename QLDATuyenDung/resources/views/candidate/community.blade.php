<!DOCTYPE html>
<html lang="vi">
<head>
    @include('layouts.head')
    <link href="{{ asset('css/shared/header.css') }}" rel="stylesheet">
    <link href="{{ asset('css/shared/footer.css') }}" rel="stylesheet">
    <link href="{{ asset('css/roles/candidate/community.css') }}" rel="stylesheet">
    <title>Cộng đồng & Tương tác - WebCV</title>
</head>
<body>
    @include('layouts.header')
    <div class="container">
        
        <!-- Tabs -->
        <div class="tabs">
            <button class="tab active" onclick="switchTab('forum')">Diễn đàn</button>
            <button class="tab" onclick="switchTab('chat')">Chat HR</button>
            <button class="tab" onclick="switchTab('mentors')">Mentor</button>
            <button class="tab" onclick="switchTab('reviews')">Đánh giá</button>
        </div>
        
        <!-- Tab nội dung -->
        <div id="forum-tab" class="tab-content active">
            <div class="forum-section">
                <div class="forum-header">
                    <h3 class="forum-title">Diễn đàn nghề nghiệp</h3>
                    <button class="create-post-btn" onclick="createPost()">Tạo bài viết</button>
                </div>
                <div class="forum-content">
                    <div class="post-item">
                        <div class="post-header">
                            <div class="post-avatar">NV</div>
                            <div class="post-author">
                                <h4 class="post-author-name">Nguyễn Văn A</h4>
                                <p class="post-time">2 giờ trước</p>
                            </div>
                        </div>
                        <h3 class="post-title">Kinh nghiệm phỏng vấn tại Google</h3>
                        <p class="post-content">Chia sẻ kinh nghiệm phỏng vấn tại Google cho vị trí Software Engineer. Quá trình gồm 5 vòng phỏng vấn kỹ thuật và 1 vòng behavioral...</p>
                        <div class="post-tags">
                            <span class="post-tag">#phỏng vấn</span>
                            <span class="post-tag">#google</span>
                            <span class="post-tag">#software engineer</span>
                        </div>
                        <div class="post-actions">
                            <div class="post-action">
                                <span>👍</span>
                                <span>24</span>
                            </div>
                            <div class="post-action">
                                <span>💬</span>
                                <span>8</span>
                            </div>
                            <div class="post-action">
                                <span>🔗</span>
                                <span>Chia sẻ</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="post-item">
                        <div class="post-header">
                            <div class="post-avatar">TB</div>
                            <div class="post-author">
                                <h4 class="post-author-name">Trần Thị B</h4>
                                <p class="post-time">5 giờ trước</p>
                            </div>
                        </div>
                        <h3 class="post-title">Xu hướng AI/ML trong năm 2024</h3>
                        <p class="post-content">Các xu hướng AI/ML mới nhất và cơ hội nghề nghiệp trong lĩnh vực này. Từ ChatGPT đến các mô hình ngôn ngữ lớn...</p>
                        <div class="post-tags">
                            <span class="post-tag">#AI</span>
                            <span class="post-tag">#Machine Learning</span>
                            <span class="post-tag">#xu hướng</span>
                        </div>
                        <div class="post-actions">
                            <div class="post-action">
                                <span>👍</span>
                                <span>18</span>
                            </div>
                            <div class="post-action">
                                <span>💬</span>
                                <span>12</span>
                            </div>
                            <div class="post-action">
                                <span>🔗</span>
                                <span>Chia sẻ</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="post-item">
                        <div class="post-header">
                            <div class="post-avatar">LC</div>
                            <div class="post-author">
                                <h4 class="post-author-name">Lê Văn C</h4>
                                <p class="post-time">1 ngày trước</p>
                            </div>
                        </div>
                        <h3 class="post-title">Tips viết CV cho developer</h3>
                        <p class="post-content">Những lỗi thường gặp khi viết CV và cách khắc phục. Tập trung vào thành tích cụ thể và sử dụng từ khóa phù hợp...</p>
                        <div class="post-tags">
                            <span class="post-tag">#CV</span>
                            <span class="post-tag">#developer</span>
                            <span class="post-tag">#tips</span>
                        </div>
                        <div class="post-actions">
                            <div class="post-action">
                                <span>👍</span>
                                <span>31</span>
                            </div>
                            <div class="post-action">
                                <span>💬</span>
                                <span>15</span>
                            </div>
                            <div class="post-action">
                                <span>🔗</span>
                                <span>Chia sẻ</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div id="chat-tab" class="tab-content">
            <div class="chat-section">
                <div class="chat-header">
                    <h3 class="chat-title">Chat với HR</h3>
                </div>
                <div class="chat-messages" id="chat-messages">
                    <div class="message">
                        <div class="message-avatar">HR</div>
                        <div class="message-content">
                            Xin chào! Tôi có thể giúp gì cho bạn?
                            <div class="message-time">10:30</div>
                        </div>
                    </div>
                    
                    <div class="message own">
                        <div class="message-avatar">A</div>
                        <div class="message-content">
                            Tôi muốn hỏi về quy trình ứng tuyển tại công ty
                            <div class="message-time">10:32</div>
                        </div>
                    </div>
                    
                    <div class="message">
                        <div class="message-avatar">HR</div>
                        <div class="message-content">
                            Quy trình ứng tuyển của chúng tôi gồm: 1. Nộp CV, 2. Phỏng vấn HR, 3. Test kỹ thuật, 4. Phỏng vấn trực tiếp với team lead
                            <div class="message-time">10:35</div>
                        </div>
                    </div>
                </div>
                <div class="chat-input">
                    <input type="text" id="chat-input" placeholder="Nhập tin nhắn..." onkeypress="handleKeyPress(event)">
                    <button onclick="sendMessage()">📤</button>
                </div>
            </div>
        </div>
        
        <div id="mentors-tab" class="tab-content">
            <div class="mentors-section">
                <div class="mentors-header">
                    <h3 class="mentors-title">Mentor nghề nghiệp</h3>
                </div>
                <div class="mentors-grid">
                    <div class="mentor-card">
                        <div class="mentor-avatar">NV</div>
                        <h4 class="mentor-name">Nguyễn Văn D</h4>
                        <p class="mentor-title">Senior Software Engineer</p>
                        <p class="mentor-company">Google Vietnam</p>
                        <div class="mentor-skills">
                            <span class="mentor-skill">JavaScript</span>
                            <span class="mentor-skill">React</span>
                            <span class="mentor-skill">Node.js</span>
                        </div>
                        <div class="mentor-actions">
                            <a href="#" class="btn btn-primary">Kết nối</a>
                            <a href="#" class="btn btn-success">Xem hồ sơ</a>
                        </div>
                    </div>
                    
                    <div class="mentor-card">
                        <div class="mentor-avatar">TB</div>
                        <h4 class="mentor-name">Trần Thị E</h4>
                        <p class="mentor-title">Product Manager</p>
                        <p class="mentor-company">Shopee</p>
                        <div class="mentor-skills">
                            <span class="mentor-skill">Product</span>
                            <span class="mentor-skill">Strategy</span>
                            <span class="mentor-skill">Analytics</span>
                        </div>
                        <div class="mentor-actions">
                            <a href="#" class="btn btn-primary">Kết nối</a>
                            <a href="#" class="btn btn-success">Xem hồ sơ</a>
                        </div>
                    </div>
                    
                    <div class="mentor-card">
                        <div class="mentor-avatar">LC</div>
                        <h4 class="mentor-name">Lê Văn F</h4>
                        <p class="mentor-title">Data Scientist</p>
                        <p class="mentor-company">Grab</p>
                        <div class="mentor-skills">
                            <span class="mentor-skill">Python</span>
                            <span class="mentor-skill">ML</span>
                            <span class="mentor-skill">Statistics</span>
                        </div>
                        <div class="mentor-actions">
                            <a href="#" class="btn btn-primary">Kết nối</a>
                            <a href="#" class="btn btn-success">Xem hồ sơ</a>
                        </div>
                    </div>
                    
                    <div class="mentor-card">
                        <div class="mentor-avatar">PD</div>
                        <h4 class="mentor-name">Phạm Thị G</h4>
                        <p class="mentor-title">UX Designer</p>
                        <p class="mentor-company">Facebook</p>
                        <div class="mentor-skills">
                            <span class="mentor-skill">UI/UX</span>
                            <span class="mentor-skill">Figma</span>
                            <span class="mentor-skill">Research</span>
                        </div>
                        <div class="mentor-actions">
                            <a href="#" class="btn btn-primary">Kết nối</a>
                            <a href="#" class="btn btn-success">Xem hồ sơ</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div id="reviews-tab" class="tab-content">
            <div class="reviews-section">
                <div class="reviews-header">
                    <h3 class="reviews-title">Đánh giá công ty</h3>
                    <button class="write-review-btn" onclick="writeReview()">Viết đánh giá</button>
                </div>
                <div class="reviews-content">
                    <div class="review-item">
                        <div class="review-header">
                            <div class="review-author">
                                <div class="review-author-avatar">NV</div>
                                <span class="review-author-name">Nguyễn Văn H</span>
                            </div>
                            <div class="review-rating">⭐⭐⭐⭐⭐</div>
                        </div>
                        <p class="review-content">Môi trường làm việc rất tốt, đồng nghiệp thân thiện và hỗ trợ nhiệt tình. Cơ hội phát triển nghề nghiệp rõ ràng.</p>
                        <p class="review-company">Google Vietnam</p>
                    </div>
                    
                    <div class="review-item">
                        <div class="review-header">
                            <div class="review-author">
                                <div class="review-author-avatar">TB</div>
                                <span class="review-author-name">Trần Thị I</span>
                            </div>
                            <div class="review-rating">⭐⭐⭐⭐</div>
                        </div>
                        <p class="review-content">Công ty có nhiều dự án thú vị và công nghệ mới. Tuy nhiên, áp lực công việc khá cao.</p>
                        <p class="review-company">Shopee</p>
                    </div>
                    
                    <div class="review-item">
                        <div class="review-header">
                            <div class="review-author">
                                <div class="review-author-avatar">LC</div>
                                <span class="review-author-name">Lê Văn K</span>
                            </div>
                            <div class="review-rating">⭐⭐⭐⭐⭐</div>
                        </div>
                        <p class="review-content">Chế độ đãi ngộ tốt, lương thưởng cạnh tranh. Công ty đầu tư nhiều vào đào tạo nhân viên.</p>
                        <p class="review-company">Grab</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/candidate/community.js') }}"></script>
    @include('layouts.footer')
</body>
</html>
