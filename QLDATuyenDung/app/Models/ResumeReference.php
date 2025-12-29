<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

//
class ResumeReference extends Model
{
    use HasFactory;

    protected $table = 'resume_references';

    protected $fillable = [
        'resume_id',
        'name',
        'relationship',
        'contact_info',
    ];

    public function resume()
    {
        return $this->belongsTo(Resume::class);
    }
}
