<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'p1Name',
        'p2Name',
        'board',
        'bag',
        'p1Rack',
        'p2Rack',
        'turnCount',
        'winner',
        'p1Score',
        'p2Score',
    ];

    protected $casts = [
        'board' => 'array',
        'bag' => 'array',
        'p1Rack' => 'array',
        'p2Rack' => 'array',
    ];
}
