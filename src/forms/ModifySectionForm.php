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

class ModifySectionForm extends MakeupForm
{

    public function create()
    {
        $slug = $_SESSION['page_slug'];
        $options = $this->doc->getOptions();

        $form = new Form;
        $form->onRender[] = [$this, 'bootstrap4'];

        $page = $this->pageModel->getPageData($slug);

        if(isset($_GET['id'])) {
            $rowIndex = $_GET['id'];
        }

        $form->addGroup(T::trans('Modify section'));
        
        if ($page[$rowIndex]['key'] != 'addButton') {
            
            $form->addSelect('options', T::trans('Options:'), $options)
            	->setPrompt(T::trans('Select an option'))
            	->setHtmlAttribute('data-live-search','true')
            	->setDefaultValue($page[$rowIndex]['key'])
            	->setRequired(T::trans('Select an option'));
            	
            

            
            $form->addUpload('file', 'File:')
                ->setRequired(false)
                ->addRule(Form::MIME_TYPE, 'File must be JPEG, PNG, GIF or SVG.', ['image/jpeg','image/gif','image/png','image/svg+xml'])
                        ->addRule(Form::MAX_FILE_SIZE, 'Maximum file size is 10 mb.', 10 * 1024 * 1024 /* size in MB */);
            	
            $form->addTextArea('option_content', T::trans('Option content'))
                ->setHtmlAttribute('rows', 10)
                ->setHtmlAttribute('data-parent', 'options')
                ->setAttribute('data-autoresize'); 
            	
            if ($page[$rowIndex]['key'] == 'image') {
                $form['option_content']->setDefaultValue($page[$rowIndex]['v2']); 
            } else {
                $form['option_content']->setDefaultValue($page[$rowIndex]['v1']);
            }
            

                	
        	
        } 

        $form->addProtection(T::trans('Security token has expired, please submit the form again'));
      
        $form->addSubmit('submit', T::trans('Modify'));

        if ($form->isSuccess()) {
            $values = $form->getValues();
            
            if ($page[$rowIndex]['key'] == 'image' && ($values['options'] != 'image' || $values['file']->hasFile())) {
                if (file_exists('json/' . $page[$rowIndex]['v1'])) {
                    unlink('json/' . $page[$rowIndex]['v1']);
                }
            }
        
        	if (!empty($values)) {
        	    
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
                    $this->pageModel->modifyPageData($slug, $rowIndex, $data);
                    header('Location:'.$this->pageModel->getTopic($slug).'/'.$this->pageModel->getFilename($slug));
        			exit;
        	    } else {
    				$this->msg->error(T::trans('Sorry something didn\'t work!'));
        	    }
        	}   
        }
        return $form;
    }
}