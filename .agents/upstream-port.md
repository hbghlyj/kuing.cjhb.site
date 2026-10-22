# MitFrame Port Checkpoint

Fetched `origin/MitFrame` in the sibling `../mitframe` checkout through
`3db25375efc48a05c7d3594f51ff7225c38be8c2` from
https://gitee.com/Discuz/DiscuzX.git on 2026-10-22.
Upstream release at this checkpoint: `20261010`; fork release now `20261010`
(per user direction, the release bump is ported rather than kept at `20260910`).
The previous note incorrectly called upstream `20260910`: `1db0682f` already
declared `20261001`. Release metadata does not imply unported upstream features.

## Latest Batch: 172c8c874..3db25375

- `01ff4317`: table-tool `sanitize` permits `content.br`; already ported in
  fork commit `20bdf5eb5`, no duplicate patch required.
- `a494eaeb`: registration checks active and archived usernames after the
  upstream UCenter check. The fork already calls `native_user_checkname()`,
  whose member UID lookups include archived members (archive lookup enabled),
  login names and username history. Keep the existing native implementation
  rather than add redundant full-row queries or restore UCenter activation.
- `3db25375`: merge of the two changes above; no extra functional change.

Validation: execute the real native username validator and member table lookup
methods against fixture storage for active, archived, login and historical
identities, plus an available name. Release remains `20261010`.
Next comparison starts after `3db25375`.

## Previous Batch: 6f7c68893..172c8c874

- `172c8c874`: adds `template/default/touch/home/misc_ajax.php` (byte-identical
  to upstream's desktop copy) so mobile `home.php?mod=misc&ac=ajax` stops
  failing with `mobile_template_no_found`. Ported as `touch/home/misc_ajax.htm`
  using the fork's desktop `home/misc_ajax.htm` content (fork keeps `.htm`
  templates with HTML5 void syntax and `cp0` class divergences). Resolution
  trace: `.htm` resolves for TPLDIR=default via the else branch; the `:829`
  `.php`-only fallback only matters when TPLDIR is not default, but mobile
  forces `setting styleid2 = 1` (default style), so `.htm` covers all mobile
  traffic. Live verified: compiled `EN_1_1_touch_home_misc_ajax_EN`, no
  `mobile_template_no_found`, style-1 mobile page renders.
Next comparison starts after `172c8c874`.

## Previous Batch: d372a5fc..6f7c68893

- `882cc712b`: `DISCUZ_RELEASE` bump 20261001 to 20261010; ported per user
  direction (fork previously kept its own release string at 20260910).
- `6f7c68893`: `cplang()` now loads MitFrame app admincp language from
  `i18n/{$_G['i18n']|currentlang()}/lang_admincp.php` instead of
  `language/lang_admincp.php`. Ported verbatim; matches the fork's existing
  app-language loading pattern in `function_core.php`. Currently a no-op in
  both trees: neither the fork nor upstream ships per-app admincp language
  files, and no caller passes the fourth `cplang()` argument.

Validation: `php -l` on `function_admincp.php`; the new branch cannot load
anything in either tree until per-app language files exist. PHP-only change:
server pull, no style or template rebuild required.
Next comparison starts after `6f7c68893`.

## Previous Batch

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

## Older Batch

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
