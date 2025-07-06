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
        $form->addTextArea('markdown', T::trans('Enter content'))
            ->setHtmlAttribute('rows', 10)
            ->setRequired(T::trans('Enter content'));

        $form->addUpload('image', T::trans('Add image from file'))
            ->setRequired(false)
            ->addRule(Form::MIME_TYPE, 'File must be JPEG, PNG, GIF or SVG.', ['image/jpeg','image/gif','image/png','image/svg+xml'])
            ->addRule(Form::MAX_FILE_SIZE, 'Maximum file size is 10 mb.', 10 * 1024 * 1024);

        $form->addProtection(T::trans('Security token has expired, please submit the form again'));
        $form->addSubmit('submit', T::trans('Add'));

        if ($form->isSuccess()) {
            $values = $form->getValues();
            $markdown = $this->pageModel->get($slug) ?? '';
            $newContent = $values['markdown'];
            $image = $values['image'];
            if ($image && $image->isOk()) {
                $arr = [
                    'name' => [$image->getName()],
                    'tmp_name' => [$image->getTemporaryFile()],
                    'error' => [$image->getError()]
                ];
                $uploaded = $this->pageModel->uploadImages($slug, $arr);
                if ($uploaded) {
                    $newContent .= "\n![](" . $uploaded[0] . ")";
                }
            }
            $markdown = rtrim($markdown) . "\n" . $newContent . "\n";
            if ($this->pageModel->put($slug, $markdown)) {
                header('Location:/page/' . $slug);
                exit;
            }
            $this->msg->error(T::trans("Sorry something didn't work!"), BASE_URL . 'page/add-section');
        }

        return $form;
    }
}
