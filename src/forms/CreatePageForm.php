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
use Nette\Forms\Form;
use Nette\Utils\Html;

class CreatePageForm extends MakeupForm
{

    /**
     * Build the create page form.
     *
     * @return array<string, string> Form HTML and datalist markup
     */
    public function create()
    {
        $form = new Form;
        $form->onRender[] = [$this, 'bootstrap4'];

        $form->addGroup(T::trans('Create new page'));

        $getTopic = $this->pageModel->getUniqTopics();

        $form->addText('topic', T::trans('Topic'))
            ->setDefaultValue(isset($_GET['topic']) ? $_GET['topic'] : '')
            ->setHtmlAttribute('placeholder', T::trans('Enter topic'))
            ->setAttribute('list', 'topicList')
            ->setAttribute('autocomplete', 'off')
            ->setRequired(T::trans('Enter topic'));

        $dataList = Html::el('datalist')->addAttributes(['id' => 'topicList']);
        if (is_array($getTopic)) {
            foreach ($getTopic as $value) {
                $dataList->create('option')->addAttributes(['value' => $value]);
            }
        }

        $form->addText('filename', T::trans('Page name'))
            ->setDefaultValue(isset($_GET['filename']) ? $_GET['filename'] : '')
            ->setHtmlAttribute('placeholder', T::trans('Enter page name'))
            ->setAttribute('autocomplete', 'off')
            ->setRequired(T::trans('Enter page name'));

        $form->addProtection(T::trans('Security token has expired, please submit the form again'));

        $form->addSubmit('submit', T::trans('Create'));

        if ($form->isSuccess()) {
            $values = $form->getValues();
            if ($this->pageModel->create($values['topic'], $values['filename'])) {
                $this->msg->success(T::trans('Created page %topic%/%filename% successfully!', [
                    '%topic%' => $values['topic'],
                    '%filename%' => $values['filename']
                ]), BASE_URL . 'page/' . $values['topic'] . '/' . $values['filename']);
            } else {
                $this->msg->error(T::trans('There is a file with the same name!'), BASE_URL . 'page/' . $values['topic'] . '/' . $values['filename']);
            }
        }
        return [
            'form' => (string) $form,
            'dataList' => (string) $dataList,
        ];
    }
}


