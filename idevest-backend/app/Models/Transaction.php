<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'amount',
        'status',
        'payment_method',
        'reference'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}