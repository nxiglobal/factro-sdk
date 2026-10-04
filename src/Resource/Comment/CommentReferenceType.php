<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Comment;

/**
 * Kind of object a comment is attached to (CommentReferenceType in the API).
 */
enum CommentReferenceType: string
{
    case TASK = 'task';
    case NOTE = 'note';
    case PACKAGE = 'package';
    case PROJECT = 'project';
    case WORK_RECORD = 'workRecord';
}
