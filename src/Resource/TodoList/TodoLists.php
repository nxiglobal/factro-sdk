<?php

declare(strict_types=1);

namespace Nxi\Factro\Resource\TodoList;

use Nxi\Factro\Resource\AbstractResource;
use Nxi\Factro\Resource\TodoList\Output\TodoList;

final class TodoLists extends AbstractResource
{
    /**
     * GET /todo-list. The todo lists visible to the API user, each with its elements.
     *
     * @return list<TodoList>
     */
    public function list(): array
    {
        return array_map(TodoList::fromArray(...), $this->rows($this->transport->request('GET', '/todo-list')));
    }
}
