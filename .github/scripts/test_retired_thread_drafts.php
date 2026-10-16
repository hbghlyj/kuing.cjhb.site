<?php
define('IN_DISCUZ', true);
set_error_handler(function($severity, $message, $file, $line) {
    if(basename($file) === 'class_template.php' && str_contains($message, 'Undefined array key 1')) return true;
    throw new ErrorException($message, 0, $severity, $file, $line);
});
class discuz_model {
    public $param = [];
    public function showmessage($key) { return $key; }
}
require 'source/app/forum/model/model_thread.php';
require 'source/app/forum/model/model_post.php';
foreach(['forum\\model_thread', 'forum\\model_post'] as $class) {
    $reflection = new ReflectionClass($class);
    $model = $reflection->newInstanceWithoutConstructor();
    $reflection->getMethod('_init_parameters')->invoke($model, [
        'save' => 1, 'publishdate' => 2000000000, 'cronpublish' => 1,
        'cronpublishdate' => '2033-05-18T12:00', 'subject' => 'Ordinary post',
    ]);
    foreach(['save', 'publishdate', 'cronpublish', 'cronpublishdate'] as $key) {
        if(array_key_exists($key, $model->param)) throw new RuntimeException('Retired parameter accepted: '.$key);
    }
    if($model->param['subject'] !== 'Ordinary post') throw new RuntimeException('Ordinary posting parameter lost');
}
$model->thread = ['displayorder' => -4];
if($model->editpost([]) !== 'thread_nonexist') throw new RuntimeException('Legacy drafts must not be published through editing');
if($model->newreply([]) !== 'thread_nonexist') throw new RuntimeException('Legacy drafts must not accept replies');
foreach(['source/app/forum/child/misc/pubsave.php', 'source/child/cron/cron_publish_halfhourly.php'] as $file) {
    if(file_exists($file)) throw new RuntimeException('Retired publisher still exists: '.$file);
}
require 'source/class/class_tplfile.php';
require 'source/class/class_template.php';
class DraftTemplateCompiler extends template {
    function loadcsstemplate() { return ''; }
    function loadcell($cellId) { return ''; }
}
function strexists($text, $part) { return str_contains($text, $part); }
function loadcache($name) {}
function getglobal($key) { return null; }
function template($file, $id = 0, $dir = '', $returnfile = false) { return 'template/default/'.$file.'.htm'; }
function lang($file, $key = null) {
    $parts = explode('/', $file);
    $name = array_pop($parts);
    $path = 'source/i18n/EN/'.($parts ? implode('/', $parts).'/' : '').'lang_'.$name.'.php';
    $lang = [];
    if(file_exists($path)) include $path;
    return $key === null ? $lang : ($lang[$key] ?? $key);
}
foreach([
    'template/default/home/space_thread.htm',
    'template/default/forum/post.htm',
    'template/default/forum/post_infloat.htm',
    'template/default/forum/post_editor_attribute.htm',
    'template/default/forum/viewthread.htm',
    'template/default/touch/forum/post.htm',
    'template/default/touch/forum/post_editor_attribute.htm',
    'template/default/touch/forum/viewthread.htm',
    'template/discuzx5/forum/viewthread.php',
    'template/discuz_blog/forum/viewthread.php',
] as $file) {
    $compiler = new DraftTemplateCompiler();
    $compiled = $compiler->parse_template($file, 1, '', 'retired_drafts');
    try {
        token_get_all($compiled, TOKEN_PARSE);
    } catch(ParseError $error) {
        throw new RuntimeException('Template compilation failed: '.$file.' '.(explode("\n", $compiled)[$error->getLine() - 1] ?? ''), 0, $error);
    }
    if(str_contains($compiled, 'cronpublish') || str_contains($compiled, 'action=pubsave')) {
        throw new RuntimeException('Retired controls in template: '.$file);
    }
}
$space = file_get_contents('template/default/home/space_thread.htm');
if(str_contains($space, "'save' =>") || str_contains($space, '$filter[save]')) throw new RuntimeException('Draft filter remains');
echo "Server draft parameters and publishers retired; affected templates compile\n";
