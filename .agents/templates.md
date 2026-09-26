# Templates

- `template/discuzx5` overlays `template/default`; missing files use default templates.
- `discuzx5` has no touch tree. Mobile uses the style in `common_setting.styleid2`.
- Verify the resolved template before changing style-specific markup.
- **DiscuzX Module CSS Structure**: `template/default/common/module.css` uses `/** <module_targets> **/` and `/** end **/` comments as section delimiters for module-scoped CSS compilation (`writetocsscache()` & `writetomodulecsscache()` in `source/function/cache/cache_styles.php`). When modifying or adding CSS to `module.css`, always place rules within their corresponding `/** ... **/` ... `/** end **/` block to ensure correct compilation into cached module stylesheets (e.g., `style_{id}_forum_viewthread.css`).
- **How a style's `common/*.css` is assembled** (`source/function/cache/cache_styles.php:150-157`): for each CSS file found in *default's* `common/`, the builder (1) uses the style's own copy if it has one, else falls back to default's, then (2) appends the style's `common/extend_<name>.css` if present.

  ```php
  $cssfile = DISCUZ_TEMPLATE('./'.$data['tpldir'].'/'.$touchdir.'common/'.$entry);
  !tplfile::file_exists($cssfile) && $cssfile = $dir.$entry;   // fall back to default
  $cssdata = tplfile::file_get_contents($cssfile);
  if (tplfile::file_exists($cssfile = DISCUZ_TEMPLATE('./'.$data['tpldir'].'/'.$touchdir.'common/extend_'.$entry))) {
      $cssdata .= tplfile::file_get_contents($cssfile);        // then append extend_
  }
  ```

  Consequences worth remembering:
  - `discuzx5` and `discuz_blog` have **no** `common/module.css` — only `common/extend_module.css`. So **default's `module.css` reaches every style**, and a rule placed there needs no per-style copy.
  - The file list is enumerated from default, so a style can only *add* via `extend_<name>.css`, never introduce a new CSS filename.
  - `extend_` is appended **after** the base, so on equal specificity the `extend_` rule wins. Use it for genuine per-style overrides, not for duplicating something already in default.
  - A rule present in both default's file and a style's `extend_` file is emitted **twice** into the compiled bundle. Harmless but redundant — keep the rule in one place.

  Verified on production by probing `.bdl`, which exists only in `template/default/common/module.css`: 12 occurrences in default, 0 in `discuz_blog/common/extend_module.css`, 12 in the compiled `data/cache/style_4_forum_forumdisplay.css`. The same logic applies to every `common/*.css`, not just `module.css` — e.g. `common.css` and `wysiwyg.css` are also assembled this way.
- **A style's own `common/<name>.css` REPLACES default's file outright - the two are never merged.** The fallback branch in `cache_styles.php:153` only fires when the style has *no* file of that name, so any `common/*.css` a style forks becomes an independent copy that stops receiving default's later additions. `discuzx5` and `discuz_blog` both fork `editor.css`, which means **`template/default/common/editor.css` currently reaches no style at all** - a feature added there is invisible to both. Files a style does *not* fork (`module.css`, `wysiwyg.css`, `common.css`) still come through from default as described above.

  This is **structural, not cache staleness**: `rebuild_styles.php` will not bring the rules back, because the base file being compiled genuinely lacks them. That is the diagnostic tell - if a rebuild does not restore a missing rule, the style is forking that file, and the fix is to copy the rule in.

  Worked example (2026-09-27, `cf7d79c37`): `default/common/editor.css:38-66` gained the MathJax symbol-picker / editor-dialog block. Both style forks predated it, so blog's compiled `style_4_editor.css` had **0 of 15** `.math-*` selectors while default and x5 had 15. Blog was shipping a *working* math button - the shared `static/js/mathjax-full-editor.js`, already loaded at `discuz_blog/common/footer.php:59`, injects `#post_math_button` itself - that opened a completely unstyled dialog. Blog's `editor.css` even carried a partial `#post_math_button .mathfx` icon rule, which is what made it easy to miss. Fixed by copying the 27 rules into `discuz_blog/common/editor.css`; now 15/15 across all three styles.

  **Checklist when adding a feature to any `template/default/common/*.css`:** list which styles fork that file (compare filenames in `template/discuzx5/common` and `template/discuz_blog/common` against `template/default/common`), then copy the rule into each fork. To detect existing drift, compare selector sets between `template/default/common/<name>.css` and the compiled `data/cache/style_{id}_<name>.css` - note the compiled files are minified onto a single line, so extract with `grep -o`, not line-based diffing.

- The same fallback means `wysiwyg.css` (the editor iframe body) exists only in default, so all styles share it and its `{FONTSIZE}` token resolves per style from the database — see `database.md`.

