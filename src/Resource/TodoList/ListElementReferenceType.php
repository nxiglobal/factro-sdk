<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\TodoList;

/**
 * What a todo-list element points at.
 */
enum ListElementReferenceType: string
{
    case TASK = 'task';
    case NOTE = 'note';
}
