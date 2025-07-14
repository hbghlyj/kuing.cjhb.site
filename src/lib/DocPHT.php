<?php declare(strict_types=1);

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

namespace DocPHT\Lib;
use \Izadori\ParsedownPlus\ParsedownPlus;
use DocPHT\Core\Translator\T;

class MediaWikiParsedown extends ParsedownPlus
{
    public function __construct()
    {
        parent::__construct();
        $this->InlineTypes['['][] = 'MediaWikiUrl';

        // disable underscore for emphasis to allow LaTeX formulas
        unset($this->InlineTypes['_']);
        if (($k = array_search('_', $this->specialCharacters, true)) !== false) {
            unset($this->specialCharacters[$k]);
        }
        unset($this->StrongRegex['_'], $this->EmRegex['_']);
    }
    protected $inlineMarkerList = '!*&[:<`~\\';

    protected function inlineMediaWikiUrl($Excerpt)
    {
        if (preg_match('/^\[\[([^\s#\]]+)([^\s\]]*)\]\]/', $Excerpt['text'], $matches))
        {
            $path     = $matches[1];
            $fragment = $matches[2];

            $requestUriParts = explode('/', $_SERVER['REQUEST_URI']);
            end($requestUriParts);
            $currentTopic = urldecode(prev($requestUriParts));

            if (strpos($path, '/') !== false) {
                list($topic, $filename) = explode('/', $path, 2);
            } else {
                $topic    = $currentTopic;
                $filename = $path;
            }

            $newPage = !file_exists('page/' . $topic . '/' . $filename . '.md');
            $url = $newPage ? '/page/create?mainfilename=' . urlencode($filename) : '/page/' . $topic . '/' . $filename;

            $attributes = [
                'href' => $url . $fragment,
            ];
            if ($newPage) {
                $attributes['class'] = 'new';
            }

            $Inline = array(
                'extent' => strlen($matches[0]),
                'element' => array(
                    'name' => 'a',
                    'text' => $filename . $fragment,
                    'attributes' => $attributes,
                ),
            );
            return $Inline;
        }
    }

    // Fix greedy LaTeX block parsing by allowing closing $$ anywhere on the line
    // and parsing any trailing Markdown after the closing delimiter
    protected function blockMath($Line)
    {
        if (substr($Line['text'], 0, 2) === '$$') {
            $Block = [
                'char' => '$',
                'markup' => '',
            ];

            $closePos = strpos($Line['text'], '$$', 2);

            if ($closePos !== false) {
                $Block['markup'] = substr($Line['text'], 0, $closePos + 2);
                $remainder = substr($Line['text'], $closePos + 2);
                if (trim($remainder) !== '') {
                    $Block['markup'] .= "\n" . $this->line($remainder);
                }
                $Block['complete'] = true;
            } else {
                $Block['markup'] = $Line['text'];
            }

            return $Block;
        }
    }

    protected function blockMathContinue($Line, $Block)
    {
        if (isset($Block['complete'])) {
            return;
        }

        if (isset($Block['interrupted'])) {
            $Block['markup'] .= "\n";
            unset($Block['interrupted']);
        }

        if (($pos = strpos($Line['text'], '$$')) !== false) {
            $Block['markup'] .= "\n" . substr($Line['text'], 0, $pos + 2);
            $remainder = substr($Line['text'], $pos + 2);
            if (trim($remainder) !== '') {
                $Block['markup'] .= "\n" . $this->line($remainder);
            }
            $Block['complete'] = true;
            return $Block;
        }

        $Block['markup'] .= "\n" . $Line['body'];

        return $Block;
    }

    // Preserve escape sequences for backslash and dollar sign so that
    // MathJax can handle them on the frontend without double escaping
    protected function inlineEscapeSequence($Excerpt)
    {
        if (isset($Excerpt['text'][1]) && in_array($Excerpt['text'][1], ['\\', '$'])) {
            return [
                'markup' => substr($Excerpt['text'], 0, 2),
                'extent' => 2,
            ];
        }

        return parent::inlineEscapeSequence($Excerpt);
    }

    // Treat + in URLs as a space character
    protected function inlineImage($Excerpt)
    {
        $Inline = parent::inlineImage($Excerpt);
        if (isset($Inline['element']['attributes']['src'])) {
            // Replace + with space in the src attribute
            $Inline['element']['attributes']['src'] = str_replace('+', ' ', $Inline['element']['attributes']['src']);
        }
        return $Inline;
    }

    protected function inlineMath($Excerpt)
    {
        if (isset($Excerpt['text'][1]) && $Excerpt['text'][0] === '$' && $Excerpt['text'][1] === '$') {
            if (preg_match('/^\$\$([^\n]+?)\$\$/s', $Excerpt['text'], $matches)) {
                $text = preg_replace('/[ ]*\n/', ' ', $matches[1]);
                return [
                    'markup' => '$$' . htmlspecialchars($text) . '$$',
                    'extent' => strlen($matches[0]),
                ];
            }
        }

        return parent::inlineMath($Excerpt);
    }
}
class DocPHT {

    /**
     * __construct
     *
     * @param  array $anchorLinks
     *
     * @return string
     */
    public function __construct(?array $anchorLinks = null)
    {
        return $this->anchorLinks($anchorLinks);
    }

    /**
     * title
     *
     * @param  string $title
     * @param  string $anchorLinkID
     *
     * @return string
     */
    public function title(string $title, ?string $anchorLinkID = null, int $level = 2)
    {
       $level = max(1, min(6, $level));
       if (isset($anchorLinkID)) {
        return '<h'.$level.' class="mt-3 mb-3" id="'.$anchorLinkID.'">'.$title.'</h'.$level.'>';
       }
       return '<h'.$level.' class="mt-3 mb-3">'.$title.'</h'.$level.'>';
    }


    /**
     * anchorLinks
     *
     * @param  array $anchorLinks
     *
     * @return ?string
     */
    public function anchorLinks(?array $anchorLinks = null)
    {
        if (isset($anchorLinks)) {
            echo '<nav class="navbar navbar-expand-lg navbar-light bg-light">
                    <div class="container-fluid">

                        <button type="button" id="sidebarCollapse" class="btn btn-secondary">
                            <i class="fa fa-align-left"></i>
                        </button>
                        <button class="btn btn-dark d-inline-block d-lg-none ml-auto" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                            <i class="fa fa-align-justify"></i>
                        </button>

                        <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <ul class="nav navbar-nav ml-auto">
                            '; foreach ($anchorLinks as $value) {
                                $title = $value;
                                echo '<li class="nav-item">
                                            <a class="nav-link" href="#'.$value.'">'.$title.'</a>
                                      </li>';
                            };
                    echo '</ul></div>
                    </div>
                </nav>';
        } else {
            echo '<nav class="navbar navbar-expand-lg navbar-light bg-light">
                    <div class="container-fluid">
                        <button type="button" id="sidebarCollapse" class="btn btn-secondary">
                            <i class="fa fa-align-left"></i>
                        </button>
                        <button class="btn btn-dark d-inline-block d-lg-none ml-auto" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                            <i class="fa fa-align-justify"></i>
                        </button>
                    </div>
                </nav>';
        }
    }



    /**
     * image
     *
     * @param  string $src
     * @param  string $title
     *
     * @return string
     */
    public function image(string $src, string $title)
    {
        return '<img src="/json/'.$src.'" class="img-fluid mb-3" alt="'.$title.'">';
    }




    /**
     * markdown
     *
     * @param  string $text
     *
     * @return string
     */
    public function markdown(string $text)
    {
        $Parsedown = new MediaWikiParsedown();
        return $Parsedown->text($text);
    }


    /**
     * addButton
     *
     *
     * @return ?string
     */
    public function addButton()
    {
        if (isset($_SESSION['Active'])) {
            return '<ul class="list-inline text-left mt-4">
                    <li class="list-inline-item" data-toggle="tooltip" data-placement="bottom" title="'.T::trans('Add').'"><a href="/page/add-section" id="sk-add" class="btn btn-outline-success btn-sm" role="button">
                            <i class="fa fa-plus" aria-hidden="true"></i>
                        </a>
                    </li>
                </ul>';
        }
    }
}