<!DOCTYPE html>
<html lang="vi">
<head>
    @include('layouts.head')
    <link rel="stylesheet" href="{{ asset('css/shared/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/shared/footer.css') }}">
</head>
<body class="page-privacy">
    @include('layouts.header')

    <main class="main">
        <div class="container-inner">
            <section style="padding:36px 0; max-width:900px; margin:0 auto;">
                <h1>Chính sách bảo mật</h1>
                <p class="muted">Phiên bản ngắn gọn của Chính sách bảo mật. Bạn có thể sửa nội dung này trong <code>resources/views/pages/privacy.blade.php</code>.</p>

                <h3>Thông tin chúng tôi thu thập</h3>
                <p class="muted">Mô tả các loại dữ liệu được thu thập, mục đích sử dụng và thời gian lưu trữ.</p>

                <h3>Quyền của người dùng</h3>
                <p class="muted">Hướng dẫn để người dùng yêu cầu truy cập, chỉnh sửa hoặc xóa dữ liệu của họ.</p>

                <p class="small muted">Nếu bạn muốn, tôi có thể soạn bản Chính sách bảo mật chi tiết phù hợp với luật địa phương (ví dụ: PDPA, GDPR) và tích hợp các liên kết/biểu mẫu cần thiết.</p>
            </section>
        </div>
    </main>

    @include('layouts.footer')
</body>
</html>
