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

use DocPHT\Lib\DocBuilder;

class VersionModel extends PageModel
{

    /**
     * checkVersion
     *
     * @param  string $file
     * @param  string $id
     *
     * @return boolean
     */
    public function checkVersion($file_path, $id)
    {
        $zipData = new \ZipArchive();
        if ($zipData->open($file_path) === true) {
            $check = $zipData->locateName(basename($id) . '.md') !== false;
            $zipData->close();
            return $check;
        }
        return false;
        
    }
    
    /**
     * getVersions
     *
     * @param  string $id
     *
     * @return array boolean
     */
    public function getVersions($id)
    {

        $path = $this->getPath($id);
        if ($path === null) {
            return false;
        }
        $dir = dirname($path);
        $filePattern = basename($path, '.md') . '_*.zip';
        $versionList = [];
        foreach (glob($dir . '/' . $filePattern) as $file) {
            if ($this->checkVersion($file, $id)) {
                $versionList[] = ['path' => $file, 'date' => filemtime($file)];
            }
        }
        return $this->sortVersions($versionList);
    }
        
    /**
     * sortVersions
     *
     * @param  array $array
     * 
     * @return array boolean
     */
    public function sortVersions($array)
    {
    
        if (!empty($array)) {
            $column = array_column($array, 'date');
            array_multisort($column, SORT_DESC, $array);
            
            return $array;
        } else {
            return FALSE;
        }

    }
        
    /**
     * saveVersion
     *
     * @param  array $id
     * 
     * @return array boolean
     */
    public function saveVersion($id)
    {
        $path = $this->getPath($id);
        if ($path === null) {
            return false;
        }
        $dir = dirname($path);
        $slug = basename($path, '.md');
        $zippedVersionPath = $dir . '/' . $slug . '_' . DocBuilder::datetimeNow() . '.zip';

        $getAssets = $this->getAssets($id);

        if (!empty($getAssets)) {
            $zipData = new \ZipArchive();
            $zipData->open($zippedVersionPath, \ZipArchive::CREATE);
            foreach ($getAssets as $file) {
                $zipData->addFile($file, basename($file));
            }
            $zipData->close();
            return true;
        }
        return false;
    }   

    /**
     * getAssets
     *
     * @param  array $id
     * 
     * @return array boolean
     */
    public function getAssets($id)
    {
        $path = $this->getPath($id);
        if ($path === null) {
            return false;
        }
        $dir = dirname($path);
        $base = basename($path, '.md');
        $assets = glob($dir . '/' . $base . '_*.{jpg,jpeg,png,gif,svg}', GLOB_BRACE);
        $assets[] = $path;
        return $assets;
    }
    
    /**
     * deleteVersion
     *
     * @param  array $path
     * 
     * @return array boolean
     */
    public function deleteVersion($path)
    {
        if (file_exists($path)) {
            unlink($path);
            return TRUE;
        } else {
            return FALSE;
        }
        
    }
}
    
