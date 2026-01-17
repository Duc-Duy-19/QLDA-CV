<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

//
class Resume extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'personal_info',
        'skills_summary',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function educations()
    {
        return $this->hasMany(ResumeEducation::class);
    }

    public function experiences()
    {
        return $this->hasMany(ResumeExperience::class);
    }

    public function activities()
    {
        return $this->hasMany(ResumeActivity::class);
    }

    public function projects()
    {
        return $this->hasMany(ResumeProject::class);
    }

    public function awards()
    {
        return $this->hasMany(ResumeAward::class);
    }

    public function certifications()
    {
        return $this->hasMany(ResumeCertification::class);
    }

    public function header()
    {
        return $this->hasOne(ResumeHeader::class);
    }

    public function extraInfos()
    {
        return $this->hasMany(ResumeExtrainfo::class);
    }

    public function hobbies()
    {
        return $this->hasMany(ResumeHobby::class);
    }

    public function skills()
    {
        return $this->hasMany(ResumeSkill::class);
    }

    public function references()
    {
        return $this->hasMany(ResumeReference::class);
    }
}
