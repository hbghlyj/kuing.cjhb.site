<?php
namespace DocPHT\Model;

use DocPHT\Lib\MediaWikiParsedown;

class FlatPageModel
{
    protected $baseDir = 'flat';

    public function getPath(string $slug): string
    {
        // Sanitize the slug to avoid directory traversal
        // by stripping any path components the user might supply.
        $safeSlug = basename($slug);
        return $this->baseDir . '/' . $safeSlug . '.md';
    }

    public function get(string $slug): ?string
    {
        $path = $this->getPath($slug);
        if (!file_exists($path)) {
            return null;
        }
        return file_get_contents($path);
    }

    public function put(string $slug, string $content): bool
    {
        $path = $this->getPath($slug);
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
