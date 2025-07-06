<?php
$dir = __DIR__ . '/../src';
$translationFile = __DIR__ . '/../src/translations/zh_CN.php';
$content = file_get_contents($translationFile);
preg_match_all("/'([^']+)'\s*=>|\"([^\"]+)\"\s*=>/", $content, $matches);
$keys = array_filter(array_merge($matches[1], $matches[2]));
$keys = array_unique($keys);
$missing = [];
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
foreach ($rii as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') continue;
    $php = file_get_contents($file->getPathname());
    preg_match_all("/T::trans\(['\"]([^'\"]+)['\"]\)/", $php, $m);
    foreach ($m[1] as $k) {
        if (!in_array($k, $keys)) {
            $missing[$k][] = str_replace(__DIR__ . '/../', '', $file->getPathname());
        }
    }
}
if ($missing) {
    echo "Missing translations:\n";
    foreach ($missing as $k => $files) {
        echo "$k: " . implode(', ', $files) . "\n";
    }
    exit(1);
}
echo "All translation keys exist\n";
