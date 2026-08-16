# php-wasm PHP syntax lint harness

Catches `ParseError`s in every tracked `*.php` file without needing a PHP
binary on the machine. A sandboxed [php-wasm](https://github.com/WordPress/wordpress-playground)
runtime reads each file and runs it through:

```php
token_get_all($source, TOKEN_PARSE);
```

`TOKEN_PARSE` makes the tokenizer run the **full compiler grammar** rather than
the plain lexer, so structurally broken code (an unbalanced brace, a malformed
function signature) throws `ParseError` instead of quietly producing a token
stream. Nothing in the linted code is ever executed — the harness only asks the
parser whether the source is well-formed, which makes it safe to point at
Discuz files that would otherwise bootstrap the whole application.

## Setup

```sh
cd .agents/tools/php-lint
npm install
```

`node_modules/` is gitignored; only `package.json`, `package-lock.json` and the
two harness files are tracked.

## Usage

```sh
# Lint every tracked *.php file (about one second for the whole repo)
node .agents/tools/php-lint/lint.mjs

# Only files changed against the base branch (plus working-tree/untracked edits)
node .agents/tools/php-lint/lint.mjs --changed

# Lint explicit paths
node .agents/tools/php-lint/lint.mjs source/app/forum/child/misc/postreview.php

# Prove the harness still detects broken files
node .agents/tools/php-lint/lint.mjs --self-test
```

Exit codes: `0` clean, `1` parse errors found, `2` harness failure.

### Options

| Option | Description |
| --- | --- |
| `--php=8.2` | PHP version for the sandbox. Default `8.2`, matching the Playwright workflow. |
| `--base=<ref>` | Base ref for `--changed`. Defaults to `origin/master`, then `master`, then `HEAD~1`. |
| `--short-open-tag` | Lint with `short_open_tag=On`. Off by default, matching production PHP. |
| `--json` | Machine-readable output (used for tooling, includes skipped files and timing). |
| `--quiet` | Only print failures and the summary line. |

## Notes

- The bundled php-wasm `php.ini` enables `short_open_tag`; the harness forces it
  **off** so the lint matches how the code is actually parsed in production.
- `template/**/diyxml/**` is skipped: those `.php` files are DIY page exports
  that begin with a `<?PHP exit('Access Denied');?>` guard and then contain raw
  XML, so the `<?xml` prologue is not meant to parse as PHP.
- `vendor/` and `data/` are skipped — the former is Composer-managed, the latter
  is runtime cache.
## CI

A ready-to-use workflow lives next to the harness as
[`php-lint.workflow.yml`](php-lint.workflow.yml). It self-tests the harness
before the full lint, so a silently broken linter cannot report a false pass.
Enable it by copying it into place and pushing from an account (not an app
token) that is allowed to change workflows:

```sh
cp .agents/tools/php-lint/php-lint.workflow.yml .github/workflows/php-lint.yml
```
