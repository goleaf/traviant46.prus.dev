<?php
namespace Core\Caching;

use App\ValueObjects\Travian\GlobalCacheKey;

class GlobalCaching extends Caching
{
    public static function singleton($key = null)
    {
        return parent::singleton(trim(GlobalCacheKey::value() . ':'));
    }
}