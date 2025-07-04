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


// Parse markdown to HTML and split by headers
$parsedown = new \DocPHT\Lib\MediaWikiParsedown();
$htmlContent = $parsedown->text($markdown);

$parts = preg_split('/(<h[1-6][^>]*>.*?<\/h[1-6]>)/i', $htmlContent, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

$segments = [];
$current = '';
$anchors = [];

foreach ($parts as $part) {
    if (preg_match('/^<h[1-6][^>]*>(.*?)<\/h[1-6]>$/is', trim($part), $m)) {
        if ($current !== '') {
            $segments[] = ['type' => 'markdown', 'text' => $current];
            $current = '';
        }
        $titleText = strip_tags($m[1]);
        $anchor = preg_replace('/[^a-z0-9]+/i', '-', strtolower($titleText));
        $segments[] = ['type' => 'title', 'text' => $titleText, 'anchor' => $anchor];
        $anchors[] = $anchor;
    } else {
        $current .= $part;
    }
}
if ($current !== '') {
    $segments[] = ['type' => 'markdown', 'text' => $current];
}

// Build a minimal DocPHT page structure
$_SESSION['page_id'] = 'flat_' . md5($slug);

$view = new View();
$view->show('partial/head.php', ['PageTitle' => htmlspecialchars($slug, ENT_QUOTES, 'UTF-8')]);

$html = new DocPHT($anchors);
$values = [];
foreach ($segments as $segment) {
    if ($segment['type'] === 'title') {
        $values[] = $html->title($segment['text'], $segment['anchor']);
    } else {
        $values[] = $html->markdown($segment['text']);
    }
}
$values[] = $html->addButton();

$view->show('page/page.php', ['values' => $values, 'flatSlug' => $slug]);
$view->show('partial/footer.php');
