<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeroFace extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'hero_id',
        'gender',
        'skin_tone',
        'hair_color',
        'hair_style',
        'eye_color',
        'facial_hair',
        'features',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'features' => 'array',
    ];

    public function hero(): BelongsTo
    {
        return $this->belongsTo(Hero::class);
    }
}
