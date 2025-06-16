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

use DocPHT\Core\Translator\T;
use Instant\Core\Controller\BaseController;
use DocPHT\Core\Helper\DiscuzBridge;

class LoginController extends BaseController
{
    
    public function login()
    {
        if (isset($_SESSION['Active'])) {
            header('Location:/doc.php');
            exit;
        }

        $form = $this->loginForm->create();
        $this->view->show('login/partial/head.php', ['PageTitle' => T::trans('Login')]);
        $this->view->show('login/login.php', ['form' => $form]);
        $this->view->show('login/partial/footer.php');
    }

    public function checkLogin(string $username, string $password)
    {
        include 'config/config_global.php';
        $table = $_config['db'][1]['tablepre'] . 'ucenter_members';
        $conn = new \mysqli($_config['db'][1]['dbhost'], $_config['db'][1]['dbuser'], $_config['db'][1]['dbpw'], $_config['db'][1]['dbname']);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        $stmt = $conn->prepare("SELECT password FROM $table WHERE username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->bind_result($hash);
        $stmt->fetch();
        $stmt->close();
        $conn->close();
        if (password_verify($password,$hash)) {
            DiscuzBridge::startDocPhtSession($username, $password);
            $this->accessLogModel->create($username);
            return true;
        } else {
            $this->accessLogModel->create($username);
            return false;
        }
    }

    public function logout()
    {
        DiscuzBridge::clearCookies();
        session_unset();
        session_destroy();

        header('Location:/doc.php');
        exit;
    }
}
