<?php
define('IN_DISCUZ', true);
require 'source/class/class_tplfile.php';
require 'source/class/class_template.php';
function strexists($text, $part) { return str_contains($text, $part); }
function loadcache($name) {}
function getglobal($key) { return null; }
function template($file, $id = 0, $dir = '', $returnfile = false) { return ''; }
function lang($file, $key = null) { return $key === null ? [] : $key; }
set_error_handler(function($severity, $message, $file, $line) {
    if(basename($file) === 'class_template.php' && str_contains($message, 'Undefined array key 1')) return true;
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$compiler = new template();
$compiled = $compiler->parse_template('template/default/touch/tag/tag.php', 1, '', 'tag/tag');
token_get_all($compiled, TOKEN_PARSE);
if(!preg_match('/const keywords = \[(.*?)\];/s', $compiled, $match)) throw new RuntimeException('Missing word-cloud payload');
$tagarray = [
    ['tagid' => 7, 'tagname' => 'Green\'s "theorem" </script>', 'hot_score' => 12.5],
    ['tagid' => 8, 'tagname' => 'Missing score'],
];
ob_start();
eval('?>'.$match[1]);
$payload = ob_get_clean();
$rows = json_decode('['.rtrim(trim($payload), ',').']', true, 512, JSON_THROW_ON_ERROR);
if($rows[0]['text'] !== $tagarray[0]['tagname'] || $rows[0]['weight'] !== 12.5 || $rows[1]['weight'] !== 0 || str_contains($payload, '</script>')) {
    throw new RuntimeException('Invalid or unsafe tag word-cloud data');
}
$controller = file_get_contents('source/app/misc/module/tag.php');
if(!str_contains($controller, 'tag.hot_score AS hot_score')) throw new RuntimeException('Tag listing query must supply the word-cloud weight');
echo "Mobile tag word-cloud payload compiles and preserves safe numeric weights\n";
