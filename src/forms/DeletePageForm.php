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


class DeletePageForm extends MakeupForm
{

    public function delete()
    {
        $slug = $_SESSION['page_slug'];
        if ($this->pageModel->delete($slug)) {
            $this->pageModel->remove($slug);
            header('Location:/doc.php');
        }
    }

    function folderEmpty($dir) {
        if (!is_readable($dir)) return null;
        return count(scandir($dir)) == 2;
    }

}
