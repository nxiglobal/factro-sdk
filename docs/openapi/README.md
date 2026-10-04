# OpenAPI copy

factro publishes its Core API specification through the Swagger UI at
`https://cloud.factro.com/api/core/docs/`. The document is embedded as a JSON literal in
`swagger-ui-init.js` and can be extracted without authentication. `bin/openapi-extract` takes the literal
between `"swaggerDoc":` and `"customOptions":`, so it does not depend on how factro formats the script
(single line until 2026-09, pretty-printed since 2026-10).

There is exactly one local copy, `openapi.json`. It is not tracked in Git because the document belongs
to factro; create it with `make openapi-fetch`.

```bash
make openapi-fetch   # writes openapi.json, prints the diff against the previous copy; fails without touching it if no OpenAPI document is found
make openapi-diff    # compares openapi.json with the live spec without replacing it
```

The diff lists added and removed operations and changed schema properties. Every change to a
resource the SDK covers becomes a PR: resource method, DTO field, fixture, test.
