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
- The same fallback means `wysiwyg.css` (the editor iframe body) exists only in default, so all styles share it and its `{FONTSIZE}` token resolves per style from the database — see `database.md`.

