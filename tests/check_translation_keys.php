<?php
$dir = __DIR__ . '/../src';
$translationFile = __DIR__ . '/../src/translations/zh_CN.php';
$content = file_get_contents($translationFile);
preg_match_all("/'([^']+)'\s*=>|\"([^\"]+)\"\s*=>/", $content, $matches);
$allKeys = array_filter(array_merge($matches[1], $matches[2]));
$counts = array_count_values($allKeys);
$duplicates = [];
foreach ($counts as $k => $c) {
    if ($c > 1) {
        $duplicates[$k] = $c;
    }
}
$keys = array_keys($counts);
$missing = [];
$used = [];
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
foreach ($rii as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') continue;
    $php = file_get_contents($file->getPathname());
    preg_match_all("/T::trans\(['\"]([^'\"]+)['\"]\)/", $php, $m);
    foreach ($m[1] as $k) {
        $used[$k] = true;
        if (!in_array($k, $keys)) {
            $missing[$k][] = str_replace(__DIR__ . '/../', '', $file->getPathname());
        }
    }
}
$unused = array_diff($keys, array_keys($used));

$hasError = false;
if ($missing) {
    echo "Missing translations:\n";
    foreach ($missing as $k => $files) {
        echo "$k: " . implode(', ', $files) . "\n";
    }
    $hasError = true;
}
if ($duplicates) {
    echo "Duplicate translation keys:\n";
    foreach ($duplicates as $k => $c) {
        echo "$k appears $c times\n";
    }
    $hasError = true;
}
if ($unused) {
    echo "Unused translation keys:\n";
    foreach ($unused as $k) {
        echo "$k\n";
    }
    $hasError = true;
}
if ($hasError) {
    exit(1);
}
echo "All translation keys valid\n";
