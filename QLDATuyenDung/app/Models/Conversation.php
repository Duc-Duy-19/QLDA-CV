<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

//
class Conversation extends Model
{
    use HasFactory;

    protected $table = 'conversations';

    protected $primaryKey = 'id_conversa';

    protected $fillable = [
        'id_user1_FK',
        'id_user2_FK',
    ];

    // Relationships
    public function user1()
    {
        return $this->belongsTo(User::class, 'id_user1_FK', 'id');
    }

    public function user2()
    {
        return $this->belongsTo(User::class, 'id_user2_FK', 'id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'id_conversa_FK', 'id_conversa');
    }
}
