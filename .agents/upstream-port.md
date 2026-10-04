# MitFrame Port Checkpoint

Fetched `origin/MitFrame` through `1db0682f` from
https://gitee.com/Discuz/DiscuzX.git on 2026-10-03.
Previous upstream baseline: `14007e09` (release `20260910`).

All commits through this checkpoint have now been assessed and applicable
changes ported manually. Upstream release remains `20260910`.

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

Next upstream comparison starts after `1db0682f` once this batch is committed.
