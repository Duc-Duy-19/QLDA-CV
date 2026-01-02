<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'WebCV')</title>
<meta name="csrf-token" content="{{ csrf_token() }}">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

@stack('styles') {{-- Nơi các trang con push CSS riêng --}}

{{-- Set global API base URL so client scripts can use the correct API origin in all environments --}}
<script>window.API_BASE_URL = '{{ url('/api') }}';</script>
