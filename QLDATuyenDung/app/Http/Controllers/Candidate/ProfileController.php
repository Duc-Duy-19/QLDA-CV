<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Profile;
use App\Http\Requests\CandidateUpdateRequest;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $profile = Profile::where('user_id', $user->id)->first();

        // Trả về ảnh base64 cho frontend
        if ($profile && $profile->avatar) {
            $profile->avatar_url = 'data:image/png;base64,' . $profile->avatar;
        }

        return response()->json(['profile' => $profile]);
    }

    public function update(CandidateUpdateRequest $request)
    {
        $user = $request->user();
        $profile = Profile::firstOrNew(['user_id' => $user->id]);
        $data = $request->validated();

        // Nếu có avatar mới thì xử lý và gắn prefix
        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $base64 = base64_encode(file_get_contents($file->getRealPath()));
            $mime = $file->getMimeType();
            $data['avatar'] = 'data:' . $mime . ';base64,' . $base64;
        }

        $profile->fill($data);
        $profile->save();

        return response()->json([
            'message' => 'Cập nhật thông tin thành công!',
            'profile' => $profile
        ]);
    }
}
