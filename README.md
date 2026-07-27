# block_oerexchangebrowse

A Moodle Dashboard block for the **OER Exchange** platform: a compact search
box plus a handful of recent published resources from the catalogue,
linking out to the full browse/search page for anything beyond a quick
glance.

Requires [`local_oerexchange`](https://github.com/adamjenkins/moodle-local_oerexchange),
the catalogue and API plugin for the OER Exchange, to be installed on the
same site. Installing this block without it will fail — Moodle enforces the
dependency declared in `version.php`.

## What it shows

- A search box that submits straight to `local_oerexchange`'s catalogue
  page (`index.php`) with the query pre-filled — the block itself does not
  duplicate any search logic.
- Up to five of the most recently shared, published resources: title
  (linked to the resource's detail page), a short trimmed summary, and the
  license.
- A "View full catalogue" link to the full browse/search page.

## Installation

```bash
git clone https://github.com/adamjenkins/moodle-block_oerexchangebrowse.git blocks/oerexchangebrowse
php admin/cli/upgrade.php
```

Then add "OER Exchange: browse" to a Dashboard from the block drawer.

## Requirements

- Moodle 5.0–5.2 (`$plugin->supported`).
- `local_oerexchange` installed on the same site.
- PHP as required by the target Moodle version.

## License

GPL-3.0-or-later, see [LICENSE](LICENSE).
