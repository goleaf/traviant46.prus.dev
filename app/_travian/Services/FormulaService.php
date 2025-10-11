<?php

namespace App\Services;

use Game\Formulas;

/**
 * Service wrapper around the legacy Game\Formulas static helper.
 *
 * This class exists to provide an "App" namespaced entry point for
 * dependency injection containers or other modern service discovery logic
 * while keeping the original implementation untouched.
 */
class FormulaService extends Formulas
{
}
