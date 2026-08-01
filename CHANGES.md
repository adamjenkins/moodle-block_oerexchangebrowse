# Release notes — 1.0.3

The card *summary* teaser was shortened to plain text before any text filter
ran, so a bilingual summary written with the multilang filter came out with
both languages run together — "Overview概要" — rather than collapsing to the
language the reader is using. No other filter (auto-linking, MathJax) ever saw
the text either.

The summary is now filtered first and flattened afterwards, matching the order
the Exchange's own catalogue cards use. Card *titles* were already correct as
of 1.0.2; this completes the same fix for the text underneath them.

No database changes; no action required after upgrading beyond the usual
`admin/cli/upgrade.php`.
