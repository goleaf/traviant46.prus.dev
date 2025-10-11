<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeroItem extends Model
{
    use HasFactory;

    protected $table = 'items';

    public $timestamps = false;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'uid',
        'btype',
        'type',
        'num',
        'placeId',
        'proc',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'uid' => 'integer',
        'btype' => 'integer',
        'type' => 'integer',
        'num' => 'integer',
        'placeId' => 'integer',
        'proc' => 'boolean',
    ];

    public function hero(): BelongsTo
    {
        return $this->belongsTo(Hero::class, 'uid', 'uid');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uid');
    }
}
