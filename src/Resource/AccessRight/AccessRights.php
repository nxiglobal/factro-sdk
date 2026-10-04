<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\AccessRight;

use Nxi\Factro\Exception\HydrationException;
use Nxi\Factro\FactroOptions;
use Nxi\Factro\Http\Transport;
use Nxi\Factro\Resource\AbstractResource;
use Nxi\Factro\Resource\AccessRight\Output\EmployeeAccessRight;
use Nxi\Factro\Resource\AccessRight\Output\TeamAccessRight;

/**
 * Read and write rights of one reference (task, package, project or template) for employees and teams.
 *
 * Reached through the owning resource: $client->tasks()->accessRights($taskId),
 * $client->packages()->accessRights($projectId, $packageId), $client->projects()->accessRights($projectId),
 * $client->templates()->structureTemplateAccessRights($templateId) and taskTemplateAccessRights($templateId).
 *
 * The employee endpoints live below $employeePath (".../read_rights", ".../write_rights"), the team
 * endpoints below $teamPath (".../team_read_rights", ".../team_write_rights"). Both are the same for
 * every reference except packages, whose team endpoints omit the "/packages" segment.
 */
final class AccessRights extends AbstractResource
{
    /**
     * @internal created by the owning resource class
     */
    public function __construct(
        Transport $transport,
        FactroOptions $options,
        private readonly string $employeePath,
        private readonly string $teamPath,
    ) {
        parent::__construct($transport, $options);
    }

    /**
     * GET .../read_rights: employee ids mapped to the reasons why each employee may read the reference.
     *
     * @return array<string, list<string>>
     */
    public function readRights(): array
    {
        return $this->reasons($this->transport->request('GET', $this->employeePath.'/read_rights'));
    }

    /**
     * GET .../write_rights: employee ids mapped to the reasons why each employee may edit the reference.
     *
     * @return array<string, list<string>>
     */
    public function writeRights(): array
    {
        return $this->reasons($this->transport->request('GET', $this->employeePath.'/write_rights'));
    }

    /**
     * PUT .../read_rights.
     */
    public function grantRead(string $employeeId): EmployeeAccessRight
    {
        return EmployeeAccessRight::fromArray($this->object($this->transport->request('PUT', $this->employeePath.'/read_rights', json: ['employeeId' => $employeeId])));
    }

    /**
     * DELETE .../read_rights/{employeeId}.
     */
    public function revokeRead(string $employeeId): void
    {
        $this->transport->request('DELETE', $this->employeePath.'/read_rights/'.rawurlencode($employeeId));
    }

    /**
     * PUT .../write_rights.
     */
    public function grantWrite(string $employeeId): EmployeeAccessRight
    {
        return EmployeeAccessRight::fromArray($this->object($this->transport->request('PUT', $this->employeePath.'/write_rights', json: ['employeeId' => $employeeId])));
    }

    /**
     * DELETE .../write_rights/{employeeId}. The OpenAPI copy of 2026-09-11 lists this route for
     * projects, packages and templates but not for tasks; for tasks the route exists nonetheless and is
     * documented elsewhere.
     */
    public function revokeWrite(string $employeeId): void
    {
        $this->transport->request('DELETE', $this->employeePath.'/write_rights/'.rawurlencode($employeeId));
    }

    /**
     * PUT .../team_read_rights.
     */
    public function grantTeamRead(string $teamId): TeamAccessRight
    {
        return TeamAccessRight::fromArray($this->object($this->transport->request('PUT', $this->teamPath.'/team_read_rights', json: ['teamId' => $teamId])));
    }

    /**
     * DELETE .../team_read_rights/{teamId}.
     */
    public function revokeTeamRead(string $teamId): void
    {
        $this->transport->request('DELETE', $this->teamPath.'/team_read_rights/'.rawurlencode($teamId));
    }

    /**
     * PUT .../team_write_rights.
     */
    public function grantTeamWrite(string $teamId): TeamAccessRight
    {
        return TeamAccessRight::fromArray($this->object($this->transport->request('PUT', $this->teamPath.'/team_write_rights', json: ['teamId' => $teamId])));
    }

    /**
     * DELETE .../team_write_rights/{teamId}.
     */
    public function revokeTeamWrite(string $teamId): void
    {
        $this->transport->request('DELETE', $this->teamPath.'/team_write_rights/'.rawurlencode($teamId));
    }

    /**
     * The rights endpoints return an object whose keys are employee ids and whose values list the
     * reasons ("officer", "directReadRights", ...) as strings. Anything else is a hydration error.
     *
     * @param array<mixed>|null $body
     *
     * @return array<string, list<string>>
     */
    private function reasons(?array $body): array
    {
        $result = [];
        foreach ($this->object($body) as $employeeId => $reasons) {
            if (!is_array($reasons)) {
                throw new HydrationException(self::class, $employeeId, 'list<string>', $reasons);
            }
            $list = [];
            foreach ($reasons as $reason) {
                if (!is_string($reason)) {
                    throw new HydrationException(self::class, $employeeId, 'list<string>', $reasons);
                }
                $list[] = $reason;
            }
            $result[$employeeId] = $list;
        }

        return $result;
    }
}
