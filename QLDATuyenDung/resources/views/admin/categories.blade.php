@extends('layouts.admin')

@section('title', 'Danh mục việc làm')
@section('page-title', 'Quản lý Danh mục ngành nghề')

@section('content')
<div class="admin-content">
    <style>
        /* Page layout */
        .admin-content {
            padding: 24px 28px;
            background: #f7f9fb;
            min-height: 72vh;
            color: #333;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial;
        }

        /* Add button */
        .add-btn {
            background: linear-gradient(180deg, #38b2ac, #2c7a7b);
            color: #fff;
            border: none;
            padding: 10px 14px;
            border-radius: 6px;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(44, 122, 123, 0.18);
            display: inline-flex;
            gap: 8px;
            align-items: center;
            font-weight: 600;
        }

        .add-btn i {
            font-size: 14px
        }

        .add-btn:hover {
            transform: translateY(-1px);
        }

        /* Table */
        .table-container {
            background: #fff;
            padding: 14px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(20, 20, 20, 0.04);
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .admin-table thead th {
            text-align: left;
            padding: 10px 12px;
            font-weight: 700;
            color: #4a5568;
            border-bottom: 2px solid #edf2f7;
        }

        .admin-table tbody td {
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .admin-table tbody tr:hover {
            background: #fbfdff;
        }

        /* Buttons used in rows and modals */
        .btn {
            padding: 8px 12px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-sm {
            padding: 6px 8px;
            font-size: 13px;
        }

        .btn-primary {
            background: #3182ce;
            color: #fff
        }

        .btn-danger {
            background: #e53e3e;
            color: #fff
        }

        .btn-secondary {
            background: #edf2f7;
            color: #2d3748
        }

        .btn+.btn {
            margin-left: 8px
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1200;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background: rgba(15, 23, 42, 0.45);
        }

        .modal-content {
            background: #fff;
            margin: 6% auto;
            border-radius: 8px;
            width: 420px;
            max-width: calc(100% - 40px);
            box-shadow: 0 10px 30px rgba(2, 6, 23, 0.2);
            overflow: hidden;
        }

        .modal-header,
        .modal-footer {
            padding: 14px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 18px;
            color: #2d3748
        }

        .modal-body {
            padding: 10px 18px 18px 18px;
        }

        .close {
            background: transparent;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #718096
        }

        /* Form */
        .form-group {
            margin-bottom: 12px;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            background: #fff;
            color: #2d3748;
            box-sizing: border-box;
            font-size: 14px;
        }

        .form-group.with-icon {
            position: relative
        }

        .form-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0
        }

        /* Responsive */
        @media (max-width: 600px) {
            .admin-content {
                padding: 14px
            }

            .modal-content {
                width: 92%;
            }

            .admin-table thead {
                display: none
            }

            .admin-table,
            .admin-table tbody,
            .admin-table tr,
            .admin-table td {
                display: block;
                width: 100%
            }

            .admin-table tr {
                margin-bottom: 12px;
            }

            .admin-table td {
                text-align: right;
                padding-left: 50%;
                position: relative
            }

            .admin-table td::before {
                content: attr(data-label);
                position: absolute;
                left: 12px;
                width: calc(50% - 24px);
                text-align: left;
                font-weight: 600;
                color: #4a5568
            }
        }
    </style>
    <!-- Add Category Button -->
    <div class="mb-4">
        <button class="add-btn" onclick="openAddCategoryModal()">
            <i class="fas fa-plus"></i>
            Thêm danh mục mới
        </button>
    </div>

    <!-- Categories Table -->
    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên danh mục</th>
                    <th>Ngày tạo</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody id="categories-table-body">
                <!-- Categories will be loaded here -->
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Category Modal -->
<div id="categoryModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Thêm danh mục mới</h3>
            <button class="close" onclick="closeCategoryModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="categoryForm">
                <input type="hidden" id="categoryId" name="id">

                <div class="form-group with-icon">
                    <input type="text" id="categoryName" name="name" placeholder="Nhập tên danh mục việc làm" required>
                    <i class="fas fa-tag form-icon"></i>
                </div>

                <div class="form-group with-icon">
                    <select id="categoryIndustryKey" name="industry_key" style="padding: 12px 40px 12px 12px; width: 100%; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                        <option value="">-- Chọn ngành nghề (tùy chọn) --</option>
                    </select>
                    <i class="fas fa-brain form-icon"></i>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeCategoryModal()">Hủy</button>
            <button type="button" class="btn btn-primary" onclick="saveCategory()">Lưu</button>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Xác nhận xóa</h3>
            <button class="close" onclick="closeDeleteModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>Bạn có chắc chắn muốn xóa danh mục <strong id="deleteCategoryName"></strong> không?</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Hủy</button>
            <button type="button" class="btn btn-danger" onclick="confirmDelete()">Xóa</button>
        </div>
    </div>
</div>

<script>
    let categories = [];
    let industries = [];
    let currentCategoryId = null;

    // Load categories on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadIndustries();
        loadCategories();
    });

    // Load industries for dropdown
    async function loadIndustries() {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const response = await fetch('/api/admin/industry-contexts', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken || ''
                },
                credentials: 'include'
            });

            if (!response.ok) {
                console.error('Failed to load industries:', response.status, response.statusText);
                const errorText = await response.text();
                console.error('Error response:', errorText);
                throw new Error('Failed to load industries');
            }

            const result = await response.json();
            console.log('Industries loaded:', result);
            industries = result.data || [];

            // Populate dropdown
            const select = document.getElementById('categoryIndustryKey');
            if (!select) {
                console.error('Select element not found!');
                return;
            }

            select.innerHTML = '<option value="">-- Chọn ngành nghề (tùy chọn) --</option>';
            industries.forEach(industry => {
                const option = document.createElement('option');
                option.value = industry.key;
                option.textContent = `${industry.name} (${industry.key})`;
                select.appendChild(option);
            });

            console.log(`Loaded ${industries.length} industries into dropdown`);
        } catch (error) {
            console.error('Error loading industries:', error);
        }
    }

    // Load categories from API
    async function loadCategories() {
        try {
            showLoading(true);
            console.log('Loading categories from API...');

            const response = await fetch('/api/admin/categories', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'include'
            });

            console.log('API Response status:', response.status);
            console.log('API Response headers:', response.headers);

            if (!response.ok) {
                const errorText = await response.text();
                console.error('API Error response:', errorText);
                throw new Error(`API Error: ${response.status} - ${errorText}`);
            }

            const data = await response.json();
            console.log('API Response data:', data);
            categories = data;
            displayCategories();
        } catch (error) {
            console.error('Error loading categories:', error);
            showAlert('Lỗi khi tải danh sách danh mục: ' + error.message, 'error');
        } finally {
            showLoading(false);
        }
    }

    // Display categories in table
    function displayCategories() {
        const tbody = document.getElementById('categories-table-body');
        tbody.innerHTML = '';

        categories.forEach(category => {
            const row = document.createElement('tr');
            row.innerHTML = `
            <td>${category.id}</td>
            <td>${category.name}</td>
            <td>${formatDate(category.created_at)}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editCategory(${category.id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteCategory(${category.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
            tbody.appendChild(row);
        });
    }

    // Open add category modal
    function openAddCategoryModal() {
        currentCategoryId = null;
        document.getElementById('modalTitle').textContent = 'Thêm danh mục mới';
        document.getElementById('categoryForm').reset();
        document.getElementById('categoryId').value = '';
        document.getElementById('categoryModal').style.display = 'block';
    }

    // Edit category
    function editCategory(id) {
        const category = categories.find(c => c.id === id);
        if (!category) return;

        currentCategoryId = id;
        document.getElementById('modalTitle').textContent = 'Chỉnh sửa danh mục';
        document.getElementById('categoryId').value = category.id;
        document.getElementById('categoryName').value = category.name;
        document.getElementById('categoryIndustryKey').value = category.industry_key || '';
        document.getElementById('categoryModal').style.display = 'block';
    }

    // Close category modal
    function closeCategoryModal() {
        document.getElementById('categoryModal').style.display = 'none';
        document.getElementById('categoryForm').reset();
        currentCategoryId = null;
    }

    // Save category
    async function saveCategory() {
        const form = document.getElementById('categoryForm');
        const formData = new FormData(form);

        const categoryData = {
            name: formData.get('name'),
            industry_key: formData.get('industry_key') || null
        };

        try {
            showLoading(true);
            const url = currentCategoryId ? `/api/admin/categories/${currentCategoryId}` : '/api/admin/categories';
            const method = currentCategoryId ? 'PUT' : 'POST';

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'include',
                body: JSON.stringify(categoryData)
            });

            if (!response.ok) {
                throw new Error('Failed to save category');
            }

            showAlert(currentCategoryId ? 'Cập nhật danh mục thành công!' : 'Thêm danh mục thành công!', 'success');
            closeCategoryModal();
            loadCategories();
        } catch (error) {
            console.error('Error saving category:', error);
            showAlert('Lỗi khi lưu danh mục', 'error');
        } finally {
            showLoading(false);
        }
    }

    // Delete category
    function deleteCategory(id) {
        const category = categories.find(c => c.id === id);
        if (!category) return;

        currentCategoryId = id;
        document.getElementById('deleteCategoryName').textContent = category.name;
        document.getElementById('deleteModal').style.display = 'block';
    }

    // Close delete modal
    function closeDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
        currentCategoryId = null;
    }

    // Confirm delete
    async function confirmDelete() {
        try {
            showLoading(true);
            const response = await fetch(`/api/admin/categories/${currentCategoryId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'include'
            });

            if (!response.ok) {
                throw new Error('Failed to delete category');
            }

            showAlert('Xóa danh mục thành công!', 'success');
            closeDeleteModal();
            loadCategories();
        } catch (error) {
            console.error('Error deleting category:', error);
            showAlert('Lỗi khi xóa danh mục', 'error');
        } finally {
            showLoading(false);
        }
    }

    // Utility functions
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('vi-VN');
    }

    function showLoading(show) {
        // Implementation for loading state
        console.log('Loading:', show);
    }

    function showAlert(message, type) {
        alert(message);
    }
</script>
@endsection