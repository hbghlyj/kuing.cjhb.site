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

namespace DocPHT\Core\Session;

class Session
{
    public function __construct()
    {
        if(session_status() === PHP_SESSION_NONE) {
            $domain = defined('DOMAIN_NAME') ? DOMAIN_NAME : $_SERVER['HTTP_HOST'];
            $secure = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off');
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => $domain,
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            ini_set('session.cookie_httponly', 1); // XSS attack protection
            ini_set('session.use_strict_mode', 1); // Prevents an attack that forces users to use a known session ID
            // Set an additional entropy
            ini_set('session.entropy_file', '/dev/urandom');
            ini_set('session.entropy_length', '256');
            session_name('ID');
            // Override the SecureHandler registered by php-secure-session
            session_set_save_handler(new \SessionHandler(), true);
            // Handle legacy encrypted sessions gracefully
            $error = null;
            set_error_handler(function($errno, $errstr) use (&$error) {
                $error = $errstr;
            });
            $ok = session_start();
            restore_error_handler();
            if (!$ok && strpos((string)$error, 'Failed to decode session object') !== false) {
                if (isset($_COOKIE[session_name()])) {
                    setcookie(session_name(), '', time() - 3600, '/');
                }
                session_start();
            }
        }
    }

    public function sessionExpiration()
    {
        $expireAfter = 30; // session life time expressed in minutes

        if(isset($_SESSION['last_action'])){

            $secondsInactive = time() - $_SESSION['last_action'];
            
            $expireAfterSeconds = $expireAfter * 60;
            
            if($secondsInactive >= $expireAfterSeconds){
                session_unset();
                session_destroy();
            }
        }

        $_SESSION['last_action'] = time();
    }

    public function preventStealingSession()
    {
        // Check previous user agent to detect session hijacking 
        // Prevent malicious users from stealing sessions
        if (isset($_SESSION['PREV_USERAGENT'])) {
            if ($_SERVER['HTTP_USER_AGENT'] != $_SESSION['PREV_USERAGENT']) {
                session_unset();
                session_destroy();
            }
        }
    }
}