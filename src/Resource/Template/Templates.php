<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Template;

use Nxi\Factro\Resource\AbstractResource;
use Nxi\Factro\Resource\AccessRight\AccessRights;
use Nxi\Factro\Resource\Project\Output\Project;
use Nxi\Factro\Resource\Task\Output\Task;
use Nxi\Factro\Resource\Template\Input\ApplyStructureTemplate;

/**
 * Structure templates (project blueprints) and task templates.
 *
 * factro exposes no endpoint that lists templates; ids come from the UI or from a webhook payload.
 */
final class Templates extends AbstractResource
{
    /**
     * Rights below /templates/structure/{id}: read_rights, write_rights, team_read_rights, team_write_rights.
     */
    public function structureTemplateAccessRights(string $templateId): AccessRights
    {
        $path = '/templates/structure/'.rawurlencode($templateId);

        return new AccessRights($this->transport, $this->options, $path, $path);
    }

    /**
     * Rights below /templates/task/{id}: read_rights, write_rights, team_read_rights, team_write_rights.
     */
    public function taskTemplateAccessRights(string $templateId): AccessRights
    {
        $path = '/templates/task/'.rawurlencode($templateId);

        return new AccessRights($this->transport, $this->options, $path, $path);
    }

    /**
     * POST /templates/structure/{id}/apply_template: creates a project from the template, or inserts
     * the template below $input->targetParentId when set.
     */
    public function applyStructureTemplate(string $templateId, ApplyStructureTemplate $input): Project
    {
        $payload = $this->asJsonObject($input->toPayload($this->options->timezone));

        return Project::fromArray($this->object($this->transport->request('POST', '/templates/structure/'.rawurlencode($templateId).'/apply_template', json: $payload)));
    }

    /**
     * POST /templates/task/{id}/apply_template: creates a task from the template, below $targetParentId when set.
     */
    public function applyTaskTemplate(string $templateId, ?string $targetParentId = null): Task
    {
        $payload = $this->asJsonObject(null === $targetParentId ? [] : ['targetParentId' => $targetParentId]);

        return Task::fromArray($this->object($this->transport->request('POST', '/templates/task/'.rawurlencode($templateId).'/apply_template', json: $payload)));
    }

    /**
     * json_encode() turns an empty PHP array into "[]"; the apply endpoints expect a JSON object, so an
     * empty body is sent as "{}".
     *
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>|\stdClass
     */
    private function asJsonObject(array $payload): array|\stdClass
    {
        return [] === $payload ? new \stdClass() : $payload;
    }
}
