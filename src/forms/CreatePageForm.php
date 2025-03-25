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
use Nette\Utils\Html;
use DocPHT\Core\Translator\T;

class CreatePageForm extends MakeupForm
{
    

    public function create()
    {
        
        $languages = $this->doc->listCodeLanguages();
        $options = $this->doc->getOptions();

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
        	
        $dataList = Html::el('datalist id="topicList"');
        
        if (is_array($getTopic)) {
            foreach ($getTopic as $value) {
                $dataList->create('option value="'.str_replace('-',' ',$value).'"');
            }
            echo $dataList;
        }
        
        
        $form->addText('mainfilename', T::trans('Page name'))
        ->setDefaultValue(isset($_GET['mainfilename']) ? $_GET['mainfilename'] : '')
        ->setHtmlAttribute('placeholder', T::trans('Enter page name'))
        ->setRequired(T::trans('Enter page name'));
        
        $form->addTextarea('description', T::trans('Description'))
            ->setHtmlAttribute('placeholder', T::trans('Enter description'))
            ->setHtmlAttribute('rows', 8)
        	->setAttribute('data-autoresize');
        
        $form->addProtection(T::trans('Security token has expired, please submit the form again'));
        
        $form->addSubmit('submit', T::trans('Create'));
        
        if ($form->isSuccess()) {
            $values = $form->getValues();
        
        	if (isset($values['topic']) && isset($values['mainfilename'])) {
                
                $id = $this->pageModel->create($values['topic'],$values['mainfilename']);
                
        	    if(isset($id)) {
            	    $this->pageModel->addPageData($id, $this->doc->valuesToArray(array('options' => 'title', 'option_content' => $values['mainfilename'])));
                    $this->pageModel->addPageData($id, $this->doc->valuesToArray(array('options' => 'markdown', 'option_content' => $values['description'])));
            	    $this->doc->buildPhpPage($id);
        
                    header('Location:'.$this->pageModel->getTopic($id).'/'.$this->pageModel->getFilename($id));
        			exit;
        	    } else {
                    $this->msg->error(T::trans('Sorry something didn\'t work!'),BASE_URL.'page/create');
        	    }
        	}
        }
        return $form;
    }
}

