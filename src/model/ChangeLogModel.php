<?php
namespace DocPHT\Model;

use DocPHT\Model\BackupsModel;

class ChangeLogModel
{
    const CHANGELOG = 'json/changelog.json';

    public function connect()
    {
        if (!file_exists(self::CHANGELOG)) {
            file_put_contents(self::CHANGELOG, json_encode([]));
        }
        $contents = file_get_contents(self::CHANGELOG);
        if ($contents === false || $contents === '') {
            $contents = '[]';
        }
        return json_decode($contents, true);
    }

    public function disconnect(array $data)
    {
        return file_put_contents(self::CHANGELOG, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public function getLastActor(string $slug): ?string
    {
        $data = $this->connect();
        for ($i = count($data) - 1; $i >= 0; $i--) {
            if ($data[$i]['slug'] === $slug) {
                return $data[$i]['actor'];
            }
        }
        return null;
    }

    public function logAction(string $slug, string $actor, string $action): void
    {
        $last = $this->getLastActor($slug);
        if (in_array($action, ['edit', 'delete']) && $actor !== $last) {
            $backup = new BackupsModel();
            $backup->createBackup();
        }
        $data = $this->connect();
        $data[] = [
            'slug' => $slug,
            'actor' => $actor,
            'action' => $action,
            'date' => date(DATAFORMAT, time())
        ];
        $this->disconnect($data);
    }
}
