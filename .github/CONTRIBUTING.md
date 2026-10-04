# Contributing

Bug reports, fixes and improvements are welcome. For new resources and changes that break backwards compatibility, open an issue first so the approach can be discussed before you write code.

## Setup

PHP 8.4 and Composer are required.

```bash
make install        # composer install
make qa             # PHPStan, PHP-CS-Fixer (dry-run), Rector (dry-run), PHPUnit; same as CI
make test           # PHPUnit only
make test-unit      # Unit suite only
make fix            # apply Rector and PHP-CS-Fixer
make openapi-fetch  # download the current Core API spec to docs/openapi/openapi.json, show changes
make openapi-diff   # compare the local copy with the live spec without replacing it
make help           # all targets
```

The targets wrap the Composer scripts (`composer qa`, `composer test`, ...), which work on their own as well.

## Code style

PHP-CS-Fixer, PHPStan and Rector enforce the code style; their configuration is part of the repository. `make fix` applies Rector and PHP-CS-Fixer, `make qa` checks everything.

Architecture decisions are recorded in `docs/DECISIONS.md`.

## Pull requests

Pull requests target `develop`; `main` only receives releases and hotfixes.

- `make qa` passes.
- User-visible changes get an entry under `[Unreleased]` in `CHANGELOG.md`.
- New resources and BC breaks are discussed in an issue first; link it in the pull request.

## Fixtures

The files in `fixtures/` are hand-written, see `fixtures/README.md`. Never paste a real API response: it contains names, e-mail addresses and ids of a real factro tenant. When the API behaves differently from a fixture, adapt or add a file with placeholder values and describe the observed behaviour in the pull request.

## License

By contributing, you agree that your contributions are licensed under the MIT license of this project.
