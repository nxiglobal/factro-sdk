<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\User;

enum Salutation: string
{
    case MALE = 'male';
    case FEMALE = 'female';
    case NONE = 'none';
}
