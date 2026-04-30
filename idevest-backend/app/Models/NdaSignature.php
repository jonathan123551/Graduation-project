<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NdaSignature extends Model
{
    protected $table = 'nda_signatures';

    protected $fillable = [
        'idea_id',
        'user_id',
        'other_user_id',
        'signed_at',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];
}
