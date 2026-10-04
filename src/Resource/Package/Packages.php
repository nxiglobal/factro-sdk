<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Package;

use Nxi\Factro\Exception\NotFoundException;
use Nxi\Factro\Resource\AbstractResource;
use Nxi\Factro\Resource\AccessRight\AccessRights;
use Nxi\Factro\Resource\Document\Input\DocumentUpload;
use Nxi\Factro\Resource\Document\Output\Document;
use Nxi\Factro\Resource\Package\Input\NewPackage;
use Nxi\Factro\Resource\Package\Input\NewPackageComment;
use Nxi\Factro\Resource\Package\Input\PackageChanges;
use Nxi\Factro\Resource\Package\Output\Package;
use Nxi\Factro\Resource\Package\Output\PackageComment;

final class Packages extends AbstractResource
{
    /**
     * GET /projects/{id}/packages.
     *
     * @return list<Package>
     */
    public function listByProject(string $projectId): array
    {
        return array_map(Package::fromArray(...), $this->rows($this->transport->request('GET', '/projects/'.rawurlencode($projectId).'/packages')));
    }

    /**
     * GET /packages/{id}.
     */
    public function get(string $packageId): Package
    {
        return Package::fromArray($this->object($this->transport->request('GET', '/packages/'.rawurlencode($packageId))));
    }

    /**
     * Like get(), but null on 404. Every other exception passes through.
     */
    public function find(string $packageId): ?Package
    {
        try {
            return $this->get($packageId);
        } catch (NotFoundException) {
            return null;
        }
    }

    /**
     * GET /projects/{id}/packages/{pid}: the project-scoped variant of get().
     */
    public function getInProject(string $projectId, string $packageId): Package
    {
        return Package::fromArray($this->object($this->transport->request('GET', $this->path($projectId, $packageId))));
    }

    /**
     * POST /projects/{id}/packages.
     */
    public function create(string $projectId, NewPackage $package): Package
    {
        return Package::fromArray($this->object($this->transport->request('POST', '/projects/'.rawurlencode($projectId).'/packages', json: $package->toPayload())));
    }

    /**
     * POST /projects/{id}/packages/packages: creates all packages in one atomic transaction.
     *
     * @param list<NewPackage> $packages
     *
     * @return list<Package>
     */
    public function createMany(string $projectId, array $packages): array
    {
        if ([] === $packages) {
            throw new \InvalidArgumentException('createMany() needs at least one NewPackage.');
        }
        $payload = array_map(static fn (NewPackage $package): array => $package->toPayload(), array_values($packages));

        return array_map(Package::fromArray(...), $this->rows($this->transport->request('POST', '/projects/'.rawurlencode($projectId).'/packages/packages', json: $payload)));
    }

    /**
     * PUT /projects/{id}/packages/{pid}.
     */
    public function update(string $projectId, string $packageId, PackageChanges $changes): Package
    {
        if ($changes->isEmpty()) {
            throw new \InvalidArgumentException('PackageChanges must set at least one field.');
        }

        return Package::fromArray($this->object($this->transport->request('PUT', $this->path($projectId, $packageId), json: $changes->toPayload($this->options->timezone))));
    }

    /**
     * PUT /projects/{id}/packages/packages: updates all packages in one atomic transaction.
     *
     * @param array<string, PackageChanges> $changes keyed by package id
     *
     * @return list<Package>
     */
    public function updateMany(string $projectId, array $changes): array
    {
        if ([] === $changes) {
            throw new \InvalidArgumentException('updateMany() needs at least one PackageChanges.');
        }
        $payload = [];
        foreach ($changes as $packageId => $packageChanges) {
            if ($packageChanges->isEmpty()) {
                throw new \InvalidArgumentException(sprintf('PackageChanges for package "%s" must set at least one field.', $packageId));
            }
            $payload[] = ['id' => $packageId] + $packageChanges->toPayload($this->options->timezone);
        }

        return array_map(Package::fromArray(...), $this->rows($this->transport->request('PUT', '/projects/'.rawurlencode($projectId).'/packages/packages', json: $payload)));
    }

    /**
     * DELETE /projects/{id}/packages/{pid}. Requires a RequestPolicy that permits DELETE.
     */
    public function delete(string $projectId, string $packageId): void
    {
        $this->transport->request('DELETE', $this->path($projectId, $packageId));
    }

    /**
     * PUT /projects/{id}/packages/{pid}/project: moves the package into another project.
     */
    public function moveToProject(string $projectId, string $packageId, string $targetProjectId, ?int $position = null): void
    {
        $payload = ['projectId' => $targetProjectId];
        if (null !== $position) {
            $payload['position'] = $position;
        }
        $this->transport->request('PUT', $this->path($projectId, $packageId, '/project'), json: $payload);
    }

    /**
     * PUT /projects/{id}/packages/{pid}/package: moves the package below another package of the project.
     */
    public function moveToPackage(string $projectId, string $packageId, string $parentPackageId, ?int $position = null): void
    {
        $payload = ['parentPackageId' => $parentPackageId];
        if (null !== $position) {
            $payload['position'] = $position;
        }
        $this->transport->request('PUT', $this->path($projectId, $packageId, '/package'), json: $payload);
    }

    /**
     * DELETE /projects/{id}/packages/{pid}/package: moves the package out of its parent into the project root.
     */
    public function removeFromParentPackage(string $projectId, string $packageId): void
    {
        $this->transport->request('DELETE', $this->path($projectId, $packageId, '/package'));
    }

    /**
     * PUT /projects/{id}/packages/{pid}/company.
     */
    public function setCompany(string $projectId, string $packageId, string $companyId): void
    {
        $this->transport->request('PUT', $this->path($projectId, $packageId, '/company'), json: ['companyId' => $companyId]);
    }

    /**
     * DELETE /projects/{id}/packages/{pid}/company.
     */
    public function removeCompany(string $projectId, string $packageId): void
    {
        $this->transport->request('DELETE', $this->path($projectId, $packageId, '/company'));
    }

    /**
     * PUT /projects/{id}/packages/{pid}/contact.
     */
    public function setContact(string $projectId, string $packageId, string $contactId): void
    {
        $this->transport->request('PUT', $this->path($projectId, $packageId, '/contact'), json: ['contactId' => $contactId]);
    }

    /**
     * DELETE /projects/{id}/packages/{pid}/contact.
     */
    public function removeContact(string $projectId, string $packageId): void
    {
        $this->transport->request('DELETE', $this->path($projectId, $packageId, '/contact'));
    }

    /**
     * POST /projects/{id}/packages/{pid}/shift_with_successors: shifts the package's tasks and their
     * editable successors by $daysDelta days (negative values shift backwards).
     */
    public function shiftWithSuccessors(string $projectId, string $packageId, int $daysDelta): void
    {
        $this->transport->request('POST', $this->path($projectId, $packageId, '/shift_with_successors'), json: ['daysDelta' => $daysDelta]);
    }

    /**
     * GET /projects/{id}/packages/{pid}/comments.
     *
     * @return list<PackageComment>
     */
    public function comments(string $projectId, string $packageId): array
    {
        return array_map(PackageComment::fromArray(...), $this->rows($this->transport->request('GET', $this->path($projectId, $packageId, '/comments'))));
    }

    /**
     * GET /projects/{id}/packages/{pid}/comments/{cid}.
     */
    public function comment(string $projectId, string $packageId, string $commentId): PackageComment
    {
        return PackageComment::fromArray($this->object($this->transport->request('GET', $this->path($projectId, $packageId, '/comments/'.rawurlencode($commentId)))));
    }

    /**
     * POST /projects/{id}/packages/{pid}/comments.
     */
    public function addComment(string $projectId, string $packageId, NewPackageComment $comment): PackageComment
    {
        return PackageComment::fromArray($this->object($this->transport->request('POST', $this->path($projectId, $packageId, '/comments'), json: $comment->toPayload())));
    }

    /**
     * DELETE /projects/{id}/packages/{pid}/comments/{cid}. The returned comment is discarded.
     */
    public function deleteComment(string $projectId, string $packageId, string $commentId): void
    {
        $this->transport->request('DELETE', $this->path($projectId, $packageId, '/comments/'.rawurlencode($commentId)));
    }

    /**
     * GET /projects/{id}/packages/{pid}/documents.
     *
     * @return list<Document>
     */
    public function documents(string $projectId, string $packageId): array
    {
        return array_map(Document::fromArray(...), $this->rows($this->transport->request('GET', $this->path($projectId, $packageId, '/documents'))));
    }

    /**
     * POST /projects/{id}/packages/{pid}/documents: uploads the file as multipart/form-data.
     */
    public function addDocument(string $projectId, string $packageId, DocumentUpload $upload): Document
    {
        return Document::fromArray($this->object($this->transport->request('POST', $this->path($projectId, $packageId, '/documents'), multipart: $upload->toMultipart())));
    }

    /**
     * DELETE /projects/{id}/packages/{pid}/documents/{did}. The returned document is discarded.
     */
    public function removeDocument(string $projectId, string $packageId, string $documentId): void
    {
        $this->transport->request('DELETE', $this->path($projectId, $packageId, '/documents/'.rawurlencode($documentId)));
    }

    /**
     * Read and write rights of the package. Employee routes live below /projects/{id}/packages/{pid},
     * team routes below /projects/{id}/{pid} (the API omits the "/packages" segment there).
     */
    public function accessRights(string $projectId, string $packageId): AccessRights
    {
        return new AccessRights(
            $this->transport,
            $this->options,
            $this->path($projectId, $packageId),
            sprintf('/projects/%s/%s', rawurlencode($projectId), rawurlencode($packageId)),
        );
    }

    /**
     * "/projects/{id}/packages/{pid}" plus an optional, already encoded suffix.
     */
    private function path(string $projectId, string $packageId, string $suffix = ''): string
    {
        return sprintf('/projects/%s/packages/%s%s', rawurlencode($projectId), rawurlencode($packageId), $suffix);
    }
}
