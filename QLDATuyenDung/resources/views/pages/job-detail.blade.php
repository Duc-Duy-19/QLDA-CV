<!DOCTYPE html>
<html lang="vi">

<head>
    @include('layouts.head')
    <link rel="stylesheet" href="{{ asset('css/shared/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/shared/footer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/home.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/job-detail.css') }}">
</head>

<body>
    @include('layouts.header')

    <main class="main job-page">
        <div class="container-inner">
            <nav class="breadcrumb" style="margin-bottom:14px; font-size:14px; color:#3b7a5a;">
                <a href="{{ route('home') }}">Trang chủ</a> &nbsp;/&nbsp; <span class="breadcrumb-section">Việc làm</span> &nbsp;/&nbsp; <span id="breadcrumb-title" class="breadcrumb-current">Chi tiết</span>
            </nav>

            <div class="job-card">
                <div class="job-grid">
                    <div class="job-main">
                        <div>
                            <h1 id="job-title" class="job-title">Đang tải...</h1>
                            <div id="job-company-sub" class="job-sub">&nbsp;</div>
                        </div>

                        <div class="job-meta" id="job-meta">
                            <!-- meta pills injected here -->
                        </div>

                        <div id="job-messages" style="margin-top:12px;color:#e03;font-size:14px"></div>

                        <div>
                            <h3 class="section-title">Chi tiết công việc</h3>
                            <div id="job-description" class="job-description">Đang tải mô tả...</div>
                        </div>
                    </div>

                    <aside class="job-side">
                        <div class="company-card">
                            <img id="company-logo" src="{{ asset('images/company-placeholder.svg') }}" alt="Logo" class="company-logo">
                            <div id="company-name" class="company-name">&nbsp;</div>
                            <div id="company-meta" class="company-meta">&nbsp;</div>
                            <!-- debug output removed -->
                            <div style="margin-top:12px;">
                                <a id="company-page-link" href="#" style="text-decoration:none;color:#1fae4f;font-weight:600;">Xem trang công ty</a>
                            </div>
                        </div>

                        <!-- Application form card below company info -->
                        <div class="application-form-card">
                            <h4>Ứng tuyển ngay</h4>

                            <div id="resume-section">
                                <label>Chọn CV để ứng tuyển</label>
                                <select id="resume-select" style="display:none;"></select>
                                <input id="resume-file" type="file" accept=".pdf,.doc,.docx" />
                                <div id="resume-note" style="font-size:13px;color:#b33;margin-top:8px; display:none;"></div>
                            </div>

                            <div id="cover-letter-section">
                                <label for="cover-letter">Thư xin việc (không bắt buộc)</label>
                                <textarea id="cover-letter" rows="4" placeholder="Giới thiệu ngắn gọn về bản thân..."></textarea>
                            </div>

                            <button id="apply-btn-fixed" class="btn-apply">Ứng tuyển</button>
                            <div id="job-messages-fixed" style="margin-top:8px;color:#e03;font-size:13px;text-align:center;"></div>
                        </div>

                        <!-- AI Match Analysis Card -->
                        <div class="ai-analysis-card" style="margin-top: 20px; padding: 20px; background: #f8f9fa; border-radius: 8px; border: 1px solid #e0e0e0;">
                            <h4 style="margin-bottom: 15px; color: #1fae4f;">AI Phân Tích Độ Phù Hợp</h4>
                            <p style="font-size: 13px; color: #666; margin-bottom: 15px;">
                                Sử dụng AI để phân tích mức độ phù hợp giữa CV của bạn và công việc này
                            </p>
                            <button id="ai-analysis-btn" class="btn-ai-analysis" style="width: 100%; padding: 12px; background: #1fae4f; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; transition: background 0.3s;">
                                <span class="btn-icon">🤖</span> Phân Tích Độ Phù Hợp
                            </button>
                            <div id="ai-quota-info" style="margin-top: 10px; font-size: 12px; color: #888; text-align: center;"></div>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </main>

    @include('layouts.footer')

    <script src="{{ asset('js/ai-feedback-widget.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/ai-analysis.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/pages/job-detail.js') }}"></script>
    <script>
        // Initialize job detail page
        const jobId = @json($id);
        const routes = {
            login: '{{ route("login") }}',
            cvBuilder: '{{ route("candidate.cv-builder") }}',
            applications: '{{ route("candidate.applications") }}'
        };
        initJobDetailPage(jobId, routes);
    </script>
</body>

</html>