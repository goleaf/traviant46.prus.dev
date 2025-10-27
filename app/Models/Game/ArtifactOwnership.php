<?php

declare(strict_types=1);

namespace App\Models\Game;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eloquent model that captures which account or village currently holds a specific artifact.
 */
class ArtifactOwnership extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'artifact_id',
        'scope',
        'village_id',
        'account_id',
        'acquired_at',
        'activated_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'acquired_at' => 'datetime',
        'activated_at' => 'datetime',
    ];

    public function artifact(): BelongsTo
    {
        // Link back to the artifact definition so services can load effect metadata when resolving ownership.
        return $this->belongsTo(Artifact::class);
    }

    public function village(): BelongsTo
    {
        // For village-scoped artifacts we expose the owning settlement relation.
        return $this->belongsTo(Village::class);
    }

    public function account(): BelongsTo
    {
        // Account-scoped artifacts resolve to the owning player account.
        return $this->belongsTo(User::class, 'account_id');
    }
}
