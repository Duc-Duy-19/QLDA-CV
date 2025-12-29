<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

//
class ResumeHeader extends Model
{
    use HasFactory;

    protected $table = 'resume_headers';

    protected $fillable = [
        'resume_id',
        'Full_name',
        'BirthDay',
        'gender',
        'Phone',
        'Email',
        'Website',
        'address',
        'avatar',
    ];

    protected $casts = [
        'BirthDay' => 'date',
    ];

    public function resume()
    {
        return $this->belongsTo(Resume::class);
    }
}
