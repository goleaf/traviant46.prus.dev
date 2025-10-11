<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AllianceBonus extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'alliance_bonus_upgrade_queue';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'aid',
        'type',
        'time',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'aid' => 'integer',
        'type' => 'integer',
        'time' => 'integer',
    ];

    public function alliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class, 'aid');
    }
}
