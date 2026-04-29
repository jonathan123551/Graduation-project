<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiChatMessage extends Model
{
    protected $fillable = [
        'user_id',
        'role',
        'message',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
