<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Task\Input;

/**
 * Body of POST /tasks/{id}/checklist (ICreateChecklistEntryRequest). Both fields are required by
 * the API, so "checked" is always sent; end date, position and assignee are set via ChecklistEntryChanges.
 */
final readonly class NewChecklistEntry
{
    public function __construct(public string $title, public bool $checked = false)
    {
        if ('' === trim($title)) {
            throw new \InvalidArgumentException('NewChecklistEntry needs a title.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return ['title' => $this->title, 'checked' => $this->checked];
    }
}
