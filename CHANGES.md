# Release notes — 1.0.2

Resource titles in this block's recent/featured resource cards previously
rendered any multilang markup as literal text, even with the site's multilang
filter enabled — a live, user-reported bug on the Exchange. The card title now
renders through the site's filters (matching `block_oerexchangeshares`'s
already-correct pattern), so a bilingual resource shows in whichever language
the viewer has selected. The licence label and summary teaser are unchanged.

Also: a displayed string ("License" → "Licence") now uses International
English spelling, matching Moodle core's own convention for user-facing
prose. No string keys or Japanese strings changed.

No database or capability changes. No action is required after upgrading.
