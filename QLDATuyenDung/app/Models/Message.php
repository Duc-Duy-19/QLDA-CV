<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

//
class Message extends Model
{
    use HasFactory;

    protected $table = 'messages';

    protected $primaryKey = 'id__message';

    protected $fillable = [
        'id_conversa_FK',
        'id_user_FK',
        'content',
        'send_at',
    ];

    protected $casts = [
        'send_at' => 'datetime',
    ];

    // Relationships
    public function conversation()
    {
        return $this->belongsTo(Conversation::class, 'id_conversa_FK', 'id_conversa');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user_FK', 'id');
    }
}
