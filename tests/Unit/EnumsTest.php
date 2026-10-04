<?php

declare(strict_types=1);

namespace Nxi\Factro\Tests\Unit;

use Nxi\Factro\Resource\ColorScheme;
use Nxi\Factro\Resource\Comment\CommentReferenceType;
use Nxi\Factro\Resource\CustomView\CustomViewReferenceType;
use Nxi\Factro\Resource\CustomView\CustomViewViewType;
use Nxi\Factro\Resource\Project\ProjectPriority;
use Nxi\Factro\Resource\Project\ProjectState;
use Nxi\Factro\Resource\Project\StructureNodeType;
use Nxi\Factro\Resource\Task\TaskPriority;
use Nxi\Factro\Resource\Task\TaskState;
use Nxi\Factro\Resource\Task\Urgency;
use Nxi\Factro\Resource\TodoList\ListElementReferenceType;
use Nxi\Factro\Resource\User\AbsenceType;
use Nxi\Factro\Resource\User\Salutation;
use Nxi\Factro\Resource\User\SecurityGroup;
use Nxi\Factro\Resource\WorkRecord\WorkRecordReferenceType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TaskState::class)]
#[CoversClass(ProjectState::class)]
#[CoversClass(TaskPriority::class)]
#[CoversClass(ProjectPriority::class)]
#[CoversClass(Urgency::class)]
#[CoversClass(SecurityGroup::class)]
#[CoversClass(WorkRecordReferenceType::class)]
#[CoversClass(StructureNodeType::class)]
#[CoversClass(Salutation::class)]
#[CoversClass(AbsenceType::class)]
#[CoversClass(CommentReferenceType::class)]
#[CoversClass(CustomViewReferenceType::class)]
#[CoversClass(CustomViewViewType::class)]
#[CoversClass(ListElementReferenceType::class)]
#[CoversClass(ColorScheme::class)]
final class EnumsTest extends TestCase
{
    public function testValuesMatchTheApi(): void
    {
        self::assertSame(['planned', 'inProcess', 'review', 'closed', 'pushedBack', 'stopped'], array_column(TaskState::cases(), 'value'));
        self::assertSame(['planned', 'inProcess', 'closed', 'pushedBack', 'stopped'], array_column(ProjectState::cases(), 'value'));
        self::assertSame([10, 20, 30, 40, 50, 60, 70, 80, 90, 99], array_column(TaskPriority::cases(), 'value'));
        self::assertSame([10, 20, 30, 40, 50, 60, 70, 80, 90, 99], array_column(ProjectPriority::cases(), 'value'));
        self::assertSame(['overdue', 'due', 'normal'], array_column(Urgency::cases(), 'value'));
        self::assertSame(['AllRights', 'BasicRights', 'AdvancedRights', 'GuestRights'], array_column(SecurityGroup::cases(), 'value'));
        self::assertSame(['task', 'package', 'project'], array_column(WorkRecordReferenceType::cases(), 'value'));
        self::assertSame(['project', 'package', 'task'], array_column(StructureNodeType::cases(), 'value'));
        self::assertSame(['male', 'female', 'none'], array_column(Salutation::cases(), 'value'));
        self::assertSame(['Planned', 'Unplanned'], array_column(AbsenceType::cases(), 'value'));
        self::assertSame(['task', 'note', 'package', 'project', 'workRecord'], array_column(CommentReferenceType::cases(), 'value'));
        self::assertSame(['Project', 'Room'], array_column(CustomViewReferenceType::cases(), 'value'));
        self::assertSame(['Kanban', 'Gantt', 'Grid', 'PSB', 'Link', 'StatusReport', 'RoomOverview'], array_column(CustomViewViewType::cases(), 'value'));
        self::assertSame(['task', 'note'], array_column(ListElementReferenceType::cases(), 'value'));
        self::assertSame(['pink', 'red', 'orange', 'yellow', 'green', 'blue', 'purple', 'gray'], array_column(ColorScheme::cases(), 'value'));
    }

    public function testHelpers(): void
    {
        self::assertTrue(TaskState::PLANNED->isOpen());
        self::assertTrue(TaskState::IN_PROCESS->isOpen());
        self::assertTrue(TaskState::REVIEW->isOpen());
        self::assertTrue(TaskState::PUSHED_BACK->isOpen());
        self::assertFalse(TaskState::CLOSED->isOpen());
        self::assertFalse(TaskState::STOPPED->isOpen());
        self::assertTrue(SecurityGroup::GUEST_RIGHTS->isGuest());
        self::assertFalse(SecurityGroup::ADVANCED_RIGHTS->isGuest());
        self::assertSame(TaskPriority::PRIORITY_99, TaskPriority::from(99));
        self::assertSame(ProjectPriority::PRIORITY_10, ProjectPriority::from(10));
    }
}
