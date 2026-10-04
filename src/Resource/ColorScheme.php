<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource;

/**
 * Colours the factro web UI offers for tasks, packages and projects (colorScheme), read from the
 * requests the UI sends. The API itself accepts and stores any string, so writers should go through
 * this enum; no colour is sent as JSON null, not as a string. Output DTOs keep colorScheme a plain
 * string because data set elsewhere may hold other values.
 */
enum ColorScheme: string
{
    case PINK = 'pink';
    case RED = 'red';
    case ORANGE = 'orange';
    case YELLOW = 'yellow';
    case GREEN = 'green';
    case BLUE = 'blue';
    case PURPLE = 'purple';
    case GRAY = 'gray';
}
