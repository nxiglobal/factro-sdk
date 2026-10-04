<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\Contact;

use Nxi\Factro\Exception\NotFoundException;
use Nxi\Factro\Resource\AbstractResource;
use Nxi\Factro\Resource\Contact\Input\ContactChanges;
use Nxi\Factro\Resource\Contact\Input\NewContact;
use Nxi\Factro\Resource\Contact\Output\Contact;

final class Contacts extends AbstractResource
{
    /**
     * GET /contacts.
     *
     * @return list<Contact>
     */
    public function list(): array
    {
        return array_map(Contact::fromArray(...), $this->rows($this->transport->request('GET', '/contacts')));
    }

    /**
     * GET /contacts/{id}.
     */
    public function get(string $contactId): Contact
    {
        return Contact::fromArray($this->object($this->transport->request('GET', '/contacts/'.rawurlencode($contactId))));
    }

    /**
     * Like get(), but null on 404. Every other exception passes through.
     */
    public function find(string $contactId): ?Contact
    {
        try {
            return $this->get($contactId);
        } catch (NotFoundException) {
            return null;
        }
    }

    /**
     * POST /contacts.
     */
    public function create(NewContact $contact): Contact
    {
        return Contact::fromArray($this->object($this->transport->request('POST', '/contacts', json: $contact->toPayload())));
    }

    /**
     * PUT /contacts/{id}.
     */
    public function update(string $contactId, ContactChanges $changes): Contact
    {
        if ($changes->isEmpty()) {
            throw new \InvalidArgumentException('ContactChanges must set at least one field.');
        }

        return Contact::fromArray($this->object($this->transport->request('PUT', '/contacts/'.rawurlencode($contactId), json: $changes->toPayload())));
    }

    /**
     * DELETE /contacts/{id}. The deleted contact in the response is discarded. Requires a RequestPolicy that permits DELETE.
     */
    public function delete(string $contactId): void
    {
        $this->transport->request('DELETE', '/contacts/'.rawurlencode($contactId));
    }

    /**
     * POST /contacts/contacts. Atomic: if one contact is invalid, none is created.
     *
     * @param list<NewContact> $contacts
     *
     * @return list<Contact>
     */
    public function createMany(array $contacts): array
    {
        if ([] === $contacts) {
            throw new \InvalidArgumentException('createMany() needs at least one contact.');
        }
        $payload = array_map(static fn (NewContact $contact): array => $contact->toPayload(), array_values($contacts));

        return array_map(Contact::fromArray(...), $this->rows($this->transport->request('POST', '/contacts/contacts', json: $payload)));
    }

    /**
     * PUT /contacts/contacts. Atomic: if one contact is invalid, none is updated.
     *
     * @param array<string, ContactChanges> $changes keyed by contact id
     *
     * @return list<Contact>
     */
    public function updateMany(array $changes): array
    {
        if ([] === $changes) {
            throw new \InvalidArgumentException('updateMany() needs at least one contact.');
        }
        $payload = [];
        foreach ($changes as $contactId => $contactChanges) {
            $payload[] = ['id' => (string) $contactId] + $contactChanges->toPayload();
        }

        return array_map(Contact::fromArray(...), $this->rows($this->transport->request('PUT', '/contacts/contacts', json: $payload)));
    }
}
