<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

//
class Job extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'title',
        'description',
        'requirements',
        'salary_range',
        'location',
        'employment_type',
        'posted_date',
        'expiration_date',
        'status',
    ];

    protected $casts = [
        'posted_date' => 'datetime',
        'expiration_date' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'job_category');
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    public function savedBy()
    {
        return $this->hasMany(SavedJob::class);
    }
}
