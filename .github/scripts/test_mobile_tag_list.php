<?php
define('IN_DISCUZ', true);
require 'source/class/class_tplfile.php';
require 'source/class/class_template.php';
function strexists($text, $part) { return str_contains($text, $part); }
function loadcache($name) {}
function getglobal($key) { return null; }
function template($file, $id = 0, $dir = '', $returnfile = false) { return ''; }
function lang($file, $key = null) { return $key === null ? [] : $key; }
function dhtmlspecialchars($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
set_error_handler(function($severity, $message, $file, $line) {
    if(basename($file) === 'class_template.php' && str_contains($message, 'Undefined array key 1')) return true;
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$compiler = new template();
$compiled = $compiler->parse_template('template/default/touch/tag/tag.php', 1, '', 'tag/tag');
token_get_all($compiled, TOKEN_PARSE);
$compiled = preg_replace('/include template\([^;]+;/', '', $compiled);
$type = '';
$name = '';
$multipage = '<nav id="fixture-pagination">2</nav>';
$tagarray = [
    ['tagid' => 7, 'tagname' => 'Green\'s "theorem" </script>', 'related_count' => 12],
    ['tagid' => 8, 'tagname' => 'Second tag', 'related_count' => 0],
];
ob_start();
eval('?>'.$compiled);
$payload = ob_get_clean();
if(!str_contains($payload, 'id=7') || !str_contains($payload, 'id=8') || !str_contains($payload, '&lt;/script&gt;') || !str_contains($payload, 'fixture-pagination') || str_contains($payload, '<script')) {
    throw new RuntimeException('Mobile tag links must render safely without JavaScript');
}
echo "Mobile wrapping tag list renders links, counts and pagination without JavaScript\n";
$tagarray = [];
ob_start();
eval('?>'.$compiled);
$empty = ob_get_clean();
if(!str_contains($empty, 'no_tag')) throw new RuntimeException('Empty tag list needs an explicit empty state');
$type = 'countitem';
$num = 5;
ob_start();
eval('?>'.$compiled);
$count = ob_get_clean();
if(trim($count) !== '5') throw new RuntimeException('Tag count endpoint must retain its plain numeric output');
