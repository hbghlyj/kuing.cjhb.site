<?php
namespace DocPHT\Model;

use DocPHT\Lib\MediaWikiParsedown;

class PageModel
{
    const DB = 'json/pages.json';

    protected function sanitizeComponent($name)
    {
        return preg_replace('/[\\\\\/:*?"<>|]/', '_', $name);
    }

    protected function computeFileSlug($topic, $filename)
    {
        $topic = $this->sanitizeComponent($topic);
        $filename = $this->sanitizeComponent($filename);
        return trim($topic) . '/' . trim($filename);
    }

    public function connect()
    {
        if (!file_exists(self::DB)) {
            file_put_contents(self::DB, json_encode([]));
        }
        return json_decode(file_get_contents(self::DB), true) ?: [];
    }

    public function disconnect($path, $data)
    {
        return file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public function slugExists($slug)
    {
        foreach ($this->connect() as $value) {
            if ($value['pages']['slug'] === $slug) {
                return true;
            }
        }
        return false;
    }

    public function create($topic, $filename)
    {
        $data = $this->connect();
        $slug = trim($topic) . '/' . trim($filename);
        if (preg_match('/\.\.\/|\.\.\\\\|\/\.\.$/', $slug)) {
            throw new \InvalidArgumentException('Invalid slug: ' . $slug);
        }
        if ($this->slugExists($slug)) {
            return false;
        }
        // Insert the new page to DB
        $data[] = [
            'pages' => [
                'slug' => $slug,
                'topic' => $topic,
                'filename' => $filename
            ]
        ];
        $this->disconnect(self::DB, $data);
        // Create the flat file with a header
        $this->put($slug, '# ' . $filename . "\n");
        return true;
    }

    public function getPagesByTopic($topic)
    {
        $data = $this->connect();
        $array = [];
        foreach ($data as $value) {
            if ($value['pages']['topic'] === $topic) {
                $array[] = $value['pages'];
            }
        }
        usort($array, function ($a, $b) {
            return $a['topic'] <=> $b['topic'];
        });
        return $array;
    }

    public function getUniqTopics()
    {
        $array = $this->getAllFromKey('topic');
        if (is_array($array)) {
            $array = array_unique($array);
            sort($array);
            return $array;
        }
        return [];
    }

    public function getAllFromKey($key)
    {
        $data = $this->connect();
        $array = [];
        foreach ($data as $value) {
            $array[] = $value['pages'][$key];
        }
        return $array;
    }

    public function getAllIndexed()
    {
        $data = $this->connect();
        $array = [];
        foreach ($data as $value) {
            foreach ($value as $v) {
                $array[] = $v;
            }
        }
        return $array;
    }

    public function getTopic($id)
    {
        $data = $this->connect();
        $key = $this->findKey($data, $id);
        return $data[$key]['pages']['topic'] ?? null;
    }

    public function getFilename($id)
    {
        $data = $this->connect();
        $key = $this->findKey($data, $id);
        return $data[$key]['pages']['filename'] ?? null;
    }

    public function remove($id)
    {
        $data = $this->connect();
        $key = $this->findKey($data, $id);
        if ($key !== false) {
            array_splice($data, $key, 1);
            $this->disconnect(self::DB, $data);
        }
    }

    public function findKey($data, $search)
    {
        $x = 0;
        foreach ($data as $array) {
            if ($array['pages']['slug'] == $search) {
                return $x;
            }
            $x++;
        }
        return false;
    }

    public function hideBySlug($slug)
    {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? 'https://' : 'http://';
        return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] !== BASE_URL . $slug;
    }

    // -------- Flat file helpers ---------

    public function getPath(string $slug): ?string
    {
        return $_SERVER['DOCUMENT_ROOT'] . '/flat/' . $slug . '.md';
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
            mkdir(dirname($path), 0666, true);
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
        if (!file_exists($path)) {
            return $saved;
        }
        $dir = dirname($path);
        $base = basename($path, '.md');
        $index = 1;
        foreach (glob($dir . '/' . $base . '_*.{jpg,jpeg,png,gif}', GLOB_BRACE) as $img) {
            if (preg_match('/_(\d+)\.[^.]+$/', $img, $m)) {
                $index = max($index, (int)$m[1] + 1);
            }
        }
        $names = $files['name'] ?? [];
        $tmp = $files['tmp_name'] ?? [];
        $errors = $files['error'] ?? [];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $allowed = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif'
        ];
        foreach ($names as $i => $name) {
            if ($errors[$i] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $mime = $finfo->file($tmp[$i]) ?: '';
                if (isset($allowed[$ext]) && $allowed[$ext] === $mime) {
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    $target = $dir . '/' . $base . '_' . $index . '.' . $ext;
                    if (move_uploaded_file($tmp[$i], $target)) {
                        $saved[] = basename($target);
                        $index++;
                    }
                }
            }
        }
        return $saved;
    }

    public function cleanUnusedImages(string $slug, string $markdown): void
    {
        $path = $this->getPath($slug);
        if (!file_exists($path)) {
            return;
        }
        $dir = dirname($path);
        $base = basename($path, '.md');
        preg_match_all('/' . preg_quote($base, '/') . '_\d+\.[A-Za-z0-9]+/i', $markdown, $matches);
        $used = isset($matches[0]) ? array_unique($matches[0]) : [];
        foreach (glob($dir . '/' . $base . '_*.{jpg,jpeg,png,gif}', GLOB_BRACE) as $img) {
            if (!in_array(basename($img), $used)) {
                @unlink($img);
            }
        }
    }

    public function delete(string $slug): bool
    {
        $path = $this->getPath($slug);
        if (!file_exists($path)) {
            return false;
        }
        $dir = dirname($path);
        $base = basename($path, '.md');
        $images = glob($dir . '/' . $base . '_*.{jpg,jpeg,png,gif}', GLOB_BRACE);
        if (!unlink($path)) {
            return false;
        }
        foreach ($images as $img) {
            @unlink($img);
        }
        return true;
    }
}

