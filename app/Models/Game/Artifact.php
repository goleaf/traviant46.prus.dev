<?php

declare(strict_types=1);

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Eloquent model representing a Travian artifact definition and its JSON effect payload.
 */
class Artifact extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'size',
        'scope',
        'effect',
        'treasury_level_req',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'effect' => 'array',
        'treasury_level_req' => 'integer',
    ];

    public function ownership(): HasOne
    {
        // Surface the active ownership relation so gameplay services can fetch holder details quickly.
        return $this->hasOne(ArtifactOwnership::class);
    }
}
