<!DOCTYPE html>
<html lang="vi">
<head>
    @include('layouts.head')
    <link rel="stylesheet" href="{{ asset('css/shared/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/shared/footer.css') }}">
</head>
<body class="page-terms">
    @include('layouts.header')

    <main class="main">
        <div class="container-inner">
            <section style="padding:36px 0; max-width:900px; margin:0 auto;">
                <h1>Điều khoản</h1>
                <p class="muted">Phiên bản ngắn gọn của Điều khoản sử dụng. Bạn có thể sửa nội dung này trong <code>resources/views/pages/terms.blade.php</code>.</p>

                <h3>1. Quyền và trách nhiệm</h3>
                <p class="muted">Nội dung điều khoản ... (thêm nội dung pháp lý phù hợp với ứng dụng của bạn).</p>

                <h3>2. Sử dụng dịch vụ</h3>
                <p class="muted">Nội dung điều khoản ...</p>

                <p class="small muted">Nếu bạn muốn, tôi có thể giúp soạn nội dung Điều khoản và Chính sách bảo mật đầy đủ cho trang web.</p>
            </section>
        </div>
    </main>

    @include('layouts.footer')
</body>
</html>
