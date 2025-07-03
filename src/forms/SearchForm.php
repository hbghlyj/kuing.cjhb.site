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

            $json = json_decode(file_get_contents('json/search.json'), TRUE);
                    
                    foreach($json as $value)  {
                        $id = $value['id'];
                        $value = $value['content'];
                        if(!empty($searchthis) && preg_match("#($searchthis)#", strtolower($value)))  {

                            similar_text($searchthis, $value, $perc);
                    
                            if (strlen($value) > 500)
                            $value = substr($value, 0, 100) . '...';

                            $pages = $this->pageModel->connect();
                            foreach ($pages as $val) {
                                if ($val['pages']['id'] == $id) {
                                    $link = ($val['pages']['home'] === 1 && !isset($_SESSION['Active'])) ? '/doc.php' : 'page/'.$this->pageModel->getSlug($id);
                                    $found[] =  array(
                                            'content' => '<div class="result-preview">'
                                                    . '<a href="'.$link.'">'
                                                        . '<h3 class="result-title">'
                                                            .$this->pageModel->getTopic($id).' '.$this->pageModel->getFilename($id).'
                                                        </h3>'
                                                        . '<p class="result-subtitle">'
                                                            .$value
                                                        . '</p>'
                                                        . '<small class="badge badge-success">'.T::trans('similarity').': '.round($perc, 1).'%</small>'
                                                    . '</a>'
                                                . '</div>'
                                                . '<hr>',
                                            'perc' => $perc
                                        );
                                }
                            }

                        }
                    }
                    if(!empty($found))
                    usort($found, function($a, $b) {
                        return [$b['perc']] <=> [$a['perc']];
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
