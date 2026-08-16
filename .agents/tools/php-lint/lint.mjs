#!/usr/bin/env node
/**
 * php-wasm lint harness.
 *
 * Runs every PHP file in the repository through
 *
 *     token_get_all($source, TOKEN_PARSE)
 *
 * inside a sandboxed php-wasm runtime, so `ParseError`s are caught without
 * needing a PHP binary on the machine (or in CI) and without executing a
 * single line of the linted code.
 *
 * Usage:
 *   node .agents/tools/php-lint/lint.mjs                # lint every tracked *.php
 *   node .agents/tools/php-lint/lint.mjs --changed      # only files changed vs. the base ref
 *   node .agents/tools/php-lint/lint.mjs source/app/... # lint explicit paths
 *   node .agents/tools/php-lint/lint.mjs --self-test    # prove the harness detects a bad file
 *
 * Options:
 *   --php=8.2            PHP version for the sandbox (default 8.2, matching CI).
 *   --base=<ref>         Base ref for --changed (default origin/master, then master, then HEAD~1).
 *   --short-open-tag     Lint with short_open_tag=On (default Off, matching production).
 *   --json               Emit machine-readable JSON instead of a human report.
 *   --quiet              Only print failures and the summary line.
 */

import { execFileSync } from 'node:child_process';
import { existsSync, mkdtempSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

import { createNodeFsMountHandler, loadNodeRuntime } from '@php-wasm/node';
import { PHP } from '@php-wasm/universal';

const HARNESS_DIR = dirname(fileURLToPath(import.meta.url));
const REPO_ROOT = resolve(HARNESS_DIR, '../../..');

const DEFAULT_PHP_VERSION = '8.2';
const VFS_REPO = '/repo';
const VFS_LINT = '/lint';
const PHP_INI_PATH = '/internal/shared/php.ini';

// Files whose *.php extension does not mean "PHP source". DIY XML page
// exports start with a `<?PHP exit(...)?>` guard and then hold raw XML, so the
// tokenizer legitimately trips over the `<?xml` prologue.
const EXCLUDE_PATTERNS = [
	/(^|\/)diyxml\//,
	/^vendor\//,
	/^data\//,
];

function parseArgv(argv) {
	const options = {
		phpVersion: DEFAULT_PHP_VERSION,
		base: null,
		changed: false,
		shortOpenTag: false,
		json: false,
		quiet: false,
		selfTest: false,
		paths: [],
	};

	for(const arg of argv) {
		if(arg === '--changed') options.changed = true;
		else if(arg === '--short-open-tag') options.shortOpenTag = true;
		else if(arg === '--json') options.json = true;
		else if(arg === '--quiet') options.quiet = true;
		else if(arg === '--self-test') options.selfTest = true;
		else if(arg.startsWith('--php=')) options.phpVersion = arg.slice('--php='.length);
		else if(arg.startsWith('--base=')) options.base = arg.slice('--base='.length);
		else if(arg.startsWith('-')) throw new Error(`Unknown option: ${arg}`);
		else options.paths.push(arg);
	}

	return options;
}

function git(args, allowFailure = false) {
	try {
		return execFileSync('git', args, { cwd: REPO_ROOT, encoding: 'utf8' }).trim();
	} catch (error) {
		if(allowFailure) return null;
		throw error;
	}
}

function isPhpFile(file) {
	return file.endsWith('.php');
}

function isExcluded(file) {
	return EXCLUDE_PATTERNS.some((pattern) => pattern.test(file));
}

function resolveBaseRef(explicitBase) {
	if(explicitBase) return explicitBase;
	for(const candidate of ['origin/master', 'origin/main', 'master', 'main']) {
		if(git(['rev-parse', '--verify', '--quiet', candidate], true)) return candidate;
	}
	return 'HEAD~1';
}

function collectFiles(options) {
	if(options.paths.length) {
		return options.paths
			.map((path) => resolve(process.cwd(), path))
			.map((path) => path.startsWith(REPO_ROOT + '/') ? path.slice(REPO_ROOT.length + 1) : path)
			.filter(isPhpFile);
	}

	if(options.changed) {
		const base = resolveBaseRef(options.base);
		const mergeBase = git(['merge-base', base, 'HEAD'], true) || base;
		const committed = git(['diff', '--name-only', '--diff-filter=ACMR', mergeBase, 'HEAD'], true) || '';
		const working = git(['diff', '--name-only', '--diff-filter=ACMR', 'HEAD'], true) || '';
		const untracked = git(['ls-files', '--others', '--exclude-standard'], true) || '';
		const files = new Set(
			[committed, working, untracked]
				.join('\n')
				.split('\n')
				.map((line) => line.trim())
				.filter(Boolean)
		);
		return [...files].filter(isPhpFile).filter((file) => existsSync(join(REPO_ROOT, file)));
	}

	return git(['ls-files', '*.php']).split('\n').filter(Boolean);
}

async function createSandbox(options) {
	const runtime = await loadNodeRuntime(options.phpVersion, {
		// php-wasm requires an explicit process id outside of its own test runner.
		emscriptenOptions: { processId: 1 },
	});
	const php = new PHP(runtime);

	// The bundled php.ini enables short_open_tag; production PHP does not. Lint
	// with the stricter (default) setting unless the caller opts in.
	const ini = php.readFileAsText(PHP_INI_PATH);
	php.writeFile(PHP_INI_PATH, `${ini}\nshort_open_tag=${options.shortOpenTag ? 1 : 0}\n`);

	return php;
}

async function runLintPass(php, hostRoot, manifest) {
	const scratch = mkdtempSync(join(tmpdir(), 'php-lint-'));
	try {
		writeFileSync(join(scratch, 'manifest.json'), JSON.stringify(manifest));

		php.mkdir(VFS_REPO);
		php.mkdir('/scratch');
		await php.mount(VFS_REPO, createNodeFsMountHandler(hostRoot));
		await php.mount('/scratch', createNodeFsMountHandler(scratch));

		const result = await php.run({
			scriptPath: `${VFS_LINT}/lint.php`,
			env: {
				LINT_MANIFEST: '/scratch/manifest.json',
				LINT_ROOT: VFS_REPO,
			},
		});

		const text = result.text.trim();
		if(!text) {
			throw new Error(`Lint script produced no output. stderr: ${result.errors}`);
		}
		try {
			return JSON.parse(text);
		} catch {
			throw new Error(`Lint script produced non-JSON output:\n${text}\n${result.errors}`);
		}
	} finally {
		rmSync(scratch, { recursive: true, force: true });
	}
}

async function selfTest(options) {
	const scratch = mkdtempSync(join(tmpdir(), 'php-lint-selftest-'));
	try {
		writeFileSync(join(scratch, 'good.php'), '<?php\nfunction good(): int { return 1; }\n');
		writeFileSync(join(scratch, 'bad.php'), '<?php\nfunction bad( {\n');
		// Valid tokens, invalid grammar: only TOKEN_PARSE rejects this one.
		writeFileSync(join(scratch, 'grammar.php'), '<?php\nif ($a) { echo 1;\n');

		const php = await createSandbox(options);
		php.mkdir(VFS_LINT);
		await php.mount(VFS_LINT, createNodeFsMountHandler(HARNESS_DIR));

		const report = await runLintPass(php, scratch, ['good.php', 'bad.php', 'grammar.php']);
		const failed = new Set(report.diagnostics.map((d) => d.file));

		const problems = [];
		if(failed.has('good.php')) problems.push('good.php was wrongly reported as broken');
		if(!failed.has('bad.php')) problems.push('bad.php (syntax error) was not detected');
		if(!failed.has('grammar.php')) problems.push('grammar.php (unbalanced brace) was not detected — is TOKEN_PARSE active?');

		if(problems.length) {
			console.error('Self-test FAILED:');
			for(const problem of problems) console.error(`  - ${problem}`);
			return 1;
		}

		console.log(`Self-test OK on PHP ${report.phpVersion}: clean file passes, both broken files raise ParseError.`);
		return 0;
	} finally {
		rmSync(scratch, { recursive: true, force: true });
	}
}

async function main() {
	const options = parseArgv(process.argv.slice(2));

	if(options.selfTest) {
		process.exit(await selfTest(options));
	}

	const candidates = collectFiles(options);
	const skipped = candidates.filter(isExcluded);
	const files = candidates.filter((file) => !isExcluded(file));

	if(!files.length) {
		if(!options.json) console.log('No PHP files to lint.');
		else console.log(JSON.stringify({ checked: 0, diagnostics: [] }));
		process.exit(0);
	}

	const php = await createSandbox(options);
	php.mkdir(VFS_LINT);
	await php.mount(VFS_LINT, createNodeFsMountHandler(HARNESS_DIR));

	const started = Date.now();
	const report = await runLintPass(php, REPO_ROOT, files);
	const elapsed = Date.now() - started;

	if(options.json) {
		console.log(JSON.stringify({ ...report, skipped, elapsedMs: elapsed }, null, 2));
		process.exit(report.diagnostics.length ? 1 : 0);
	}

	if(!options.quiet) {
		console.log(`php-wasm ${report.phpVersion} (short_open_tag=${report.shortOpenTag ? 'On' : 'Off'})`);
		if(skipped.length) console.log(`Skipped ${skipped.length} non-source *.php file(s) (DIY XML exports, vendor, data).`);
	}

	for(const diagnostic of report.diagnostics) {
		console.error(`${diagnostic.file}:${diagnostic.line}: ${diagnostic.type}: ${diagnostic.message}`);
	}

	const summary = `Checked ${report.checked} file(s) in ${elapsed}ms — ${report.diagnostics.length} parse error(s).`;
	if(report.diagnostics.length) {
		console.error(summary);
		process.exit(1);
	}
	console.log(summary);
	process.exit(0);
}

main().catch((error) => {
	console.error(error?.stack || String(error));
	process.exit(2);
});
