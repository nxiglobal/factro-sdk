<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Team;

use Nxi\Factro\Exception\NotFoundException;
use Nxi\Factro\Resource\AbstractResource;
use Nxi\Factro\Resource\Team\Input\NewTeam;
use Nxi\Factro\Resource\Team\Input\TeamChanges;
use Nxi\Factro\Resource\Team\Output\Team;
use Nxi\Factro\Resource\Team\Output\TeamMember;

final class Teams extends AbstractResource
{
    /**
     * GET /teams. The teams visible to the requesting user.
     *
     * @return list<Team>
     */
    public function list(): array
    {
        return array_map(Team::fromArray(...), $this->rows($this->transport->request('GET', '/teams')));
    }

    /**
     * GET /teams/{id}.
     */
    public function get(string $teamId): Team
    {
        return Team::fromArray($this->object($this->transport->request('GET', '/teams/'.rawurlencode($teamId))));
    }

    /**
     * Like get(), but null on 404. Every other exception passes through.
     */
    public function find(string $teamId): ?Team
    {
        try {
            return $this->get($teamId);
        } catch (NotFoundException) {
            return null;
        }
    }

    /**
     * POST /teams.
     */
    public function create(NewTeam $team): Team
    {
        return Team::fromArray($this->object($this->transport->request('POST', '/teams', json: $team->toPayload())));
    }

    /**
     * PUT /teams/{id}.
     */
    public function update(string $teamId, TeamChanges $changes): Team
    {
        if ($changes->isEmpty()) {
            throw new \InvalidArgumentException('TeamChanges must set at least one field.');
        }

        return Team::fromArray($this->object($this->transport->request('PUT', '/teams/'.rawurlencode($teamId), json: $changes->toPayload())));
    }

    /**
     * DELETE /teams/{id}. Requires a RequestPolicy that permits DELETE; the deleted team in the response is discarded.
     */
    public function delete(string $teamId): void
    {
        $this->transport->request('DELETE', '/teams/'.rawurlencode($teamId));
    }

    /**
     * GET /teams/{id}/members.
     *
     * @return list<TeamMember>
     */
    public function members(string $teamId): array
    {
        return array_map(TeamMember::fromArray(...), $this->rows($this->transport->request('GET', '/teams/'.rawurlencode($teamId).'/members')));
    }

    /**
     * PUT /teams/{id}/members. Returns the new membership; its id is what removeMember() expects.
     */
    public function addMember(string $teamId, string $employeeId): TeamMember
    {
        return TeamMember::fromArray($this->object($this->transport->request('PUT', '/teams/'.rawurlencode($teamId).'/members', json: ['employeeId' => $employeeId])));
    }

    /**
     * DELETE /teams/{id}/members/{memberId}. Takes the membership id, not the employee id; the response is discarded.
     */
    public function removeMember(string $teamId, string $teamMemberId): void
    {
        $this->transport->request('DELETE', '/teams/'.rawurlencode($teamId).'/members/'.rawurlencode($teamMemberId));
    }
}
