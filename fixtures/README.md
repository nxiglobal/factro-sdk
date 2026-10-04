# Fixtures

factro Core API responses, one JSON file per response, pretty-printed. They back the unit tests
of the DTOs and Api classes and are shipped with the SDK so that consumers can feed them into
`MockHttpClient` through `Nxi\Factro\Testing\Fixtures::path()` and `::json()`.

All files are hand-written from the OpenAPI spec and observed API behaviour (mixed date variants,
missing address keys, JavaScript timestamps). They contain no real API responses. When the API
behaves differently, adapt or add a file by hand and describe the observed behaviour in the pull
request; never paste a real response.

## Placeholders

| Key | Value |
|:--|:--|
| `id`, `*Id`, `mandantId` | random UUIDs, the same UUID wherever the same object is referenced |
| `emailAddress` | `user<n>@example.invalid` |
| `firstName`, `lastName` | `FirstName<n>`, `LastName<n>` |
| `street`, `city`, `zipCode`, `phone`, `website`, `customerId` | `null` when present |
| `name`, `shortName` | `Company <n>` (companies) or `Tag <n>` (employee tags) |
| `title` | `Title <n>` |
| `description`, `text`, `internalBookingDetails`, `externalBookingDetails`, `createdInContextOfReferenceTitle` | `Text <n>` or `null` |
| `customFields` | neutral keys (`subproject_number`), values `"x"` |

## Files

| File | Endpoint | Content |
|:--|:--|:--|
| `projects.json` | `GET /projects` | 3 projects, the test project first |
| `project.json` | `GET /projects/{id}` | |
| `project-structure.json` | `GET /projects/{id}/structure` | trimmed to 2 packages with at most 3 tasks each |
| `packages.json` | `GET /projects/{id}/packages` | 5 packages, one root package (`parentPackageId` null) |
| `package.json` | `GET /packages/{id}` | |
| `tasks-by-project.json` | `GET /tasks/by-project/{id}` | 5 tasks: `T22:00Z`, `T23:00Z`, `T00:00Z`, no dates, milestone |
| `task.json` | `GET /tasks/{id}` | |
| `task-comments.json` | `GET /tasks/{id}/comments` | 2 comments, one with `subComments` |
| `task-connections.json` | `GET /tasks/{id}/task_connections` | empty list (live finding) |
| `task-connections-synthetic.json` | `GET /tasks/{id}/task_connections` | one predecessor connection |
| `users.json` | `GET /users` | 5 users: one per `securityGroup`, one inactive |
| `user.json` | `GET /users/{id}` | a non-guest |
| `user-tags.json` | `GET /users/{id}/tags` | |
| `employee-tags.json` | `GET /users/tags` | first 5 |
| `companies.json` | `GET /companies` | 3 companies, two without address keys |
| `company.json` | `GET /companies/{id}` | |
| `work-records-by-project.json` | `GET /work-records/by-project/{id}` | 3 records, one with `utcOffset`, one with `createdInContextOf*` |
| `work-record.json` | `GET /work-records/{id}` | |
| `errors/401.txt` | any, invalid token | `Invalid CoreApiAccessToken provided!` |
| `errors/404-task.txt` | `GET /tasks/{id}` | `Task with id "..." not found` |
| `errors/404-path.html` | unknown path | Express error page |
| `errors/403-task-closed.json` | write on a closed task | `{"message":"Task is closed"}` |
| `errors/429.txt` | any, rate limit | `Rate limit exceeded` |
| `access-rights.json` | `GET .../read_rights` | employee id => reasons |
| `employee-access-right.json`, `team-access-right.json` | `PUT .../read_rights`, `PUT .../team_read_rights` | |
| `documents.json`, `document.json`, `data-quota.json` | `GET /documents`, `GET /documents/{id}`, `GET /documents/quota` | |
| `checklist.json`, `task-tags.json` | `GET /tasks/{id}/checklist`, `GET /tasks/tags` | |
| `project-comments.json`, `project-tags.json` | `GET /projects/{id}/comments`, `GET /projects/tags` | |
| `package-comments.json` | `GET /projects/{id}/packages/{pid}/comments` | |
| `absences.json`, `absence.json`, `user-quota.json` | `GET /users/absences`, `PUT /users/absences/{id}`, `GET /users/quota` | |
| `company-tags.json`, `contacts.json`, `contact.json` | `GET /companies/tags`, `GET /contacts`, `GET /contacts/{id}` | |
| `work-record-comments.json`, `comment.json`, `note-comments.json` | `GET /work-records/{id}/comments`, `GET /comments/{id}`, `GET /note/{id}/comments` | |
| `appointments.json`, `appointment.json` | `GET /appointments`, `GET /appointments/{id}` | |
| `teams.json`, `team.json`, `team-members.json` | `GET /teams`, `GET /teams/{id}`, `GET /teams/{id}/members` | |
| `todo-lists.json`, `webhook-payloads.json` | `GET /todo-list`, `GET /webhook/payload/{startDate}` | |
| `custom-views.json`, `custom-view.json` | `GET /custom-views/templates`, `GET /custom-views/{id}` | |
