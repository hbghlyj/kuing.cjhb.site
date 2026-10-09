# MitFrame Port Checkpoint

Fetched `origin/MitFrame` through `d372a5fc` from
https://gitee.com/Discuz/DiscuzX.git on 2026-10-08.
Upstream release at this checkpoint: `20261001`; fork release remains `20260910`.
The previous note incorrectly called upstream `20260910`: `1db0682f` already
declared `20261001`. Release metadata does not imply unported upstream features.

## Latest Batch: 1db0682f..d372a5fc

- `23fe6b73`: moderation charts show the 15 largest positive operation counts,
  with the remainder in a localized Other bucket. Use a shared PHP aggregation
  helper instead of upstream array_combine/array_replace chains, retaining
  unknown action labels and preventing label/value length mismatches. Preserve
  JSON_HEX flags and forum-stat-charts.js cache busting. Port adaptive height,
  empty chart fallback, staff-label truncation and barCategoryGap support.
- `66f5652c`: collect JSON editor attachments by files/file payload shape rather
  than the images block type, retaining the fork's integer IDs and missing-ID checks.
- `ccc40fb1`: already satisfied; import_styles no longer inserts assert/perm
  into navigation records. No changes needed to the fork's custom installer.
- `d372a5fc`: rename admin/rootcolor.htm to admin/rootcolor.php.
- `8f1808f0`: merge commit; no separate functional port.

Validation: attachment extraction in all three post paths; positive/empty/unknown
moderation actions, top-15 ordering and remainder sums; actual EN/SC/TC template
compilation; Chrome chart/gauge/empty-state and custom bar spacing checks.
Existing unrelated IP-notice and language edits remain untouched.
Next comparison starts after `d372a5fc`. Rebuild styles/scripts and clear compiled
templates when deploying this batch.

## Previous Batch

Previous upstream baseline: `14007e09` (release `20260910`).

All commits through `1db0682f` were assessed and applicable changes ported manually.

- `2c099c99`: arrow asset fix already implemented in this fork.
- `98701625`: not applicable; this fork uses Git-based file checking.
- `922b7a32`: navigation overflow interaction ported manually, retaining
  fork navigation styles and adding edge-timer termination and Ctrl-wheel protection.
- `704be17e`: popup styles ported, retaining the fork's width and wrapping
  behavior. Old visited-forum markup is absent; do not restore its retired
  menu rules or fixed widths.
- `9b7e800e`, `46e8fd32`, `0c354e12`: quick-navigation visual changes ported.
- `be96e41a`, `af73a3b0`, `05ccfcce`, `1db0682f`: statistics redesign and palette
  changes ported. Retain the fork's JSON chart API, native date inputs,
  timestamps, UID and last-post member columns. Translate new labels in
  EN/SC/TC. Guard and dispose the ECharts probe; check actual series support
  before using gauge, with a pie fallback for the bundled trimmed build.
  Keep both export closing conditionals: they close nested blocks, contrary
  to the earlier preliminary assessment. Do not restore `calendar.js` or
  introduce new `dgmdate()` consumers.

Validation: local Chrome JSON charts, gauge fallback, empty-response refresh;
actual template compilation in EN/SC/TC; CSV rows and operator-ID preservation.
New checks are included in the Playwright workflow. Full site CI and live
visual verification have not yet run for this batch.

The next batch above continues from `1db0682f`.
