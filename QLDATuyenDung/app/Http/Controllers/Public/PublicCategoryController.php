<?php
// filepath: c:\laragon\www\websitetuyendungthucte\webcv\app\Http\Controllers\Public\CategoryController.php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class PublicCategoryController extends Controller
{
    //
    /**
     * Danh sách tất cả categories công khai
     */
    public function index()
    {
        $categories = Category::withCount(['jobs' => function ($query) {
            $query->where('status', 'open')
                ->where('expiration_date', '>', now());
        }])
            ->orderBy('name')
            ->get();

        return response()->json([
            'message' => 'Danh sách ngành nghề',
            'data' => $categories
        ]);
    }

    /**
     * Chi tiết category với jobs
     */
    public function show($id)
    {
        $category = Category::withCount(['jobs' => function ($query) {
            $query->where('status', 'open')
                ->where('expiration_date', '>', now());
        }])->find($id);

        if (!$category) {
            return response()->json([
                'message' => 'Không tìm thấy ngành nghề'
            ], 404);
        }

        return response()->json([
            'message' => 'Chi tiết ngành nghề',
            'data' => $category
        ]);
    }

    /**
     * Jobs theo category (có thể dùng hoặc redirect đến JobController)
     */
    public function jobs($id, Request $request)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'message' => 'Không tìm thấy ngành nghề'
            ], 404);
        }

        $query = $category->jobs()
            ->where('status', 'open')
            ->where('expiration_date', '>', now())
            ->with([
                'company:id,company_name,address',
                'categories:id,name'
            ]);

        // Tìm kiếm trong category
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        // Lọc theo location
        if ($request->has('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }

        // Lọc theo employment_type
        if ($request->has('employment_type')) {
            $query->where('employment_type', $request->employment_type);
        }

        $jobs = $query->latest('posted_date')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'message' => 'Việc làm theo ngành nghề',
            'data' => [
                'category' => $category,
                'jobs' => $jobs
            ]
        ]);
    }

    /**
     * Categories phổ biến (có nhiều jobs nhất)
     */
    public function popular(Request $request)
    {
        $categories = Category::withCount(['jobs' => function ($query) {
            $query->where('status', 'open')
                ->where('expiration_date', '>', now());
        }])
            ->having('jobs_count', '>', 0)
            ->orderBy('jobs_count', 'desc')
            ->take($request->get('limit', 10))
            ->get();

        return response()->json([
            'message' => 'Ngành nghề phổ biến',
            'data' => $categories
        ]);
    }
}
