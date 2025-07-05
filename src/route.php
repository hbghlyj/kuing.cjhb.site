<?php

use DocPHT\Controller\FormPageController;
use DocPHT\Controller\ErrorPageController;
use DocPHT\Controller\LoginController;
use DocPHT\Model\AdminModel;

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

$route->get('/', 'DocPHT\Controller\HomeController@index');

$route->get('/switch-theme', 'Instant\Core\Controller\BaseController@switchTheme');

$route->get_post('/login', 'DocPHT\Controller\LoginController@login');

if (isset($_SESSION['Active'])) {

    $route->get('/logout', 'DocPHT\Controller\LoginController@logout');
    
    $route->group('/admin', function()
    {
        // /admin/
        $this->get('/', 'DocPHT\Controller\AdminController@settings');
        
        $adminModel = new AdminModel(); 
        if (isset($_SESSION['Active']) && $adminModel->checkUserIsAdmin($_SESSION['Username']) == true ) {
            
            // /admin/backup
            $this->get_post('/backup', 'DocPHT\Controller\AdminController@backup');
            
            // /admin/backup/save
            $this->get_post('/backup/save', 'DocPHT\Controller\AdminController@saveBackup');
            
            // /admin/backup/export
            $this->get_post('/backup/export', 'DocPHT\Controller\AdminController@exportBackup');
            
            // /admin/backup/delete
            $this->get_post('/backup/delete', 'DocPHT\Controller\AdminController@deleteBackup');
            
            // /admin/backup/import
            $this->get_post('/backup/import', 'DocPHT\Controller\AdminController@importBackup');
            
            // /admin/backup/restore
            $this->get_post('/backup/restore', 'DocPHT\Controller\AdminController@restoreOptions');

            // /admin/upload-logo
            $this->get_post('/upload-logo', 'DocPHT\Controller\AdminController@uploadLogo');

            // /admin/remove-logo
            $this->get_post('/remove-logo', 'DocPHT\Controller\AdminController@removeLogo');

            // /admin/remove-fav
            $this->get_post('/remove-fav', 'DocPHT\Controller\AdminController@removeFav');

            // /admin/lastlogins
            $this->get_post('/lastlogins', 'DocPHT\Controller\AdminController@lastLogin');

        }
        
        // /admin/translations
        $this->get_post('/translations', 'DocPHT\Controller\AdminController@translations');

        // Anything else
        $this->any('/*', function(){
            $error = new ErrorPageController();
            $error->getPage();
        });
    });
} else {
    $route->any('/admin', function(){
        $login = new LoginController();
        $login->login();
    });
    
    $route->any('/admin/*', function(){
        $login = new LoginController();
        $login->login();
    });
}

// /page
$route->group('/page', function()
{
    // /page/topic/filename
    $this->get_post('/{topic}/{filename}', function($topic, $filename){
        $model = new \DocPHT\Model\PageModel();
        $slug = $topic.'/'.$filename;
        $id = $model->getIdBySlug($slug);
        if ($id !== null) {
            $page = new FormPageController();
            $page->getPage($topic, $filename);
        } else {
            $error = new ErrorPageController();
            $error->getPage($topic, $filename);
        }
    });

    // /page/search
    $this->get_post('/search', 'Instant\Core\Controller\BaseController@search');

    if (isset($_SESSION['Active'])) {
        // /page/create
        $this->get_post('/create', 'DocPHT\Controller\FormPageController@getCreatePageForm');
        // /page/add-section
        $this->get_post('/add-section', 'DocPHT\Controller\FormPageController@getAddSectionForm');
        // /page/update
        $this->get_post('/update', 'DocPHT\Controller\FormPageController@getUpdatePageForm');
        // /page/insert
        $this->get_post('/insert', 'DocPHT\Controller\FormPageController@getInsertSectionForm');
        // /page/modify
        $this->get_post('/modify', 'DocPHT\Controller\FormPageController@getModifySectionForm');
        // /page/remove
        $this->get_post('/remove', 'DocPHT\Controller\FormPageController@getRemoveSectionForm');
        // /page/delete
        $adminModel = new AdminModel(); 
        if ($adminModel->checkUserIsAdmin($_SESSION['Username']) == true) {
            $this->get_post('/delete', 'DocPHT\Controller\FormPageController@getDeletePageForm');
        }
        // /page/import-version
        $this->get_post('/import-version', 'DocPHT\Controller\FormPageController@getImportVersionForm');
        // /page/export-version
        $this->get_post('/export-version', 'DocPHT\Controller\FormPageController@getExportVersionForm');
        // /page/restore-version
        $this->get_post('/restore-version', 'DocPHT\Controller\FormPageController@getRestoreVersionForm');
        // /page/delete-version
        if ($adminModel->checkUserIsAdmin($_SESSION['Username']) == true) {
            $this->get_post('/delete-version', 'DocPHT\Controller\FormPageController@getDeleteVersionForm');
        }
        // /page/save-version
        $this->get_post('/save-version', 'DocPHT\Controller\FormPageController@getSaveVersionForm');
    } else {
        $this->any('/*', function(){
            $login = new LoginController();
            $login->login();
        });
    }
    
    // Anything else
    $this->any('/*', function(){
        $error = new ErrorPageController();
        $error->getPage();
    });
});

// Anything else
$route->any('/*', function(){
    $error = new ErrorPageController();
    $error->getPage();
});