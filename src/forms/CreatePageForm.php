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
            ->setHtmlAttribute('placeholder', T::trans('Enter topic'))
            ->setAttribute('list', 'topicList')
            ->setAttribute('autocomplete', 'off')
            ->setRequired(T::trans('Topic is required.'))
            ->setAttribute('onkeyup', 'this.value = this.value.toLowerCase();')
            ->setAttribute('pattern','[a-z0-9]+(?:-[a-z0-9]+)*')
            ->addRule(Form::PATTERN, T::trans('Must be alphanumeric and lowercase, use a hyphen for spaces.'), '[a-z0-9]+(?:-[a-z0-9]+)*')
            ->setDefaultValue(isset($_GET['topic']) ? htmlspecialchars($_GET['topic'], ENT_QUOTES, 'UTF-8') : '');

        $dataList = Html::el('datalist')->addAttributes(['id' => 'topicList']);
        if (is_array($getTopic)) {
            foreach ($getTopic as $value) {
                $dataList->create('option')->addAttributes(['value' => str_replace('-', ' ', $value)]);
            }
        }

        $form->addText('filename', T::trans('Filename'))
            ->setHtmlAttribute('placeholder', T::trans('Enter filename'))
            ->setRequired(T::trans('Filename is required.'))
            ->setAttribute('onkeyup', 'this.value = this.value.toLowerCase();')
            ->setAttribute('pattern','[a-z0-9]+(?:-[a-z0-9]+)*')
            ->addRule(Form::PATTERN, T::trans('Must be alphanumeric and lowercase, use a hyphen for spaces.'), '[a-z0-9]+(?:-[a-z0-9]+)*')
            ->setDefaultValue(isset($_GET['filename']) ? htmlspecialchars($_GET['filename'], ENT_QUOTES, 'UTF-8') : '');

        $form->addText('title', T::trans('Title'))
            ->setHtmlAttribute('placeholder', T::trans('Enter title'))
            ->setRequired(T::trans('Title is required.'))
            ->setDefaultValue(isset($_GET['title']) ? htmlspecialchars($_GET['title'], ENT_QUOTES, 'UTF-8') : '');


        $form->addUpload('file', T::trans('Add an image or a code file'))
            ->setRequired(false)
            ->addRule(Form::MIME_TYPE, T::trans('Not a valid file.'), [
                'image/gif', 'image/png', 'image/jpeg', 'image/svg+xml',
                'application/zip', 'application/x-rar-compressed', 'application/octet-stream',
                'text/x-c', 'text/x-c++', 'text/x-c-header', 'text/x-c-source',
                'text/x-d', 'text/x-pascal', 'text/x-fortran', 'text/x-asm', 'text/x-java-source',
                'text/x-lisp', 'text/x-python', 'text/x-h', 'text/x-php', 'text/x-shellscript',
                'application/json', 'application/xml', 'application/javascript', 'application/x-httpd-php',
                'text/css', 'text/html', 'text/csv', 'text/markdown'
            ])
            ->addRule(Form::MAX_FILE_SIZE, T::trans('Maximum file size is 10 mb.'), 10 * 1024 * 1024);

        $form->addProtection(T::trans('Security token has expired, please submit the form again'));

        $form->addSubmit('submit', T::trans('Create'));

        if ($form->isSuccess()) {
            $values = $form->getValues();
            $id = $this->pageModel->create($values['topic'], $values['filename']);
            $ok = true;
            $ok = $ok && $this->pageModel->addPageData(
                $id,
                ['key' => 'title', 'v1' => $values['title'], 'v2' => '']
            );

            $file = $values['file'];
            if ($file instanceof \Nette\Http\FileUpload && $file->isOk()) {
                $filePath = $this->doc->upload($file, $this->pageModel->getPhpPath($id));
                if ($filePath) {
                    $mime = $file->getContentType();
                    $option = 'image';
                    $ok = $ok && $this->pageModel->addPageData(
                        $id,
                        ['key' => $option, 'v1' => substr($filePath, 5), 'v2' => '']
                    );
                } else {
                    $ok = false;
                }
            }

            if ($ok) {
                $this->doc->buildPhpPage($id);
                header('Location:'.$this->pageModel->getTopic($id).'/'.$this->pageModel->getFilename($id));
                exit;
            }

            // Roll back the partially created page to avoid orphaned data
            if (isset($filePath) && $filePath && file_exists($filePath)) {
                unlink($filePath);
            }

            $json = $this->pageModel->getJsonPath($id);
            if ($json && file_exists($json)) {
                unlink($json);
            }

            $jsonDir = dirname($json);
            if ($this->folderEmpty($jsonDir)) {
                rmdir($jsonDir);
            }
            $phpDir = dirname($this->pageModel->getPhpPath($id));
            if ($this->folderEmpty($phpDir)) {
                rmdir($phpDir);
            }

            $this->pageModel->remove($id);

            $this->msg->error(T::trans('Sorry something didn\'t work!'), BASE_URL.'page/create');
        }
        return [
            'form' => (string) $form,
            'dataList' => (string) $dataList,
        ];
    }

    private function folderEmpty($dir)
    {
        return is_readable($dir) ? count(scandir($dir)) === 2 : false;
    }
}


