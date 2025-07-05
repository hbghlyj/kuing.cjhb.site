<?php

/**
 * This file is part of the DocPHT project.
 * 
 * @author Valentino Pesce
 * @copyright (c) Valentino Pesce <valentino@iltuobrand.it>
 * @copyright (c) Craig Crosby <creecros@gmail.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DocPHT\Form;

use DocPHT\Core\Translator\T;

class SearchForm extends MakeupForm
{
    public function create()
    {
        $searchthis = '';
        $found = [];

        if(!empty($_POST["search"])) {
            $searchthis = $this->sanitizing($_POST["search"]);

            $pages = $this->pageModel->connect();
            $cmd = "grep -R -o -i --include='*.md' " . escapeshellarg($searchthis) . " flat | cut -d: -f1 | sort | uniq -c | sort -nr";
            $output = shell_exec($cmd);
            $lines = array_filter(explode("\n", trim($output)));

            foreach ($lines as $line) {
                if (preg_match('/^\s*(\d+)\s+(.*)$/', $line, $matches)) {
                    $count = (int)$matches[1];
                    $file = $matches[2];
                    $slug = substr($file, 5); // remove 'flat/' prefix
                    $slug = substr($slug, 0, -3); // remove '.md'

                    foreach ($pages as $val) {
                        if ($val['pages']['slug'] == $slug) {
                            $found[] = [
                                'content' => '<div class="result-preview">'
                                    . '<a href="/page/'.$slug.'">'
                                        . '<h3 class="result-title">'
                                            .$this->pageModel->getTopic($slug).' '.$this->pageModel->getFilename($slug).'
                                        </h3>'
                                        . '<small class="badge badge-success">'.T::trans('Matches found').': '.$count.'</small>'
                                    . '</a>'
                                . '</div>'
                                . '<hr>',
                                'count' => $count
                            ];
                        }
                    }
                }
            }

            if(!empty($found))
            usort($found, function($a, $b) {
                return [$b['count']] <=> [$a['count']];
            });
            $found = array_column($found, 'content');
            $found = array_unique($found);

            if(!empty($found)) { return implode($found); }
        } else {
            header('Location:/doc.php');
            exit;
        }
    }

    public function filter($file)
    {
        $exclude = ['doc-pht','pages.json'];
        return ! in_array($file->getFilename(), $exclude);
    }

    public function sanitizing($data) 
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = strtolower($data);
        $data = strip_tags($data);
        $data = htmlspecialchars($data);
        return $data;
    }
}
