<?php
require __DIR__ . '/vendor/autoload.php';
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

// Validate that the requested file lives under the flat/ directory
$baseDir = realpath(__DIR__ . '/flat');
$path = realpath($baseDir . '/' . $slug . '.md');
if ($path === false || strpos($path, $baseDir) !== 0) {
    http_response_code(404);
    echo 'Page not found';
    exit;
}
// Convert back to a slug relative to flat/ for page metadata
$slug = substr($path, strlen($baseDir) + 1, -3);

$model = new FlatPageModel();
$markdown = $model->get($slug);
if ($markdown === null) {
    http_response_code(404);
    echo 'Page not found';
    exit;
}

// Build a minimal DocPHT page structure
$_SESSION['page_id'] = 'flat_' . md5($slug);

$view = new View();
$view->show('partial/head.php', ['PageTitle' => htmlspecialchars($slug, ENT_QUOTES, 'UTF-8')]);

$html = new DocPHT([$slug]);
$values = [
    $html->markdown($markdown),
    $html->addButton(),
];

$view->show('page/page.php', ['values' => $values]);
$view->show('partial/footer.php');
