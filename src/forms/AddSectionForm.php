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
        $options = $this->doc->getOptions();

        $form = new Form;
        $form->onRender[] = [$this, 'bootstrap4'];

        $form->addGroup(T::trans('Add section'));
        
        
        $form->addSelect('options', T::trans('Options:'), $options)
        	->setPrompt(T::trans('Select an option'))
            ->setDefaultValue('markdown')
        	->setHtmlAttribute('data-live-search','true')
        	->setRequired(T::trans('Select an option'));
        	
        $form->addUpload('file', 'File:')
            ->setRequired(false)
            ->addRule(Form::MIME_TYPE, 'File must be JPEG, PNG, GIF or SVG.', ['image/jpeg','image/gif','image/png','image/svg+xml'])
                ->addRule(Form::MAX_FILE_SIZE, 'Maximum file size is 10 mb.', 10 * 1024 * 1024 /* size in MB */);
        	
        $form->addTextArea('option_content', T::trans('Option content'))
        	->setHtmlAttribute('placeholder', T::trans('Enter content'))
        	->setHtmlAttribute('data-parent', 'options'); 
        	
        $form->addProtection(T::trans('Security token has expired, please submit the form again'));
        
        $form->addSubmit('submit', T::trans('Add'));
        

        if ($form->isSuccess()) {
            $values = $form->getValues();
            
        	if (isset($values['options']) && isset($values['option_content'])) {
        	    
                $file = $values['file'];
                $file_path = $this->doc->upload($file, $this->pageModel->getPhpPath($slug));

                    if(isset($slug)) {
                    $data = ['key' => $values['options'], 'v1' => '', 'v2' => ''];
                    switch ($values['options']) {
                        case 'title':
                        case 'markdown':
                            $data['v1'] = $values['option_content'];
                            break;
                        case 'image':
                            $data['v1'] = substr($file_path, 5);
                            $data['v2'] = $values['option_content'];
                            break;
                    }
                    $this->pageModel->addPageData($slug, $data);
                    header('Location:'.$this->pageModel->getTopic($slug).'/'.$this->pageModel->getFilename($slug));
        			exit;
        	    } else {
    				$this->msg->error(T::trans('Sorry something didn\'t work!'),BASE_URL.'page/add-section');
        	    }
        	}
        }
        
        return $form;
    }
}
