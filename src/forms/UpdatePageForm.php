<?php
namespace DocPHT\Form;

use Nette\Forms\Form;
use DocPHT\Core\Translator\T;
use DocPHT\Core\Helper\AnchorHelper;

class UpdatePageForm extends MakeupForm
{
    private function splitSection(string $markdown, string $anchor)
    {
        if ($anchor === '') {
            return [$markdown, '', ''];
        }

        preg_match_all('/^(#{1,6})\s*(.+)$/m', $markdown, $matches, PREG_OFFSET_CAPTURE);
        $anchors = [];
        $sections = [];
        foreach ($matches[0] as $i => $full) {
            $level = strlen($matches[1][$i][0]);
            $title = $matches[2][$i][0];
            $an = AnchorHelper::generate($title, $anchors);
            $sections[] = ['anchor' => $an, 'level' => $level, 'offset' => $full[1]];
        }

        $len = strlen($markdown);
        foreach ($sections as $i => $sec) {
            if ($sec['anchor'] !== $anchor) {
                continue;
            }
            $start = $sec['offset'];
            $end = $len;
            for ($j = $i + 1; $j < count($sections); $j++) {
                if ($sections[$j]['level'] <= $sec['level']) {
                    $end = $sections[$j]['offset'];
                    break;
                }
            }
            $before = substr($markdown, 0, $start);
            $section = substr($markdown, $start, $end - $start);
            $after = substr($markdown, $end);
            return [$section, $before, $after];
        }

        return [$markdown, '', ''];
    }

    public function create()
    {
        $slug = $_SESSION['page_slug'];
        $section = $_GET['section'] ?? '';
        $markdown = $this->pageModel->get($slug) ?? '';
        list($editText) = $this->splitSection($markdown, $section);

        $form = new Form;
        $form->onRender[] = [$this, 'bootstrap4'];
        $form->addGroup(T::trans('Update page'));
        $form->addTextArea('markdown', T::trans('Enter content'))
            ->setHtmlAttribute('rows', 20)
            ->setDefaultValue($editText);
        $form->addUpload('image', T::trans('Add image from file'))
            ->setRequired(false)
            ->addRule(Form::MIME_TYPE, 'File must be JPEG, PNG, GIF or SVG.', ['image/jpeg','image/gif','image/png','image/svg+xml'])
            ->addRule(Form::MAX_FILE_SIZE, 'Maximum file size is 10 mb.', 10 * 1024 * 1024);
        $form->addProtection(T::trans('Security token has expired, please submit the form again'));
        $form->addSubmit('submit', T::trans('Update'));

        if ($form->isSuccess()) {
            $values = $form->getValues();
            $current = $this->pageModel->get($slug) ?? '';
            if ($section !== '') {
                list(, $before, $after) = $this->splitSection($current, $section);
                $newMarkdown = $before . $values['markdown'] . $after;
            } else {
                $newMarkdown = $values['markdown'];
            }

            if ($this->pageModel->put($slug, $newMarkdown)) {
                $this->pageModel->cleanUnusedImages($slug, $newMarkdown);
                $location = '/page/' . $slug;
                if ($section !== '') {
                    $location .= '#' . rawurlencode($section);
                }
                header('Location:' . $location);
                exit;
            } else {
                $this->msg->error(T::trans("Sorry something didn't work!"), BASE_URL . 'page/update');
            }
        }
        return $form;
    }
}
