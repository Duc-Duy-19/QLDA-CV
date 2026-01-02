@extends('layouts.admin')

@section('title', $industry ? 'Sửa Ngành' : 'Thêm Ngành Mới')
@section('page-title', $industry ? 'Chỉnh sửa Ngành: ' . $industry->name : 'Thêm Ngành Mới')

@section('content')
<div class="admin-content">
    <style>
        .admin-content {
            padding: 24px 28px;
            background: #f7f9fb;
            min-height: 72vh;
        }

        .form-container {
            background: #fff;
            padding: 28px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            max-width: 900px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-label .required {
            color: #e53e3e;
        }

        .form-input,
        .form-textarea,
        .form-select {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
        }

        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-input:focus,
        .form-textarea:focus {
            outline: none;
            border-color: #3182ce;
            box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
        }

        .help-text {
            font-size: 12px;
            color: #718096;
            margin-top: 4px;
        }

        .focus-areas-list {
            margin-top: 10px;
        }

        .focus-area-item {
            display: flex;
            gap: 8px;
            margin-bottom: 8px;
        }

        .focus-area-item input {
            flex: 1;
        }

        .btn-remove {
            background: #e53e3e;
            color: #fff;
            border: none;
            padding: 10px 14px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-add {
            background: #38b2ac;
            color: #fff;
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            margin-top: 8px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid #edf2f7;
        }

        .btn {
            padding: 10px 18px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(180deg, #3182ce, #2c5aa0);
            color: #fff;
            box-shadow: 0 2px 6px rgba(49, 130, 206, 0.3);
        }

        .btn-secondary {
            background: #edf2f7;
            color: #4a5568;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-danger {
            background: #fed7d7;
            color: #742a2a;
            border-left: 4px solid #e53e3e;
        }

        .alert ul {
            margin: 8px 0 0 20px;
            padding: 0;
        }
    </style>

    <div class="form-container">
        @if($errors->any())
        <div class="alert alert-danger">
            <strong>Có lỗi xảy ra:</strong>
            <ul>
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ $industry ? route('admin.industry-contexts.update', $industry->id) : route('admin.industry-contexts.store') }}"
            method="POST">
            @csrf
            @if($industry)
            @method('PUT')
            @endif

            <div class="form-group">
                <label class="form-label">
                    Key (Mã định danh) <span class="required">*</span>
                </label>
                <input type="text"
                    name="key"
                    class="form-input"
                    value="{{ old('key', $industry->key ?? '') }}"
                    placeholder="it, marketing, sales, finance..."
                    required>
                <div class="help-text">Chỉ dùng chữ thường, không dấu, không khoảng trắng</div>
            </div>

            <div class="form-group">
                <label class="form-label">
                    Tên ngành (Hiển thị) <span class="required">*</span>
                </label>
                <input type="text"
                    name="name"
                    class="form-input"
                    value="{{ old('name', $industry->name ?? '') }}"
                    placeholder="Công nghệ thông tin, Marketing, Tài chính..."
                    required>
            </div>

            <div class="form-group">
                <label class="form-label">
                    Vai trò AI (Role) <span class="required">*</span>
                </label>
                <input type="text"
                    name="role"
                    class="form-input"
                    value="{{ old('role', $industry->role ?? '') }}"
                    placeholder="Chuyên gia Tuyển dụng IT/Tech"
                    required>
                <div class="help-text">Vai trò mà AI sẽ đóng khi đánh giá CV</div>
            </div>

            <div class="form-group">
                <label class="form-label">
                    Tiêu chí đánh giá ưu tiên (Focus Areas) <span class="required">*</span>
                </label>
                <div class="focus-areas-list" id="focusAreasList">
                    @php
                    $focusAreas = old('focus_areas', $industry ? $industry->focus_areas_array : ['']);
                    if (empty($focusAreas)) $focusAreas = [''];
                    @endphp
                    @foreach($focusAreas as $index => $area)
                    <div class="focus-area-item">
                        <input type="text"
                            name="focus_areas[]"
                            class="form-input"
                            value="{{ $area }}"
                            placeholder="Tech Stack và công nghệ cụ thể..."
                            required>
                        @if($index > 0)
                        <button type="button" class="btn-remove" onclick="this.parentElement.remove()">
                            <i class="fas fa-times"></i>
                        </button>
                        @endif
                    </div>
                    @endforeach
                </div>
                <button type="button" class="btn-add" onclick="addFocusArea()">
                    <i class="fas fa-plus"></i> Thêm tiêu chí
                </button>
            </div>

            <div class="form-group">
                <label class="form-label">
                    Chỉ số quan trọng (Key Metrics) <span class="required">*</span>
                </label>
                <textarea name="key_metrics"
                    class="form-textarea"
                    required>{{ old('key_metrics', $industry->key_metrics ?? '') }}</textarea>
                <div class="help-text">Các chỉ số/metric quan trọng cần xem xét khi đánh giá</div>
            </div>

            <div class="form-group">
                <label class="form-label">
                    Dấu hiệu cảnh báo (Red Flags) <span class="required">*</span>
                </label>
                <textarea name="red_flags"
                    class="form-textarea"
                    required>{{ old('red_flags', $industry->red_flags ?? '') }}</textarea>
                <div class="help-text">Những dấu hiệu khiến ứng viên không phù hợp với ngành này</div>
            </div>

            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox"
                        name="is_active"
                        id="is_active"
                        {{ old('is_active', $industry->is_active ?? true) ? 'checked' : '' }}>
                    <label for="is_active" class="form-label" style="margin: 0;">
                        Kích hoạt ngành này (AI sẽ sử dụng)
                    </label>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    {{ $industry ? 'Cập nhật' : 'Lưu ngành mới' }}
                </button>
                <a href="{{ route('admin.industry-contexts.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    Hủy
                </a>
            </div>
        </form>
    </div>

    <script>
        function addFocusArea() {
            const list = document.getElementById('focusAreasList');
            const div = document.createElement('div');
            div.className = 'focus-area-item';
            div.innerHTML = `
                <input type="text" 
                       name="focus_areas[]" 
                       class="form-input" 
                       placeholder="Nhập tiêu chí..."
                       required>
                <button type="button" class="btn-remove" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;
            list.appendChild(div);
        }
    </script>
</div>
@endsection