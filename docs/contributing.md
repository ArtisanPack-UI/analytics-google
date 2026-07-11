---
title: Contributing
---

# Contributing

The authoritative contribution guide lives at `CONTRIBUTING.md` in the package root. This page summarizes what a code contributor to `artisanpack-ui/analytics-google` specifically needs to know.

## Development setup

Fork and clone the package repository, then pull it into an ArtisanPack UI dev app via a Composer path repository:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../artisanpack-ui-analytics-google",
            "options": { "symlink": true }
        }
    ],
    "require": {
        "artisanpack-ui/analytics-google": "@dev"
    }
}
```

The `artisanpack-ui-dev` app is already wired this way — symlinks live under `packages/analytics-google/`.

Install package dependencies:

```bash
cd packages/analytics-google
composer install
```

## Running tests

```bash
composer test          # runs Pest
```

Filter to a single file or test:

```bash
./vendor/bin/pest tests/Feature/Reporting/Ga4DataClientTest.php
./vendor/bin/pest --filter="runs a report"
```

The test suite uses Orchestra Testbench with an in-memory SQLite database. See [[Testing]] for reusable patterns.

## Code style

```bash
composer lint          # php-cs-fixer --dry-run + phpcs
composer fix           # php-cs-fixer fix
composer cs            # phpcs only
composer cs:fix        # phpcbf (auto-fix what phpcs can)
```

The package follows the ArtisanPack UI code-style conventions — WordPress-style spacing, Yoda conditionals, real tabs. See the `.php-cs-fixer.dist.php` and `phpcs.xml` files in the package root.

## Documentation

The docs under `/docs` follow the same conventions as the sibling packages:

- Every markdown file starts with YAML front matter containing at least `title`.
- Every subdirectory has a sibling `.md` file with the same name serving as its index page.
- Cross-links use GitLab wiki style: `[[Page Name]]` or `[[Slug Path|Label]]`.
- Real content only — no placeholder text.

## Package layout to know about

- `src/Reporting/` — the Data API surface (`Ga4DataClient`, DTOs, fetchers).
- `src/Tracking/Gtag.php` — the client-side snippet renderer.
- `src/Livewire/` — the two Livewire reporting components.
- `src/CmsFramework/` — AdminWidget wrappers for the CMS framework.
- `src/Http/Controllers/` — the two reporting HTTP endpoints.
- `src/Providers/` — `Ga4Provider` and the analytics-parent adapter.
- `src/Support/` — `BaseInstalled` (autoload check) and `GoogleConnectionResolver` (connection lookup).
- `src/Exceptions/` — `BaseNotInstalledException` and `ReportingException`.
- `resources/js/` — React, Vue, and shared TypeScript sources.
- `resources/views/livewire/` — the two Livewire component Blade views.

## Related

- [[Testing]] — patterns for testing the package and code that uses it.
- [[API Reference]] — the public surface at a glance.
