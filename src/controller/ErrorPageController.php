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

namespace DocPHT\Controller;

use DocPHT\Core\Translator\T;
use Instant\Core\Controller\BaseController;

class ErrorPageController extends BaseController
{

    public function getPage($slug = null)
    {
        http_response_code(404);
        if ($slug) {
            [$topic, $filename] = explode('/', $slug, 2);
            $this->view->load('Page not found', 'suggest_create_page.php', [
                'topic' => $topic,
                'filename' => $filename
            ]);
        } else {
            $this->view->load('Page not found', 'error_page.php');
        }
    }

}
