<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessRequest extends Model
{
    protected $fillable = [
        'investor_id',
        'founder_id',
        'idea_id',
        'status',
        'message'
    ];

    public function investor()
    {
        return $this->belongsTo(User::class, 'investor_id');
    }

    public function founder()
    {
        return $this->belongsTo(User::class, 'founder_id');
    }

    public function idea()
    {
        return $this->belongsTo(Idea::class);
    }
}