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

use Nette\Forms\Form;
use DocPHT\Core\Translator\T;

class AddSectionForm extends MakeupForm
{

    public function create()
    {
        $slug = $_SESSION['page_slug'];

        $form = new Form;
        $form->onRender[] = [$this, 'bootstrap4'];

        $form->addGroup(T::trans('Add section'));
        $form->addTextArea('markdown', T::trans('Option content'))
            ->setHtmlAttribute('rows', 10)
            ->setRequired(T::trans('Enter content'));

        $form->addProtection(T::trans('Security token has expired, please submit the form again'));
        $form->addSubmit('submit', T::trans('Add'));

        if ($form->isSuccess()) {
            $values = $form->getValues();
            $markdown = $this->pageModel->get($slug) ?? '';
            $markdown = rtrim($markdown) . "\n\n" . $values['markdown'] . "\n";
            if ($this->pageModel->put($slug, $markdown)) {
                header('Location:/page/' . $slug);
                exit;
            }
            $this->msg->error(T::trans("Sorry something didn't work!"), BASE_URL . 'page/add-section');
        }

        return $form;
    }
}
