<?php
namespace DocPHT\Controller;

use Instant\Core\Controller\BaseController;
use DocPHT\Model\FlatPageModel;

class FlatEditController extends BaseController
{
    public function edit($topic, $filename)
    {
        if (!isset($_SESSION['Active'])) {
            http_response_code(403);
            echo 'Unauthorized';
            return;
        }
        $slug = $topic . '/' . $filename;
        $model = new FlatPageModel();
        $markdown = $model->get($slug) ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $markdown = $_POST['markdown'] ?? '';
            if (!empty($_FILES['images']['name'][0])) {
                $model->uploadImages($slug, $_FILES['images']);
            }
            $model->put($slug, $markdown);
            $model->cleanUnusedImages($slug, $markdown);
            header('Location: /page/' . rawurlencode($topic) . '/' . rawurlencode($filename));
            exit;
        }

        $this->view->show('partial/head.php', ['PageTitle' => 'Edit ' . htmlspecialchars($slug, ENT_QUOTES, 'UTF-8')]);
        include 'src/views/partial/sidebar_button.php';
        $this->view->show('flat/edit.php', [
            'topic' => $topic,
            'filename' => $filename,
            'markdown' => $markdown,
            'saveError' => null
        ]);
        $this->view->show('partial/footer.php');
    }
}
