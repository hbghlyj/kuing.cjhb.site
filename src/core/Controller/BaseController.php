<?php

/**
 * This file is part of the Instant MVC micro-framework project.
 * 
 * @package     Instant MVC micro-framework
 * @author      Valentino Pesce 
 * @link        https://github.com/kenlog
 * @copyright   2019 (c) Valentino Pesce <valentino@iltuobrand.it>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Instant\Core\Controller;

use DocPHT\Form\LoginForm;
use DocPHT\Form\SearchForm;
use DocPHT\Model\PageModel;
use DocPHT\Model\AdminModel;
use Instant\Core\Views\View;
use DocPHT\Core\Translator\T;
use DocPHT\Form\BackupsForms;
use DocPHT\Form\VersionForms;
use DocPHT\Model\SearchModel;
use DocPHT\Model\BackupsModel;
use DocPHT\Model\VersionModel;
use DocPHT\Form\AddSectionForm;
use DocPHT\Form\CreatePageForm;
use DocPHT\Form\DeletePageForm;
use DocPHT\Form\UpdatePageForm;
use DocPHT\Form\UploadLogoForm;
use DocPHT\Core\Session\Session;
use DocPHT\Model\AccessLogModel;
use DocPHT\Form\TranslationsForm;
use DocPHT\Form\VersionSelectForm;
use Plasticbrain\FlashMessages\FlashMessages;
use DocPHT\Model\ChangeLogModel;

class BaseController
{
	protected $view;
	protected $translationsForm;
	protected $createPageForm;
	protected $adminModel;
	protected $addSectionPageForm;
	protected $updatePageForm;
	protected $deletePageForm;
	protected $msg;
	protected $versionForms;
	protected $backupsForms;
	protected $version;
	protected $search;
        protected $pageModel;
        protected $searchModel;
	protected $backupsModel;
	protected $versionModel;
        protected $uploadlogo;
        protected $accessLogModel;
        protected $changeLogModel;
        protected $loginForm;
        protected $session;

	public function __construct()
	{
		$this->view = new View();
		$this->translationsForm = new TranslationsForm();
		$this->createPageForm = new CreatePageForm();
		$this->addSectionPageForm = new AddSectionForm();
		$this->updatePageForm = new UpdatePageForm();
		$this->deletePageForm = new DeletePageForm();
		$this->msg = new FlashMessages();
		$this->versionForms = new VersionForms();
		$this->backupsForms = new BackupsForms();
		$this->version = new VersionSelectForm;
                $this->search = new SearchForm();
		$this->adminModel = new AdminModel();
		$this->pageModel = new PageModel();
                $this->searchModel = new SearchModel();
		$this->backupsModel = new BackupsModel();
		$this->versionModel = new VersionModel();
                $this->uploadlogo = new UploadLogoForm();
                $this->accessLogModel = new AccessLogModel();
                $this->changeLogModel = new ChangeLogModel();
                $this->loginForm = new LoginForm();
                $this->session = new Session();
	}

	public function search()
	{
            $results = $this->search->create();
		if (isset($results)) {
			$this->view->load('Search','search_results.php', ['results' => $results]);
		} else {
			$this->msg->info(T::trans('Search term did not produce results'),$_SERVER['HTTP_REFERER']);
		}
	}

	public function switchTheme()
	{
		if (isset($_COOKIE["theme"]) && $_COOKIE["theme"] == 'dark') {
			setcookie("theme", "light");			
		} else {
			setcookie("theme", "dark");
		}
			header('Location:'.$_SERVER['HTTP_REFERER']);
			exit;
	}

}