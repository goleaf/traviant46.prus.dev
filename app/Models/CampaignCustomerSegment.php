<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CampaignCustomerSegment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'filters',
        'is_active',
        'match_count',
        'last_calculated_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'is_active' => 'boolean',
        'last_calculated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $segment): void {
            if (blank($segment->slug)) {
                $segment->slug = Str::slug($segment->name);
            }
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function normalizedFilters(): array
    {
        return array_values(array_filter($this->filters ?? [], function ($filter): bool {
            return is_array($filter) && isset($filter['field'], $filter['operator']);
        }));
    }

    public function applyToQuery(Builder $query): Builder
    {
        foreach ($this->normalizedFilters() as $filter) {
            $query = $this->applySingleFilter($query, $filter);
        }

        return $query;
    }

    public function recalculateMatchCount(): void
    {
        $count = $this->applyToQuery(User::query())->count();

        $this->forceFill([
            'match_count' => $count,
            'last_calculated_at' => Carbon::now(),
        ])->save();
    }

    public function summarizeFilters(): array
    {
        return array_map(function (array $filter): array {
            return [
                'field' => $filter['field'],
                'operator' => $filter['operator'],
                'value' => $filter['value'] ?? null,
            ];
        }, $this->normalizedFilters());
    }

    private function applySingleFilter(Builder $query, array $filter): Builder
    {
        $field = Arr::get($filter, 'field');
        $operator = Arr::get($filter, 'operator');
        $value = Arr::get($filter, 'value');

        if (! is_string($field) || ! is_string($operator)) {
            return $query;
        }

        $dateFields = ['created_at', 'last_login_at', 'last_owner_login_at'];

        if (in_array($field, $dateFields, true) && ! empty($value)) {
            $value = Carbon::parse($value);
        }

        return match ($operator) {
            'equals' => $query->where($field, $value),
            'not_equals' => $query->where($field, '!=', $value),
            'contains' => $query->where($field, 'like', '%' . $value . '%'),
            'starts_with' => $query->where($field, 'like', $value . '%'),
            'ends_with' => $query->where($field, 'like', '%' . $value),
            'greater_than' => $query->where($field, '>', $value),
            'greater_than_or_equal_to' => $query->where($field, '>=', $value),
            'less_than' => $query->where($field, '<', $value),
            'less_than_or_equal_to' => $query->where($field, '<=', $value),
            'is_null' => $query->whereNull($field),
            'is_not_null' => $query->whereNotNull($field),
            default => $query,
        };
    }
}
