<?php

namespace App\Services;

use Game\Starvation as LegacyStarvation;

/**
 * Service wrapper that exposes the legacy starvation logic under the
 * modern application namespace.
 */
class StarvationService extends LegacyStarvation
{
}
