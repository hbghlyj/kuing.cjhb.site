# MitFrame Port Checkpoint

## 20261127 selective port

- Compared MitFrame `c6e7e124..ee74fc3e`; next comparison starts after
  `ee74fc3e`.
- `6ad7d698`: removed the deprecated Block XML item from AdminCP's portal menu.
  Kept its action implementation and language strings, matching upstream's
  one-line scope.
- `ee74fc3e`: made the avatar endpoint's default size explicit before
  validation. The fork already guarded the query parameter inline; this uses
  the same validated `big`/`middle`/`small` behavior with a local default.

Validation: PHP lint for the modified AdminCP menu and avatar endpoint; diff
check passed.

## 20261120 selective port

- Compared MitFrame `badcea04..c6e7e124` and updated the release marker to
  `20261120`. Next comparison starts after `c6e7e124`.
- `6378bb9d`: the fork already checks the configured username censor pattern.
- `c6e7e124`: the duplicated X5 `tmp132` language entries do not exist
  in the fork, so there is nothing to copy.
- Adapted request and authorization checks for ads, moderation, comments,
  albums and portal blocks; added expiry to login handoff tokens. Ported
  targeted attachment, search, announcement, REST and table-layer fixes.
- Retained fork-specific SVG handling, remote image download, native accounts,
  and row-only comment removal. Changes not in the selective diff were not
  imported wholesale.

## JSON Editor: 9bf00903 and badcea04

- Integrated and reviewed Claude Code's selective JSON editor patch. Context-aware
  token expansion filters rich text, attributes, URLs, dynamic tags and code
  literals without re-running template directives over submitted data. Review
  fixed a computed-URL path that bypassed the scheme allow-list.
- Updated Editor.js to 2.31.7 and the upstream undo bundle; Chrome verifies
  actual undo/redo and load-only RAW behavior. Kept saved RAW source escaped and
  editable instead of upstream's file deletion; refresh its stored configuration
  to 1.0.4 (toolbox:false). No content deletion or schema migration.
- Preserved fork attachment checks, HTML5 markup, diagram source and localized
  output. Omitted payTune/payblock (upstream depends on a missing DB method),
  unrelated code-block sizing, image-manager changes and active image cropper.
- PHP sanitizer regressions cover built-in blocks, hostile contexts, full
  discuzcode rendering, code literals, RAW fallback and bundle hashes; fixed
  CRLF-sensitive test assertions. PHP lint and actual Chrome bundle tests pass.

## 20261110 security batch (reviewed through badcea04)

This is a partial, selective port, not a complete checkpoint advance.

- Ported independent 9bf00903/fb47a8e5 request-token checks and matching
  continuation links, admin entry guards, escaping and SQL-value quoting,
  plugin script realpath confinement, read-only ad API lookups, atomic invite
  consumption and registration locking, portal content access checks, upload
  target sanitization and local image path guards, and REST redirect auth.
- Kept the fork's safe REST redirect allowlist, native user implementation,
  PM recipient-membership check, atomic process lock, locale/time output,
  tag management, SVG sanitizer and custom editor behaviour.
- Ported usergroup layout/label corrections using the fork's actual column
  counts; added EN/SC/TC usergroups_systemtype labels. Release is 20261110.
- Platform-cache fixes e0d2b366/ca2c748e were already selectively ported.
- Ported cfde8b8c payment callback logs/reconciliation and 201c2d5d callback
  hardening, including EN/SC/TC keys and polling-channel template dependencies.
  Active order lookups also enforce the recorded channel. New callback log
  dates use explicit UTC rather than reintroducing display-time dgmdate calls.
  Gateway XML parsing rejects DTDs, disables network access, and restores local
  error handling without the deprecated global entity-loader switch.
- Ported remote-cron bootstrap redesign: check configuration before startup,
  initialize the application once, retain init flags, bypass visitor defenses
  for callbacks/CLI, and do not run cron from payment callbacks.
- Ported 7058aaa3 plugin-magic image paths with identifier validation and no
  broken image markup when the icon is absent.
- Comment replies (677b40b8) were adapted and deployed separately as 367a6e3fb:
  transient reply_comment_id and a localized content prefix, no new schema or
  rpid overload. Existing self-delete, MathJax and Pusher behavior is retained.
- Pending: account-settings layout; JSON Editor server sanitizer/rewrite and
  compiled JS updates (Claude Code Opus 5.5 xhigh session 62e9318c completed;
  patch integrated locally and reviewed, not included in this deployed batch);
  image upload signature heuristics; image-cropper retirement.
  badcea04 Raw-block retirement is being assessed with the JSON editor changes
  to avoid losing existing stored content. Merge 5f461791 has no separate port.
- Do not apply imgcropper deletion as an unused-file cleanup: this fork still
  has two portalcp_block_itemfields.htm controls calling openCropper(), which
  builds misc.php?mod=imgcropper URLs. Retain route/assets/translations until
  that live consumer is migrated or explicitly retired.
- Newly added source JSON checks sanitize contentType/contentEditor and reject
  invalid content; the larger JSON editor rewrite remains separate.

Validation: PHP lint across modified source; system-error, upstream-port,
plugin-upgrade-notice, comment-reply, plugin-path/ad/invite and payment security
regression scripts. Payment checks cover channel mismatch, missing signing
keys, valid/malformed/DTD XML, safe logs, cron lifecycle and EN/SC/TC keys.
Full browser CI has not been run for this batch.

## Selective port: e0d2b366 (2026-11-10)

- Ported database-backed AdminCP platform cache rebuilding, damaged-cache
  recovery, platform list improvements and long-name dropdown display.
- Added EN/SC/TC labels and preserved unrelated persisted admin cache fields
  even when the caller only loaded a partial platform cache.
- This is not a full upstream checkpoint advance; continue reviewing changes
  after 10fcc8bc, accounting for Gitee's rewritten MitFrame history.

## 20261101 Batch: 3db25375..10fcc8bc

Upstream comparison through 10fcc8bcee828a3ee7b1712867929601205788cd.
Fork release metadata updated to 20261101; this is a selective port.

- Ported article cover input validation, signed click hashes, matching password
  recovery errors, SWF allowlist removal, attachment preview ownership,
  REST variable fixes, and admin delete confirmation tokens.
- Ported search username escaping and integer special-type filters. Existing
  fork search extensions remain; other upstream search changes need review.
- Fixed both username censor setting references, including the guard upstream
  missed. Enabled outbound socket safety in defaults, not live configuration.
- Already covered: safe JSON for remote image replies, class restrictions in
  unserialization, and no backup-delete link in the fork's export handler.
- Deferred: payment reconciliation and bootstrap changes, JSON-editor append
  handling, myrepeats changes, and cloud plugin menu policy changes. Keep the
  fork's add-on upgrade notice policy and review these separately.

Use 10fcc8bc for the next upstream comparison; track deferred work above.

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
