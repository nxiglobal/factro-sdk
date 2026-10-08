# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- `TaskChanges` and `PackageChanges` take `clear`, a list of payload keys sent as explicit `null` to empty the field in factro (e.g. `['colorScheme']`). An unknown key or a key that is also set throws `\InvalidArgumentException`.

- `AccessRights::readRights()` and `writeRights()` return `array<string, list<AccessRightReason>>` (employee id => reasons) instead of string lists. `AccessRightReason` carries `reason` as a plain string plus `projectId`, `packageId`, `teamId` and `taskId` as factro sends them in `IAccessRightReason`. `fixtures/access-rights.json` follows the real response shape.

### Added

- `FactroClientFactory` builds the transport chain: request policy, retry (GET only), rate limit (sliding window 480/min per token), scoping with raw token header and timeouts. `Transport::getMany()` sends GETs concurrently.
- `FactroOptions`, `RequestPolicy`, `RateLimitInfo` and the exception family under `Nxi\Factro\Exception`.
- Coverage of the factro Core API (OpenAPI document of 2026-09-11) except `GET /tasks` and the deprecated `/efforts*` routes, grouped by resource under `Nxi\Factro\Resource`:
  - Tasks: CRUD, `setState()`, batch `createMany()`/`updateMany()`, comments, connections, checklist entries, task tags, project/package/company/contact associations, documents.
  - Projects: CRUD, batch operations, `structure()` (`ProjectStructureNode`), project tags, company/contact associations, comments, documents.
  - Packages: CRUD, batch operations, moves between projects and packages, company/contact associations, `shiftWithSuccessors()`, comments, documents.
  - Users: CRUD, batch operations, employee tags (`tagsForMany()` concurrent, 404 as empty list), substitutes, absences, `quota()`.
  - Companies: CRUD, batch operations, company tags.
  - Work records: CRUD, batch operations, comments.
  - `Appointments`, `Contacts`, `Teams`, `Documents` (with `quota()`), `Comments`, `Notes`, `TodoLists`, `Webhooks`, `CustomViews`, `Templates`.
- Shared `AccessRights` (employee and team read/write rights) reachable through `tasks()`, `packages()`, `projects()` and `templates()`.
- Document uploads as `multipart/form-data` (`DocumentUpload`) without a `symfony/mime` dependency.
- `Nxi\Factro\Resource\ColorScheme`: the eight colours the factro UI offers for `colorScheme`. Input and output DTOs keep `colorScheme` a string because the API stores any value.
- Time helpers `FactroDateTime` and `CalendarDate` for the three date storage variants of factro.
- Testing support: hand-written response fixtures under `fixtures/` with `Nxi\Factro\Testing\Fixtures`, and factories under `Nxi\Factro\Testing\Factory`.
