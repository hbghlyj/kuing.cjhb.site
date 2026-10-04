# MitFrame Port Checkpoint

Fetched `origin/MitFrame` through `1db0682f` from
https://gitee.com/Discuz/DiscuzX.git on 2026-10-03.
Previous upstream baseline: `14007e09` (release `20260910`).

This is a partial port, not a new complete upstream baseline.

- `2c099c99`: arrow asset fix already implemented in this fork.
- `98701625`: not applicable; this fork uses Git-based file checking.
- `922b7a32`: navigation overflow interaction ported manually, retaining
  fork navigation styles and adding edge-timer termination and Ctrl-wheel protection.
- `704be17e`: old visited-forum table markup absent in this fork; remaining
  popup visual changes not ported.
- `9b7e800e`, `46e8fd32`, `0c354e12`: quick-navigation visual changes not ported.
- `be96e41a`, `af73a3b0`, `05ccfcce`, `1db0682f`: statistics redesign and palette
  changes pending manual integration. Preserve native date inputs, localized
  timestamps and translations. Fix the unmatched closing conditional in
  `stat_misc_export.php` and guard the missing ECharts probe instance before
  disposing it. Do not restore removed `calendar.js` or `dgmdate()` consumers.

Continue from the pending commits above, not from `1db0682f` as a completed port.
