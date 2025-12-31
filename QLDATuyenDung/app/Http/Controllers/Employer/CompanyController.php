<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompanyRequest;
use App\Models\Company;
use App\Models\CompanyUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CompanyController extends Controller
{
    //
    public function getMyCompany()
    {
        $userId = Auth::id();

        $companyUser = CompanyUser::where('user_id', $userId)
            ->where('company_users.status', 'active') // Chỉ định bảng tránh ambiguous
            ->whereIn('role_in_company', ['Owner', 'Admin'])
            ->with('company') // Chỉ load company, bỏ users
            ->first();

        if (!$companyUser || !$companyUser->company) {
            return response()->json([
                'error' => 'Bạn chưa thuộc công ty nào hoặc công ty chưa được duyệt'
            ], 404);
        }

        return response()->json([
            'company' => $companyUser->company,
            'user_role' => $companyUser->role_in_company
        ], 200);
    }
    public function store(StoreCompanyRequest $request)
    {
        $data = $request->validated();

        // Xử lý logo base64 nếu có upload
        if ($request->hasFile('logo')) {
            $image = $request->file('logo');
            $imageData = base64_encode(file_get_contents($image->getRealPath()));
            $mime = $image->getMimeType();
            $data['logo'] = 'data:' . $mime . ';base64,' . $imageData;
        }

        return DB::transaction(function () use ($data) {
            // Tạo công ty trạng thái pending
            $company = Company::create([
                'company_name' => $data['company_name'],
                'address'      => $data['address'] ?? null,
                'description'  => $data['description'] ?? null,
                'website'      => $data['website'] ?? null,
                'email'        => $data['email'] ?? null,
                'phone'        => $data['phone'] ?? null,
                'logo'         => $data['logo'] ?? null,
                'status'       => 'pending',
            ]);

            // Gắn user hiện tại làm Owner nhưng ở trạng thái pending (đợi duyệt)
            CompanyUser::create([
                'company_id'      => $company->id,
                'user_id'         => Auth::id(),
                'role_in_company' => 'Owner',
                'status'          => 'pending',   // SỬA: để pending, KHÔNG active ngay
                'joined_at'       => null,        // sẽ set khi approve
            ]);

            return response()->json([
                'message' => 'Đăng ký công ty thành công, chờ admin duyệt',
                'company' => $company
            ], 201);
        });
    }

    public function update(StoreCompanyRequest $request, $id)
    {
        $company = Company::findOrFail($id);

        // Chỉ cho phép cập nhật khi công ty đã được duyệt
        if ($company->status !== 'active') {
            return response()->json([
                'error' => 'Công ty chưa được duyệt. Chỉ công ty active mới được cập nhật.'
            ], 403);
        }

        // Kiểm tra user hiện tại có phải Owner và membership đang active không
        $companyUser = CompanyUser::where('company_id', $company->id)
            ->where('user_id', Auth::id())
            ->first();

        if (
            !$companyUser ||
            $companyUser->role_in_company !== 'Owner' ||
            $companyUser->status !== 'active'
        ) {
            return response()->json([
                'error' => 'Chỉ Owner (đã active) mới có quyền chỉnh sửa thông tin công ty'
            ], 403);
        }

        $data = $request->validated();

        // Nếu có logo mới thì cập nhật base64
        if ($request->hasFile('logo')) {
            $image = $request->file('logo');
            $imageData = base64_encode(file_get_contents($image->getRealPath()));
            $mime = $image->getMimeType();
            $data['logo'] = 'data:' . $mime . ';base64,' . $imageData;
        }

        // Cập nhật thông tin công ty
        $company->update([
            'company_name' => $data['company_name'] ?? $company->company_name,
            'address'      => $data['address'] ?? $company->address,
            'description'  => $data['description'] ?? $company->description,
            'website'      => $data['website'] ?? $company->website,
            'email'        => $data['email'] ?? $company->email,
            'phone'        => $data['phone'] ?? $company->phone,
            'logo'         => $data['logo'] ?? $company->logo,
        ]);

        return response()->json([
            'message' => 'Cập nhật thông tin công ty thành công',
            'company' => $company
        ]);
    }
}
