<!DOCTYPE html>
<html lang="vi">
<head>
    @include('layouts.head')
    <link href="{{ asset('css/shared/header.css') }}" rel="stylesheet">
    <link href="{{ asset('css/shared/footer.css') }}" rel="stylesheet">
    <link href="{{ asset('css/roles/candidate/favorites.css') }}" rel="stylesheet">
    <title>Quản lý yêu thích - WebCV</title>
</head>
<body>
    @include('layouts.header')
    <div class="container">
        
        <!-- Tabs -->
        <div class="tabs">
            <button class="tab active" onclick="switchTab('jobs')">Việc làm yêu thích</button>
            <button class="tab" onclick="switchTab('companies')">Công ty theo dõi</button>
        </div>
        
        
        
        <!-- Tab nội dung -->
        <div id="jobs-tab" class="tab-content active">
            <div class="favorites-grid" id="jobs-list">
                <div class="empty-state">
                    <i>❤️</i>
                    <h3>Chưa có việc làm yêu thích</h3>
                    <p>Bạn chưa lưu việc làm nào. Hãy tìm kiếm và lưu những việc làm phù hợp!</p>
                    <a href="{{ route('candidate.job-search') }}" class="btn btn-primary">Tìm việc làm</a>
                </div>
            </div>
        </div>
        
        <div id="companies-tab" class="tab-content">
            <div class="favorites-grid" id="companies-list">
                <div class="empty-state">
                    <i>🏢</i>
                    <h3>Chưa theo dõi công ty nào</h3>
                    <p>Bạn chưa theo dõi công ty nào. Hãy tìm và theo dõi những công ty bạn quan tâm!</p>
                    <a href="{{ route('candidate.job-search') }}" class="btn btn-primary">Tìm công ty</a>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/api.js') }}"></script>
    <script src="{{ asset('js/candidate/favorites.js') }}"></script>
    @include('layouts.footer')
</body>
</html>
