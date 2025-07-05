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
 *
 *
 */
namespace DocPHT\Model;

use DocPHT\Model\VersionModel;
use DocPHT\Lib\DocBuilder;

class BackupsModel extends PageModel
{

    /**
     * checkBackups
     *
     * @param  string $file
     * @param  string $id
     *
     * @return boolean
     */
    public function checkBackups($file_path)
    {
        $zipData = new \ZipArchive();
        if ($zipData->open($file_path) === true) {

            $check = $zipData->locateName('json/pages.json') !== false;
            if ($check) {
                $backupPages = json_decode(file_get_contents("zip://".$file_path."#json/pages.json"), true);
                foreach ($backupPages as $pages) {
                    $fs = $this->computeFileSlug($pages['pages']['topic'], $pages['pages']['filename']);
                    $phpPath = 'pages/'.$fs.'.php';
                    $jsonPath = 'json/'.$fs.'.json';
                    if ($zipData->locateName($phpPath) === false || $zipData->locateName($jsonPath) === false) {
                        $check = false;
                        break;
                    }
                }
            }
            $zipData->close();

            return $check;
        } else {

        return false;

        }
        
    }
    
    /**
     * getZipList
     *
     * @param  string $file
     *
     * @return array boolean
     */
    public function getZipList($file)
    {
        $zip = new \ZipArchive(); 
        if ($zip->open($file) === true) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename[] = $zip->getNameIndex($i);
            }
            return $filename;
        } else {
            return false;
        }
    }
    
    /**
     * getBackups
     *
     * @param  string $id
     *
     * @return array
     */
    public function getBackups()
    {
        $path = 'json/';
        $filePattern = 'DocPHT_Backup_*.zip';

        $versionList = array();
        foreach (glob($path . $filePattern) as $file) {
            $addFile = $this->checkBackups($file);
            if($addFile) array_push($versionList, ['path' => $file, 'date' => filemtime($file)]);
        }
        
        return $this->sortBackups($versionList);
    }
        
    /**
     * sortBackups
     *
     * @param  array $array
     * 
     * @return array boolean
     */
    public function sortBackups($array)
    {
    
        if (!empty($array)) {
            $column = array_column($array, 'date');
            array_multisort($column, SORT_DESC, $array);
            
            return $array;
        } else {
            return false;
        }

    }
        
    /**
     * createBackup
     *
     * 
     * @return boolean
     */
    public function createBackup()
    {
        $this->versionModel = new VersionModel;
        $pages = $this->connect();
        $assets = ['json/pages.json'];
        
        if(file_exists('json/users.json'))array_push($assets, 'json/users.json');
        if(file_exists('json/logo.png'))array_push($assets, 'json/logo.png');
        if(file_exists('json/favicon.png'))array_push($assets, 'json/favicon.png');
        if(file_exists('json/accesslog.json'))array_push($assets, 'json/accesslog.json');
        
        $this->doc = new DocBuilder;
        $filename = 'json/DocPHT_Backup_' . $this->doc->datetimeNow() . '_'.uniqid().'.zip';
        
        if (is_array($pages) && count($pages) > 0) {
            foreach($pages as $page) {
                $asset = $this->versionModel->getAssets($page['pages']['slug']) ?: [];
                $version = $this->versionModel->getVersions($page['pages']['slug']) ?: [];
                
                foreach($asset as $a) { array_push($assets, $a); }
                if(!empty($version))foreach($version as $ver) { array_push($assets, $ver['path']); }
            }
            
            if (!empty($assets)) {
                $zipData = new \ZipArchive();
                $zipData->open($filename, \ZipArchive::CREATE);
                foreach ($assets as $file) {
                    $zipData->addFile($file, $file);
                }
                $zipData->close();
                return true;
            } else {
                return false;
            }
        }
    }
    
    /**
     * deleteBackup
     *
     * @param  array $path
     * 
     * @return boolean
     */
    public function deleteBackup($path)
    {
        if (file_exists($path)) {
            unlink($path);
            return true;
        } else {
            return false;
        }
        
    }
}
    
