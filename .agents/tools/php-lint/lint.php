<?php

/**
 * Guest script executed inside the php-wasm sandbox by lint.mjs.
 *
 * It reads a JSON manifest of repo-relative paths, runs each file through
 * token_get_all($src, TOKEN_PARSE) and reports every ParseError as JSON.
 *
 * TOKEN_PARSE makes the tokenizer run the full compiler grammar instead of
 * the plain lexer, so genuine syntax errors throw ParseError rather than
 * silently producing a token stream.
 */

if(PHP_SAPI !== 'cli' && !getenv('LINT_MANIFEST')) {
	fwrite(STDERR, "This script only runs inside the php-wasm lint harness.\n");
	exit(1);
}

$manifest = getenv('LINT_MANIFEST') ?: '/lint/manifest.json';
$root = rtrim(getenv('LINT_ROOT') ?: '/repo', '/').'/';

$files = json_decode((string)@file_get_contents($manifest), true);
if(!is_array($files)) {
	echo json_encode(['fatal' => 'Unable to read manifest '.$manifest]);
	exit(1);
}

$diagnostics = [];
$checked = 0;

foreach($files as $relative) {
	$absolute = $root.$relative;
	$source = @file_get_contents($absolute);
	if($source === false) {
		$diagnostics[] = [
			'file' => $relative,
			'line' => 0,
			'type' => 'UnreadableFile',
			'message' => 'Unable to read file',
		];
		continue;
	}

	$checked++;
	try {
		token_get_all($source, TOKEN_PARSE);
	} catch (ParseError $e) {
		$diagnostics[] = [
			'file' => $relative,
			'line' => $e->getLine(),
			'type' => 'ParseError',
			'message' => $e->getMessage(),
		];
	} catch (CompileError $e) {
		$diagnostics[] = [
			'file' => $relative,
			'line' => $e->getLine(),
			'type' => 'CompileError',
			'message' => $e->getMessage(),
		];
	} catch (Throwable $e) {
		$diagnostics[] = [
			'file' => $relative,
			'line' => $e->getLine(),
			'type' => get_class($e),
			'message' => $e->getMessage(),
		];
	}
	unset($source);
}

echo json_encode([
	'phpVersion' => PHP_VERSION,
	'shortOpenTag' => (bool)ini_get('short_open_tag'),
	'checked' => $checked,
	'diagnostics' => $diagnostics,
]);
