<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\User;

enum SecurityGroup: string
{
    case ALL_RIGHTS = 'AllRights';
    case BASIC_RIGHTS = 'BasicRights';
    case ADVANCED_RIGHTS = 'AdvancedRights';
    case GUEST_RIGHTS = 'GuestRights';

    public function isGuest(): bool
    {
        return self::GUEST_RIGHTS === $this;
    }
}
