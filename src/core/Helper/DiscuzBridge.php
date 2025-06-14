<?php
namespace DocPHT\Core\Helper;

use DocPHT\Model\AdminModel;

class DiscuzBridge
{
    public static function syncSession()
    {
        $debug = defined('DISCUZ_BRIDGE_DEBUG') && DISCUZ_BRIDGE_DEBUG;
        if ($debug) {
            echo "[DiscuzBridge] Starting syncSession\n";
        }
        if (isset($_SESSION['Active'])) {
            if ($debug) {
                echo "[DiscuzBridge] Session already active\n";
            }
            return;
        }
        $configPath = __DIR__ . '/../../../config/config_global.php';
        if (!file_exists($configPath)) {
            if ($debug) {
                echo "[DiscuzBridge] Config not found: $configPath\n";
            }
            return;
        }
        if ($debug) {
            echo "[DiscuzBridge] Loading config from $configPath\n";
        }
        require $configPath;
        if (!isset($_config['cookie']['cookiepre']) || !isset($_config['security']['authkey'])) {
            if ($debug) {
                echo "[DiscuzBridge] Missing cookiepre or authkey in config\n";
            }
            return;
        }
        $cookiePre = $_config['cookie']['cookiepre'] .
            substr(md5($_config['cookie']['cookiepath'] . '|' . $_config['cookie']['cookiedomain']), 0, 4) .
            '_';
        $authCookie = $cookiePre . 'auth';
        $saltCookie = $cookiePre . 'saltkey';
        if ($debug) {
            echo "[DiscuzBridge] Expecting cookies '$authCookie' and '$saltCookie'\n";
        }
        if (empty($_COOKIE[$authCookie]) || empty($_COOKIE[$saltCookie])) {
            if ($debug) {
                echo "[DiscuzBridge] Required cookies not present\n";
            }
            return;
        }
        $authkey = md5($_config['security']['authkey'] . $_COOKIE[$saltCookie]);
        $rawAuth = rawurldecode($_COOKIE[$authCookie]);
        $data = self::authcode($rawAuth, 'DECODE', $authkey);
        if (empty($data) || strpos($data, "\t") === false) {
            if ($debug) {
                echo "[DiscuzBridge] Failed to decode auth cookie\n";
            }
            return;
        }
        list($password, $uid) = explode("\t", $data);
        if (!$uid) {
            if ($debug) {
                echo "[DiscuzBridge] UID not found in decoded cookie\n";
            }
            return;
        }
        $db = $_config['db'][1];
        if ($debug) {
            echo "[DiscuzBridge] Connecting to database\n";
        }
        $mysqli = new \mysqli($db['dbhost'], $db['dbuser'], $db['dbpw'], $db['dbname']);
        if ($mysqli->connect_error) {
            if ($debug) {
                echo "[DiscuzBridge] DB connection failed: {$mysqli->connect_error}\n";
            }
            return;
        }
        $userData = self::fetchUserFromDb($mysqli, $db['tablepre'], 'common_member', $uid);
        if (!$userData || !hash_equals($userData[1], $password)) {
            $userData = self::fetchUserFromDb($mysqli, $db['tablepre'], 'ucenter_members', $uid);
        }
        // Close the connection after both lookups
        $mysqli->close();

        if (!$userData) {
            if ($debug) {
                echo "[DiscuzBridge] User record not found\n";
            }
            return;
        }

        [$username, $hash] = $userData;

        // Discuz! stores the user's hashed password directly in the auth cookie.
        // Therefore the cookie value should exactly match the value from the
        // database regardless of whether Discuz! uses the legacy md5+salt
        // algorithm or the newer password_hash() format.
        // Compare both hashes using hash_equals() for timing-safe validation.
        $valid = hash_equals($hash, $password);
        if (!$valid) {
            if ($debug) {
                echo "[DiscuzBridge] Password hash mismatch\n";
            }
            return;
        }
        $adminModel = new AdminModel();
        if (!$adminModel->userExists($username)) {
            $langHeader = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
            $adminModel->create([
                'username' => $username,
                // generate a cryptographically secure placeholder password
                'password' => bin2hex(random_bytes(16)),
                'translations' => (stripos($langHeader, 'zh') === false) ? 'en_EN' : 'zh_CN',
                'admin' => false
            ]);
        }
        session_regenerate_id(true);
        $_SESSION['PREV_USERAGENT'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SESSION['Username'] = $username;
        $_SESSION['Active'] = true;
        if ($debug) {
            echo "[DiscuzBridge] Login sync complete for $username\n";
        }
    }

    /**
     * Clear Discuz cookies to log out from the forum
     */
    public static function clearCookies(): void
    {
        $configPath = __DIR__ . '/../../../config/config_global.php';
        if (!file_exists($configPath)) {
            return;
        }
        require $configPath;
        if (!isset($_config['cookie']['cookiepre'])) {
            return;
        }
        $cookiePre = $_config['cookie']['cookiepre'] .
            substr(md5($_config['cookie']['cookiepath'] . '|' . $_config['cookie']['cookiedomain']), 0, 4) .
            '_';
        $domain = $_config['cookie']['cookiedomain'] ?: $_SERVER['HTTP_HOST'];
        $path = $_config['cookie']['cookiepath'] ?: '/';
        $cookies = [
            $cookiePre . 'auth',
            $cookiePre . 'saltkey',
            $cookiePre . 'sid',
            $cookiePre . 'lastvisit',
            $cookiePre . 'lastact'
        ];
        foreach ($cookies as $name) {
            setcookie($name, '', time() - 3600, $path, $domain);
        }
    }

    /**
     * Fetch username and password hash from the specified Discuz! table.
     *
     * @param mysqli $mysqli   Active database connection
     * @param string $prefix   Table prefix from config
     * @param string $suffix   Table name without prefix
     * @param int    $uid      Discuz UID to lookup
     *
     * @return array{0:string,1:string}|null  [$username, $hash] on success, null otherwise
     */
    private static function fetchUserFromDb(\mysqli $mysqli, string $prefix, string $suffix, int $uid): ?array
    {
        $table = $prefix . $suffix;
        $stmt = $mysqli->prepare("SELECT username,password FROM $table WHERE uid = ?");
        if (!$stmt) {
            error_log("DiscuzBridge: Failed to prepare statement for table '$table': " . $mysqli->error);
            return null;
        }
        $stmt->bind_param('i', $uid);
        if (!$stmt->execute()) {
            error_log("DiscuzBridge: Failed to execute statement for table '$table': " . $stmt->error);
            $stmt->close();
            return null;
        }
        $stmt->bind_result($username, $hash);
        $resultFetched = $stmt->fetch();
        $stmt->close();

        if ($resultFetched === false || $resultFetched === null || empty($username)) {
            return null;
        }
        return [$username, $hash];
    }

    // Implementation derived from Discuz! authcode() helper
    private static function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0)
    {
        $ckey_length = 4;
        $key = md5($key);
        $keya = md5(substr($key, 0, 16));
        $keyb = md5(substr($key, 16, 16));
        $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';
        $cryptkey = $keya . md5($keya . $keyc);
        $key_length = strlen($cryptkey);
        $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
        $string_length = strlen($string);
        $result = '';
        $box = range(0, 255);
        $rndkey = [];
        for ($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }
        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        for ($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }
        if ($operation == 'DECODE') {
            if (((int)substr($result, 0, 10) == 0 || (int)substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) === substr(md5(substr($result, 26) . $keyb), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {
            return $keyc . str_replace('=', '', base64_encode($result));
        }
    }
}
