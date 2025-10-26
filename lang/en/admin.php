<?php

declare(strict_types=1);

return [
    'campaign_customer_segments' => [
        'fields' => [
            'name' => [
                'label' => 'Segment name',
            ],
            'slug' => [
                'label' => 'Slug',
            ],
            'description' => [
                'label' => 'Description',
            ],
            'filters' => [
                'label' => 'Filters (JSON)',
            ],
            'is_active' => [
                'label' => 'Segment is active',
            ],
        ],
        'status' => [
            'created' => 'Segment created successfully.',
            'updated' => 'Segment updated successfully.',
            'deleted' => 'Segment deleted successfully.',
            'recalculated' => 'Segment recalculated successfully.',
        ],
    ],
];
