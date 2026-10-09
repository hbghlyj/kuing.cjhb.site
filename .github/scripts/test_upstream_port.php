<?php
define('IN_DISCUZ', true);
require __DIR__.'/../../source/function/function_stat.php';

$actions = ['total' => 999];
$labels = [];
for($i = 1; $i <= 18; $i++) {
    $actions['action'.$i] = ['count' => $i];
    $labels['action'.$i] = 'Action '.$i;
}
$actions['zero'] = ['count' => 0];
$chart = stat_moderation_chart($actions, $labels, 'Other');
if(count($chart['categories']) !== 16 || $chart['categories'][0] !== 'Action 18' || $chart['values'][0] !== 18 || $chart['categories'][15] !== 'Other' || $chart['values'][15] !== 6 || array_sum($chart['values']) !== 171) {
    throw new RuntimeException('Top-15 moderation distribution lost labels or counts');
}
if(stat_moderation_chart(['total' => 0], [], 'Other') !== ['categories' => [], 'values' => []]) {
    throw new RuntimeException('Empty moderation distribution must remain empty');
}
if(stat_moderation_chart(['unknown' => ['count' => 2]], [], 'Other')['categories'] !== ['unknown']) {
    throw new RuntimeException('Unknown action codes must retain their own label');
}

foreach(['newthread', 'newreply', 'editpost'] as $name) {
    $source = file_get_contents(__DIR__.'/../../source/app/forum/child/post/'.$name.'.php');
    $start = strpos($source, "foreach(\$blocksData['blocks']");
    if($start === false) throw new RuntimeException('Missing attachment collection: '.$name);
    $code = '';
    $depth = 0;
    $opened = false;
    foreach(token_get_all('<?php '.substr($source, $start)) as $token) {
        if(is_array($token)) {
            if($token[0] !== T_OPEN_TAG) $code .= $token[1];
        } else {
            $code .= $token;
            if($token === '{') { $depth++; $opened = true; }
            if($token === '}' && --$depth === 0 && $opened) break;
        }
    }
    $identifiers = ['custom', 'images', 'image'];
    $blocksData = ['blocks' => [
        ['type' => 'custom', 'data' => ['files' => [['aid' => 11], ['aid' => 12], []]]],
        ['type' => 'images', 'data' => ['files' => [], 'file' => ['aid' => 13]]],
        ['type' => 'image', 'data' => ['file' => ['aid' => 14]]],
    ]];
    $_GET = [];
    eval($code);
    if(array_keys($_GET['attachnew'] ?? []) !== [11, 12, 13, 14]) {
        throw new RuntimeException('Attachment payload shape not respected: '.$name);
    }
}
echo "Upstream moderation and attachment regressions passed\n";
