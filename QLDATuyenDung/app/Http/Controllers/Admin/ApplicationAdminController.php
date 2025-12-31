<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\Request;

//
class ApplicationAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = Application::with(['job', 'candidate', 'resume']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('job_id')) {
            $query->where('job_id', $request->job_id);
        }
        if ($request->has('candidate_id')) {
            $query->where('candidate_id', $request->candidate_id);
        }

        $list = $query->latest('applied_at')->paginate($request->get('per_page', 20));

        return response()->json($list);
    }

    public function show($id)
    {
        $app = Application::with(['job', 'candidate', 'resume'])->find($id);
        if (! $app) {
            return response()->json(['error' => 'Không tìm thấy ứng tuyển'], 404);
        }
        return response()->json($app);
    }

    public function destroy($id)
    {
        $app = Application::find($id);
        if (! $app) {
            return response()->json(['error' => 'Không tìm thấy ứng tuyển'], 404);
        }
        $app->delete();
        return response()->json(['message' => 'Xóa ứng tuyển thành công']);
    }
}
