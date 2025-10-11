<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeroFace extends Model
{
    use HasFactory;

    protected $table = 'face';

    protected $primaryKey = 'uid';

    public $incrementing = false;

    protected $keyType = 'int';

    public $timestamps = false;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'uid',
        'headProfile',
        'hairColor',
        'hairStyle',
        'ears',
        'eyebrow',
        'eyes',
        'nose',
        'mouth',
        'beard',
        'gender',
        'lastupdate',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'uid' => 'integer',
        'headProfile' => 'integer',
        'hairColor' => 'integer',
        'hairStyle' => 'integer',
        'ears' => 'integer',
        'eyebrow' => 'integer',
        'eyes' => 'integer',
        'nose' => 'integer',
        'mouth' => 'integer',
        'beard' => 'integer',
        'lastupdate' => 'integer',
    ];

    public function hero(): BelongsTo
    {
        return $this->belongsTo(Hero::class, 'uid', 'uid');
    }
}
