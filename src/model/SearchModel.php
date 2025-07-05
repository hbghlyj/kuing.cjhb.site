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
    
    public function add($slug, $content)
    {
        return [
                    'slug' => $slug,
                    'content' => $content
                ];
    }
}