<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Deal extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'idea_id',
        'investor_id',
        'founder_id',
        'amount',
        'equity_percentage',
        'status',
        'terms',
        'accepted_at'
    ];

    protected $casts = [
        'accepted_at' => 'datetime'
    ];

    public function idea()
    {
        return $this->belongsTo(Idea::class);
    }

    public function investor()
    {
        return $this->belongsTo(User::class, 'investor_id');
    }

    public function founder()
    {
        return $this->belongsTo(User::class, 'founder_id');
    }
}