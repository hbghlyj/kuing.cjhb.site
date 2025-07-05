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

class UpdatePageForm extends MakeupForm
{

    public function create()
    {

        $slug = $_SESSION['page_slug'];
        $options = $this->doc->getOptions();

        $form = new Form;
        $form->onRender[] = [$this, 'bootstrap4'];

        $page = $this->pageModel->getPageData($slug);

        $index = 0;
        
        $form->addGroup(T::trans('Update page'));
        
        foreach ($page as $fields) {
            
                $form->addSelect('options'.$index, T::trans('Options:'), $options)
                	->setPrompt(T::trans('Select an option'))
                	->setHtmlAttribute('data-live-search','true')
                	->setDefaultValue($fields['key'])
                	->setRequired(T::trans('Select an option'));
                	
                $form->addUpload('file'.$index, 'File:')
                    ->setRequired(false)
                    ->addRule(Form::MIME_TYPE, 'File must be JPEG, PNG, GIF or SVG.', ['image/jpeg','image/gif','image/png','image/svg+xml'])
                    ->addRule(Form::MAX_FILE_SIZE, 'Maximum file size is 10 mb.', 10 * 1024 * 1024 /* size in MB */);
                	
                if (isset($fields['v1']) && $fields['key'] != 'image') { $oc = $fields['v1']; } else { $oc = $fields['v2']; }
                
                $form->addTextArea('option_content'.$index, T::trans('Option content'))
                    ->setHtmlAttribute('data-parent', 'options'.$index)
                    ->setAttribute('data-autoresize')
                	->setDefaultValue($oc); 
                	
                if ($fields['key'] == 'image') {
                    $form['option_content'.$index]->setDefaultValue($fields['v2']); 
                } else {
                    $form['option_content'.$index]->setDefaultValue($fields['v1']);
                }
                

        $index++;
        	
        }
        
        $form->addProtection(T::trans('Security token has expired, please submit the form again'));
      
        $form->addSubmit('submit', T::trans('Update'));
        
        if ($form->isSuccess()) {
            $values = $form->getValues();
            
            for ($x=0; $x < $index; $x++) {
                
                $mapped = array(
                            'options'       => (isset($values['options'.$x])) ? $values['options'.$x] : '',
                            'option_content'=> (isset($values['option_content'.$x])) ? $values['option_content'.$x] : '',
                            'file'          => ($values['file'.$x]->hasFile()) ? $values['file'.$x] : $page[$x]['v1']
                            );
            
                
                if($mapped['options'] == 'image' && $values['file'.$x]->hasFile()) {
                    $file = $mapped['file'];
                    $file_path = $this->doc->upload($file, $this->pageModel->getPhpPath($slug));
                } else {
                    unset($mapped['file']);
                    $file_path = ($mapped['options'] != 'addButton') ? 'json/'.$page[$x]['v1'] : '';
                }
                
                if (isset($page[$x]['v1']) && $page[$x]['key'] == 'image' && ($mapped['options'] != 'image' || $values['file'.$x]->hasFile())) {
                    if (file_exists('json/' . $page[$x]['v1'])) {
                        unlink('json/' . $page[$x]['v1']);
                    }
                }
        
                    if(isset($slug)) {
                            $data = ['key' => $mapped['options'], 'v1' => '', 'v2' => ''];
                            switch ($mapped['options']) {
                                case 'title':
                                case 'markdown':
                                    $data['v1'] = $mapped['option_content'];
                                    break;
                                case 'image':
                                    $data['v1'] = substr($file_path, 5);
                                    $data['v2'] = $mapped['option_content'];
                                    break;
                            }
                            $this->pageModel->modifyPageData($slug, $x, $data);
            	    }
            }
            header('Location:'.$this->pageModel->getTopic($slug).'/'.$this->pageModel->getFilename($slug));
            exit;
        } elseif (!isset($slug)) {
            $this->msg->error(T::trans('Sorry something didn\'t work!'));
        }

        return $form;
    }
}