<!DOCTYPE html>
<html lang="vi">

<head>
    @include('layouts.head')
    <title>Thông tin công ty</title>
    <link rel="stylesheet" href="{{ asset('css/shared/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/shared/footer.css') }}">
    @php
    $companiesPath = public_path('css/pages/companies.css');
    $companyDetailPath = public_path('css/pages/company-detail.css');
    $companiesVersion = file_exists($companiesPath) ? '?v=' . filemtime($companiesPath) : '';
    $companyDetailVersion = file_exists($companyDetailPath) ? '?v=' . filemtime($companyDetailPath) : '';
    @endphp
    <link rel="stylesheet" href="{{ asset('css/pages/companies.css') }}{{ $companiesVersion }}">
    <link rel="stylesheet" href="{{ asset('css/pages/company-detail.css') }}{{ $companyDetailVersion }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
</head>

<body>
    @include('layouts.header')

    <main class="main">
        <div class="container" style="padding:8px 0 0 0;">
            <!-- spacer between header and page content (minimal) -->
            <div style="height:4px"></div>

            <!-- breadcrumb (will be updated with company name after load) -->
            <div id="company-breadcrumb" class="breadcrumb">Danh sách Công ty &nbsp;›&nbsp; Thông tin </div>

            <div id="company-detail-root" data-company-id="{{ $id ?? '' }}">
                <div style="text-align:center; padding:30px;">
                    <div id="company-detail-loading">Đang tải thông tin công ty...</div>
                </div>
            </div>
        </div>
    </main>

    @include('layouts.footer')

    @vite(['resources/js/app.js'])
    <script src="{{ asset('js/company/company-detail.js') }}"></script>
</body>
</html>