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
 *
 *
 */
namespace DocPHT\Model;

use DocPHT\Lib\DocBuilder;
use DocPHT\Model\PageModel;

class SearchModel extends PageModel
{
    public function feed()
    {
        $data = $this->getAllIndexed();
        $array = [];
        if($data !== false){
            foreach ($data as $value) {
                if(!empty($value['slug']) && !empty($value['topic'])) {
                    array_push($array, $this->add($value['slug'], $value['topic']));
                    array_push($array, $this->add($value['slug'], $value['filename']));
                }
                foreach($this->getPageData($value['slug']) as $page) {
                    for ($i = 1; $i <= 4; $i++) {
                        if (!empty($page["v$i"])) {
                            array_push($array, $this->add($value['slug'], $page["v$i"]));
                        }
                    }
                }
            }
        }
        $this->disconnect('json/search.json',$array);
    }
    
    public function add($slug, $content)
    {
        return [
                    'slug' => $slug,
                    'content' => $content
                ];
    }
}