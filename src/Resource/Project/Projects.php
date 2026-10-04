<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Project;

use Nxi\Factro\Exception\NotFoundException;
use Nxi\Factro\Resource\AbstractResource;
use Nxi\Factro\Resource\AccessRight\AccessRights;
use Nxi\Factro\Resource\Document\Input\DocumentUpload;
use Nxi\Factro\Resource\Document\Output\Document;
use Nxi\Factro\Resource\Project\Input\NewProject;
use Nxi\Factro\Resource\Project\Input\NewProjectComment;
use Nxi\Factro\Resource\Project\Input\ProjectChanges;
use Nxi\Factro\Resource\Project\Output\Project;
use Nxi\Factro\Resource\Project\Output\ProjectComment;
use Nxi\Factro\Resource\Project\Output\ProjectStructureNode;
use Nxi\Factro\Resource\Project\Output\ProjectTag;

final class Projects extends AbstractResource
{
    /**
     * GET /projects.
     *
     * @return list<Project>
     */
    public function list(): array
    {
        return array_map(Project::fromArray(...), $this->rows($this->transport->request('GET', '/projects')));
    }

    /**
     * GET /projects/{id}.
     */
    public function get(string $projectId): Project
    {
        return Project::fromArray($this->object($this->transport->request('GET', '/projects/'.rawurlencode($projectId))));
    }

    /**
     * Like get(), but null on 404. Every other exception passes through.
     */
    public function find(string $projectId): ?Project
    {
        try {
            return $this->get($projectId);
        } catch (NotFoundException) {
            return null;
        }
    }

    /**
     * GET /projects/{id}/structure: the project with its packages and tasks as a tree.
     */
    public function structure(string $projectId): ProjectStructureNode
    {
        return ProjectStructureNode::fromArray($this->object($this->transport->request('GET', '/projects/'.rawurlencode($projectId).'/structure')));
    }

    /**
     * POST /projects.
     */
    public function create(NewProject $project): Project
    {
        return Project::fromArray($this->object($this->transport->request('POST', '/projects', json: $project->toPayload($this->options->timezone))));
    }

    /**
     * PUT /projects/{id}.
     */
    public function update(string $projectId, ProjectChanges $changes): Project
    {
        if ($changes->isEmpty()) {
            throw new \InvalidArgumentException('ProjectChanges must set at least one field.');
        }

        return Project::fromArray($this->object($this->transport->request('PUT', '/projects/'.rawurlencode($projectId), json: $changes->toPayload($this->options->timezone))));
    }

    /**
     * DELETE /projects/{id}. The returned project is discarded. Requires a RequestPolicy that permits DELETE.
     */
    public function delete(string $projectId): void
    {
        $this->transport->request('DELETE', '/projects/'.rawurlencode($projectId));
    }

    /**
     * POST /projects/projects: creates all projects in one atomic transaction.
     *
     * @param list<NewProject> $projects
     *
     * @return list<Project>
     */
    public function createMany(array $projects): array
    {
        if ([] === $projects) {
            throw new \InvalidArgumentException('createMany() needs at least one project.');
        }
        $payloads = [];
        foreach ($projects as $project) {
            $payloads[] = $project->toPayload($this->options->timezone);
        }

        return array_map(Project::fromArray(...), $this->rows($this->transport->request('POST', '/projects/projects', json: $payloads)));
    }

    /**
     * PUT /projects/projects: updates all projects in one atomic transaction.
     *
     * @param array<string, ProjectChanges> $changes keyed by project id
     *
     * @return list<Project>
     */
    public function updateMany(array $changes): array
    {
        if ([] === $changes) {
            throw new \InvalidArgumentException('updateMany() needs at least one project.');
        }
        $payloads = [];
        foreach ($changes as $projectId => $projectChanges) {
            if ($projectChanges->isEmpty()) {
                throw new \InvalidArgumentException(sprintf('ProjectChanges for project "%s" must set at least one field.', $projectId));
            }
            $payloads[] = ['id' => $projectId] + $projectChanges->toPayload($this->options->timezone);
        }

        return array_map(Project::fromArray(...), $this->rows($this->transport->request('PUT', '/projects/projects', json: $payloads)));
    }

    /**
     * GET /projects/tags: every project tag of the tenant.
     *
     * @return list<ProjectTag>
     */
    public function projectTags(): array
    {
        return array_map(ProjectTag::fromArray(...), $this->rows($this->transport->request('GET', '/projects/tags')));
    }

    /**
     * POST /projects/tags.
     */
    public function createProjectTag(string $name): ProjectTag
    {
        return ProjectTag::fromArray($this->object($this->transport->request('POST', '/projects/tags', json: ['name' => $name])));
    }

    /**
     * DELETE /projects/tags/{tagId}. The returned tag is discarded.
     */
    public function deleteProjectTag(string $tagId): void
    {
        $this->transport->request('DELETE', '/projects/tags/'.rawurlencode($tagId));
    }

    /**
     * GET /projects/{id}/tags: the tags assigned to one project.
     *
     * @return list<ProjectTag>
     */
    public function tags(string $projectId): array
    {
        return array_map(ProjectTag::fromArray(...), $this->rows($this->transport->request('GET', '/projects/'.rawurlencode($projectId).'/tags')));
    }

    /**
     * PUT /projects/{id}/tags.
     */
    public function addTag(string $projectId, string $tagId): void
    {
        $this->transport->request('PUT', '/projects/'.rawurlencode($projectId).'/tags', json: ['tagId' => $tagId]);
    }

    /**
     * DELETE /projects/{id}/tags/{tagId}.
     */
    public function removeTag(string $projectId, string $tagId): void
    {
        $this->transport->request('DELETE', '/projects/'.rawurlencode($projectId).'/tags/'.rawurlencode($tagId));
    }

    /**
     * PUT /projects/{id}/company.
     */
    public function setCompany(string $projectId, string $companyId): void
    {
        $this->transport->request('PUT', '/projects/'.rawurlencode($projectId).'/company', json: ['companyId' => $companyId]);
    }

    /**
     * DELETE /projects/{id}/company.
     */
    public function removeCompany(string $projectId): void
    {
        $this->transport->request('DELETE', '/projects/'.rawurlencode($projectId).'/company');
    }

    /**
     * PUT /projects/{id}/contact.
     */
    public function setContact(string $projectId, string $contactId): void
    {
        $this->transport->request('PUT', '/projects/'.rawurlencode($projectId).'/contact', json: ['contactId' => $contactId]);
    }

    /**
     * DELETE /projects/{id}/contact.
     */
    public function removeContact(string $projectId): void
    {
        $this->transport->request('DELETE', '/projects/'.rawurlencode($projectId).'/contact');
    }

    /**
     * GET /projects/{id}/comments.
     *
     * @return list<ProjectComment>
     */
    public function comments(string $projectId): array
    {
        return array_map(ProjectComment::fromArray(...), $this->rows($this->transport->request('GET', '/projects/'.rawurlencode($projectId).'/comments')));
    }

    /**
     * GET /projects/{id}/comments/{commentId}.
     */
    public function comment(string $projectId, string $commentId): ProjectComment
    {
        return ProjectComment::fromArray($this->object($this->transport->request('GET', '/projects/'.rawurlencode($projectId).'/comments/'.rawurlencode($commentId))));
    }

    /**
     * POST /projects/{id}/comments.
     */
    public function addComment(string $projectId, NewProjectComment $comment): ProjectComment
    {
        return ProjectComment::fromArray($this->object($this->transport->request('POST', '/projects/'.rawurlencode($projectId).'/comments', json: $comment->toPayload())));
    }

    /**
     * DELETE /projects/{id}/comments/{commentId}. The returned comment is discarded.
     */
    public function deleteComment(string $projectId, string $commentId): void
    {
        $this->transport->request('DELETE', '/projects/'.rawurlencode($projectId).'/comments/'.rawurlencode($commentId));
    }

    /**
     * GET /projects/{id}/documents.
     *
     * @return list<Document>
     */
    public function documents(string $projectId): array
    {
        return array_map(Document::fromArray(...), $this->rows($this->transport->request('GET', '/projects/'.rawurlencode($projectId).'/documents')));
    }

    /**
     * POST /projects/{id}/documents: uploads the file as multipart/form-data and attaches it to the project.
     */
    public function addDocument(string $projectId, DocumentUpload $upload): Document
    {
        return Document::fromArray($this->object($this->transport->request('POST', '/projects/'.rawurlencode($projectId).'/documents', multipart: $upload->toMultipart())));
    }

    /**
     * DELETE /projects/{id}/documents/{documentId}. The returned document is discarded.
     */
    public function removeDocument(string $projectId, string $documentId): void
    {
        $this->transport->request('DELETE', '/projects/'.rawurlencode($projectId).'/documents/'.rawurlencode($documentId));
    }

    /**
     * Read and write rights of the project; employee and team endpoints both live below /projects/{id}.
     */
    public function accessRights(string $projectId): AccessRights
    {
        $path = '/projects/'.rawurlencode($projectId);

        return new AccessRights($this->transport, $this->options, $path, $path);
    }
}
