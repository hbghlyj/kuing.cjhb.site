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

namespace Instant\Core\Views;

use DocPHT\Model\PageModel;
use DocPHT\Core\Translator\T;
use DocPHT\Model\BackupsModel;
use DocPHT\Form\VersionSelectForm;
use Plasticbrain\FlashMessages\FlashMessages;

class View 
{
	protected $pageModel;
	protected $backupsModel;
        protected $version;
	protected $msg;

	public function __construct()
	{
                $this->pageModel = new PageModel();
                $this->backupsModel = new BackupsModel();
                $this->version = new VersionSelectForm();
                $this->msg = new FlashMessages();
	}

	public function show($file, $data = null)
	{
                $lang = T::detectLang();
                $t = T::getTranslator($lang);
		
		if (is_array($data))
		{
			extract($data);
		}
		include 'src/views/'.$file;
	}

	public function load(string $title, string $path, array $viewdata = null)
	{
		$data = ['PageTitle' => T::trans($title)];
		$this->show('partial/head.php',$data);
		$this->show($path, $viewdata);
		$this->show('partial/footer.php');
	}
}