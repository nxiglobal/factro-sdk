# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

`nxi/factro-sdk`: unofficial PHP 8.4 SDK for the factro Core API, a library built only on Symfony components (`http-client`, `rate-limiter`, `clock`). Public, MIT, SemVer.

## Commands

```bash
make qa              # same as CI: PHPStan (level 9 + strict rules), php-cs-fixer dry-run, rector dry-run, PHPUnit
make fix             # rector, then php-cs-fixer
make test-unit       # Unit suite; make test-integration for the Integration suite
vendor/bin/phpunit --filter TasksTest                       # single test class
vendor/bin/phpunit tests/Unit/Resource/Task/TasksTest.php   # single file
make openapi-fetch   # download the live spec to docs/openapi/openapi.json (untracked) and diff it
make openapi-diff    # diff the local copy against the live spec without replacing it
```

PHPUnit runs with `failOnWarning` and `failOnDeprecation`; `tests/bootstrap.php` sets the default timezone to UTC. PHPStan also analyses `tests/` and `bin/`.

## Architecture

Request flow: `FactroClientFactory::create()` builds a decorator chain around the base `HttpClientInterface`: `PolicyHttpClient` (`RequestPolicy`, e.g. `withoutDeletes()`) -> retry (GET only, `FactroRetryStrategy`) -> `RateLimitHttpClient` (sliding window 480/min per token) -> scoping (base URI, raw token header without "Bearer", timeouts). `Http\Transport` sits on top: JSON decode, error mapping via `ErrorMapper` onto `Nxi\Factro\Exception\*`, one log line per call (never headers or bodies), `getMany()` for concurrent GETs.

`FactroClient` hands out a new stateless resource instance per call (`tasks()`, `projects()`, ...). A new resource class must be wired there.

Resource layout (see `docs/DECISIONS.md` 2026-09-14): `src/Resource/<Name>/` holds the resource class (plural, no suffix: `Tasks`), its enums, `Input/` (request DTOs with direction prefixes `NewX`, `XChanges`) and `Output/` (readonly response DTOs). Method names follow the shared scheme in the README table (`list/get/find/create/update/delete`, `createMany/updateMany`, comments, tags, associations, documents, `accessRights()`). Access rights are one shared class `Resource\AccessRight\AccessRights`; comments and tags stay per resource.

Conventions that span many files:

- Output DTOs hydrate only through `Mapping\Field` (typed accessors, `HydrationException` on mismatch, no normalisation). `AbstractResource::rows()`/`object()` turn decoded bodies into string-keyed rows first.
- Input DTOs build bodies through `toPayload(\DateTimeZone)` and `Mapping\Payload::withoutNulls()`: factro treats PUT as a partial update, so null means "not sent". Clearing a field is not supported.
- Dates: `Time\CalendarDate` for date-only fields (factro mixes `T22:00Z`, `T23:00Z` and `T00:00Z` for the same day; mapped via `FactroOptions::$timezone`, default Europe/Berlin), `Time\FactroDateTime` for timestamps.
- Paths are written with a leading slash (`/tasks/{id}`, also in policy patterns), but `Transport` strips it internally so `base_uri` path segments survive. Test base URIs must end in `/`.
- Enum cases UPPERCASE with spelled-out names (`IN_PROCESS`, `PRIORITY_10`), backing values as the API sends them.
- Delete-type methods return `void`. Package methods take `$projectId` before `$packageId`.
- `Http`, `Mapping` and `Resource\AbstractResource` are `@internal` (outside the BC promise); everything else is public API, so BC breaks need a major version.
- Out of scope on purpose: `GET /tasks` (unpaginated tenant-wide) and the deprecated `/efforts*` routes.

## Tests and fixtures

- Unit tests mirror `src/` under `tests/Unit/`. Resource tests use `tests/Support/MockFactro::client([...])` with routes keyed `"METHOD /path"`; a `MockResponse` can be sent only once, routes hit repeatedly must be closures.
- `fixtures/*.json` are hand-written from the OpenAPI spec and observed API behaviour and ship with the package. Never paste a real API response (tenant names, e-mails, ids). Placeholder conventions are in `fixtures/README.md`.
- `src/Testing/` (`Fixtures`, `Factory\*Factory::make()/dto()/many()`) is public API for consumers, not test-only code.

## Docs and release hygiene

- `docs/DECISIONS.md` holds date-prefixed architecture decisions; `docs/plans/` holds plans. README is user-facing; don't duplicate it.
- User-visible changes get an entry under `[Unreleased]` in `CHANGELOG.md`. `src/Version.php` holds the version used in the User-Agent.
