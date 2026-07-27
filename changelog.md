# Changelog

All notable changes to this project are documented in this file, in
[Keep a Changelog](https://keepachangelog.com/) format.

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
