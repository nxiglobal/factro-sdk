<?php

declare(strict_types=1);

namespace Nxi\Factro;

use Nxi\Factro\Http\Transport;
use Nxi\Factro\Resource\Appointment\Appointments;
use Nxi\Factro\Resource\Comment\Comments;
use Nxi\Factro\Resource\Company\Companies;
use Nxi\Factro\Resource\Contact\Contacts;
use Nxi\Factro\Resource\CustomView\CustomViews;
use Nxi\Factro\Resource\Document\Documents;
use Nxi\Factro\Resource\Note\Notes;
use Nxi\Factro\Resource\Package\Packages;
use Nxi\Factro\Resource\Project\Projects;
use Nxi\Factro\Resource\Task\Tasks;
use Nxi\Factro\Resource\Team\Teams;
use Nxi\Factro\Resource\Template\Templates;
use Nxi\Factro\Resource\TodoList\TodoLists;
use Nxi\Factro\Resource\User\Users;
use Nxi\Factro\Resource\Webhook\Webhooks;
use Nxi\Factro\Resource\WorkRecord\WorkRecords;

/**
 * Entry point to the factro Core API. Build it with FactroClientFactory::create().
 */
final readonly class FactroClient
{
    /**
     * @internal use FactroClientFactory::create()
     */
    public function __construct(private Transport $transport, private FactroOptions $options)
    {
    }

    public function projects(): Projects
    {
        return new Projects($this->transport, $this->options);
    }

    public function packages(): Packages
    {
        return new Packages($this->transport, $this->options);
    }

    public function tasks(): Tasks
    {
        return new Tasks($this->transport, $this->options);
    }

    public function users(): Users
    {
        return new Users($this->transport, $this->options);
    }

    public function companies(): Companies
    {
        return new Companies($this->transport, $this->options);
    }

    public function contacts(): Contacts
    {
        return new Contacts($this->transport, $this->options);
    }

    public function workRecords(): WorkRecords
    {
        return new WorkRecords($this->transport, $this->options);
    }

    public function appointments(): Appointments
    {
        return new Appointments($this->transport, $this->options);
    }

    public function teams(): Teams
    {
        return new Teams($this->transport, $this->options);
    }

    public function documents(): Documents
    {
        return new Documents($this->transport, $this->options);
    }

    public function comments(): Comments
    {
        return new Comments($this->transport, $this->options);
    }

    public function notes(): Notes
    {
        return new Notes($this->transport, $this->options);
    }

    public function todoLists(): TodoLists
    {
        return new TodoLists($this->transport, $this->options);
    }

    public function webhooks(): Webhooks
    {
        return new Webhooks($this->transport, $this->options);
    }

    public function customViews(): CustomViews
    {
        return new CustomViews($this->transport, $this->options);
    }

    public function templates(): Templates
    {
        return new Templates($this->transport, $this->options);
    }

    public function options(): FactroOptions
    {
        return $this->options;
    }

    /**
     * @internal for resource classes and tests
     */
    public function transport(): Transport
    {
        return $this->transport;
    }
}
