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
 * connect()
 * connectPageData($id)
 * create($topic, $filename)
 * getPagesByTopic($topic)
 * getUniqTopics()
 * getPhpPath($id)
 * getSlug($id)
 * getJsonPath($id)
 * getAllFromKey($key)
 * getAllFromDataKey($data, $key)
 * getAllIndexed()
 * getId($path)
 * getTopic($id)
 * getFilename($id)
 * getPageData($id)
 * putPageData($id, $data)
 * addPageData($id, $array)
 * modifyPageData($id, $index, $array)
 * removePageData($id, $index)
 * insertPageData($id, $index, $before_or_after, $array)
 * remove($id)
 * disconnect($path, $data)
 * findKey($data, $search)
 * hideBySlug($slug)
 */
namespace DocPHT\Model;

use DocPHT\Core\Translator\T;

class PageModel
{
    const DB = 'json/pages.json';

    protected function sanitizeComponent($name)
    {
        return preg_replace('/[\\\\\/:*?"<>|]/', '_', $name);
    }

    protected function computeFileSlug($topic, $filename)
    {
        $topic = $this->sanitizeComponent($topic);
        $filename = $this->sanitizeComponent($filename);
        return trim($topic) . '/' . trim($filename);
    }

    protected function getFileSlug($id)
    {
        $data = $this->connect();
        $key = $this->findKey($data, $id);
        if ($key === false) {
            return null;
        }
        if (isset($data[$key]['pages']['file_slug'])) {
            return $data[$key]['pages']['file_slug'];
        }
        $topic = $data[$key]['pages']['topic'];
        $filename = $data[$key]['pages']['filename'];
        return $this->computeFileSlug($topic, $filename);
    }

    public function getIdBySlug($slug)
    {
        $data = $this->connect();
        foreach ($data as $value) {
            if ($value['pages']['slug'] === $slug) {
                return $value['pages']['id'];
            }
        }
        return null;
    }
    
    
    /**
     * connect
     *
     * 
     * @return array
     */
    public function connect()
    {
		if(!file_exists(self::DB))
		{
		    file_put_contents(self::DB,[]);
		} 
		
		return json_decode(file_get_contents(self::DB),true);
    }
    
    /**
     * connectPageData
     *
     * @param  string $id
     * 
     * @return array
     */
    public function connectPageData($id)
    {
        $path = $this->getJsonPath($id);
        
        if (isset($path)) {
            if (!file_exists(pathinfo($path, PATHINFO_DIRNAME))) {
                mkdir(pathinfo($path, PATHINFO_DIRNAME), 0755, true);
            }
            
            if(!file_exists($path))
            {
                file_put_contents($path,array(
                    
                    ));
            } 
            
            return json_decode(file_get_contents(realpath($path)),true);
        }
    }
    
    /**
     * create
     *
     * @param  string $topic
     * @param  string $filename
     *
     * @return string
     */
    public function create($topic, $filename)
    {
        $data = $this->connect();
        $id = uniqid();
        $topic = pathinfo($topic, PATHINFO_FILENAME);
        $filename = pathinfo($filename, PATHINFO_FILENAME);
        $slug = trim($topic) . '/' . trim($filename);
        $fileSlug = $this->computeFileSlug($topic, $filename);

        if (!is_null($data)) {
            
            $slugs = $this->getAllFromKey('file_slug');

            if (is_array($slugs)) {
                if(in_array($fileSlug, $slugs))
                {
                    $count = 1;
                    while(in_array(($fileSlug . '-' . ++$count ), $slugs));
                    $slug = $slug . '-' . $count;
                    $filename = $filename . '-' . $count;
                    $fileSlug = $fileSlug . '-' . $count;
                }
            };
        }   
        
        $data[] = array(
            'pages' => [
                    'id' => $id,
                    'slug' => $slug,
                    'file_slug' => $fileSlug,
                    'topic' => $topic,
                    'filename' => $filename,
                    'home' => 0
            ]);
            
            
        $this->connectPageData($id);
        $this->disconnect(self::DB, $data);
        
		return $id;
    }

    /**
     * getPagesByTopic
     *
     * @param  string $topic
     *
     * @return array|bool
     */
    public function getPagesByTopic($topic)
    {
        $data = $this->connect();
        if (!is_null($data)) {
            foreach($data as $value){
                if($value['pages']['topic'] === $topic) {
                  $array[] = $value['pages'];  
                }
            } 
            usort($array, function($a, $b) {
                return $a['topic'] <=> $b['topic'];
            });
            return (isset($array)) ? $array : false;
        } else {
            return false;
        }
    }

    /**
     * getUniqTopics
     * 
     * @return array|bool
     */
    public function getUniqTopics()
    {
        $data = $this->connect();
        $array = $this->getAllFromKey('topic');
        if (is_array($array) && !is_null($array)) {
            $array = array_unique($array);
            sort($array);
            return (isset($array)) ? $array : false;
        } else {
            return false;
        } 
    }

    /**
     * getPhpPath
     *
     * @param  string $id
     *
     * @return string
     */
    public function getPhpPath($id)
    {
        $slug = $this->getFileSlug($id);

        if (empty($slug)) {
            return null;
        }

        return 'pages/'.$slug.'.php';
    }
    
    /**
     * getSlug
     *
     * @param  string $id
     *
     * @return string
     */
    public function getSlug($id)
    {
        $data = $this->connect();
        $key = $this->findKey($data, $id);

        return isset($data[$key]) ? $data[$key]['pages']['slug'] : null;
    }
    
    /**
     * getJsonPath
     *
     * @param  string $id
     *
     * @return string
     */
    public function getJsonPath($id)
    {
        $slug = $this->getFileSlug($id);

        if (empty($slug)) {
            return null;
        }

        return 'json/'.$slug.'.json';
    }
    
    /**
     * getAllFromKey
     * 
     * @param string $key
     * 
     * @return array|bool
     */
    public function getAllFromKey($key)
    {
        $data = $this->connect();
        if (!is_null($data) && !empty($data)) {
            foreach($data as $value){
                if ($key === 'file_slug') {
                    $array[] = isset($value['pages']['file_slug']) ? $value['pages']['file_slug'] : $this->computeFileSlug($value['pages']['topic'], $value['pages']['filename']);
                } else {
                    $array[] = $value['pages'][$key];
                }
            }
            return $array;
        } else {
            return false;
        }
    }   
    
    /**
     * getAllFromDataKey
     * 
     * @param array $data
     * @param string $key
     * 
     * @return array|bool
     */
    public function getAllFromDataKey($data, $key)
    {
        if (!is_null($data) && !empty($data)) {
            foreach($data as $value){
                if ($key === 'file_slug') {
                    $array[] = isset($value['pages']['file_slug']) ? $value['pages']['file_slug'] : $this->computeFileSlug($value['pages']['topic'], $value['pages']['filename']);
                } else {
                    $array[] = $value['pages'][$key];
                }
            }
            return $array;
        } else {
            return false;
        }
    }
    
    
    /**
     * getAllIndexed
     * 
     * 
     * @return array|bool
     */
    public function getAllIndexed()
    {
        $data = $this->connect();
        if (!is_null($data)) {
            foreach($data as $value){
                foreach ($value as $value) {
                    $array[] =  $value;
            }
        }

            return $array;
        } else {
            return false;
        }
    }
    
    /**
     * getId
     *
     * @param  string $path
     *
     * @return string
     */
    public function getId($path)
    {
        $data = $this->connect();
        foreach ($data as $value) {
            $fileSlug = isset($value['pages']['file_slug']) ? $value['pages']['file_slug'] : $this->computeFileSlug($value['pages']['topic'], $value['pages']['filename']);
            $phpPath = 'pages/'.$fileSlug.'.php';
            if ($phpPath === $path) {
                return $value['pages']['id'];
            }
        }

        return null;
    }
    
    /**
     * getTopic
     *
     * @param  string $id
     *
     * @return string
     */
    public function getTopic($id)
    {
        $data = $this->connect();
        $key = $this->findKey($data, $id);
        
        return $data[$key]['pages']['topic'];
    }
    
    /**
     * getFilename
     *
     * @param  string $id
     *
     * @return string
     */
    public function getFilename($id)
    {
        $data = $this->connect();
        $key = $this->findKey($data, $id);
        
        return $data[$key]['pages']['filename'];
    }
    
    /**
     * getPageData
     *
     * @param  string $id
     *
     * @return array
     */
    public function getPageData($id)
    {
        $data = $this->connectPageData($id);

        return $data;
    }
    
    /**
     * putPageData
     *
     * @param  string $id
     * @param  array $data
     *
     * @return int|bool
     */
    public function putPageData($id, $data)
    {
        $path = $this->getJsonPath($id);

        return file_put_contents($path, json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
    }
    
    /**
     * addPageData
     *
     * @param  string $id
     * @param  array $array
     *
     * @return int|bool
     */
    public function addPageData($id, $array)
    {
        $data = $this->getPageData($id);
        $data[] = array(
            'key' => $array['key'],
            'v1' => $array['v1'],
            'v2' => $array['v2'],
            'v3' => $array['v3'],
            'v4' => $array['v4']
            );
            
        return $this->putPageData($id, $data);
    }
    
    /**
     * modifyPageData
     *
     * @param  string $id
     * @param  integer $index
     * @param  array $array
     *
     * @return int|bool
     */
    public function modifyPageData($id, $index, $array)
    {
        $data = $this->getPageData($id);
        $data[$index] = array(
            'key' => $array['key'],
            'v1' => $array['v1'],
            'v2' => $array['v2'],
            'v3' => $array['v3'],
            'v4' => $array['v4']
            );
            
        return $this->putPageData($id, $data);
    }
    
    /**
     * removePageData
     *
     * @param  string $id
     * @param  integer $index
     *
     * @return int|bool
     */
    public function removePageData($id, $index)
    {
        $data = $this->getPageData($id);
        array_splice($data, $index, 1);

            
        return $this->putPageData($id, $data);
    }
    
    /**
     * insertPageData
     *
     * @param  string $id
     * @param  integer $index
     * @param  string $before_or_after
     * @param  array $array
     *
     * @return int|bool
     */
    public function insertPageData($id, $index, $before_or_after, $array)
    {
        $data = $this->getPageData($id);
        
        array_splice($data, ($before_or_after == 'b') ? (int)$index : (int)$index + 1, 0, array([
            'key' => $array['key'],
            'v1' => $array['v1'],
            'v2' => $array['v2'],
            'v3' => $array['v3'],
            'v4' => $array['v4']
            ]));

        return $this->putPageData($id, $data);
    }
    
    
    /**
     * remove
     *
     * @param  string $id
     *
     * @return int|bool
     */
    public function remove($id)
    {
        $data = $this->connect();
        $key = $this->findKey($data, $id);
        
        if ($key !== false) {
        array_splice($data, $key, 1);
        }
        
        return $this->disconnect(self::DB, $data);
    }
    
    /**
     * disconnect
     *
     * @param  string $path
     * @param  array $data
     *
     * @return int|bool
     */
    public function disconnect($path, $data)
    {
        return file_put_contents($path, json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
    }
    
    /**
     * findKey
     *
     * @param  string $id
     *
     * @return int|bool
     */
    public function findKey($data, $search)
    {
        $x = 0;
        if (isset($data)) {
            foreach ($data as $array) {
                if ($array['pages']['id'] == $search) $key = $x;
                $x++;
            }
            
            return isset($key) ? $key : false;
        }
    }

    /**
     * hideBySlug
     *
     * @param  string $slug
     *
     * @return string
     */
    public function hideBySlug($slug)
    {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
            $protocol  = "https://";
        } else {
            $protocol  = "http://";
        }
        
        if (isset($slug)) {
            return $protocol.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'] !== BASE_URL.$slug;
        }
    }
    
}
