<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\WorkRecord;

use Nxi\Factro\Exception\NotFoundException;
use Nxi\Factro\Resource\AbstractResource;
use Nxi\Factro\Resource\WorkRecord\Input\NewWorkRecord;
use Nxi\Factro\Resource\WorkRecord\Input\NewWorkRecordComment;
use Nxi\Factro\Resource\WorkRecord\Input\WorkRecordChanges;
use Nxi\Factro\Resource\WorkRecord\Output\WorkRecord;
use Nxi\Factro\Resource\WorkRecord\Output\WorkRecordComment;
use Nxi\Factro\Time\CalendarDate;

final class WorkRecords extends AbstractResource
{
    /**
     * GET /work-records?startDate=Y-m-d&endDate=Y-m-d (both optional).
     *
     * @return list<WorkRecord>
     */
    public function list(?CalendarDate $from = null, ?CalendarDate $to = null): array
    {
        return array_map(WorkRecord::fromArray(...), $this->rows($this->transport->request('GET', '/work-records', $this->dateQuery($from, $to))));
    }

    /**
     * GET /work-records/by-project/{id}?startDate=Y-m-d&endDate=Y-m-d (both optional).
     *
     * @return list<WorkRecord>
     */
    public function listByProject(string $projectId, ?CalendarDate $from = null, ?CalendarDate $to = null): array
    {
        return array_map(WorkRecord::fromArray(...), $this->rows($this->transport->request('GET', '/work-records/by-project/'.rawurlencode($projectId), $this->dateQuery($from, $to))));
    }

    /**
     * GET /work-records/{id}.
     */
    public function get(string $workRecordId): WorkRecord
    {
        return WorkRecord::fromArray($this->object($this->transport->request('GET', '/work-records/'.rawurlencode($workRecordId))));
    }

    /**
     * Like get(), but null on 404. Every other exception passes through.
     */
    public function find(string $workRecordId): ?WorkRecord
    {
        try {
            return $this->get($workRecordId);
        } catch (NotFoundException) {
            return null;
        }
    }

    /**
     * POST /work-records.
     */
    public function create(NewWorkRecord $record): WorkRecord
    {
        return WorkRecord::fromArray($this->object($this->transport->request('POST', '/work-records', json: $record->toPayload())));
    }

    /**
     * PUT /work-records/{id}.
     */
    public function update(string $workRecordId, WorkRecordChanges $changes): WorkRecord
    {
        if ($changes->isEmpty()) {
            throw new \InvalidArgumentException('WorkRecordChanges must set at least one field.');
        }

        return WorkRecord::fromArray($this->object($this->transport->request('PUT', '/work-records/'.rawurlencode($workRecordId), json: $changes->toPayload())));
    }

    /**
     * POST /work-records/work-records. Creates all records in one atomic transaction: if one fails, none is created.
     *
     * @param list<NewWorkRecord> $records
     *
     * @return list<WorkRecord>
     */
    public function createMany(array $records): array
    {
        if ([] === $records) {
            throw new \InvalidArgumentException('createMany() needs at least one NewWorkRecord.');
        }
        $payload = array_map(static fn (NewWorkRecord $record): array => $record->toPayload(), array_values($records));

        return array_map(WorkRecord::fromArray(...), $this->rows($this->transport->request('POST', '/work-records/work-records', json: $payload)));
    }

    /**
     * PUT /work-records/work-records. Keys are work record ids; each element is sent as changes plus "id".
     * Atomic like createMany(): if one update fails, none is applied.
     *
     * @param array<string, WorkRecordChanges> $changesById
     *
     * @return list<WorkRecord>
     */
    public function updateMany(array $changesById): array
    {
        if ([] === $changesById) {
            throw new \InvalidArgumentException('updateMany() needs at least one WorkRecordChanges.');
        }
        $payload = [];
        foreach ($changesById as $workRecordId => $changes) {
            if ($changes->isEmpty()) {
                throw new \InvalidArgumentException(sprintf('WorkRecordChanges for "%s" must set at least one field.', $workRecordId));
            }
            $payload[] = ['id' => (string) $workRecordId] + $changes->toPayload();
        }

        return array_map(WorkRecord::fromArray(...), $this->rows($this->transport->request('PUT', '/work-records/work-records', json: $payload)));
    }

    /**
     * DELETE /work-records/{id}. Requires a RequestPolicy that permits DELETE.
     */
    public function delete(string $workRecordId): void
    {
        $this->transport->request('DELETE', '/work-records/'.rawurlencode($workRecordId));
    }

    /**
     * GET /work-records/{id}/comments.
     *
     * @return list<WorkRecordComment>
     */
    public function comments(string $workRecordId): array
    {
        return array_map(WorkRecordComment::fromArray(...), $this->rows($this->transport->request('GET', '/work-records/'.rawurlencode($workRecordId).'/comments')));
    }

    /**
     * GET /work-records/{id}/comments/{commentId}.
     */
    public function comment(string $workRecordId, string $commentId): WorkRecordComment
    {
        return WorkRecordComment::fromArray($this->object($this->transport->request('GET', '/work-records/'.rawurlencode($workRecordId).'/comments/'.rawurlencode($commentId))));
    }

    /**
     * POST /work-records/{id}/comments. The API offers no DELETE for work record comments.
     */
    public function addComment(string $workRecordId, NewWorkRecordComment $comment): WorkRecordComment
    {
        return WorkRecordComment::fromArray($this->object($this->transport->request('POST', '/work-records/'.rawurlencode($workRecordId).'/comments', json: $comment->toPayload())));
    }

    /**
     * @return array<string, string>
     */
    private function dateQuery(?CalendarDate $from, ?CalendarDate $to): array
    {
        return array_filter(['startDate' => $from?->toYmd(), 'endDate' => $to?->toYmd()], static fn (?string $v): bool => null !== $v);
    }
}
