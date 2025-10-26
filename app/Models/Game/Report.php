<?php

declare(strict_types=1);

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Stringable;

use function collect;

class Report extends Model
{
    /** @use HasFactory<\Database\Factories\Game\ReportFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'world_id',
        'for_user_id',
        'kind',
        'data',
        'created_at',
    ];

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function getCasualtiesSummaryAttribute(): string
    {
        return $this->formatDataValue($this->data['casualties'] ?? null);
    }

    public function getBountySummaryAttribute(): string
    {
        return $this->formatDataValue($this->data['bounty'] ?? null);
    }

    public function getDamagesSummaryAttribute(): string
    {
        return $this->formatDataValue($this->data['damages'] ?? null);
    }

    private function formatDataValue(mixed $value): string
    {
        $text = $this->stringifyData($value);

        return $text !== '' ? $text : 'None recorded';
    }

    private function stringifyData(mixed $value): string
    {
        if (is_array($value)) {
            return collect($value)
                ->map(function ($item, $key): ?string {
                    $label = is_string($key) ? Str::headline((string) $key).': ' : '';
                    $itemValue = $this->stringifyData($item);

                    if ($itemValue === '') {
                        return null;
                    }

                    return $label.$itemValue;
                })
                ->filter()
                ->implode(', ');
        }

        if ($value instanceof Stringable) {
            return (string) $value;
        }

        if (is_scalar($value) && $value !== '') {
            return (string) $value;
        }

        if ($value === null || $value === '') {
            return '';
        }

        $encoded = json_encode($value);

        return is_string($encoded) ? $encoded : '';
    }
}
