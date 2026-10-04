<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Task;

enum TaskPriority: int
{
    case PRIORITY_10 = 10;
    case PRIORITY_20 = 20;
    case PRIORITY_30 = 30;
    case PRIORITY_40 = 40;
    case PRIORITY_50 = 50;
    case PRIORITY_60 = 60;
    case PRIORITY_70 = 70;
    case PRIORITY_80 = 80;
    case PRIORITY_90 = 90;
    case PRIORITY_99 = 99;
}
