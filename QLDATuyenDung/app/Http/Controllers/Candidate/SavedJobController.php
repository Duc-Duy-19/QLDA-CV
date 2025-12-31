<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\SavedJob;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SavedJobController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();

        $saved = SavedJob::with('job')
            ->where('user_id', $userId)
            ->orderByDesc('saved_at')
            ->paginate($request->get('per_page', 20));

        return response()->json($saved);
    }

    public function store(Request $request)
    {
        $request->validate([
            'job_id' => 'required|exists:jobs,id',
        ]);

        $userId = Auth::id();

        $job = Job::find($request->job_id);
        if (! $job) {
            return response()->json(['error' => 'Job không tồn tại'], 404);
        }

        $saved = SavedJob::firstOrCreate(
            ['user_id' => $userId, 'job_id' => $job->id],
            ['saved_at' => now()]
        );

        return response()->json(['message' => 'Lưu job thành công', 'data' => $saved], $saved->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy($jobId)
    {
        $userId = Auth::id();

        $saved = SavedJob::where('user_id', $userId)
            ->where('job_id', $jobId)
            ->first();

        if (! $saved) {
            return response()->json(['error' => 'Saved job không tồn tại'], 404);
        }

        $saved->delete();

        return response()->json(['message' => 'Bỏ lưu job thành công']);
    }
}
