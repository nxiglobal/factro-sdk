<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\CustomView;

/**
 * How a custom view renders its reference.
 */
enum CustomViewViewType: string
{
    case KANBAN = 'Kanban';
    case GANTT = 'Gantt';
    case GRID = 'Grid';
    case PSB = 'PSB';
    case LINK = 'Link';
    case STATUS_REPORT = 'StatusReport';
    case ROOM_OVERVIEW = 'RoomOverview';
}
