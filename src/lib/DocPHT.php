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

            $newPage = !file_exists('flat/' . $topic . '/' . $filename . '.md');
            $url = $newPage ? '/page/create?mainfilename=' . urlencode($filename) : '/page/' . $topic . '/' . $filename;
            $Inline = array(
                'extent' => strlen($matches[0]),
                'element' => array(
                    'name' => 'a',
                    'text' => $filename . $fragment,
                    'attributes' => array(
                        'href' => $url . $fragment,
                        'class' => $newPage ? 'new' : '',
                    ),
                ),
            );
            return $Inline;
        }
    }

    // Fix greedy LaTeX block parsing by allowing closing $$ anywhere on the line
    protected function blockMath($Line)
    {
        if (preg_match('/^\\$\\$(.*)$/', $Line['text'], $matches)) {
            $Block = array(
                'char' => $Line['text'][0],
                'element' => array(
                    'name' => 'p',
                    'text' => $Line['text'],
                    'attributes' => array(
                        'class' => 'block-math'
                    )
                ),
            );

            if (strpos($matches[1], '$$') !== false) {
                $Block['complete'] = true;
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
            $Block['element']['text'] .= "\n";
            unset($Block['interrupted']);
        }

        if (strpos($Line['text'], '$$') !== false) {
            $Block['element']['text'] .= "\n" . $Line['text'];
            $Block['complete'] = true;
            return $Block;
        }

        $Block['element']['text'] .= "\n" . $Line['body'];

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
}

class DocPHT {

    /**
     * __construct
     *
     * @param  array $anchorLinks
     *
     * @return string
     */
    public function __construct(array $anchorLinks = null)
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
    public function title(string $title, string $anchorLinkID = null)
    {
       if (isset($anchorLinkID)) {
        return '<tr><td><h2 class="mt-3 mb-3" id="'.$anchorLinkID.'">'.$title.' </h2></td></tr>';
       }
       return '<tr><td><h2 class="mt-3 mb-3">'.$title.'  </h2></td></tr>';
    }


    /**
     * anchorLinks
     *
     * @param  array $anchorLinks
     *
     * @return string
     */
    public function anchorLinks(array $anchorLinks = null)
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
                </nav>
                <div class="table-responsive"><table class="sortable" width="100%"><tbody>';
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
                </nav>
                <div class="table-responsive"><table class="sortable" width="100%"><tbody>';
        }
        if (isset($_SESSION['Active'])) {
            echo '<tr class="range-handle text-center text-secondary start"><td data-toggle="tooltip" title="'.T::trans('Drag downwards to set the start of edit').'"><i class="fa fa-arrow-down sort"></i></td></tr>';
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
        return '<tr><td><img src="/json/'.$src.'" class="img-fluid mb-3" alt="'.$title.'"></td></tr>';
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
        $markdown = '<tr><td class="markdown-col">';
        $markdown .= $Parsedown->text($text);
        $markdown .= '</td></tr>';
        return $markdown;
    }


    /**
     * addButton
     *
     *
     * @return string
     */
    public function addButton()
    {
        if (isset($_SESSION['Active'])) {
            return '<tr class="range-handle text-center text-secondary end"><td data-toggle="tooltip" title="'.T::trans('Drag upwards to set the end of edit').'"><i class="fa fa-arrow-up sort"></i></td></tr>
            <tr><td><ul class="list-inline text-left mt-4">
                    <li class="list-inline-item" data-toggle="tooltip" data-placement="bottom" title="'.T::trans('Add').'"><a href="/page/add-section" id="sk-add" class="btn btn-outline-success btn-sm" role="button">
                            <i class="fa fa-plus-square" aria-hidden="true"></i>
                        </a>
                    </li>
                </ul></td></tr>
                </tbody></table></div>';
        } else {
            return '</tbody></table></div>';
        }
    }

    /**
     * removeButton
     *
     *
     * @return string
     */
    public function removeButton()
    {
        if (isset($_SESSION['Active'])) {
        return '<span class="text-right remove-button">
                    <a onmouseover="setIndexRemove(this)" href="/page/remove" onclick="return confirmationRemoval()" class="anchorjs-link btn btn-danger btn-sm text-right" data-toggle="tooltip" data-placement="bottom" title="'.T::trans('Remove').'" role="button">
                        <i class="fa fa-minus-square" aria-hidden="true" style="vertical-align: middle;"></i>
                    </a>
                </span>';
        } else {
            return '';
        }
    }

    /**
     * modifyButton
     *
     *
     * @return string
     */
    public function modifyButton()
    {
        if (isset($_SESSION['Active'])) {
        return '<span class="text-right modify-button">
                    <a onmouseover="setIndexModify(this)" href="/page/modify" class="anchorjs-link btn btn-info btn-sm text-right" data-toggle="tooltip" data-placement="bottom" title="'.T::trans('Modify').'" role="button">
                        <i class="fa fa-pencil-square" aria-hidden="true" style="vertical-align: middle;"></i>
                    </a>
                </span>';
        } else {
            return '';
        }
    }

    /**
     * insertBeforeButton
     *
     *
     * @return string
     */
    public function insertBeforeButton()
    {
        if (isset($_SESSION['Active'])) {
        return '<span class="text-right modify-button">
                    <a onmouseover="setIndexInsertB(this)" href="/page/insert" class="anchorjs-link btn btn-success btn-sm text-right" data-toggle="tooltip" data-placement="bottom" title="'.T::trans('Insert Before').'" role="button">
                        <i class="fa fa-arrow-circle-up" aria-hidden="true" style="vertical-align: middle;"></i>
                    </a>
                </span>';
        } else {
            return '';
        }
    }

    /**
     * InsertAfterButton
     *
     *
     * @return string
     */
    public function InsertAfterButton()
    {
        if (isset($_SESSION['Active'])) {
        return '<span class="text-right modify-button">
                    <a onmouseover="setIndexInsertA(this)" href="/page/insert" class="anchorjs-link btn btn-success btn-sm text-right" data-toggle="tooltip" data-placement="bottom" title="'.T::trans('Insert After').'" role="button">
                        <i class="fa fa-arrow-circle-down" aria-hidden="true" style="vertical-align: middle;"></i>
                    </a>
                </span>';
        } else {
            return '';
        }
    }


}