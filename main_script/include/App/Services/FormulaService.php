<?php

namespace App\Services;

use Game\Formulas as LegacyFormulas;

/**
 * Service wrapper around the legacy formulas utility.
 *
 * This class allows the legacy static helper that lives under the
 * `Game\\Formulas` namespace to be referenced via the new
 * `App\\Services` namespace without duplicating the implementation.
 */
class FormulaService extends LegacyFormulas
{
}
