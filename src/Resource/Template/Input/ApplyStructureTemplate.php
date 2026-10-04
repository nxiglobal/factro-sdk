<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Template\Input;

use Nxi\Factro\Mapping\Payload;

/**
 * Body of POST /templates/structure/{id}/apply_template (IApplyStructureTemplateRequest).
 *
 * Without targetParentId factro creates a new project from the template; with it the template's
 * structure is inserted below the given parent. All fields are optional and only non-null ones are sent.
 * packageCloneOptions and taskCloneOptions are untyped objects in the OpenAPI copy and therefore
 * passed through as arrays; an empty array would be encoded as a JSON list, so pass at least one key.
 */
final readonly class ApplyStructureTemplate
{
    /**
     * @param list<string>|null         $additionalCustomFieldAssignments
     * @param list<string>|null         $customFieldsToClone
     * @param array<string, mixed>|null $packageCloneOptions
     * @param array<string, mixed>|null $taskCloneOptions
     */
    public function __construct(
        public ?string $targetParentId = null,
        public ?AdditionalAccessRights $additionalAccessRights = null,
        public ?array $additionalCustomFieldAssignments = null,
        public ?ProjectPropertyOverwrites $projectPropertyOverwrites = null,
        public ?array $customFieldsToClone = null,
        public ?bool $cloneDocuments = null,
        public ?bool $clonePeriods = null,
        public ?bool $cloneTags = null,
        public ?array $packageCloneOptions = null,
        public ?array $taskCloneOptions = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(\DateTimeZone $timezone): array
    {
        return Payload::withoutNulls([
            'targetParentId' => $this->targetParentId,
            'additionalAccessRights' => $this->additionalAccessRights?->toPayload(),
            'additionalCustomFieldAssignments' => $this->additionalCustomFieldAssignments,
            'projectPropertyOverwrites' => $this->projectPropertyOverwrites?->toPayload($timezone),
            'customFieldsToClone' => $this->customFieldsToClone,
            'cloneDocuments' => $this->cloneDocuments,
            'clonePeriods' => $this->clonePeriods,
            'cloneTags' => $this->cloneTags,
            'packageCloneOptions' => $this->packageCloneOptions,
            'taskCloneOptions' => $this->taskCloneOptions,
        ]);
    }
}
