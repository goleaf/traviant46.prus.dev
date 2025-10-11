<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArtifactLog extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'artifact_id',
        'captor_user_id',
        'captor_village_id',
        'captor_name_snapshot',
        'captured_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'captured_at' => 'datetime',
    ];

    public function artifact(): BelongsTo
    {
        return $this->belongsTo(Artifact::class);
    }

    public function captor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'captor_user_id');
    }

    public function captorVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'captor_village_id');
    }
}
