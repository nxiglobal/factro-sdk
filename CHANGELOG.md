# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- Document uploads build their `multipart/form-data` body with the new dependency `symfony/mime`. `DocumentUpload` rejects a content type that is not a media type (CR/LF used to end up raw in the part headers). `Http\MultipartBody` is gone.
- `FactroOptions` validates on construction (`\InvalidArgumentException` naming the option): `baseUrl` absolute https, timeouts > 0, `maxRetries` and `maxRetryAfterSeconds` >= 0, `userAgent` without CR/LF.
- `RequestPolicy` validates when built: methods are upper-cased (`['get']` used to deny everything), only GET, POST, PUT and DELETE are allowed, an invalid deny pattern throws instead of permitting everything. A pattern failing at match time denies the request.
- Ids in request paths must be UUIDs, otherwise `\InvalidArgumentException` before sending. Dot segments used to bypass deny patterns (`packages()->delete($projectId, '..')`). `webhooks()->payloadsByAction()` rejects an empty action and `.`/`..`.
- The default rate limiter keeps one window per token across all clients of a process, instead of one per `FactroClientFactory::create()` (two clients could send 960 requests/min).
- A retried 429 without `Retry-After` waits for `t` from the `ratelimit` header (capped at `maxRetryAfterSeconds`) instead of backing off exponentially.
- `RateLimitInfo::$limit`, `$windowSeconds` and `$resetSeconds` are `?int` (`null` = not sent, instead of `0`). `RateLimitException::retryAfterSeconds()` is `null` without `Retry-After` and `t`.
- HTTP 403 throws the new `ForbiddenException`; `AuthenticationException` means 401 only. Neither extends the other.
- A success response with invalid JSON or a JSON scalar throws `UnexpectedResponseException` instead of `\JsonException` or `\UnexpectedValueException`.
- Enum properties of output DTOs are nullable with a raw `int|string` sibling `<property>Value` (e.g. `Task::$taskState`/`$taskStateValue`). An unknown value hydrates as `null` instead of failing the whole list; a missing required value or a non-int/string value still throws.
- Hydration: int fields reject floats outside the int range (`1e19`), `FactroDateTime::fromIsoUtc()`/`fromDateAndTime()` reject impossible dates and times (`2026-02-31`, `24:00`), `HydrationException` exposes `actualType` without the value.
- `TaskComment::$text` is `?string`: factro returns `text: null` for some comments (observed 2026-10-12).
- `AccessRights::readRights()`/`writeRights()` return `array<string, list<AccessRightReason>>` (employee id => reasons) following the real response shape, also in `fixtures/access-rights.json`.
- `toPayload()` of all input DTOs (also `WorkRecordLocation`, `SortEntry`) is `@internal`; `isEmpty()` stays public.

### Added

- `clear` on `TaskChanges`, `ProjectChanges`, `PackageChanges`, `ChecklistEntryChanges` and `NewTask`: payload keys sent as explicit `null` to empty a field (e.g. `['colorScheme']`). Each class lists the keys verified to accept `null` in `CLEARABLE`; the constructor throws `\InvalidArgumentException` for any other key (e.g. `officerId`, refused by factro with 403) or a key that is also set.
- Support for Symfony 7.4 LTS (`^7.4 || ^8.0`).
- `FactroClient` and `Transport` hide the token from `print_r()`/`var_dump()` via `__debugInfo()`.
- `FactroClientFactory` with the transport chain: request policy, retry (GET only), rate limit (sliding window 480/min per token), scoping with raw token header and timeouts. `Transport::getMany()` sends GETs concurrently.
- `FactroOptions`, `RequestPolicy`, `RateLimitInfo` and the exception family under `Nxi\Factro\Exception`.
- Coverage of the factro Core API (OpenAPI document of 2026-09-11) except `GET /tasks` and the deprecated `/efforts*` routes, grouped under `Nxi\Factro\Resource`:
  - Tasks: CRUD, `setState()`, batch operations, comments, connections, checklist entries, tags, associations, documents.
  - Projects: CRUD, batch operations, `structure()`, tags, associations, comments, documents.
  - Packages: CRUD, batch operations, moves, associations, `shiftWithSuccessors()`, comments, documents.
  - Users: CRUD, batch operations, employee tags (`tagsForMany()` concurrent), substitutes, absences, `quota()`.
  - Companies and work records: CRUD, batch operations, tags or comments.
  - `Appointments`, `Contacts`, `Teams`, `Documents`, `Comments`, `Notes`, `TodoLists`, `Webhooks`, `CustomViews`, `Templates`.
- Shared `AccessRights` through `tasks()`, `packages()`, `projects()` and `templates()`.
- `ColorScheme` with the eight colours of the factro UI; `colorScheme` stays a string in DTOs because the API stores any value.
- `FactroDateTime` and `CalendarDate` for the three date storage variants of factro.
- Testing support: fixtures under `fixtures/` with `Testing\Fixtures`, factories under `Testing\Factory`.
