<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\User;

enum AbsenceType: string
{
    case PLANNED = 'Planned';
    case UNPLANNED = 'Unplanned';
}
