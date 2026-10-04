<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Task;

enum Urgency: string
{
    case OVERDUE = 'overdue';
    case DUE = 'due';
    case NORMAL = 'normal';
}
