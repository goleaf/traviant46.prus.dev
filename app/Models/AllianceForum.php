<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AllianceForum extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'forum_forums';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'aid',
        'name',
        'forum_desc',
        'area',
        'sitter',
        'pos',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'aid' => 'integer',
        'area' => 'integer',
        'sitter' => 'integer',
        'pos' => 'integer',
    ];

    public function alliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class, 'aid');
    }
}
