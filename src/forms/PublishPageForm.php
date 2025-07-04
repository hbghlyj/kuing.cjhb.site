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


class PublishPageForm extends MakeupForm
{
    public function publish()
    {
        $slug = $_SESSION['page_slug'];
        $pages = $this->pageModel->connect();

        foreach ($pages as $key => $value) {

            if ($value['pages']['slug'] === $slug) {
                if ($value['pages']['published'] === 0 && $value['pages']['home'] !== 1) {
                    $published = 1;
                } else {
                    $published = 0;
                }
                $pages[$key] = array(
                    'pages' => [
                        'slug' => $value['pages']['slug'],
                        'topic' => $value['pages']['topic'],
                        'filename' => $value['pages']['filename'],
                        'published' => $published,
                        'home' => $value['pages']['home']
                    ]
                );
            }

            $this->pageModel->disconnect('json/pages.json', $pages);
        }
    
        
        header('Location:'.$this->pageModel->getTopic($slug).'/'.$this->pageModel->getFilename($slug));
        exit;
    }
}
