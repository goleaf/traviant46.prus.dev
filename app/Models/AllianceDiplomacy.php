<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AllianceDiplomacy extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'diplomacy';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'aid1',
        'aid2',
        'type',
        'accepted',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'aid1' => 'integer',
        'aid2' => 'integer',
        'type' => 'integer',
        'accepted' => 'integer',
    ];

    public function sourceAlliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class, 'aid1');
    }

    public function targetAlliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class, 'aid2');
    }
}
