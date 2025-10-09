<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestDefinition extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'category',
        'title',
        'description',
        'requirements',
        'rewards',
        'repeatable',
    ];

    protected $casts = [
        'requirements' => 'array',
        'rewards' => 'array',
        'repeatable' => 'boolean',
    ];

    public function progress(): HasMany
    {
        return $this->hasMany(QuestProgress::class);
    }
}
