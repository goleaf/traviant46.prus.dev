<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Models\CampaignCustomerSegment;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use JsonException;

class CampaignCustomerSegmentResource
{
    public const FIELDS = [
        'name' => [
            'label' => 'Name',
            'type' => 'string',
        ],
        'username' => [
            'label' => 'Username',
            'type' => 'string',
        ],
        'email' => [
            'label' => 'Email',
            'type' => 'string',
        ],
        'last_login_at' => [
            'label' => 'Last Login At',
            'type' => 'datetime',
        ],
        'created_at' => [
            'label' => 'Created At',
            'type' => 'datetime',
        ],
        'last_login_ip' => [
            'label' => 'Last Login IP',
            'type' => 'string',
        ],
    ];

    public const OPERATORS = [
        'equals' => [
            'label' => 'Equals',
            'requires_value' => true,
        ],
        'not_equals' => [
            'label' => 'Does not equal',
            'requires_value' => true,
        ],
        'contains' => [
            'label' => 'Contains',
            'requires_value' => true,
        ],
        'starts_with' => [
            'label' => 'Starts with',
            'requires_value' => true,
        ],
        'ends_with' => [
            'label' => 'Ends with',
            'requires_value' => true,
        ],
        'greater_than' => [
            'label' => 'Greater than',
            'requires_value' => true,
        ],
        'greater_than_or_equal_to' => [
            'label' => 'Greater than or equal to',
            'requires_value' => true,
        ],
        'less_than' => [
            'label' => 'Less than',
            'requires_value' => true,
        ],
        'less_than_or_equal_to' => [
            'label' => 'Less than or equal to',
            'requires_value' => true,
        ],
        'is_null' => [
            'label' => 'Is empty',
            'requires_value' => false,
        ],
        'is_not_null' => [
            'label' => 'Is not empty',
            'requires_value' => false,
        ],
    ];

    public static function formSchema(): array
    {
        return [
            'name' => [
                'label' => 'Segment name',
                'placeholder' => 'Players active last week',
                'hint' => 'Used to identify the segment throughout the admin panel.',
            ],
            'slug' => [
                'label' => 'Slug',
                'placeholder' => 'players-active-last-week',
                'hint' => 'Automatically generated when left empty. Must be unique.',
            ],
            'description' => [
                'label' => 'Description',
                'placeholder' => 'Optional context for other administrators.',
            ],
            'filters' => [
                'label' => 'Filters (JSON)',
                'placeholder' => json_encode([
                    ['field' => 'last_login_at', 'operator' => 'greater_than', 'value' => now()->subDays(7)->toIso8601String()],
                ], JSON_PRETTY_PRINT),
                'hint' => 'Provide an array of filter rules. Each filter requires a field, operator and optional value.',
            ],
            'is_active' => [
                'label' => 'Segment is active',
                'hint' => 'Inactive segments are hidden from campaign builders.',
            ],
        ];
    }

    public static function tableColumns(): array
    {
        return [
            'name' => 'Name',
            'slug' => 'Slug',
            'match_count' => 'Matched Users',
            'is_active' => 'Active',
            'last_calculated_at' => 'Last Calculated',
            'updated_at' => 'Last Updated',
        ];
    }

    public static function rules(?CampaignCustomerSegment $segment = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('campaign_customer_segments', 'slug')->ignore($segment),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public static function decodeFilters(?string $raw): array
    {
        if (blank($raw)) {
            return [];
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('The filters JSON is invalid: '.$exception->getMessage());
        }

        if (! is_array($decoded)) {
            throw new InvalidArgumentException('The filters must be provided as an array.');
        }

        $filters = [];

        foreach ($decoded as $index => $filter) {
            if (! is_array($filter)) {
                throw new InvalidArgumentException('Filter #'.($index + 1).' must be an object.');
            }

            $field = Arr::get($filter, 'field');
            $operator = Arr::get($filter, 'operator');
            $value = Arr::get($filter, 'value');

            if (! is_string($field) || ! array_key_exists($field, self::FIELDS)) {
                throw new InvalidArgumentException('Filter #'.($index + 1).' references an unknown field.');
            }

            if (! is_string($operator) || ! array_key_exists($operator, self::OPERATORS)) {
                throw new InvalidArgumentException('Filter #'.($index + 1).' uses an unsupported operator.');
            }

            $requiresValue = self::OPERATORS[$operator]['requires_value'];

            if ($requiresValue) {
                if ($value === null || (is_string($value) && trim($value) === '')) {
                    throw new InvalidArgumentException('Filter #'.($index + 1).' requires a value.');
                }

                $value = self::normalizeFilterValue($field, $value);
            } else {
                $value = null;
            }

            $filters[] = [
                'field' => $field,
                'operator' => $operator,
                'value' => $value,
            ];
        }

        return $filters;
    }

    public static function filtersPreview(array $filters): string
    {
        if (empty($filters)) {
            return 'No filters defined';
        }

        return collect($filters)
            ->map(function (array $filter): string {
                $fieldLabel = Arr::get(self::FIELDS, $filter['field'].'.label', Str::title(str_replace('_', ' ', $filter['field'])));
                $operatorLabel = Arr::get(self::OPERATORS, $filter['operator'].'.label', $filter['operator']);
                $value = $filter['value'] ?? '—';

                if (is_array($value)) {
                    $value = json_encode($value);
                }

                return sprintf('%s %s %s', $fieldLabel, strtolower($operatorLabel), $value);
            })
            ->implode('; ');
    }

    private static function normalizeFilterValue(string $field, mixed $value): mixed
    {
        $fieldType = Arr::get(self::FIELDS, $field.'.type', 'string');

        return match ($fieldType) {
            'datetime' => self::parseDateValue($value),
            default => is_scalar($value) ? (string) $value : $value,
        };
    }

    private static function parseDateValue(mixed $value): string
    {
        if ($value instanceof Carbon) {
            return $value->toIso8601String();
        }

        return Carbon::parse((string) $value)->toIso8601String();
    }
}
