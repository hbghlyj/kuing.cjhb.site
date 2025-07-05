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
        $form->addProtection(T::trans('Security token has expired, please submit the form again'));
        $form->addSubmit('submit', T::trans('Update'));

        if ($form->isSuccess()) {
            $values = $form->getValues();
            $this->pageModel->put($slug, $values['markdown']);
            header('Location:/page/' . $slug);
            exit;
        }
        return $form;
    }
}
