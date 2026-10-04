<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Task;

enum TaskState: string
{
    case PLANNED = 'planned';
    case IN_PROCESS = 'inProcess';
    case REVIEW = 'review';
    case CLOSED = 'closed';
    case PUSHED_BACK = 'pushedBack';
    case STOPPED = 'stopped';

    /**
     * Everything except closed and stopped.
     */
    public function isOpen(): bool
    {
        return self::CLOSED !== $this && self::STOPPED !== $this;
    }
}
