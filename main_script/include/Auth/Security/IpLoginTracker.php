<?php

namespace Auth\Security;

use Core\Helper\IPTracker;

class IpLoginTracker
{
    public function trackLogin(int $uid): void
    {
        if ($uid <= 0) {
            return;
        }
        IPTracker::addCurrentIP($uid);
    }
}
