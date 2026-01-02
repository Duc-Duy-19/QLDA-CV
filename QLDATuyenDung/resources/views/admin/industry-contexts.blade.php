@extends('layouts.admin')

@section('title', 'Quản lý Ngữ cảnh Ngành')
@section('page-title', 'Quản lý Ngữ cảnh AI cho từng Ngành nghề')

@section('content')
<div class="admin-content">
    <style>
        .admin-content {
            padding: 24px 28px;
            background: #f7f9fb;
            min-height: 72vh;
            color: #333;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial;
        }

        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .add-btn {
            background: linear-gradient(180deg, #38b2ac, #2c7a7b);
            color: #fff;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(44, 122, 123, 0.18);
            display: inline-flex;
            gap: 8px;
            align-items: center;
            font-weight: 600;
            text-decoration: none;
        }

        .add-btn:hover {
            transform: translateY(-1px);
        }

        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #c6f6d5;
            color: #22543d;
            border-left: 4px solid #38a169;
        }

        .industries-grid {
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
        }

        .industry-card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            border-left: 4px solid #3182ce;
            transition: all 0.2s;
        }

        .industry-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .industry-card.inactive {
            opacity: 0.6;
            border-left-color: #cbd5e0;
        }

        .industry-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .industry-title {
            font-size: 18px;
            font-weight: 700;
            color: #2d3748;
            margin: 0 0 4px 0;
        }

        .industry-key {
            display: inline-block;
            background: #edf2f7;
            color: #4a5568;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-family: monospace;
            margin-bottom: 8px;
        }

        .industry-role {
            font-size: 14px;
            color: #718096;
            margin-bottom: 12px;
            font-style: italic;
        }

        .industry-section {
            margin-bottom: 12px;
        }

        .section-label {
            font-size: 12px;
            font-weight: 700;
            color: #4a5568;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .focus-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .focus-list li {
            font-size: 13px;
            color: #4a5568;
            padding-left: 16px;
            margin-bottom: 4px;
            position: relative;
        }

        .focus-list li:before {
            content: "▸";
            position: absolute;
            left: 0;
            color: #3182ce;
        }

        .section-text {
            font-size: 13px;
            color: #4a5568;
            line-height: 1.6;
        }

        .card-actions {
            display: flex;
            gap: 8px;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #edf2f7;
        }

        .btn {
            padding: 7px 12px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-primary {
            background: #3182ce;
            color: #fff;
        }

        .btn-warning {
            background: #ed8936;
            color: #fff;
        }

        .btn-danger {
            background: #e53e3e;
            color: #fff;
        }

        .btn-secondary {
            background: #edf2f7;
            color: #4a5568;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-badge.active {
            background: #c6f6d5;
            color: #22543d;
        }

        .status-badge.inactive {
            background: #fed7d7;
            color: #742a2a;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }

        .empty-state i {
            font-size: 48px;
            color: #cbd5e0;
            margin-bottom: 16px;
        }

        .empty-state h3 {
            color: #4a5568;
            margin-bottom: 8px;
        }

        .empty-state p {
            color: #a0aec0;
        }
    </style>

    <div class="header-actions">
        <div>
            <p style="color: #718096; margin: 0; font-size: 14px;">
                Quản lý prompt AI cho từng ngành nghề. AI sẽ sử dụng ngữ cảnh này để đánh giá CV chính xác hơn.
            </p>
        </div>
        <a href="{{ route('admin.industry-contexts.create') }}" class="add-btn">
            <i class="fas fa-plus"></i>
            Thêm ngành mới
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        {{ session('success') }}
    </div>
    @endif

    @if($industries->isEmpty())
    <div class="empty-state">
        <i class="fas fa-inbox"></i>
        <h3>Chưa có ngành nào</h3>
        <p>Hãy thêm ngành đầu tiên để cấu hình prompt AI</p>
    </div>
    @else
    <div class="industries-grid">
        @foreach($industries as $industry)
        <div class="industry-card {{ $industry->is_active ? '' : 'inactive' }}">
            <div class="industry-header">
                <div style="flex: 1;">
                    <h3 class="industry-title">{{ $industry->name }}</h3>
                    <span class="industry-key">{{ $industry->key }}</span>
                </div>
                <span class="status-badge {{ $industry->is_active ? 'active' : 'inactive' }}">
                    <i class="fas fa-circle" style="font-size: 6px;"></i>
                    {{ $industry->is_active ? 'Đang dùng' : 'Tắt' }}
                </span>
            </div>

            <div class="industry-role">
                <i class="fas fa-user-tie"></i> {{ $industry->role }}
            </div>

            <div class="industry-section">
                <div class="section-label">Tiêu chí đánh giá</div>
                <ul class="focus-list">
                    @foreach($industry->focus_areas_array as $area)
                    <li>{{ $area }}</li>
                    @endforeach
                </ul>
            </div>

            <div class="industry-section">
                <div class="section-label">Chỉ số quan trọng</div>
                <div class="section-text">{{ $industry->key_metrics }}</div>
            </div>

            <div class="industry-section">
                <div class="section-label">Dấu hiệu cảnh báo</div>
                <div class="section-text">{{ $industry->red_flags }}</div>
            </div>

            <div class="card-actions">
                <a href="{{ route('admin.industry-contexts.edit', $industry->id) }}" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Sửa
                </a>

                <form action="{{ route('admin.industry-contexts.toggle', $industry->id) }}"
                    method="POST" style="display: inline;">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-{{ $industry->is_active ? 'eye-slash' : 'eye' }}"></i>
                        {{ $industry->is_active ? 'Tắt' : 'Bật' }}
                    </button>
                </form>

                <form action="{{ route('admin.industry-contexts.destroy', $industry->id) }}"
                    method="POST"
                    style="display: inline;"
                    onsubmit="return confirm('Bạn chắc chắn muốn xóa ngành {{ $industry->name }}?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Xóa
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection