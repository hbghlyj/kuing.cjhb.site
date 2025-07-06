<?php
namespace DocPHT\Form;

use Nette\Forms\Form;
use DocPHT\Core\Translator\T;

class UpdatePageForm extends MakeupForm
{
    public function create()
    {
        $slug = $_SESSION['page_slug'];
        $markdown = $this->pageModel->get($slug) ?? '';

        $form = new Form;
        $form->onRender[] = [$this, 'bootstrap4'];
        $form->addGroup(T::trans('Update page'));
        $form->addTextArea('markdown', T::trans('Enter content'))
            ->setHtmlAttribute('rows', 20)
            ->setDefaultValue($markdown);
        $form->addUpload('images', T::trans('Add image from file'))
            ->setHtmlAttribute('multiple', true)
            ->setRequired(false)
            ->addRule(Form::MIME_TYPE, 'File must be JPEG, PNG, GIF or SVG.', ['image/jpeg','image/gif','image/png','image/svg+xml'])
            ->addRule(Form::MAX_FILE_SIZE, 'Maximum file size is 10 mb.', 10 * 1024 * 1024);
        $form->addProtection(T::trans('Security token has expired, please submit the form again'));
        $form->addSubmit('submit', T::trans('Update'));

        if ($form->isSuccess()) {
            $values = $form->getValues();
            if (!empty($_FILES['images']['name'][0])) {
                $this->pageModel->uploadImages($slug, $_FILES['images']);
            }
            if ($this->pageModel->put($slug, $values['markdown'])) {
                $this->pageModel->cleanUnusedImages($slug, $values['markdown']);
                header('Location:/page/' . $slug);
                exit;
            } else {
                $this->msg->error(T::trans("Sorry something didn't work!"), BASE_URL . 'page/update');
            }
        }
        return $form;
    }
}
