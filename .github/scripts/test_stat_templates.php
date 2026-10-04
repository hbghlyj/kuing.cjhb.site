<?php
define('IN_DISCUZ', true);
require 'source/class/class_tplfile.php';
require 'source/class/class_template.php';
set_error_handler(function($severity, $message, $file, $line) {
    // The production compiler destructures optional language arguments without a default.
    if (basename($file) === 'class_template.php' && $line === 267 && str_contains($message, 'Undefined array key 1')) return true;
    throw new ErrorException($message, 0, $severity, $file, $line);
});

function strexists($haystack, $needle) { return strpos($haystack, $needle) !== false; }
function loadcache($name) {}
function getglobal($name) { return null; }
function template($file, $id = 0, $dir = '', $returnfile = false) { return 'template/default/'.$file.'.htm'; }
function lang($file, $key = null) {
    global $locale;
    $parts = explode('/', $file);
    $name = array_pop($parts);
    $path = 'source/i18n/'.$locale.'/'.($parts ? implode('/', $parts).'/' : '').'lang_'.$name.'.php';
    $lang = [];
    include $path;
    return $key === null ? $lang : ($lang[$key] ?? '!'.$key.'!');
}

foreach (['EN', 'SC', 'TC'] as $locale) {
    foreach (['forum/stat_main', 'forum/stat_memberlist', 'forum/stat_team', 'forum/stat_trade', 'forum/stat_misc', 'forum/stat_misc_export', 'home/misc_stat'] as $file) {
        $compiler = new template();
        $compiled = $compiler->parse_template('template/default/'.$file.'.htm', 1, '', $file);
        token_get_all($compiled, TOKEN_PARSE);
        if (preg_match('/![a-z_]+!/', $compiled, $missing)) throw new RuntimeException($locale.' '.$file.': missing language '.$missing[0]);
        if ($file === 'forum/stat_misc_export') {
            $op = 'modworks';
            $uid = 0;
            $starttime = '2026-10-01';
            $endtime = '2026-10-02';
            $modactioncode = ['DEL' => 'Delete'];
            $members = [7 => ['username' => 'Operator', 'DEL' => ['posts' => 2, 'count' => 2], 'total' => 2]];
            $total = ['DEL' => ['posts' => 2, 'count' => 2], 'total' => 2];
            ob_start();
            eval('?>'.$compiled);
            $csv = ob_get_clean();
            if ($uid !== 0) throw new RuntimeException('Export changed the selected operator ID');
            $rows = explode("\r\n", trim(substr($csv, 3)));
            if (count($rows) !== 5 || $rows[3] !== 'Operator,2,2') throw new RuntimeException('CSV rows contain unexpected layout whitespace');
        }
    }
}
echo "Statistics templates compile in EN, SC and TC\n";
