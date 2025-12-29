<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

//
class ResumeProject extends Model
{
    use HasFactory;

    protected $table = 'resume_projects';

    protected $fillable = [
        'resume_id',
        'project_name',
        'role',
        'technologies',
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
