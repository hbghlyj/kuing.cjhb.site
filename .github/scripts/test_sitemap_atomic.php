<?php
define('IN_DISCUZ', true);
$directory = sys_get_temp_dir().'/sitemap-'.bin2hex(random_bytes(8));
mkdir($directory);
define('DISCUZ_ROOT', $directory.'/');
$_G = ['setting' => ['siteurl' => 'https://example.test/', 'postperpage' => 10]];
$errors = [];
function runlog($type, $message) { $GLOBALS['errors'][] = $message; }
function generateSitemap() { include __DIR__.'/../../source/child/cron/cron_sitemap.php'; }
function sitemapUrls() {
    $document = new DOMDocument();
    if(!$document->load(DISCUZ_ROOT.'sitemap.xml')) throw new RuntimeException('Published sitemap is invalid XML');
    return array_map(fn($node) => $node->textContent, iterator_to_array($document->getElementsByTagName('loc')));
}
class DB {
    public static $calls = 0;
    public static $fail = false;
    public static function query($sql, $parameters) {
        if(self::$fail) throw new RuntimeException('Fixture database failure');
        $id = ++self::$calls;
        if($id === 1) {
            // Interleave a second full generation while the first has an open temporary file.
            generateSitemap();
            if(sitemapUrls() !== ['https://example.test/forum.php?mod=viewthread&tid=2']) throw new RuntimeException('Concurrent writer did not publish a complete file');
        } elseif($id === 2) {
            $temporary = glob(DISCUZ_ROOT.'.sm*');
            if(count($temporary) !== 2 || count(array_unique($temporary)) !== 2) throw new RuntimeException('Generators must use distinct temporary files');
        }
        return (object)['id' => $id, 'done' => false];
    }
    public static function fetch($query) {
        if($query->done) return false;
        $query->done = true;
        return ['tid' => $query->id, 'replies' => 0, 'lastpost' => 1700000000, 'excl' => 0];
    }
}
try {
    file_put_contents(DISCUZ_ROOT.'sitemap.xml', '<urlset/>');
    generateSitemap();
    if(sitemapUrls() !== ['https://example.test/forum.php?mod=viewthread&tid=1'] || $errors || glob(DISCUZ_ROOT.'.sm*')) throw new RuntimeException('Interleaved generation lost or leaked files');
    $published = file_get_contents(DISCUZ_ROOT.'sitemap.xml');
    DB::$fail = true;
    try {
        generateSitemap();
        throw new LogicException('Expected fixture failure');
    } catch(RuntimeException $exception) {
        if($exception->getMessage() !== 'Fixture database failure') throw $exception;
    }
    if(file_get_contents(DISCUZ_ROOT.'sitemap.xml') !== $published || glob(DISCUZ_ROOT.'.sm*')) throw new RuntimeException('Failure must preserve the old sitemap and remove its temporary file');
    if($errors !== ['Sitemap generation failed: Fixture database failure']) throw new RuntimeException('Generation failure details missing');
    $errors = [];
    DB::$fail = false;
    unlink(DISCUZ_ROOT.'sitemap.xml');
    mkdir(DISCUZ_ROOT.'sitemap.xml');
    generateSitemap();
    if(count($errors) !== 1 || !str_contains($errors[0], 'Sitemap rename ') || !str_contains($errors[0], 'rename(') || glob(DISCUZ_ROOT.'.sm*')) {
        throw new RuntimeException('Rename failure must record its stage and system warning, then clean up');
    }
    rmdir(DISCUZ_ROOT.'sitemap.xml');
    file_put_contents(DISCUZ_ROOT.'sitemap.xml', $published);
    echo "Interleaved sitemap generation uses unique files and preserves atomic publication\n";
} finally {
    foreach(glob(DISCUZ_ROOT.'.sm*') as $file) unlink($file);
    if(is_dir(DISCUZ_ROOT.'sitemap.xml')) rmdir(DISCUZ_ROOT.'sitemap.xml');
    else unlink(DISCUZ_ROOT.'sitemap.xml');
    rmdir($directory);
}
