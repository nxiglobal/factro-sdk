<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Project;

enum StructureNodeType: string
{
    case PROJECT = 'project';
    case PACKAGE = 'package';
    case TASK = 'task';
}
