# Changelog

All notable changes to this project are documented in this file, in
[Keep a Changelog](https://keepachangelog.com/) format.

## [0.1.0] - 2026-07-19

### Added

- `block_oerexchangebrowse` Dashboard block: search-shortcut form
  (GET to `local_oerexchange`'s `index.php`, query pre-filled) plus up to
  five recent/featured published catalogue resource cards (title, trimmed
  summary, license), each linking to the resource's detail page.
- `$plugin->dependencies` on `local_oerexchange` in `version.php` — the
  block cannot be installed without its parent plugin.
- `classes/local/content_builder.php`: the block's data-access layer,
  kept separate from `get_content()` so it is unit-testable.
