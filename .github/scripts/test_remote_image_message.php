<?php
define('IN_DISCUZ', true);
function libfile($file) { return __FILE__; }
function dexit() {}
$_G = ['group' => ['allowdownremoteimg' => 1]];
$message = '$\frac{1}{2} + \text{test} + \newcommand{\foo}{x}$'."\r\n".
    "Quotes: ' and \"; literal \\n and \\u0041; </script><script>alert(1)</script>";
foreach([0, 1] as $wysiwyg) {
    $_GET = ['message' => $message, 'wysiwyg' => $wysiwyg];
    ob_start();
    include 'source/app/forum/child/ajax/downremoteimg.php';
    $response = ob_get_clean();
    if(!preg_match('/parent\.updateDownImageList\(([^\r\n]+)\);/', $response, $match)) {
        throw new RuntimeException('Missing serialized editor callback');
    }
    if(json_decode($match[1], true, 512, JSON_THROW_ON_ERROR) !== $message) {
        throw new RuntimeException('Remote image callback changed backslashes, quotes or newlines');
    }
    if(substr_count($response, '</script>') !== 1) {
        throw new RuntimeException('Editor contents escaped the response script');
    }
}
echo "Remote image callback preserves LaTeX, literal escapes, quotes and newlines in both modes\n";
