<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

//
class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'profile_id',
        'user_id',
        'job_id',
        'resume_id',
        'status',
        'applied_at',
        'cv_file_url',
        'cover_letter',
        'resume_snapshot',
        'applicant_name',
        'applicant_email',
    ];

    protected $casts = [
        'applied_at' => 'datetime',
        'resume_snapshot' => 'array',
    ];

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }
    public function resume()
    {
        return $this->belongsTo(Resume::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
