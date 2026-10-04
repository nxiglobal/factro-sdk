# Decisions

## 2026-09-14 Resources live under Nxi\Factro\Resource with Input and Output sub-namespaces

One namespace per API resource below `Nxi\Factro\Resource` holds the resource class without suffix
(`Tasks`, `Projects`, ...), its enums, `Input` for sent bodies and `Output` for received payloads. DTOs keep
direction prefixes (`NewTask`, `TaskChanges`) to avoid import collisions. Cross-cutting namespaces
(`Exception`, `Http`, `Mapping`, `Policy`, `Time`, `Testing`) stay outside. `Resource\AbstractResource`
and `Mapping` are internal. Rejected: a layout by kind (`Api`, `Data`, `Enum`) and the names `Model` and
`Api`.

## 2026-09-14 Access rights are one shared class, comments and tags stay per resource

Tasks, packages, projects and templates share the read/write-right shapes, so `Resource\AccessRight\AccessRights`
implements them once and the owner hands out a bound instance (`$client->tasks()->accessRights($id)`).
Packages pass a second path because factro's team-right routes omit `/packages`. Comments and tags carry
their owner's reference key (`taskId`, `projectId`, ...), so they stay resource-local DTOs behind uniform
method names. Contacts reuse `Resource\User\Salutation` because the API shares the schema.

## 2026-09-14 Enum cases are UPPERCASE and abbreviations are spelled out

`IN_PROCESS`, `GUEST_RIGHTS`, `PRIORITY_10` (not `P10`). Backing values stay as the API sends them.

## 2026-09-14 Relative paths are sent without a leading slash

Per RFC 3986, Symfony's `resolveUrl()` lets `/tasks/{id}` replace the whole `base_uri` path. The public
contract keeps leading slashes (`Transport::request('GET', '/tasks/abc')`, policy patterns like
`#^/tasks/#`). Internally the base URL ends with exactly one `/`, `Transport` strips the leading slash and
`PolicyHttpClient` restores it before `RequestPolicy::permits()`. Test `MockHttpClient` base URIs end in `/`.

## 2026-09-14 Response rows are rebuilt with string keys in AbstractResource

`rows()` and `object()` copy decoded rows into string-keyed arrays so `fromArray(array<string, mixed>)`
holds without `@var` assertions. JSON object keys are always strings, so the copy changes nothing.

## 2026-09-14 Document uploads build multipart bodies without symfony/mime

`POST .../documents` has no documented request body. `Http\MultipartBody` sends one `multipart/form-data`
part named `file`, taken from observed API behaviour; `symfony/mime` is not worth a dependency for one route.

## 2026-09-14 Out of scope: GET /tasks and the deprecated efforts routes

`GET /tasks` returns all tenant tasks unpaginated; use `Tasks::listByProject()`. The deprecated `/efforts*`
routes are covered by work records. Webhook `Action` (about 300 values) stays a string; the undocumented
`startDate` path parameter of webhook routes is sent as ISO UTC.

## 2026-10-04 Fixtures are hand-written, there is no live recorder

Fixtures ship with the package and are public. A recording from a real tenant would publish tenant and
object IDs and custom field names, and the former recorder covered only a fifth of the files. Fixtures are
written by hand from the OpenAPI spec and observed API behaviour; reported deviations are fixed the same way.
