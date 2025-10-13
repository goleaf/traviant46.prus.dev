<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyQuestProgress extends Model
{
    use HasFactory;

    protected $table = 'daily_quest';

    public $timestamps = false;

    protected $fillable = [
        'uid',
        'lastDailyQuestReset',
    ];

    protected $casts = [
        'uid' => 'integer',
        'lastDailyQuestReset' => 'integer',
    ];
}
