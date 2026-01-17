<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;

//
class CategoryAdminController extends Controller
{
    public function index()
    {
        $categories = Category::all();
        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
        ]);

        $category = Category::Create([
            'name' => $request->name,
        ]);

        return response()->json([
            'message' => 'Thêm danh mục thành công',
            'data' => $category
        ], 201);
    }

    public function show($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json(["message" => "Không tìm thấy danh mục"]);
        }
        return response()->json(['data' => $category]);
    }

    public function update(Request $request, $id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json(['message' => "Không tìm thấy danh mục"]);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $id,
        ]);

        $category->update([
            'name' => $request->name,
        ]);
        return response()->json([
            'message' => 'cập nhật thành công',
            'data' => $category

        ]);
    }

    public function destroy($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json(['message' => 'Không tìm thấy danh mục']);
        }
        $category->delete();

        return response()->json([
            'message' => 'xóa danh mục thành công'
        ]);
    }
}
