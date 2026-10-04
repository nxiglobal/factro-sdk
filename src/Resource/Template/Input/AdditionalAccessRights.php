<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Template\Input;

/**
 * Access rights granted on top of the template's own rights when a structure template is applied.
 *
 * factro requires all four lists in the request body, so empty lists are sent as [] rather than omitted.
 */
final readonly class AdditionalAccessRights
{
    /**
     * @param list<string> $directWriteTeamIds
     * @param list<string> $directReadTeamIds
     * @param list<string> $directWriteEmployeeIds
     * @param list<string> $directReadEmployeeIds
     */
    public function __construct(
        public array $directWriteTeamIds = [],
        public array $directReadTeamIds = [],
        public array $directWriteEmployeeIds = [],
        public array $directReadEmployeeIds = [],
    ) {
    }

    /**
     * @return array<string, list<string>>
     */
    public function toPayload(): array
    {
        return [
            'directWriteTeamIds' => $this->directWriteTeamIds,
            'directReadTeamIds' => $this->directReadTeamIds,
            'directWriteEmployeeIds' => $this->directWriteEmployeeIds,
            'directReadEmployeeIds' => $this->directReadEmployeeIds,
        ];
    }
}
