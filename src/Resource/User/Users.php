<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\User;

use Nxi\Factro\Exception\NotFoundException;
use Nxi\Factro\Resource\AbstractResource;
use Nxi\Factro\Resource\User\Input\AbsenceChanges;
use Nxi\Factro\Resource\User\Input\NewAbsence;
use Nxi\Factro\Resource\User\Input\NewUser;
use Nxi\Factro\Resource\User\Input\UserChanges;
use Nxi\Factro\Resource\User\Output\Absence;
use Nxi\Factro\Resource\User\Output\EmployeeTag;
use Nxi\Factro\Resource\User\Output\User;
use Nxi\Factro\Resource\User\Output\UserQuota;

/**
 * Users, employee tags, substitutes, quota and absences. The literal paths /users/tags, /users/users,
 * /users/quota and /users/absences share the prefix of /users/{id}; ids are always URL-encoded.
 */
final class Users extends AbstractResource
{
    /**
     * GET /users. The endpoint ignores query parameters and returns every user of the tenant.
     *
     * @return list<User>
     */
    public function list(): array
    {
        return array_map(User::fromArray(...), $this->rows($this->transport->request('GET', '/users')));
    }

    /**
     * GET /users/{id}.
     */
    public function get(string $userId): User
    {
        return User::fromArray($this->object($this->transport->request('GET', '/users/'.rawurlencode($userId))));
    }

    /**
     * Like get(), but null on 404. Every other exception passes through.
     */
    public function find(string $userId): ?User
    {
        try {
            return $this->get($userId);
        } catch (NotFoundException) {
            return null;
        }
    }

    /**
     * GET /users/{id}/tags.
     *
     * @return list<EmployeeTag>
     */
    public function tags(string $userId): array
    {
        return array_map(EmployeeTag::fromArray(...), $this->rows($this->transport->request('GET', '/users/'.rawurlencode($userId).'/tags')));
    }

    /**
     * GET /users/{id}/tags for many users, $concurrency requests at a time. A 404 yields an empty list.
     *
     * @param list<string> $userIds
     *
     * @return array<string, list<EmployeeTag>> keyed by user id, in input order
     */
    public function tagsForMany(array $userIds, int $concurrency = 5): array
    {
        if ([] === $userIds) {
            return [];
        }
        $paths = [];
        foreach ($userIds as $userId) {
            $paths['/users/'.rawurlencode($userId).'/tags'] = $userId;
        }
        $bodies = $this->transport->getMany(array_keys($paths), $concurrency);
        $result = [];
        foreach ($paths as $path => $userId) {
            $result[$userId] = array_map(EmployeeTag::fromArray(...), $this->rows($bodies[$path] ?? null));
        }

        return $result;
    }

    /**
     * GET /users/tags: every employee tag of the tenant.
     *
     * @return list<EmployeeTag>
     */
    public function employeeTags(): array
    {
        return array_map(EmployeeTag::fromArray(...), $this->rows($this->transport->request('GET', '/users/tags')));
    }

    /**
     * POST /users.
     */
    public function create(NewUser $user): User
    {
        return User::fromArray($this->object($this->transport->request('POST', '/users', json: $user->toPayload())));
    }

    /**
     * PUT /users/{id}.
     */
    public function update(string $userId, UserChanges $changes): User
    {
        if ($changes->isEmpty()) {
            throw new \InvalidArgumentException('UserChanges must set at least one field.');
        }

        return User::fromArray($this->object($this->transport->request('PUT', '/users/'.rawurlencode($userId), json: $changes->toPayload())));
    }

    /**
     * DELETE /users/{id}. The response body (the deleted user) is discarded. Requires a RequestPolicy that permits DELETE.
     */
    public function delete(string $userId): void
    {
        $this->transport->request('DELETE', '/users/'.rawurlencode($userId));
    }

    /**
     * POST /users/users: creates several users in one atomic transaction.
     *
     * @param list<NewUser> $users
     *
     * @return list<User>
     */
    public function createMany(array $users): array
    {
        if ([] === $users) {
            throw new \InvalidArgumentException('createMany() needs at least one NewUser.');
        }
        $payload = array_map(static fn (NewUser $user): array => $user->toPayload(), array_values($users));

        return array_map(User::fromArray(...), $this->rows($this->transport->request('POST', '/users/users', json: $payload)));
    }

    /**
     * PUT /users/users: updates several users in one atomic transaction.
     *
     * @param array<string, UserChanges> $changes keyed by user id
     *
     * @return list<User>
     */
    public function updateMany(array $changes): array
    {
        if ([] === $changes) {
            throw new \InvalidArgumentException('updateMany() needs at least one UserChanges.');
        }
        $payload = [];
        foreach ($changes as $userId => $userChanges) {
            if ($userChanges->isEmpty()) {
                throw new \InvalidArgumentException(sprintf('UserChanges for user "%s" must set at least one field.', $userId));
            }
            $payload[] = ['id' => (string) $userId] + $userChanges->toPayload();
        }

        return array_map(User::fromArray(...), $this->rows($this->transport->request('PUT', '/users/users', json: $payload)));
    }

    /**
     * POST /users/tags.
     */
    public function createEmployeeTag(string $name): EmployeeTag
    {
        return EmployeeTag::fromArray($this->object($this->transport->request('POST', '/users/tags', json: ['name' => $name])));
    }

    /**
     * DELETE /users/tags/{tagId}. The response body (the deleted tag) is discarded.
     */
    public function deleteEmployeeTag(string $tagId): void
    {
        $this->transport->request('DELETE', '/users/tags/'.rawurlencode($tagId));
    }

    /**
     * PUT /users/{id}/tags: associates an employee tag with the user.
     */
    public function addTag(string $userId, string $tagId): void
    {
        $this->transport->request('PUT', '/users/'.rawurlencode($userId).'/tags', json: ['tagId' => $tagId]);
    }

    /**
     * DELETE /users/{id}/tags/{tagId}.
     */
    public function removeTag(string $userId, string $tagId): void
    {
        $this->transport->request('DELETE', '/users/'.rawurlencode($userId).'/tags/'.rawurlencode($tagId));
    }

    /**
     * GET /users/{id}/substitutes: the users configured as substitute for the given user.
     *
     * @return list<User>
     */
    public function substitutes(string $userId): array
    {
        return array_map(User::fromArray(...), $this->rows($this->transport->request('GET', '/users/'.rawurlencode($userId).'/substitutes')));
    }

    /**
     * GET /users/{id}/substituted: the users that have configured the given user as their substitute.
     *
     * @return list<User>
     */
    public function substitutedUsers(string $userId): array
    {
        return array_map(User::fromArray(...), $this->rows($this->transport->request('GET', '/users/'.rawurlencode($userId).'/substituted')));
    }

    /**
     * PUT /users/{id}/substitutes: makes $substituteId a substitute of $userId.
     */
    public function addSubstitute(string $userId, string $substituteId): void
    {
        $this->transport->request('PUT', '/users/'.rawurlencode($userId).'/substitutes', json: ['substituteId' => $substituteId]);
    }

    /**
     * DELETE /users/{id}/substitutes/{substituteId}.
     */
    public function removeSubstitute(string $userId, string $substituteId): void
    {
        $this->transport->request('DELETE', '/users/'.rawurlencode($userId).'/substitutes/'.rawurlencode($substituteId));
    }

    /**
     * GET /users/quota.
     */
    public function quota(): UserQuota
    {
        return UserQuota::fromArray($this->object($this->transport->request('GET', '/users/quota')));
    }

    /**
     * GET /users/absences: every absence visible to the requesting user.
     *
     * @return list<Absence>
     */
    public function absences(): array
    {
        return array_map(Absence::fromArray(...), $this->rows($this->transport->request('GET', '/users/absences')));
    }

    /**
     * GET /users/{id}/absences.
     *
     * @return list<Absence>
     */
    public function absencesOf(string $userId): array
    {
        return array_map(Absence::fromArray(...), $this->rows($this->transport->request('GET', '/users/'.rawurlencode($userId).'/absences')));
    }

    /**
     * POST /users/{id}/absences. The user comes from the path; NewAbsence::$employeeId is not sent.
     */
    public function createAbsence(string $userId, NewAbsence $absence): Absence
    {
        return Absence::fromArray($this->object($this->transport->request('POST', '/users/'.rawurlencode($userId).'/absences', json: $absence->toPayload())));
    }

    /**
     * POST /users/absences: creates several absences in one atomic transaction. Every element needs an employeeId.
     *
     * @param list<NewAbsence> $absences
     *
     * @return list<Absence>
     */
    public function createAbsences(array $absences): array
    {
        if ([] === $absences) {
            throw new \InvalidArgumentException('createAbsences() needs at least one NewAbsence.');
        }
        $payload = array_map(static fn (NewAbsence $absence): array => $absence->toBatchPayload(), array_values($absences));

        return array_map(Absence::fromArray(...), $this->rows($this->transport->request('POST', '/users/absences', json: $payload)));
    }

    /**
     * PUT /users/absences/{absenceId}.
     */
    public function updateAbsence(string $absenceId, AbsenceChanges $changes): Absence
    {
        if ($changes->isEmpty()) {
            throw new \InvalidArgumentException('AbsenceChanges must set at least one field.');
        }

        return Absence::fromArray($this->object($this->transport->request('PUT', '/users/absences/'.rawurlencode($absenceId), json: $changes->toPayload())));
    }

    /**
     * PUT /users/absences: updates several absences in one atomic transaction.
     *
     * @param array<string, AbsenceChanges> $changes keyed by absence id
     *
     * @return list<Absence>
     */
    public function updateAbsences(array $changes): array
    {
        if ([] === $changes) {
            throw new \InvalidArgumentException('updateAbsences() needs at least one AbsenceChanges.');
        }
        $payload = [];
        foreach ($changes as $absenceId => $absenceChanges) {
            if ($absenceChanges->isEmpty()) {
                throw new \InvalidArgumentException(sprintf('AbsenceChanges for absence "%s" must set at least one field.', $absenceId));
            }
            $payload[] = ['id' => (string) $absenceId] + $absenceChanges->toPayload();
        }

        return array_map(Absence::fromArray(...), $this->rows($this->transport->request('PUT', '/users/absences', json: $payload)));
    }

    /**
     * DELETE /users/absences/{absenceId}. The response body, when present, is discarded.
     */
    public function deleteAbsence(string $absenceId): void
    {
        $this->transport->request('DELETE', '/users/absences/'.rawurlencode($absenceId));
    }
}
