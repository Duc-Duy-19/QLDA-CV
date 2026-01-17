<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

//
class ResumeAward extends Model
{
    use HasFactory;

    protected $table = 'resume_awards';

    protected $fillable = [
        'resume_id',
        'award_name',
        'organization',
        'date_received',
        'description',
    ];

    protected $casts = [
        'date_received' => 'date',
    ];

    public function resume()
    {
        return $this->belongsTo(Resume::class);
    }
}
