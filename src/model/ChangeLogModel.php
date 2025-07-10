<?php
/**
 * This file is part of the DocPHT project.
 */
namespace DocPHT\Model;

class ChangeLogModel
{
    const CHANGELOG = 'json/changelog.json';
    const MAX_LOG_ENTRIES = 1000;

    public function connect()
    {
        if (!file_exists(self::CHANGELOG)) {
            file_put_contents(self::CHANGELOG, json_encode([]));
        }
        return json_decode(file_get_contents(self::CHANGELOG), true);
    }

    public function disconnect($path, $data)
    {
        return file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public function log($slug, $action, $username)
    {
        $data = $this->connect();
        $data[] = [
            'slug' => $slug,
            'username' => $username,
            'action' => $action,
            'date' => date(DATAFORMAT, time())
        ];
        $excess = count($data) - self::MAX_LOG_ENTRIES;
        if ($excess > 0) {
            $data = array_slice($data, $excess);
        }
        $this->disconnect(self::CHANGELOG, $data);
    }

    public function getLastActor($slug)
    {
        $data = array_reverse($this->connect());
        foreach ($data as $entry) {
            if ($entry['slug'] === $slug) {
                return $entry['username'];
            }
        }
        return null;
    }

    public function getLog()
    {
        return $this->connect();
    }
}
