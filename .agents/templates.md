# Templates

- `template/discuzx5` overlays `template/default`; missing files use default templates.
- `discuzx5` has no touch tree. Mobile uses the style in `common_setting.styleid2`.
- Verify the resolved template before changing style-specific markup.
- **DiscuzX Module CSS Structure**: `template/default/common/module.css` uses `/** <module_targets> **/` and `/** end **/` comments as section delimiters for module-scoped CSS compilation (`writetocsscache()` & `writetomodulecsscache()` in `source/function/cache/cache_styles.php`). When modifying or adding CSS to `module.css`, always place rules within their corresponding `/** ... **/` ... `/** end **/` block to ensure correct compilation into cached module stylesheets (e.g., `style_{id}_forum_viewthread.css`).

