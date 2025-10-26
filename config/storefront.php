<?php

return [
    /*
     |--------------------------------------------------------------------------
     | Storefront Defaults
     |--------------------------------------------------------------------------
     | These values seed the in-memory repository that powers the catalogue
     | views and ensure consistent content across controllers and Livewire
     | components during local development and automated testing.
     */
    'currency' => 'USD',

    'checkout' => [
        'steps' => ['account_details', 'payment_method', 'review_order'],
        'next_release' => 'storefront.checkout.next_release_notice',
        'cart' => [
            'currency' => 'USD',
            'summary' => [
                'subtotal' => 24.00,
                'tax_amount' => 2.40,
                'total' => 26.40,
            ],
        ],
        'meta' => [
            'title' => 'Complete your TravianT order',
            'description' => 'Review the premium bundle in your cart and confirm your payment preferences.',
            'og_image' => 'images/storefront/checkout-og.jpg',
        ],
    ],

    'products' => [
        'starter-pack' => [
            'slug' => 'starter-pack',
            'name' => 'Starter Pack',
            'summary' => 'Jumpstart your first village with premium resources and build boosts.',
            'description' => 'Includes a balanced resource boost, a temporary construction queue slot, and advisor tips to guide your opening moves.',
            'price' => 9.99,
            'features' => [
                'resource-boost',
                'construction-queue',
                'advisor-tips',
            ],
            'availability' => 'in_stock',
            'delivery' => 'instant',
            'image' => 'images/storefront/products/starter-pack.jpg',
            'meta' => [
                'title' => 'Starter Pack – TravianT Boost',
                'description' => 'Accelerate your first village with resource boosts, queue slots, and advisor support.',
            ],
            'related' => ['village-expansion', 'hero-bundle'],
        ],
        'village-expansion' => [
            'slug' => 'village-expansion',
            'name' => 'Village Expansion Kit',
            'summary' => 'Plan your second village with settler support and crop scouting intel.',
            'description' => 'Unlocks advisor briefings, crop finder access, and a shipment of resources tuned for early expansion.',
            'price' => 19.99,
            'features' => [
                'expansion-blueprint',
                'crop-intel',
                'settler-support',
            ],
            'availability' => 'limited',
            'delivery' => 'scheduled',
            'image' => 'images/storefront/products/village-expansion.jpg',
            'meta' => [
                'title' => 'Village Expansion Kit – TravianT',
                'description' => 'Prepare a new settlement with scout intel, build plans, and resource shipments.',
            ],
            'related' => ['starter-pack', 'hero-bundle'],
        ],
        'hero-bundle' => [
            'slug' => 'hero-bundle',
            'name' => 'Hero Mastery Bundle',
            'summary' => 'Train your hero faster with exclusive artifacts and experience boosts.',
            'description' => 'Features repeatable training quests, an artifact shard, and coaching sessions focused on hero specialisation.',
            'price' => 14.99,
            'features' => [
                'hero-artifact',
                'experience-boost',
                'coaching-session',
            ],
            'availability' => 'in_stock',
            'delivery' => 'instant',
            'image' => 'images/storefront/products/hero-bundle.jpg',
            'meta' => [
                'title' => 'Hero Mastery Bundle – TravianT',
                'description' => 'Empower your hero with artifact shards, experience accelerators, and tactical coaching.',
            ],
            'related' => ['starter-pack'],
        ],
    ],
];
