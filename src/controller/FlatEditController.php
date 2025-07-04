<?php
namespace DocPHT\Controller;

use Instant\Core\Controller\BaseController;
use DocPHT\Model\FlatPageModel;

class FlatEditController extends BaseController
{
    public function edit($slug)
    {
        if (!isset($_SESSION['Active'])) {
            http_response_code(403);
            echo 'Unauthorized';
            return;
        }
        $model = new FlatPageModel();
        $markdown = $model->get($slug) ?? '';

        $saveError = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $markdown = $_POST['markdown'] ?? '';
            if (!empty($_FILES['images']['name'][0])) {
                $model->uploadImages($slug, $_FILES['images']);
            }
            if ($model->put($slug, $markdown)) {
                $model->cleanUnusedImages($slug, $markdown);
                header('Location: /page/' . $slug);
                exit;
            } else {
                $saveError = 'Failed to save changes.';
            }
        }

        $this->view->show('partial/head.php', ['PageTitle' => 'Edit ' . htmlspecialchars($slug, ENT_QUOTES, 'UTF-8')]);
        $this->view->show('flat/edit.php', [
            'slug' => $slug,
            'markdown' => $markdown,
            'saveError' => $saveError
        ]);
        $this->view->show('partial/footer.php');
    }
}
