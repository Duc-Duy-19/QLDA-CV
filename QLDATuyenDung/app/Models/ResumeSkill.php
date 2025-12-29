<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

//
class ResumeSkill extends Model
{
    use HasFactory;

    protected $table = 'resume_skills';

    protected $fillable = [
        'resume_id',
        'skill_name',
    ];

    public function resume()
    {
        return $this->belongsTo(Resume::class);
    }
}
