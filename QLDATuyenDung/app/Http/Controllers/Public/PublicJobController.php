<?php
// filepath: c:\laragon\www\websitetuyendungthucte\webcv\app\Http\Controllers\Public\JobController.php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\Category;
use Illuminate\Http\Request;

class PublicJobController extends Controller
{
    //
    /**
     * Danh sách job công khai
     */
    public function index(Request $request)
    {
        $query = Job::where('status', 'open')
            ->where('expiration_date', '>', now())
            ->with([
                'company:id,company_name,address',
                'categories:id,name'
            ]);

        // Tìm kiếm theo title
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

        // Lọc theo category
        if ($request->has('category_id')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('categories.id', $request->category_id);
            });
        }

        // Lọc theo company
        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        // Sắp xếp
        $sortBy = $request->get('sort_by', 'posted_date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $jobs = $query->paginate($request->get('per_page', 20));

        return response()->json($jobs);
    }

    /**
     * Chi tiết job công khai
     */
    public function show($id)
    {


        $job = Job::where('status', 'open')
            ->where('expiration_date', '>', now())
            ->with([
                'company:id,company_name,address,description,website,email',
                'categories:id,name'
            ])
            ->findOrFail($id);

        return response()->json($job);
    }



    /**
     * Tìm kiếm nâng cao
     */
    public function search(Request $request)
    {
        $query = Job::where('status', 'open')
            ->where('expiration_date', '>', now())
            ->with([
                'company:id,company_name,address',
                'categories:id,name'
            ]);

        // Multi-field search
        if ($request->has('q')) {
            $searchTerm = $request->q;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                    ->orWhere('description', 'like', "%{$searchTerm}%")
                    ->orWhere('requirements', 'like', "%{$searchTerm}%");
            });
        }

        // Filters
        if ($request->has('categories')) {
            $categoryIds = explode(',', $request->categories);
            $query->whereHas('categories', function ($q) use ($categoryIds) {
                $q->whereIn('categories.id', $categoryIds);
            });
        }

        if ($request->has('locations')) {
            $locations = explode(',', $request->locations);
            $query->whereIn('location', $locations);
        }

        if ($request->has('employment_types')) {
            $types = explode(',', $request->employment_types);
            $query->whereIn('employment_type', $types);
        }

        // Salary range
        if ($request->has('min_salary') || $request->has('max_salary')) {
            // Implementation depends on salary_range format
        }

        $jobs = $query->latest('posted_date')
            ->paginate($request->get('per_page', 20));

        return response()->json($jobs);
    }
}
