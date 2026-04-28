<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Idea extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'founder_id',
        'title',
        'description',
        'sector',
        'location',
        'capital_required',
        'capital_required_usd',
        'ai_score',
        'risk_score',
        'market_score',
        'status',
        'decision',
        'pitch_deck_url',
        'evaluation_version',
        'ai_evaluation'
    ];

    public function founder()
    {
        return $this->belongsTo(User::class, 'founder_id');
    }

    public function deals()
    {
        return $this->hasMany(Deal::class);
    }

    public function savedBy()
    {
        return $this->hasMany(SavedIdea::class);
    }

    public function accessRequests()
    {
        return $this->hasMany(AccessRequest::class);
    }
}