<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_id',
        'reviewer_id',
        'reviewed_id',
        'rating',
        'comment',
        'criteria',
        'response',
        'is_published'
    ];

    protected $casts = [
        'criteria' => 'array',
        'is_published' => 'boolean'
    ];

    public function match()
    {
        return $this->belongsTo(MatchModel::class, 'match_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviewed()
    {
        return $this->belongsTo(User::class, 'reviewed_id');
    }
}
