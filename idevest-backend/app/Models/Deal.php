<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Deal extends Model
{
    use HasFactory;

    protected $fillable = [
        'idea_id',
        'investor_id',
        'founder_id',
        'investment_amount',
        'equity_percentage',
        'valuation',
        'platform_fee',
        'status',
        'payment_status',
        'terms',
        'accepted_at',
        'nda_signed_at',
    ];

    protected $casts = [
        'investment_amount' => 'decimal:2',
        'equity_percentage' => 'decimal:2',
        'valuation'         => 'decimal:2',
        'platform_fee'      => 'decimal:2',
        'accepted_at'       => 'datetime',
        'nda_signed_at'     => 'datetime',
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
