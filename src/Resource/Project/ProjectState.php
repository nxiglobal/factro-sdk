<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Project;

enum ProjectState: string
{
    case PLANNED = 'planned';
    case IN_PROCESS = 'inProcess';
    case CLOSED = 'closed';
    case PUSHED_BACK = 'pushedBack';
    case STOPPED = 'stopped';
}
