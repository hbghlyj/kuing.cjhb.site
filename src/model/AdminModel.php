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

namespace DocPHT\Model;

class AdminModel
{
    const USERS = 'json/users.json';
    
    /**
     * connect
     *
     *
     * @return array
     */
    public function connect()
    {
        $dir = dirname(self::USERS);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        if (!is_writable($dir)) {
            error_log('Directory not writable: ' . $dir);
        }
        if (!file_exists(self::USERS)) {
            file_put_contents(self::USERS, '[]');
        }

        $contents = file_get_contents(self::USERS);
        if ($contents === false || $contents === '') {
            $contents = '[]';
        }
        return json_decode($contents, true);
    }

    /**
     * create
     *
     * @param  array $values
     *
     * @return array
     */
    public function create($values)
    {
        $data = $this->connect();
        $data[] = array(
            'Username' => $values['username'],
            'Password' => password_hash($values['password'], PASSWORD_DEFAULT),
            'Token'    => '',
            'Admin'    => $values['admin']
            );
            
        return $this->disconnect(self::USERS, $data);
    }

    /**
     * userExists
     *
     * @param  string $user
     * 
     * @return boolean
     */
    public function userExists($user)
    {
        return in_array($user, $this->getUsernames());
    }
 
    /**
     * getUsernames
     *
     * @return array
     */
    public function getUsernames()
    {
        $data = $this->connect();
        $usernames = array_column($data, 'Username');        
        return $usernames;
    }

    
    /**
     * removeUser
     *
     * @param  string $userindex
     * 
     * @return array
     */
    public function removeUser($userindex)
    {
        $data = $this->connect();

        array_splice($data, $userindex, 1); 
        
        return $this->disconnect(self::USERS, $data);
    }

    /**
     * checkUserIsAdmin
     *
     * @param  string $username
     *
     * @return boolean
     */
    public function checkUserIsAdmin($username)
    {
        $data = $this->connect();
        $key = array_search($username, array_column($data, 'Username'));
        
        return $data[$key]['Admin'];
    }

    /**
     * createTimedToken
     *
     * @param  string $hour
     *
     * @return string
     */
    public function createTimedToken(string $hour)
    {
        $date = date('Y-m-d H:i', strtotime("+".$hour." hour"));
        $expiry = strtotime($date);
        $token = md5(uniqid(rand(), true));

        return $token.'&expiry='.$expiry;
    }

    /**
     * addToken
     *
     * @param  string $username
     * @param  string $token
     *
     * @return string
     */
    public function addToken($username, $token)
    {
        $data = $this->connect();
        $key = array_search($username, array_column($data, 'Username'));
        
        $data[$key]['Token'] = $token;
        
        return $this->disconnect(self::USERS, $data);
    }

    /**
     * getUsernameFromToken
     *
     * @param  string $token
     *
     * @return string
     */
    public function getUsernameFromToken($token)
    {
        $data = $this->connect();
        $key = array_search($token, array_column($data, 'Token'));
        
        return $data[$key]['Username'];
    }

    /**
     * getTokenFromUsername
     *
     * @param  string $username
     *
     * @return string
     */
    public function getTokenFromUsername($username)
    {
        $data = $this->connect();
        $key = array_search($username, array_column($data, 'Username'));
        
        return $data[$key]['Token'];
    }

    /**
     * disconnect
     *
     * @param  string $path
     * @param  array $data
     *
     * @return array
     */
    public function disconnect($path, $data)
    {
        return file_put_contents($path, json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
    }
    
    /**
     * getUsers
     *
     *
     * @return array
     */
    public function getUsers()
    {
        $data = $this->connect();

        return $data;
    }

}