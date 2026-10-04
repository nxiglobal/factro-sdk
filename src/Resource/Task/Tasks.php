<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Task;

use Nxi\Factro\Exception\NotFoundException;
use Nxi\Factro\Mapping\Payload;
use Nxi\Factro\Resource\AbstractResource;
use Nxi\Factro\Resource\AccessRight\AccessRights;
use Nxi\Factro\Resource\Document\Input\DocumentUpload;
use Nxi\Factro\Resource\Document\Output\Document;
use Nxi\Factro\Resource\Task\Input\ChecklistEntryChanges;
use Nxi\Factro\Resource\Task\Input\NewChecklistEntry;
use Nxi\Factro\Resource\Task\Input\NewTask;
use Nxi\Factro\Resource\Task\Input\NewTaskComment;
use Nxi\Factro\Resource\Task\Input\TaskChanges;
use Nxi\Factro\Resource\Task\Input\TaskConnectionInput;
use Nxi\Factro\Resource\Task\Output\ChecklistEntry;
use Nxi\Factro\Resource\Task\Output\Task;
use Nxi\Factro\Resource\Task\Output\TaskComment;
use Nxi\Factro\Resource\Task\Output\TaskConnection;
use Nxi\Factro\Resource\Task\Output\TaskTag;
use Nxi\Factro\Time\FactroDateTime;

final class Tasks extends AbstractResource
{
    /**
     * GET /tasks/by-project/{id}. GET /tasks without a project is not offered: the tenant-wide list is unusably large.
     *
     * @return list<Task>
     */
    public function listByProject(string $projectId): array
    {
        return array_map(Task::fromArray(...), $this->rows($this->transport->request('GET', '/tasks/by-project/'.rawurlencode($projectId))));
    }

    /**
     * GET /tasks/{id}.
     */
    public function get(string $taskId): Task
    {
        return Task::fromArray($this->object($this->transport->request('GET', '/tasks/'.rawurlencode($taskId))));
    }

    /**
     * Like get(), but null on 404. Every other exception passes through.
     */
    public function find(string $taskId): ?Task
    {
        try {
            return $this->get($taskId);
        } catch (NotFoundException) {
            return null;
        }
    }

    /**
     * POST /tasks.
     */
    public function create(NewTask $task): Task
    {
        $payload = $task->toPayload($this->options->timezone, $this->options->clock, $this->options->sendCreationDate);

        return Task::fromArray($this->object($this->transport->request('POST', '/tasks', json: $payload)));
    }

    /**
     * PUT /tasks/{id}.
     */
    public function update(string $taskId, TaskChanges $changes): Task
    {
        if ($changes->isEmpty()) {
            throw new \InvalidArgumentException('TaskChanges must set at least one field.');
        }

        return Task::fromArray($this->object($this->transport->request('PUT', '/tasks/'.rawurlencode($taskId), json: $changes->toPayload($this->options->timezone))));
    }

    /**
     * POST /tasks/tasks. factro creates the valid tasks even when some elements are invalid.
     *
     * @param list<NewTask> $tasks
     *
     * @return list<Task>
     */
    public function createMany(array $tasks): array
    {
        if ([] === $tasks) {
            throw new \InvalidArgumentException('createMany() needs at least one task.');
        }
        $payload = [];
        foreach ($tasks as $task) {
            $payload[] = $task->toPayload($this->options->timezone, $this->options->clock, $this->options->sendCreationDate);
        }

        return array_map(Task::fromArray(...), $this->rows($this->transport->request('POST', '/tasks/tasks', json: $payload)));
    }

    /**
     * PUT /tasks/tasks. Keys are task ids; every element must change at least one field.
     *
     * @param array<string, TaskChanges> $changes keyed by task id
     *
     * @return list<Task>
     */
    public function updateMany(array $changes): array
    {
        if ([] === $changes) {
            throw new \InvalidArgumentException('updateMany() needs at least one task.');
        }
        $payload = [];
        foreach ($changes as $taskId => $taskChanges) {
            if ($taskChanges->isEmpty()) {
                throw new \InvalidArgumentException(sprintf('TaskChanges for task "%s" must set at least one field.', $taskId));
            }
            $payload[] = ['id' => $taskId] + $taskChanges->toPayload($this->options->timezone);
        }

        return array_map(Task::fromArray(...), $this->rows($this->transport->request('PUT', '/tasks/tasks', json: $payload)));
    }

    /**
     * PUT /tasks/{id}/state. The response body is undocumented and therefore discarded.
     */
    public function setState(string $taskId, TaskState $state, ?\DateTimeImmutable $pausedUntil = null): void
    {
        $payload = ['state' => $state->value];
        if (null !== $pausedUntil) {
            $payload['pausedUntil'] = FactroDateTime::toIsoUtc($pausedUntil);
        }
        $this->transport->request('PUT', '/tasks/'.rawurlencode($taskId).'/state', json: $payload);
    }

    /**
     * DELETE /tasks/{id}. Requires a RequestPolicy that permits DELETE.
     */
    public function delete(string $taskId): void
    {
        $this->transport->request('DELETE', '/tasks/'.rawurlencode($taskId));
    }

    /**
     * GET /tasks/{id}/comments.
     *
     * @return list<TaskComment>
     */
    public function comments(string $taskId): array
    {
        return array_map(TaskComment::fromArray(...), $this->rows($this->transport->request('GET', '/tasks/'.rawurlencode($taskId).'/comments')));
    }

    /**
     * POST /tasks/{id}/comments.
     */
    public function addComment(string $taskId, NewTaskComment $comment): TaskComment
    {
        return TaskComment::fromArray($this->object($this->transport->request('POST', '/tasks/'.rawurlencode($taskId).'/comments', json: $comment->toPayload())));
    }

    /**
     * GET /tasks/{id}/comments/{commentId}.
     */
    public function comment(string $taskId, string $commentId): TaskComment
    {
        return TaskComment::fromArray($this->object($this->transport->request('GET', '/tasks/'.rawurlencode($taskId).'/comments/'.rawurlencode($commentId))));
    }

    /**
     * DELETE /tasks/{id}/comments/{commentId}. The response (the deleted comment) is discarded.
     */
    public function deleteComment(string $taskId, string $commentId): void
    {
        $this->transport->request('DELETE', '/tasks/'.rawurlencode($taskId).'/comments/'.rawurlencode($commentId));
    }

    /**
     * GET /tasks/{id}/checklist.
     *
     * @return list<ChecklistEntry>
     */
    public function checklist(string $taskId): array
    {
        return array_map(ChecklistEntry::fromArray(...), $this->rows($this->transport->request('GET', '/tasks/'.rawurlencode($taskId).'/checklist')));
    }

    /**
     * POST /tasks/{id}/checklist.
     */
    public function addChecklistEntry(string $taskId, NewChecklistEntry $entry): ChecklistEntry
    {
        return ChecklistEntry::fromArray($this->object($this->transport->request('POST', '/tasks/'.rawurlencode($taskId).'/checklist', json: $entry->toPayload())));
    }

    /**
     * PUT /tasks/{id}/checklist/{entryId}.
     */
    public function updateChecklistEntry(string $taskId, string $entryId, ChecklistEntryChanges $changes): ChecklistEntry
    {
        if ($changes->isEmpty()) {
            throw new \InvalidArgumentException('ChecklistEntryChanges must set at least one field.');
        }

        return ChecklistEntry::fromArray($this->object($this->transport->request('PUT', '/tasks/'.rawurlencode($taskId).'/checklist/'.rawurlencode($entryId), json: $changes->toPayload($this->options->timezone))));
    }

    /**
     * DELETE /tasks/{id}/checklist/{entryId}. The response (the deleted entry) is discarded.
     */
    public function removeChecklistEntry(string $taskId, string $entryId): void
    {
        $this->transport->request('DELETE', '/tasks/'.rawurlencode($taskId).'/checklist/'.rawurlencode($entryId));
    }

    /**
     * GET /tasks/tags: every task tag of the tenant.
     *
     * @return list<TaskTag>
     */
    public function taskTags(): array
    {
        return array_map(TaskTag::fromArray(...), $this->rows($this->transport->request('GET', '/tasks/tags')));
    }

    /**
     * POST /tasks/tags.
     */
    public function createTaskTag(string $name): TaskTag
    {
        return TaskTag::fromArray($this->object($this->transport->request('POST', '/tasks/tags', json: ['name' => $name])));
    }

    /**
     * DELETE /tasks/tags/{tagId}. The response (the deleted tag) is discarded.
     */
    public function deleteTaskTag(string $tagId): void
    {
        $this->transport->request('DELETE', '/tasks/tags/'.rawurlencode($tagId));
    }

    /**
     * GET /tasks/{id}/tags: the tags associated with one task.
     *
     * @return list<TaskTag>
     */
    public function tags(string $taskId): array
    {
        return array_map(TaskTag::fromArray(...), $this->rows($this->transport->request('GET', '/tasks/'.rawurlencode($taskId).'/tags')));
    }

    /**
     * PUT /tasks/{id}/tags.
     */
    public function addTag(string $taskId, string $tagId): void
    {
        $this->transport->request('PUT', '/tasks/'.rawurlencode($taskId).'/tags', json: ['tagId' => $tagId]);
    }

    /**
     * DELETE /tasks/{id}/tags/{tagId}.
     */
    public function removeTag(string $taskId, string $tagId): void
    {
        $this->transport->request('DELETE', '/tasks/'.rawurlencode($taskId).'/tags/'.rawurlencode($tagId));
    }

    /**
     * GET /tasks/{id}/task_connections. An empty list when the task has no connections.
     *
     * @return list<TaskConnection>
     */
    public function connections(string $taskId): array
    {
        return array_map(TaskConnection::fromArray(...), $this->rows($this->transport->request('GET', '/tasks/'.rawurlencode($taskId).'/task_connections')));
    }

    /**
     * PUT /tasks/{id}/task_connections.
     */
    public function addConnection(string $taskId, TaskConnectionInput $connection): void
    {
        $this->transport->request('PUT', '/tasks/'.rawurlencode($taskId).'/task_connections', json: $connection->toPayload());
    }

    /**
     * DELETE /tasks/{id}/task_connections/{connectedTaskId}.
     */
    public function removeConnection(string $taskId, string $connectedTaskId): void
    {
        $this->transport->request('DELETE', '/tasks/'.rawurlencode($taskId).'/task_connections/'.rawurlencode($connectedTaskId));
    }

    /**
     * PUT /tasks/{id}/project: moves the task into another project; position is omitted when null.
     */
    public function moveToProject(string $taskId, string $projectId, ?int $position = null): void
    {
        $this->transport->request('PUT', '/tasks/'.rawurlencode($taskId).'/project', json: Payload::withoutNulls(['projectId' => $projectId, 'position' => $position]));
    }

    /**
     * DELETE /tasks/{id}/project: moves the task out of its project.
     */
    public function removeFromProject(string $taskId): void
    {
        $this->transport->request('DELETE', '/tasks/'.rawurlencode($taskId).'/project');
    }

    /**
     * PUT /tasks/{id}/package: moves the task into a package; position is omitted when null.
     */
    public function moveToPackage(string $taskId, string $packageId, ?int $position = null): void
    {
        $this->transport->request('PUT', '/tasks/'.rawurlencode($taskId).'/package', json: Payload::withoutNulls(['packageId' => $packageId, 'position' => $position]));
    }

    /**
     * DELETE /tasks/{id}/package: moves the task out of its package into the project root.
     */
    public function removeFromPackage(string $taskId): void
    {
        $this->transport->request('DELETE', '/tasks/'.rawurlencode($taskId).'/package');
    }

    /**
     * PUT /tasks/{id}/company.
     */
    public function setCompany(string $taskId, string $companyId): void
    {
        $this->transport->request('PUT', '/tasks/'.rawurlencode($taskId).'/company', json: ['companyId' => $companyId]);
    }

    /**
     * DELETE /tasks/{id}/company.
     */
    public function removeCompany(string $taskId): void
    {
        $this->transport->request('DELETE', '/tasks/'.rawurlencode($taskId).'/company');
    }

    /**
     * PUT /tasks/{id}/contact.
     */
    public function setContact(string $taskId, string $contactId): void
    {
        $this->transport->request('PUT', '/tasks/'.rawurlencode($taskId).'/contact', json: ['contactId' => $contactId]);
    }

    /**
     * DELETE /tasks/{id}/contact.
     */
    public function removeContact(string $taskId): void
    {
        $this->transport->request('DELETE', '/tasks/'.rawurlencode($taskId).'/contact');
    }

    /**
     * GET /tasks/{id}/documents.
     *
     * @return list<Document>
     */
    public function documents(string $taskId): array
    {
        return array_map(Document::fromArray(...), $this->rows($this->transport->request('GET', '/tasks/'.rawurlencode($taskId).'/documents')));
    }

    /**
     * POST /tasks/{id}/documents: multipart upload of one file (see DocumentUpload for the part name).
     */
    public function addDocument(string $taskId, DocumentUpload $upload): Document
    {
        return Document::fromArray($this->object($this->transport->request('POST', '/tasks/'.rawurlencode($taskId).'/documents', multipart: $upload->toMultipart())));
    }

    /**
     * DELETE /tasks/{id}/documents/{documentId}. The response (the deleted document) is discarded.
     */
    public function removeDocument(string $taskId, string $documentId): void
    {
        $this->transport->request('DELETE', '/tasks/'.rawurlencode($taskId).'/documents/'.rawurlencode($documentId));
    }

    /**
     * Read and write rights of the task; employee and team endpoints both live below /tasks/{id}.
     */
    public function accessRights(string $taskId): AccessRights
    {
        return new AccessRights($this->transport, $this->options, '/tasks/'.rawurlencode($taskId), '/tasks/'.rawurlencode($taskId));
    }
}
