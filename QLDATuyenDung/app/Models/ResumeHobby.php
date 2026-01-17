<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

//
class ResumeHobby extends Model
{
    use HasFactory;

    protected $table = 'resume_hobbies';

    protected $fillable = [
        'resume_id',
        'description',
    ];

    public function resume()
    {
        return $this->belongsTo(Resume::class);
    }
}
