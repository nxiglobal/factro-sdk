<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Company;

use Nxi\Factro\Exception\NotFoundException;
use Nxi\Factro\Resource\AbstractResource;
use Nxi\Factro\Resource\Company\Input\CompanyChanges;
use Nxi\Factro\Resource\Company\Input\NewCompany;
use Nxi\Factro\Resource\Company\Output\Company;
use Nxi\Factro\Resource\Company\Output\CompanyTag;

final class Companies extends AbstractResource
{
    /**
     * GET /companies.
     *
     * @return list<Company>
     */
    public function list(): array
    {
        return array_map(Company::fromArray(...), $this->rows($this->transport->request('GET', '/companies')));
    }

    /**
     * GET /companies/{id}.
     */
    public function get(string $companyId): Company
    {
        return Company::fromArray($this->object($this->transport->request('GET', '/companies/'.rawurlencode($companyId))));
    }

    /**
     * Like get(), but null on 404. Every other exception passes through.
     */
    public function find(string $companyId): ?Company
    {
        try {
            return $this->get($companyId);
        } catch (NotFoundException) {
            return null;
        }
    }

    /**
     * POST /companies.
     */
    public function create(NewCompany $company): Company
    {
        return Company::fromArray($this->object($this->transport->request('POST', '/companies', json: $company->toPayload())));
    }

    /**
     * PUT /companies/{id}.
     */
    public function update(string $companyId, CompanyChanges $changes): Company
    {
        if ($changes->isEmpty()) {
            throw new \InvalidArgumentException('CompanyChanges must set at least one field.');
        }

        return Company::fromArray($this->object($this->transport->request('PUT', '/companies/'.rawurlencode($companyId), json: $changes->toPayload())));
    }

    /**
     * DELETE /companies/{id}. The deleted company in the response is discarded. Requires a RequestPolicy that permits DELETE.
     */
    public function delete(string $companyId): void
    {
        $this->transport->request('DELETE', '/companies/'.rawurlencode($companyId));
    }

    /**
     * POST /companies/companies. factro creates the valid companies even when some entries are invalid.
     *
     * @param list<NewCompany> $companies
     *
     * @return list<Company>
     */
    public function createMany(array $companies): array
    {
        if ([] === $companies) {
            throw new \InvalidArgumentException('createMany() needs at least one company.');
        }
        $payload = array_map(static fn (NewCompany $company): array => $company->toPayload(), array_values($companies));

        return array_map(Company::fromArray(...), $this->rows($this->transport->request('POST', '/companies/companies', json: $payload)));
    }

    /**
     * PUT /companies/companies. factro updates the valid companies even when some entries are invalid.
     *
     * @param array<string, CompanyChanges> $changes keyed by company id
     *
     * @return list<Company>
     */
    public function updateMany(array $changes): array
    {
        if ([] === $changes) {
            throw new \InvalidArgumentException('updateMany() needs at least one company.');
        }
        $payload = [];
        foreach ($changes as $companyId => $companyChanges) {
            $payload[] = ['id' => (string) $companyId] + $companyChanges->toPayload();
        }

        return array_map(Company::fromArray(...), $this->rows($this->transport->request('PUT', '/companies/companies', json: $payload)));
    }

    /**
     * GET /companies/tags: every company tag of the tenant. The path is literal, not a company id.
     *
     * @return list<CompanyTag>
     */
    public function companyTags(): array
    {
        return array_map(CompanyTag::fromArray(...), $this->rows($this->transport->request('GET', '/companies/tags')));
    }

    /**
     * POST /companies/tags.
     */
    public function createCompanyTag(string $name): CompanyTag
    {
        return CompanyTag::fromArray($this->object($this->transport->request('POST', '/companies/tags', json: ['name' => $name])));
    }

    /**
     * DELETE /companies/tags/{tagId}. The deleted tag in the response is discarded.
     */
    public function deleteCompanyTag(string $tagId): void
    {
        $this->transport->request('DELETE', '/companies/tags/'.rawurlencode($tagId));
    }

    /**
     * GET /companies/{id}/tags.
     *
     * @return list<CompanyTag>
     */
    public function tags(string $companyId): array
    {
        return array_map(CompanyTag::fromArray(...), $this->rows($this->transport->request('GET', '/companies/'.rawurlencode($companyId).'/tags')));
    }

    /**
     * PUT /companies/{id}/tags: associates an existing company tag with the company.
     */
    public function addTag(string $companyId, string $tagId): void
    {
        $this->transport->request('PUT', '/companies/'.rawurlencode($companyId).'/tags', json: ['tagId' => $tagId]);
    }

    /**
     * DELETE /companies/{id}/tags/{tagId}: removes the association, the tag itself stays.
     */
    public function removeTag(string $companyId, string $tagId): void
    {
        $this->transport->request('DELETE', '/companies/'.rawurlencode($companyId).'/tags/'.rawurlencode($tagId));
    }
}
