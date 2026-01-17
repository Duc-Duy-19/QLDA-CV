<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

//
class ResumeExtrainfo extends Model
{
    use HasFactory;

    protected $table = 'resume_extrainfos';

    protected $fillable = [
        'resume_id',
        'content',
    ];

    public function resume()
    {
        return $this->belongsTo(Resume::class);
    }
}
