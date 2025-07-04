<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/src/config/config.php';

use DocPHT\Core\Session\Session;
use DocPHT\Core\Helper\DiscuzBridge;
use DocPHT\Model\FlatPageModel;
use Instant\Core\Views\View;

$session = new Session();
$session->sessionExpiration();
$session->preventStealingSession();
DiscuzBridge::syncSession();

if (!isset($_SESSION['Active'])) {
    http_response_code(403);
    echo 'Unauthorized';
    exit;
}

$slug = $_GET['page'] ?? '';
if ($slug === '') {
    http_response_code(400);
    echo 'Missing page parameter';
    exit;
}

$model = new FlatPageModel();
$markdown = $model->get($slug);
if ($markdown === null) {
    $markdown = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = $_POST['markdown'] ?? '';
    if ($model->put($slug, $content)) {
        header('Location: flat_doc.php?page=' . rawurlencode($slug));
        exit;
    }
    echo 'Failed to save';
}

$view = new View();
$view->show('partial/head.php', ['PageTitle' => 'Edit ' . htmlspecialchars($slug, ENT_QUOTES, 'UTF-8')]);
include 'src/views/partial/sidebar_button.php';
?>
<div class="card fade-in-fwd">
    <div class="card-body">
        <form method="post">
            <div class="form-group">
                <textarea name="markdown" class="form-control" rows="20" data-autoresize required><?php echo htmlspecialchars($markdown); ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="flat_doc.php?page=<?php echo htmlspecialchars($slug); ?>" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
<?php
$view->show('partial/footer.php');
