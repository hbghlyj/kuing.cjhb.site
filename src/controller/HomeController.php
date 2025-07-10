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

class HomeController extends BaseController
{
    
        public function index()
        {
                $changes = $this->changeLogModel->getLog();
                $this->view->show('partial/head.php', ['PageTitle' => T::trans('TITLE')]);
                $this->view->show('home.php', ['changes' => $changes]);
                $this->view->show('partial/footer.php');
        }

}