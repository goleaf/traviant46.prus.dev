<?php

declare(strict_types=1);

/**
 * Storefront translation strings organised by domain for the English locale.
 *
 * @var array<string, mixed> $translations Structured copy used by the premium storefront views.
 */
$translations = [
    'checkout' => [
        'headline' => 'Complete your Travian upgrade',
        'read_only' => 'Preview the upcoming premium release and review the contents of your order before checkout opens.',
        'steps_title' => 'How the checkout works',
        'next_step' => 'What happens next',
        'cta' => 'Return to cart overview',
        'steps' => [
            'select_bundle' => [
                'title' => 'Select your bundle',
                'description' => 'Choose the premium bundle that best fits your strategy for the next Travian world.',
            ],
            'confirm_account' => [
                'title' => 'Confirm account access',
                'description' => 'Sign in with the Travian ID that should receive the upgrade when sales open.',
            ],
            'review_order' => [
                'title' => 'Review your order',
                'description' => 'Double-check pricing, taxes, and delivery preferences before submitting payment.',
            ],
            'secure_payment' => [
                'title' => 'Secure payment',
                'description' => 'Complete the payment flow in our secure portal once the preorder window unlocks.',
            ],
        ],
    ],
    'cart' => [
        'summary' => 'Order summary',
        'subtotal' => 'Subtotal',
        'taxes' => 'Estimated taxes',
        'total' => 'Total due',
        'tax_hint' => 'Final taxes are confirmed when checkout opens in your region.',
    ],
    'product' => [
        'cta' => 'Back to premium catalogue',
        'headline_prefix' => 'Travian storefront',
        'includes' => 'This bundle includes',
        'details_heading' => 'More about this bundle',
        'availability_title' => 'Availability',
        'availability' => [
            'in_stock' => 'Available now — activation is instant when checkout unlocks.',
            'limited' => 'Limited supply — secure your upgrade early to guarantee access.',
            'out_of_stock' => 'Currently unavailable — check back soon for the next release.',
        ],
        'delivery' => [
            'instant' => 'Delivered instantly to the linked Travian account after purchase.',
            'email' => 'Instructions arrive via email within minutes once payment clears.',
            'scheduled' => 'Delivery is scheduled to coincide with the next world restart.',
        ],
        'related' => 'You may also like',
    ],
    'catalogue' => [
        'cta' => 'Explore the full catalogue',
    ],
    'common' => [
        'currency_suffix' => ':value :currency',
        'per_item' => 'Per item',
    ],
    'features' => [
        'plus_account' => '30 days of Travian Plus with enhanced build and overview tools.',
        'queue_slots' => 'Additional building queue slots to keep construction moving overnight.',
        'raid_list' => 'Automated raid lists with smart scheduling for efficient farming.',
        'crop_finder' => 'Advanced crop finder filters to secure the perfect capital location.',
        'hero_boosts' => 'Exclusive hero adventures with bonus loot and experience.',
        'trade_routes' => 'Custom trade routes to balance resources between your villages.',
    ],
    'products' => [
        'plus-account' => [
            'name' => 'Travian Plus Account',
            'summary' => 'Unlock build queues, detailed statistics, and UI enhancements for 30 days.',
            'description' => 'The Travian Plus Account keeps your empire efficient with additional build slots, resource overviews, and quick navigation shortcuts that save precious time every session.',
        ],
        'gold-club' => [
            'name' => 'Gold Club Access',
            'summary' => 'Gain raid lists, troop escape controls, and marketplace automation.',
            'description' => 'Gold Club Access equips you with advanced military and economic tools, letting you automate raiding, protect your troops during surprise attacks, and streamline your market activity.',
        ],
        'starter-pack' => [
            'name' => 'Starter Resource Pack',
            'summary' => 'Kick-start your world with a curated bundle of gold and hero boosts.',
            'description' => 'The Starter Resource Pack delivers an upfront burst of gold, hero consumables, and instant-complete vouchers so you can establish momentum before the competition catches up.',
        ],
    ],
];

return $translations;
