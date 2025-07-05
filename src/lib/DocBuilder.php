<?php declare(strict_types=1);

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

namespace DocPHT\Lib;

use DocPHT\Model\PageModel;
use DocPHT\Core\Translator\T;
use Instant\Core\Helper\TextHelper;
use Plasticbrain\FlashMessages\FlashMessages;

class DocBuilder 
{
    
    protected $pageModel;
    protected $msg;

	public function __construct()
	{
        $this->pageModel = new PageModel();
        $this->msg = new FlashMessages();
	}
    /**
     * jsonSwitch
     *
     * @param array $jsonVals
     *
     * @return string
     */
    public function jsonSwitch($jsonVals)
    {
        if (isset($jsonVals['key'])) {
            switch ($jsonVals['key']) {
                case 'title':
                    $option = $this->title($jsonVals['v1'], $jsonVals['v1']);
                    break;
                case 'image':
                    $option = $this->image($jsonVals['v1'], $jsonVals['v2']);
                    break;
                case 'markdown':
                    $option = $this->markdown($jsonVals['v1']);
                    break;
                case 'addButton':
                    $option = '$html->addButton(),' . "\n";
                    break;
                    
                default:
                    $option = '';
                    break;
            }
    }

    }
    
    /**
     * startsWith
     *
     * @param  mixed $haystack
     * @param  mixed $needle
     *
     * @return bool
     */
    public function startsWith($haystack, $needle)
    {
         $length = strlen($needle);
         return (substr($haystack, 0, $length) === $needle);
    }    

    /**
    * datetimeNow
    *
    * @return string
    */
    public static function datetimeNow() 
    {
      $timeZone = new \DateTimeZone(TIMEZONE);
      $datetime = new \DateTime();
      $datetime->setTimezone($timeZone);
      return $datetime->format(DATAFORMAT);
    }

    /**
     * setFolderPermissions
     * 
     * @param  string $needle
     *
     * @return void
     */
    public function setFolderPermissions($folder)
    {
        $dirpath = $folder;
        $dirperm = 0755;
        $fileperm = 0644; 
        chmod ($dirpath, $dirperm);
        $glob = glob($dirpath."/*");
        foreach($glob as $ch)
        {
            $ch = (is_dir($ch)) ? chmod ($ch, $dirperm) : chmod ($ch, $fileperm);
        }
    }
    
    /**
     * upload
     *
     * @param  string $file
     * @param  string $path
     *
     * @return resource
     */
    public function upload($file, $path)
    {
        if (isset($file) && $file->isOk()) {
            $file_contents = $file->getContents();
            $file_name = $file->getName();
            $this->setFolderPermissions('json');
            $file_path = 'json/' . substr(pathinfo($path, PATHINFO_DIRNAME ), 6) . '/' . uniqid() . '_' . $file_name;
            file_put_contents($file_path, $file_contents);
            return $file_path;
        } else {
            return '';
        }
        
    }

    
    /**
     * uploadNoUniqid
     *
     * @param  string $file
     * @param  string $path
     *
     * @return resource
     */
    public function uploadNoUniqid($file, $path)
    {
        if (isset($file) && $file->isOk()) {
            $file_contents = $file->getContents();
            $file_name = $file->getName();
            $this->setFolderPermissions('json');
            $file_path = 'json/' . substr(pathinfo($path, PATHINFO_DIRNAME ), 6) . '/' . $file_name;
            file_put_contents($file_path, $file_contents);
            return $file_path;
        } else {
            return '';
        }
        
    }

    /**
     * uploadLogoDocPHT
     *
     * @param  string $file
     *
     * @return resource
     */
    public function uploadLogoDocPHT($file)
    {
        if (isset($file) && $file->isOk()) {
            $file_contents = $file->getContents();
            $this->setFolderPermissions('json');
            $file_path = 'json/logo.png';
            file_put_contents($file_path, $file_contents);
            return $file_path;
        } else {
            return '';
        }
        
    }

    /**
     * uploadFavDocPHT
     *
     * @param  string $file
     *
     * @return resource
     */
    public function uploadFavDocPHT($file)
    {
        if (isset($file) && $file->isOk()) {
            $file_contents = $file->getContents();
            $this->setFolderPermissions('json');
            $file_path = 'json/favicon.png';
            file_put_contents($file_path, $file_contents);
            return $file_path;
        } else {
            return '';
        }
        
    }
    
    /**
     * uploadBackup
     *
     * @param  string $file
     *
     * @return resource
     */
    public function uploadBackup($file)
    {
        if (isset($file) && $file->isOk()) {
            $file_contents = $file->getContents();
            $file_name = $file->getName();
            $this->setFolderPermissions('json');
            $file_path = 'json/' . $file_name;
            file_put_contents($file_path, $file_contents);
            return $file_path;
        } else {
            return '';
        }
        
    }
    
    /**
     * checkImportVersion
     *
     * @param  string $file
     * @param  string $path
     *
     * @return bool
     */
    public function checkImportVersion($file_path, $path)
    {
        $zipData = new \ZipArchive(); 
        if ($zipData->open($file_path) === TRUE) {

            $check = is_bool($zipData->locateName($path)); 
            $zipData->close();
            
            if ($check) { return false; } else { return true; }
        } else {
        
        return false;
        
        }
        
    }
    
    /**
     * title
     *
     * @param  string $val
     * @param  string $anch
     *
     * @return string
     */
    public function title($val,$anch)
    {
        $val = TextHelper::e($val);
        $anch = TextHelper::e($anch);
        $out = '$html->title'."('{$val}','{$anch}'), \n";
        return $out;
    }


    
    
    /**
     * image
     *
     * @param  string $src
     * @param  string $val
     *
     * @return resource
     */
    public function image($src,$val)
    {
        $val = TextHelper::e($val);
        $src = TextHelper::e($src);
        $out = '$html->image'."('{$src}','{$val}'), \n";
        return $out; 
    }
    
    /**
     * markdown
     *
     * @param  string $val
     *
     * @return resource
     */
    public function markdown($val)
    {
        $identifier = $this->generateHeredocIdentifier($val);
        $out = '$html->markdown(<<<' . "'$identifier'
$val
$identifier),\n";
        return $out; 
    }

    /**
    * Generate a shortest identifier for a string to be used in a heredoc/nowdoc.
    *
    * @param string $string
    * @return string
    */
    public function generateHeredocIdentifier(string $string): string
    {
        // Generate all possible identifiers starting with a non-digit character or underscore
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_';
        $suffixCharacters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_';
        $queue = str_split($characters);
    
        while (!empty($queue)) {
            $current = array_shift($queue);
            if (!preg_match('/' . preg_quote($current, '/') . '/', $string)) {
                return $current;
            }
            foreach (str_split($suffixCharacters) as $char) {
                $queue[] = $current . $char;
            }
        }
        
    }
    
    
    /**
     * getOptions
     *
     * @return array
     */
    public function getOptions()
    {
        return [
        'title' => T::trans('Add title'),
    	'markdown' => T::trans('Add markdown'),
        'image' => T::trans('Add image from file')
        ];
    }


}
