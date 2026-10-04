<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\WorkRecord;

enum WorkRecordReferenceType: string
{
    case TASK = 'task';
    case PACKAGE = 'package';
    case PROJECT = 'project';
}
