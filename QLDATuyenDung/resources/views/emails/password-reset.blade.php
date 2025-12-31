<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reset mật khẩu</title>
</head>
<body>
    <h2>Xin chào {{ $user->name ?? $user->email }},</h2>
    <p>Bạn vừa yêu cầu đổi mật khẩu cho tài khoản. Nhấn vào link bên dưới để đổi mật khẩu:</p>
    <p>
        <a href="{{ $url }}">Reset mật khẩu</a>
    </p>
    <p>Nếu bạn không yêu cầu, hãy bỏ qua email này.</p>
</body>
</html>