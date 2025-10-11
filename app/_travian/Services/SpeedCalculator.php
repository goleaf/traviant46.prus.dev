<?php

namespace App\Services;

use Game\SpeedCalculator as GameSpeedCalculator;

/**
 * Exposes the SpeedCalculator under the App\Services namespace so it can be
 * type-hinted as a service without refactoring the original implementation.
 */
class SpeedCalculator extends GameSpeedCalculator
{
}
