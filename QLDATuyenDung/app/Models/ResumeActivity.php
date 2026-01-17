<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

//
class ResumeActivity extends Model
{
    use HasFactory;

    protected $table = 'resume_activities';

    protected $fillable = [
        'resume_id',
        'organization_name',
        'role',
        'start_date',
        'end_date',
        'description',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function resume()
    {
        return $this->belongsTo(Resume::class);
    }
}
