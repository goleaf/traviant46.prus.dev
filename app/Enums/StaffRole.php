<?php

declare(strict_types=1);

namespace App\Enums;

enum StaffRole: string
{
    case Player = 'player';
    case Viewer = 'viewer';
    case CatalogManager = 'catalog_manager';
    case ProductManager = 'product_manager';
    case OrderManager = 'order_manager';
    case CustomerSupport = 'customer_support';
    case Legal = 'legal';
    case SettingsManager = 'settings_manager';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Player => 'Player',
            self::Viewer => 'Viewer',
            self::CatalogManager => 'Catalog Manager',
            self::ProductManager => 'Product Manager',
            self::OrderManager => 'Order Manager',
            self::CustomerSupport => 'Customer Support',
            self::Legal => 'Legal',
            self::SettingsManager => 'Settings Manager',
            self::Admin => 'Administrator',
        };
    }

    public function canAccessCatalog(): bool
    {
        return in_array($this, [self::Admin, self::ProductManager, self::CatalogManager], true);
    }

    public function canManageProducts(): bool
    {
        return $this->canAccessCatalog();
    }

    public function canManageOrders(): bool
    {
        return in_array($this, [self::Admin, self::ProductManager, self::OrderManager], true);
    }

    public function canManageCustomers(): bool
    {
        return in_array($this, [self::Admin, self::ProductManager, self::OrderManager, self::CustomerSupport], true);
    }

    public function canReviewLegal(): bool
    {
        return in_array($this, [self::Admin, self::Legal], true);
    }

    public function canManageSettings(): bool
    {
        return in_array($this, [self::Admin, self::SettingsManager], true);
    }
}
