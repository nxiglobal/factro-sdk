# Decisions

## 2026-09-14 Resource namespace layout

`Nxi\Factro\Resource\<Name>\` holds the resource class without suffix (`Tasks`), its enums, `Input` (sent bodies)
and `Output` (received payloads). DTOs keep direction prefixes (`NewTask`, `TaskChanges`) to avoid import
collisions. `Exception`, `Http`, `Mapping`, `Policy`, `Time` and `Testing` stay outside; `AbstractResource` and
`Mapping` are internal. Rejected: a layout by kind (`Api`, `Data`, `Enum`) and the names `Model` and `Api`.

## 2026-09-14 Shared access rights, per-resource comments and tags

`Resource\AccessRight\AccessRights` implements the read/write-right shapes once; owners hand out a bound
instance (`$client->tasks()->accessRights($id)`). Packages pass a second path because factro's team-right routes
omit `/packages`. Comments and tags carry their owner's key (`taskId`, ...) and stay resource-local behind
uniform method names. Contacts reuse `Resource\User\Salutation` (shared schema).

## 2026-09-14 Enum cases UPPERCASE, abbreviations spelled out

`IN_PROCESS`, `PRIORITY_10` (not `P10`). Backing values stay as the API sends them.

## 2026-09-14 Relative paths are sent without a leading slash

Symfony's `resolveUrl()` (RFC 3986) lets `/tasks/{id}` replace the whole `base_uri` path. The public contract
keeps leading slashes (`Transport::request('GET', '/tasks/abc')`, policy patterns like `#^/tasks/#`). Internally
the base URL ends in one `/`, `Transport` strips the leading slash and `PolicyHttpClient` restores it for
`RequestPolicy::permits()`. Test base URIs end in `/`.

## 2026-09-14 Response rows are rebuilt with string keys

`AbstractResource::rows()`/`object()` copy decoded rows into string-keyed arrays so `fromArray(array<string, mixed>)`
holds without `@var`. JSON object keys are strings anyway.

## 2026-09-14 Out of scope: GET /tasks and the deprecated efforts routes

`GET /tasks` is unpaginated and tenant-wide; use `Tasks::listByProject()`. Work records cover `/efforts*`.
Webhook `Action` (about 300 values) stays a string; the undocumented `startDate` path parameter of webhook routes
is sent as ISO UTC.

## 2026-10-04 Fixtures are hand-written, no live recorder

Fixtures ship publicly. Recordings would publish tenant and object IDs and custom field names, and the former
recorder covered only a fifth of the files. Fixtures come from the OpenAPI spec and observed API behaviour;
deviations are fixed by hand.

## 2026-10-12 Symfony 7.4 LTS is supported for the whole 1.x line

No 8.0-only API is used, so components accept `^7.4 || ^8.0` (LTS maintained until 2029). Dropping 7.4 is a BC
break and needs a major version. CI tests the lowest and newest 7.4 and the lowest 8.x.

## 2026-10-12 Document uploads use symfony/mime

Replaces the hand-written `Http\MultipartBody` from 2026-09-14 (one `file` part, taken from observed behaviour;
the route has no documented body). `DocumentUpload::toMultipart()` builds a `FormDataPart`, which encodes header
values, so CR/LF in the content type cannot add part headers. It does not reject invalid values (a type without
`/` ends in a PHP warning and a `TypeError`), so `DocumentUpload` still validates the media type. `"`, CR and LF
are stripped from the file name because `symfony/mime` would percent-encode them. Parts always carry
`Content-Transfer-Encoding: 8bit`.
