<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

//
class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'address',
        'description',
        'website',
        'logo',
        'email',
        'phone',
        'status',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'company_users')
            ->withPivot('role_in_company', 'status', 'joined_at')
            ->withTimestamps();
    }

    public function companyUsers()
    {
        return $this->hasMany(CompanyUser::class);
    }
    public function jobs()
    {
        return $this->hasMany(Job::class);
    }
}
