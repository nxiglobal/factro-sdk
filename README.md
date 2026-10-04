# nxi/factro-sdk

[![QA](https://github.com/nxiglobal/factro-sdk/actions/workflows/qa.yml/badge.svg)](https://github.com/nxiglobal/factro-sdk/actions/workflows/qa.yml)
[![Packagist Version](https://img.shields.io/packagist/v/nxi/factro-sdk)](https://packagist.org/packages/nxi/factro-sdk)
[![License](https://img.shields.io/packagist/l/nxi/factro-sdk)](LICENSE)

> Unofficial SDK. Not affiliated with, endorsed by or supported by Schuchert Managementberatung GmbH & Co. KG,
> the maker of factro. factro® is a registered trademark of Schuchert Managementberatung GmbH & Co. KG.

[factro](https://www.factro.de) is a web-based project management software made in Germany. Its Core API is a REST API for projects, packages, tasks, users, work records and the other objects of a factro tenant; the official documentation is at https://cloud.factro.com/api/core/docs/.

This package is a PHP SDK for the factro Core API, built exclusively on Symfony components (`symfony/http-client`, `symfony/rate-limiter`, `symfony/clock`). It encapsulates the API's quirks (raw token header, IETF rate-limit headers, mixed date formats, partial-object PUT semantics) behind typed Api classes, readonly DTOs and a small exception family.

## Requirements

- PHP 8.4 or newer
- Symfony 8 components: `symfony/http-client`, `symfony/rate-limiter`, `symfony/clock`
- `psr/log` 3
- a token for the factro Core API

## Installation

```bash
composer require nxi/factro-sdk:^1.0
```

## Usage

```php
use Nxi\Factro\FactroClientFactory;
use Nxi\Factro\FactroOptions;
use Nxi\Factro\Policy\RequestPolicy;
use Nxi\Factro\Resource\Task\Input\NewTask;
use Nxi\Factro\Resource\Task\Input\TaskChanges;
use Nxi\Factro\Resource\Task\TaskState;
use Nxi\Factro\Time\CalendarDate;

$client = FactroClientFactory::create(
    token: $token,                        // raw factro Core API token, sent without "Bearer"
    options: new FactroOptions(
        policy: RequestPolicy::withoutDeletes(),
        logger: $logger,
        limiterFactory: $limiterFactory,  // optional, default: in-memory sliding window 480/min
    ),
    httpClient: $baseClient,              // optional, default: HttpClient::create()
);

$task = $client->tasks()->get('c1c9...');
$open = array_filter($client->tasks()->listByProject($projectId), fn ($t) => $t->taskState->isOpen());

$created = $client->tasks()->create(new NewTask(
    title: 'Review offer',
    targetParentId: $packageId,
    endDate: CalendarDate::fromYmd('2026-09-30'),
));
$client->tasks()->update($created->id, new TaskChanges(description: '<p>Done</p>'));
$client->tasks()->setState($created->id, TaskState::CLOSED);
```

`FactroClientFactory::create()` wraps the base client in a decorator chain: request policy, retry (GET only), rate limiter, scoping (base URI, headers, timeouts). Every request passes through `Transport`, which decodes JSON, maps failures onto the exception family in `Nxi\Factro\Exception` and logs one line per call (never headers, never bodies). Resource classes are reached through `$client->projects()`, `packages()`, `tasks()`, `users()`, `companies()`, `contacts()`, `workRecords()`, `appointments()`, `teams()`, `documents()`, `comments()`, `notes()`, `todoLists()`, `webhooks()`, `customViews()` and `templates()`; together they cover every operation of the Core API except `GET /tasks` (tenant-wide, unpaginated) and the deprecated `/efforts*` routes.

## Package layout

Classes are grouped by API resource under `Nxi\Factro\Resource`. Each resource namespace holds the resource class (reached through `$client->tasks()` and friends) and its enums; what the SDK sends lives in `Input`, what it receives in `Output`:

```
Nxi\Factro\Resource\Task\Tasks
Nxi\Factro\Resource\Task\TaskState, TaskPriority, Urgency
Nxi\Factro\Resource\Task\Input\NewTask, TaskChanges, NewTaskComment, TaskConnectionInput
Nxi\Factro\Resource\Task\Output\Task, TaskComment, TaskConnection
```

`Project`, `Package`, `WorkRecord`, `User`, `Company`, `Contact`, `Appointment`, `Team`, `Document`, `Comment`, `Note`, `TodoList`, `Webhook`, `CustomView` and `Template` follow the same pattern. Enum cases are UPPERCASE (`TaskState::IN_PROCESS`, `TaskPriority::PRIORITY_50`). `Nxi\Factro\Http`, `Nxi\Factro\Mapping` and `Resource\AbstractResource` are `@internal`.

Operations that recur across resources share one naming scheme:

| Operation | Methods |
|:--|:--|
| CRUD | `list()`, `get()`, `find()` (null on 404), `create()`, `update()`, `delete()` |
| Batch routes (`POST /x/x`, `PUT /x/x`) | `createMany(list<NewX>)`, `updateMany(array<id, XChanges>)` |
| Comments | `comments()`, `comment()`, `addComment()`, `deleteComment()` |
| Tags | `taskTags()`/`projectTags()`/`companyTags()`/`employeeTags()`, `createXTag()`, `deleteXTag()`, `tags($id)`, `addTag()`, `removeTag()` |
| Associations | `setCompany()`, `removeCompany()`, `setContact()`, `removeContact()`, `moveToProject()`, `removeFromProject()`, `moveToPackage()`, `removeFromPackage()` |
| Documents | `documents()`, `addDocument(DocumentUpload)`, `removeDocument()` |
| Access rights | `accessRights($id)` returns `AccessRights` with `readRights()`, `writeRights()`, `grantRead()`, `revokeRead()`, `grantWrite()`, `revokeWrite()`, `grantTeamRead()`, `revokeTeamRead()`, `grantTeamWrite()`, `revokeTeamWrite()` |

Delete-type operations return `void` and discard factro's response body. Package methods take `$projectId` before `$packageId`.

## Users and tags

`GET /users` ignores query parameters and returns every user of the tenant (a few thousand rows), so cache the result. Employee tags are fetched per user; `tagsForMany()` sends the requests concurrently in batches and maps a 404 to an empty list:

```php
$users = array_filter($client->users()->list(), fn (User $u) => $u->isActive && !$u->securityGroup->isGuest());
$tags = $client->users()->tagsForMany(array_column($users, 'id'), concurrency: 5);   // user id => list<EmployeeTag>
$all = $client->users()->employeeTags();                                            // GET /users/tags
```

Comments (`comments()`, `addComment()`), connections (`connections()`, `addConnection()`) and the project structure (`projects()->structure()`, a tree with `find()`, `depthOf()` and `packageIds()`) complete the read model. `users()` also manages users (`create()`, `update()`, `delete()`), substitutes (`substitutes()`, `substitutedUsers()`, `addSubstitute()`, `removeSubstitute()`), absences (`absences()`, `absencesOf()`, `createAbsence()`, `updateAbsence()`, `deleteAbsence()` and the batch variants) and the seat quota (`quota()`).

## Checklists, tags, associations and access rights

```php
$entry = $client->tasks()->addChecklistEntry($taskId, new NewChecklistEntry('Call customer'));
$client->tasks()->updateChecklistEntry($taskId, $entry->id, new ChecklistEntryChanges(checked: true));

$tag = $client->tasks()->createTaskTag('urgent');
$client->tasks()->addTag($taskId, $tag->id);

$client->tasks()->moveToPackage($taskId, $packageId);          // PUT /tasks/{id}/package
$client->packages()->shiftWithSuccessors($projectId, $packageId, daysDelta: 5);

$rights = $client->projects()->accessRights($projectId);
$rights->grantTeamWrite($teamId);
$reasons = $rights->readRights();                                // employee id => list of reasons
```

## Documents

```php
$document = $client->tasks()->addDocument($taskId, DocumentUpload::fromFile('/tmp/offer.pdf', 'application/pdf'));
$all = $client->documents()->list();
$quota = $client->documents()->quota();                          // maxDiskSpace, usedDiskSpace, freeDiskSpace()
```

Uploads are sent as one `multipart/form-data` part named `file`; the OpenAPI document does not describe the request body, so the part name is derived from the observed behaviour of the API.

## Appointments, teams, contacts and templates

```php
$client->appointments()->create(new NewAppointment($employeeId, $start, $end, subject: 'Kickoff'));
$team = $client->teams()->create(new NewTeam('Backend', '#3366ff'));
$client->teams()->addMember($team->id, $employeeId);
$contact = $client->contacts()->create(new NewContact('Erika', 'Muster', emailAddress: 'erika@example.invalid'));

$project = $client->templates()->applyStructureTemplate($templateId, new ApplyStructureTemplate(
    projectPropertyOverwrites: new ProjectPropertyOverwrites(title: 'Relaunch 2027'),
));
```

`comments()->get($id)` resolves any comment by id with its `referenceType`; `notes()` reads and writes note comments; `todoLists()->list()` returns the to-do lists with their elements; `webhooks()->payloads($since)` and `payloadsByAction($action, $since)` read delivered webhook payloads; `customViews()` manages saved views and their templates.

## Work records

```php
$records = $client->workRecords()->listByProject($projectId, CalendarDate::fromYmd('2026-01-01'), CalendarDate::fromYmd('2026-01-31'));
$start = $records[0]->startsAt(new DateTimeZone('Europe/Berlin'));   // uses the record's utcOffset when present

$client->workRecords()->create(new NewWorkRecord(
    startDate: CalendarDate::fromYmd('2026-09-01'),
    minutesWorked: 90,
    utcOffsetMinutes: 120,
    bookedOnReferenceId: $taskId,
    bookedOnReferenceType: WorkRecordReferenceType::TASK,
    startTime: '09:00',
    isBillable: true,
));
```

## Options

`FactroOptions` is readonly and built with named arguments:

| Option | Default | Purpose |
|:--|:--|:--|
| `baseUrl` | `https://cloud.factro.com/api/core` | test servers, fake endpoints in E2E tests |
| `requestTimeoutSeconds` | 20.0 | `max_duration` of a request |
| `inactivityTimeoutSeconds` | 10.0 | `timeout` (Symfony semantics: inactivity) |
| `timezone` | `Europe/Berlin` | calendar days for `CalendarDate` fields in write DTOs |
| `policy` | `RequestPolicy::all()` | allowed methods and denied path patterns |
| `limiterFactory` | sliding window 480/min, `InMemoryStorage` | pass a factory with `CacheStorage` when several processes share one token |
| `maxRetries` | 3 | GET only |
| `maxRetryAfterSeconds` | 5 | longer `Retry-After` values surface as `RateLimitException` instead of blocking |
| `logger` | `NullLogger` | one line per call |
| `clock` | `NativeClock` | `creationDate` when creating tasks, `MockClock` in tests |
| `onRateLimit` | `null` | closure receiving `RateLimitInfo` after every response, for metrics |
| `userAgent` | `nxi-factro-sdk/<version>` | identification towards factro |
| `sendCreationDate` | `true` | whether `NewTask` sends `creationDate`; the API's default is undocumented |

The token is a `#[\SensitiveParameter]` everywhere, is never logged and never appears in an exception message.

## Errors

All exceptions implement `Nxi\Factro\Exception\FactroException`.

| Exception | When |
|:--|:--|
| `AuthenticationException` | HTTP 401 (invalid token) and 403 (for example "Task is closed") |
| `NotFoundException` | HTTP 404; `find*()` methods turn it into `null` |
| `ValidationException` | HTTP 400 and 422, `errors()` returns the body's error list |
| `RateLimitException` | HTTP 429, `retryAfterSeconds()` from `Retry-After` or the `ratelimit` header |
| `ServerException` | HTTP 500 to 599 |
| `FactroRequestException` | every other status of 400 or higher; base class of the above |
| `TransportException` | DNS, TCP, TLS or timeout failure; `possiblyExecuted()` is true for POST, PUT, DELETE |
| `OperationNotPermittedException` | the `RequestPolicy` forbids the call; thrown before anything is sent |
| `HydrationException` | a response field is missing or has an unexpected type |

`FactroRequestException` carries `method`, `path`, `statusCode`, `rawBody`, `headers` and offers `factroMessage()` (from a JSON `message`/`error`/`detail`/`title` or the plain-text body).

## Dates

factro stores dates as UTC timestamps in three variants: `T22:00Z` and `T23:00Z` (created in the UI, local midnight in Europe/Berlin) and `T00:00Z` (created through the API with a plain date). DTOs expose them unchanged as `DateTimeImmutable` in UTC. Use `CalendarDate::fromFactro($utc, $timezone)` to get the intended calendar day and `CalendarDate` in write DTOs; `toFactro()` produces local midnight in UTC, the storage form of the UI.

The `timezone` option defaults to `Europe/Berlin` because the factro UI stores calendar days as local midnight in that zone. With this default, dates written through the SDK look the same as dates entered in the UI. Change it only if your tenant's users work in a different time zone.

Work records use their own representation: `startDate`/`endDate` as `Y-m-d`, `startTime`/`endTime` as `HH:MM`, `utcOffset` in minutes. Comments and work records carry JavaScript timestamps (milliseconds) in `changeDate`, `createdAt` and `updatedAt`; `FactroDateTime::fromJsTimestamp()` converts them.

## Testing

Pass a `MockHttpClient` as base client; the SDK ships hand-written response fixtures:

```php
use Nxi\Factro\Testing\Fixtures;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;

$mock = new MockHttpClient(fn ($method, $url) => JsonMockResponse::fromFile(Fixtures::path('task')), 'https://factro.test/api/core/');
$client = FactroClientFactory::create('test-token', new FactroOptions(baseUrl: 'https://factro.test/api/core', maxRetries: 0), $mock);
```

`Fixtures::json('tasks-by-project')` returns the decoded rows, `Fixtures::raw('errors/404-task.txt')` the text bodies. See `fixtures/README.md` for the placeholder conventions.

### Testing factories

`Nxi\Factro\Testing\Factory\*` (Task, Project, Package, User, Company, Contact, TaskComment, WorkRecord, EmployeeTag, Document, Appointment, Team, Absence, ChecklistEntry, TodoList, CustomView) build rows and DTOs from the fixtures; they live under `src/` so consumers can use them without `require-dev`:

```php
$row = TaskFactory::make(['title' => 'Custom', 'executorId' => null]);   // array, overrides merged with array_replace
$task = TaskFactory::dto(['taskState' => 'closed']);                     // Nxi\Factro\Resource\Task\Output\Task
$rows = ProjectFactory::many(3);                                         // distinct ids "<fixture-id>-<n>", increasing numbers
```

## Keeping up with the API

`docs/openapi/openapi.json` holds a local copy of the factro OpenAPI document (not tracked, created by `make openapi-fetch`); `bin/openapi-diff <old.json> <new.json>` lists added and removed operations and changed schema properties. See `docs/openapi/README.md` for the extraction command.

## Symfony integration

No bundle is needed:

```yaml
# config/packages/framework.yaml
framework:
    http_client:
        scoped_clients:
            factro.base:
                base_uri: '%env(FACTRO_API_URL)%'

# config/services.yaml
services:
    Nxi\Factro\FactroOptions:
        arguments:
            $logger: '@monolog.logger.factro'
            $policy: !service { class: Nxi\Factro\Policy\RequestPolicy, factory: [Nxi\Factro\Policy\RequestPolicy, withoutDeletes] }

    Nxi\Factro\FactroClient:
        factory: [Nxi\Factro\FactroClientFactory, create]
        arguments:
            $token: '%env(FACTRO_API_TOKEN)%'
            $options: '@Nxi\Factro\FactroOptions'
            $httpClient: '@factro.base'
```

In multi-tenant applications where the token changes per user, do not register `FactroClient` as a service; call the factory per request with the framework's scoped client as base.

## Versioning

The SDK follows [Semantic Versioning](https://semver.org): breaking changes only come with a new major version. Classes, methods and namespaces marked `@internal` (`Nxi\Factro\Http`, `Nxi\Factro\Mapping`, `Resource\AbstractResource`) are not covered and may change in any release. `CHANGELOG.md` lists the changes of every release.

## Contributing

See [`.github/CONTRIBUTING.md`](.github/CONTRIBUTING.md) for setup, Make targets and pull request rules.

Architecture decisions are recorded in `docs/DECISIONS.md`.
