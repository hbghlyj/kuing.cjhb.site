<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/src/core/constants.php';
require __DIR__ . '/src/config/config.php';

use DocPHT\Core\Session\Session;
use DocPHT\Core\Helper\DiscuzBridge;
use DocPHT\Lib\DocPHT;
use DocPHT\Model\FlatPageModel;
use Instant\Core\Views\View;

$session = new Session();
$session->sessionExpiration();
$session->preventStealingSession();
DiscuzBridge::syncSession();

$slug = $_GET['page'] ?? 'example';
$slug = basename($slug);

$model = new FlatPageModel();
$markdown = $model->get($slug);
if ($markdown === null) {
    http_response_code(404);
    echo 'Page not found';
    exit;
}

// Build a minimal DocPHT page structure
$_SESSION['page_id'] = 'flat_' . md5($slug);
$html = new DocPHT([$slug]);
$values = [
    $html->markdown($markdown),
    $html->addButton(),
];

$view = new View();
$view->show('partial/head.php', ['PageTitle' => $slug]);
$view->show('page/page.php', ['values' => $values]);
$view->show('partial/footer.php');
