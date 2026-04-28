<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedIdea extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'idea_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ideas()
    {
        return $this->belongsTo(Idea::class, 'idea_id');
    }
}