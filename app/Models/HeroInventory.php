<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeroInventory extends Model
{
    use HasFactory;

    protected $table = 'inventory';

    protected $primaryKey = 'uid';

    public $incrementing = false;

    protected $keyType = 'int';

    public $timestamps = false;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'uid',
        'helmet',
        'body',
        'leftHand',
        'rightHand',
        'shoes',
        'horse',
        'bag',
        'lastupdate',
        'lastWaterBucketUse',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'uid' => 'integer',
        'helmet' => 'integer',
        'body' => 'integer',
        'leftHand' => 'integer',
        'rightHand' => 'integer',
        'shoes' => 'integer',
        'horse' => 'integer',
        'bag' => 'integer',
        'lastupdate' => 'integer',
        'lastWaterBucketUse' => 'integer',
    ];

    public function hero(): BelongsTo
    {
        return $this->belongsTo(Hero::class, 'uid', 'uid');
    }
}
