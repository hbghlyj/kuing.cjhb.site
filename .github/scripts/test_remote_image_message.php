<?php
define('IN_DISCUZ', true);
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
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

class discuz_upload {
    public $attach;
    public function fileext($url) { return pathinfo($url, PATHINFO_EXTENSION); }
    public function is_image_ext($ext) { return $ext === 'png'; }
    public function get_target_extension($ext) { return $ext; }
    public function get_target_dir($type) { return ''; }
    public function get_target_filename($type) { return 'fixture'; }
    public function get_image_info($file) { return (bool)getimagesize($file); }
}
class C {
    public static function t($table) { return new self(); }
    public function insert($row) { $GLOBALS['inserted'][] = $row; }
}
function dfsockopen($url) {
    $GLOBALS['downloads'][] = $url;
    return str_contains($url, '/good.png') ? base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aX1cAAAAASUVORK5CYII=') : '';
}
function getglobal($key) { return $GLOBALS['fixtureDir'].'/'; }
function ftpperm(...$args) { return false; }
function getattachnewaid() { return 42; }
$fixtureDir = sys_get_temp_dir().'/remote-image-test-'.bin2hex(random_bytes(8));
mkdir($fixtureDir);
mkdir($fixtureDir.'/forum');
$_G += ['uid' => 1, 'timestamp' => 1, 'setting' => ['thumbsource' => 0, 'thumbstatus' => 0, 'watermarkstatus' => 0]];
try {
    foreach([0, 1] as $wysiwyg) {
        $failed = '[img]https://example.test/failed.png[/img]';
        $unsupported = '[img]https://example.test/image.txt[/img]';
        $good = '[img]https://example.test/good.png[/img]';
        $downloads = $inserted = [];
        $_GET = ['message' => $failed.$unsupported.$good.$failed.$good, 'wysiwyg' => $wysiwyg];
        ob_start();
        include 'source/app/forum/child/ajax/downremoteimg.php';
        $response = ob_get_clean();
        preg_match('/parent\.updateDownImageList\(([^\r\n]+)\);/', $response, $match);
        $actual = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
        $attachment = '[attachimg]42[/attachimg]';
        if($actual !== $failed.$unsupported.$attachment.$failed.$attachment || count($inserted) !== 1) {
            throw new RuntimeException('Failed images must remain; successful duplicates must share one attachment');
        }
        if(count(array_filter($downloads, fn($url) => str_contains($url, '/good.png'))) !== 1) {
            throw new RuntimeException('Successful duplicate was downloaded more than once');
        }
    }
} finally {
    if(file_exists($fixtureDir.'/forum/fixture.png')) unlink($fixtureDir.'/forum/fixture.png');
    rmdir($fixtureDir.'/forum');
    rmdir($fixtureDir);
}
echo "Failed remote images survive mixed results; successful duplicates share attachments\n";
