<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'body',
        'data',
        'read'
    ];

    protected $casts = [
        'data' => 'array',
        'read' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}