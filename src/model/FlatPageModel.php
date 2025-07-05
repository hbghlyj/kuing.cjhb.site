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

    public function uploadImages(string $slug, array $files): array
    {
        $saved = [];
        $path = $this->getPath($slug);
        if ($path === null) {
            return $saved;
        }
        $dir = dirname($path);
        $base = basename($path, '.md');

        $index = 1;
        foreach (glob($dir . '/' . $base . '_*.*') as $img) {
            if (preg_match('/_(\d+)\.[^.]+$/', $img, $m)) {
                $index = max($index, (int)$m[1] + 1);
            }
        }

        $names = $files['name'] ?? [];
        $tmp   = $files['tmp_name'] ?? [];
        $errors= $files['error'] ?? [];

        foreach ($names as $i => $name) {
            if ($errors[$i] === UPLOAD_ERR_OK) {
                $ext = pathinfo($name, PATHINFO_EXTENSION);
                $target = $dir . '/' . $base . '_' . $index . '.' . $ext;
                if (move_uploaded_file($tmp[$i], $target)) {
                    $saved[] = basename($target);
                    $index++;
                }
            }
        }
        return $saved;
    }

    public function cleanUnusedImages(string $slug, string $markdown): void
    {
        $path = $this->getPath($slug);
        if ($path === null) {
            return;
        }
        $dir = dirname($path);
        $base = basename($path, '.md');

        preg_match_all('/' . preg_quote($base, '/') . '_\d+\.[A-Za-z0-9]+/', $markdown, $matches);
        $used = isset($matches[0]) ? array_unique($matches[0]) : [];

        foreach (glob($dir . '/' . $base . '_*.*') as $img) {
            if (!in_array(basename($img), $used)) {
                @unlink($img);
            }
        }
    }

    public function delete(string $slug): bool
    {
        $path = $this->getPath($slug);
        if ($path === null || !file_exists($path)) {
            return false;
        }
        $dir = dirname($path);
        $base = basename($path, '.md');
        foreach (glob($dir . '/' . $base . '_*.*') as $img) {
            @unlink($img);
        }
        return unlink($path);
    }
}
