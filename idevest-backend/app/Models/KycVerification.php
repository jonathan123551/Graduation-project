<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KycVerification extends Model
{
    protected $fillable = [
        'user_id',
        'full_legal_name',
        'national_id',
        'date_of_birth',
        'nationality',
        'address',
        'id_card_front',
        'id_card_back',
        'ai_verification_result',
        'status',
        'rejection_reason',
        'reviewed_at',
        'reviewed_by',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'reviewed_at'   => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
