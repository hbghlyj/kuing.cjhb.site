# Development

## Generated caches

The web-process user must own the generated runtime trees in development clones:

- `data/cache`
- `data/template`
- `data/sysdata`

Incorrect ownership can prevent template or style cache regeneration even when directory permissions are otherwise permissive.

## Languages

Server and JavaScript language files are sourced exclusively from `source/i18n/{SC_UTF8,TC_UTF8,EN_UTF8}`. Do not add keys to the removed legacy language tree.

After changing language files, rebuild the relevant language cache and clear the compiled template cache when the changed key is rendered by a template.

## Templates and styles

`template/discuzx5` is an overlay on `template/default`; it does not implement every desktop template. Verify the resolved template before assuming a page is X5-specific.

Mobile templates are different: `discuzx5` has no touch template tree. Mobile rendering uses `common_setting.styleid2`, which should remain the default style unless touch templates are supplied for another style.

## HTTPS test environment

Use HTTPS for browser testing. Plain HTTP can omit browser fetch metadata and trigger the bot classifier, causing behavior that does not reproduce on the production HTTPS origin.
