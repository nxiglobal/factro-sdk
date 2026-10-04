<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\CustomView;

/**
 * The kind of object a custom view is attached to.
 */
enum CustomViewReferenceType: string
{
    case PROJECT = 'Project';
    case ROOM = 'Room';
}
