# Changelog

All notable changes to this project are documented in this file, in
[Keep a Changelog](https://keepachangelog.com/) format.

## [1.0.2] - 2026-07-31

### Fixed

- The recent/featured resource card title rendered multilang markup as
  literal text even with the site's multilang filter enabled. Now uses
  `format_string()` with a system context, matching
  `block_oerexchangeshares`'s already-correct pattern.
- International English spelling ("License" → "Licence") corrected in a
  displayed string.

## [1.0.1] - 2026-07-29

### Changed

- The camp release-publishing workflow now uses the registry's current
  tokenless template (OIDC trusted publishing, camp-tools v0.2.35). The
  previous template pinned camp-tools v0.2.25, whose index-entry schema
  predates the registry's `source-repo-id` field, so publication of v1.0.0
  could not succeed. No change to the plugin itself.

## [1.0.0] - 2026-07-29

First stable release. `$plugin->maturity` is now `MATURITY_STABLE`.

No functional change since 0.1.2 — the whole OER Exchange suite moves to 1.0.0
together, so a site never has a stable plugin depending on an alpha one.

### Fixed

- Backfilled the missing `[0.1.1]` entry below. That release shipped and was
  described in its release notes, but was never recorded here.

## [0.1.2] - 2026-07-27

### Added

- Each listed resource leads with its cover-image thumbnail
  (`local_oerexchange\local\cover_image::listitem()`), with a neutral
  equally sized panel where a resource has no cover so rows stay aligned.

### Changed

- Rows are laid out thumbnail-left, text-right. The thumbnail links to the
  same resource page as the title but is hidden from assistive technology,
  so it widens the click target without announcing the same destination
  twice.
- Thumbnail URLs for the whole block are resolved in one batch query.

## [0.1.1] - 2026-07-27

Backfilled on 2026-07-29 — this release shipped and was described in its
release notes at the time, but the entry never reached this file.

### Fixed

- Corrected the installation floor to Moodle 5.0 (`$plugin->requires`); the
  previous value permitted installs on untested 4.5 sites.
- Summaries stored with pre-encoded entities no longer render double-escaped.
- Resources created in the same second now render in a stable order.

### Changed

- Dropped the unjustified XSS/spam risk bitmask flags from the add-block
  capability, so role-audit screens describe it accurately.

### Added

- Behat coverage of the block on its primary home, the Dashboard.
- A test covering `get_content()`'s HTML escaping of stored resource fields.
- Japanese (ja) language pack.
- GitHub Actions CI workflow (moodle-plugin-ci).

## [0.1.0] - 2026-07-19

### Added

- `block_oerexchangebrowse` Dashboard block: search-shortcut form
  (GET to `local_oerexchange`'s `index.php`, query pre-filled) plus up to
  five recent/featured published catalogue resource cards (title, trimmed
  summary, licence), each linking to the resource's detail page.
- `$plugin->dependencies` on `local_oerexchange` in `version.php` — the
  block cannot be installed without its parent plugin.
- `classes/local/content_builder.php`: the block's data-access layer,
  kept separate from `get_content()` so it is unit-testable.
