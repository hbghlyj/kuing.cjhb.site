<?php
namespace DocPHT\Form;

use Nette\Forms\Form;
use DocPHT\Core\Translator\T;

class UpdatePageForm extends MakeupForm
{
    public function create(?string $anchor = null)
    {
        $slug = $_SESSION['page_slug'];
        $markdown = $this->pageModel->get($slug) ?? '';
        $chunkbefore = '';
        $chunkafter = '';
        if ($anchor) {
            list($chunkbefore, $markdown, $chunkafter) = $this->pageModel->splitSection($markdown, $anchor);
        }

        $form = new Form;
        $form->onRender[] = [$this, 'bootstrap4'];
        $form->addGroup(T::trans('Update page'));
        $form->addTextArea('markdown', T::trans('Enter content'))
            ->setHtmlAttribute('rows', 20)
            ->setDefaultValue($markdown);
        if ($anchor) {
            $form->addHidden('chunkbefore')->setDefaultValue($chunkbefore);
            $form->addHidden('chunkafter')->setDefaultValue($chunkafter);
        }
        $form->addUpload('image', T::trans('Add image from file'))
            ->setRequired(false)
            ->addRule(Form::MIME_TYPE, 'File must be JPEG, PNG, GIF or SVG.', ['image/jpeg','image/gif','image/png','image/svg+xml'])
            ->addRule(Form::MAX_FILE_SIZE, 'Maximum file size is 10 mb.', 10 * 1024 * 1024);
        $form->addProtection(T::trans('Security token has expired, please submit the form again'));
        $form->addSubmit('submit', T::trans('Update'));

        if ($form->isSuccess()) {
            $values = $form->getValues();
            $content = $values['markdown'];
            if ($anchor) {
                $content = $values['chunkbefore'] . $content . $values['chunkafter'];
            }
            if ($this->pageModel->put($slug, $content)) {
                $this->pageModel->cleanUnusedImages($slug, $content);
                header('Location:/page/' . $slug);
                exit;
            } else {
                $this->msg->error(T::trans("Sorry something didn't work!"), BASE_URL . 'page/update');
            }
        }
        return $form;
    }
}
