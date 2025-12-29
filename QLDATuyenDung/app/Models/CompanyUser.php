<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

//
class CompanyUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'email',
        'role_in_company',
        'status',
        'joined_at',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
