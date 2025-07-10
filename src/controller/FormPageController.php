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

namespace DocPHT\Controller;

use Instant\Core\Controller\BaseController;

class FormPageController extends BaseController
{

        public function getCreatePageForm()
        {
                $formData = $this->createPageForm->create();
                $this->view->load('Create new page', 'form-page/create_page.php', $formData);
        }

        public function getPage($slug)
        {
                $_SESSION['page_slug'] = $slug;
                $markdown = $this->pageModel->get($slug);
                if ($markdown === null) {
                        $error = new ErrorPageController();
                        $error->getPage($slug);
                        return;
                }

                $parsedown = new \DocPHT\Lib\MediaWikiParsedown();
                $htmlContent = $parsedown->text($markdown);

                $parts = preg_split('/(<h[1-6][^>]*>.*?<\\/h[1-6]>)/i', $htmlContent, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

                $segments = [];
                $current = '';
                $anchors = [];
                foreach ($parts as $part) {
                        if (preg_match('/^<h([1-6])[^>]*>(.*?)<\\/h[1-6]>$/is', trim($part), $m)) {
                                if ($current !== '') {
                                        $segments[] = ['type' => 'markdown', 'text' => $current];
                                        $current = '';
                                }
                                $level = (int) $m[1];
                                $titleText = strip_tags($m[2]);
                                $baseAnchor = preg_replace('/[ %\/#]/', '-', strtolower($titleText));
                                $anchor = $baseAnchor;
                                $counter = 2;
                                while (in_array($anchor, $anchors, true)) {
                                        $anchor = $baseAnchor . '-' . $counter++;
                                }
                                $segments[] = ['type' => 'title', 'text' => $titleText, 'anchor' => $anchor, 'level' => $level];
                                $anchors[] = $anchor;
                        } else {
                                $current .= $part;
                        }
                }
                if ($current !== '') {
                        $segments[] = ['type' => 'markdown', 'text' => $current];
                }

                $this->view->show('partial/head.php', ['PageTitle' => htmlspecialchars($slug, ENT_QUOTES, 'UTF-8')]);
                $html = new \DocPHT\Lib\DocPHT($anchors);
                $values = [];
                foreach ($segments as $segment) {
                        if ($segment['type'] === 'title') {
                                $values[] = $html->title($segment['text'], $segment['anchor'], $segment['level']);
                        } else {
                                $values[] = $html->markdown($segment['text']);
                        }
                }
                $values[] = $html->addButton();

                $this->view->show('page/page.php', ['values' => $values]);
                $this->view->show('partial/footer.php');
        }

	public function getAddSectionForm()
	{
		$form = $this->addSectionPageForm->create();
		$this->view->load('Add section','form-page/add_section.php', ['form' => $form]);
                echo '<script src="/public/assets/js/add-section-upload.js"></script>';
	}

	public function getUpdatePageForm()
	{
		$form = $this->updatePageForm->create();
		$this->view->load('Update page','form-page/update_page.php', ['form' => $form]);
                echo '<script src="/public/assets/js/add-section-upload.js"></script>';
	}
	
	public function getInsertSectionForm()
	{
		$form = $this->insertSectionForm->create();
		$this->view->load('Insert section','form-page/insert_section.php', ['form' => $form]);
	}
	
	public function getModifySectionForm()
	{
		$form = $this->modifySectionForm->create();
		$this->view->load('Modify section','form-page/modify_section.php', ['form' => $form]);
	}
	
	public function getRemoveSectionForm()
	{
		$form = $this->removeSectionForm->create();
	}
	
	
	public function getDeletePageForm()
	{
		$form = $this->deletePageForm->delete();
	}
	
	public function getImportVersionForm()
	{
		$form = $this->versionForms->import();
		$this->view->load('Import version','form-page/import_version.php', ['form' => $form]);
	}
	
	public function getExportVersionForm()
	{
		$form = $this->versionForms->export();
	}
	
	public function getRestoreVersionForm()
	{
		$form = $this->versionForms->restore();
	}
	
	public function getDeleteVersionForm()
	{
		$form = $this->versionForms->delete();
	}
	
        public function getSaveVersionForm()
        {
                $form = $this->versionForms->save();
        }

        public function uploadImage()
        {
                header('Content-Type: application/json');
                $slug = $_SESSION['page_slug'] ?? null;
                if (!$slug || empty($_FILES['image'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'No file']);
                        return;
                }

                $arr = [
                        'name' => [$_FILES['image']['name']],
                        'tmp_name' => [$_FILES['image']['tmp_name']],
                        'error' => [$_FILES['image']['error']]
                ];

                $uploaded = $this->pageModel->uploadImages($slug, $arr);
                if ($uploaded) {
                        echo json_encode(['path' => $uploaded[0]]);
                } else {
                        http_response_code(400);
                        echo json_encode(['error' => $_FILES['image']['error']]);
                }
        }

}
