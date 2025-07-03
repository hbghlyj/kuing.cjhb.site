<?php
namespace DocPHT\Model;

use DocPHT\Lib\MediaWikiParsedown;

class FlatPageModel
{
    protected $baseDir = 'flat';

    public function getPath(string $slug): ?string
    {
        $base = realpath($this->baseDir);
        if ($base === false) {
            return null;
        }

        // Build the target path and validate it
        $target = $base . '/' . $slug . '.md';
        $resolved = realpath($target);

        // If realpath fails or the resolved path escapes the base directory
        if ($resolved === false || strpos($resolved, $base) !== 0) {
            return null;
        }

        return $resolved;
    }

    public function get(string $slug): ?string
    {
        $path = $this->getPath($slug);
        if ($path === null || !file_exists($path)) {
            return null;
        }
        return file_get_contents($path);
    }

    public function put(string $slug, string $content): bool
    {
        $path = $this->getPath($slug);
        if ($path === null) {
            return false;
        }
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        return file_put_contents($path, $content) !== false;
    }

    public function render(string $slug): ?string
    {
        $markdown = $this->get($slug);
        if ($markdown === null) {
            return null;
        }
        $parsedown = new MediaWikiParsedown();
        return $parsedown->text($markdown);
    }
}
