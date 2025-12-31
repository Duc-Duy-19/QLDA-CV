<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;

class PublicCompanyController extends Controller
{
    //
    /**
     * Danh sách tất cả công ty public
     */
    public function index(Request $request)
    {
        $query = Company::where('status', 'active');

        // Tìm kiếm theo tên công ty
        if ($request->has('search')) {
            $query->where('company_name', 'like', '%' . $request->search . '%');
        }

        // Lọc theo ngành nghề (thông qua jobs của công ty)
        // if ($request->has('category')) {
        //     $query->whereHas('jobs.categories', function($q) use ($request) {
        //         $q->where('categories.id', $request->category)
        //         ->orWhere('categories.name', $request->category);
        //     });
        // }

        $companies = $query->paginate($request->get('per_page', 20));

        return response()->json($companies, 200);
    }

    /**
     * Chi tiết công ty public
     */
    public function show($id)
    {
        $company = Company::where('status', 'active')
            ->findOrFail($id);

        return response()->json([
            'company' => $company
        ], 200);
    }

    /**
     * Lấy danh sách job của công ty (public)
     */
    public function getJobs($id, Request $request)
    {
        $company = Company::where('status', 'active')->findOrFail($id);

        // $jobs = Job::where('company_id', $id)
        //     ->where('status', 'active')
        //     ->latest()
        //     ->paginate($request->get('per_page', 20));

        return response()->json([
            'company' => $company,
            // 'jobs' => $jobs
            'message' => 'Job listing will be implemented later'
        ], 200);
    }
}
