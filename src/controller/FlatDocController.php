<?php
namespace DocPHT\Controller;

use Instant\Core\Controller\BaseController;
use DocPHT\Model\FlatPageModel;
use DocPHT\Lib\DocPHT;
use DocPHT\Controller\ErrorPageController;

class FlatDocController extends BaseController
{
    public function show($topic, $filename)
    {
        $slug = $topic . '/' . $filename;
        $model = new FlatPageModel();
        $markdown = $model->get($slug);
        if ($markdown === null) {
            $error = new ErrorPageController();
            $error->getPage($topic, $filename);
            return;
        }

        $parsedown = new \DocPHT\Lib\MediaWikiParsedown();
        $htmlContent = $parsedown->text($markdown);

        $parts = preg_split('/(<h[1-6][^>]*>.*?<\\/h[1-6]>)/i', $htmlContent, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        $segments = [];
        $current = '';
        $anchors = [];
        foreach ($parts as $part) {
            if (preg_match('/^<h[1-6][^>]*>(.*?)<\\/h[1-6]>$/is', trim($part), $m)) {
                if ($current !== '') {
                    $segments[] = ['type' => 'markdown', 'text' => $current];
                    $current = '';
                }
                $titleText = strip_tags($m[1]);
                $baseAnchor = preg_replace('/[^a-z0-9]+/i', '-', strtolower($titleText));
                $anchor = $baseAnchor;
                $counter = 2;
                while (in_array($anchor, $anchors, true)) {
                    $anchor = $baseAnchor . '-' . $counter++;
                }
                $segments[] = ['type' => 'title', 'text' => $titleText, 'anchor' => $anchor];
                $anchors[] = $anchor;
            } else {
                $current .= $part;
            }
        }
        if ($current !== '') {
            $segments[] = ['type' => 'markdown', 'text' => $current];
        }

        $_SESSION['page_id'] = 'flat_' . md5($slug);

        $this->view->show('partial/head.php', ['PageTitle' => htmlspecialchars($slug, ENT_QUOTES, 'UTF-8')]);

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

        $this->view->show('page/page.php', ['values' => $values, 'flatSlug' => $slug]);
        $this->view->show('partial/footer.php');
    }
}
