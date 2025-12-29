<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_users')
            ->withPivot('role_in_company', 'status', 'joined_at')
            ->withTimestamps();
    }
    public function companyUsers()
    {
        return $this->hasMany(CompanyUser::class);
    }


    public function resumes()
    {
        return $this->hasMany(Resume::class);
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    public function savedJobs()
    {
        return $this->hasMany(SavedJob::class);
    }

    // public function conversationAsUser1()
    // {
    //     return $this->hasMany(Conversation::class,'user1_id');
    // }

    // public function conversationAsUser2()
    // {
    //     return $this->hasMany(Conversation::class,'user2_id');
    // }

    // public function messages()
    // {
    //     return $this->hasMany(Message::class);
    // }

    // public function notifications()
    // {
    //     return $this->hasMany(Notification::class);
    // }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected static function booted()
    {
        static::created(function ($user) {
            $pendingInvites = \App\Models\CompanyUser::whereNull('user_id')
                ->where('email', $user->email)
                ->where('status', 'pending')
                ->get();

            foreach ($pendingInvites as $invite) {
                $invite->update([
                    'user_id' => $user->id,
                ]);
            }
        });
    }
}
