<?php

/**
 *      Markdown rendering for the aigeo_knowledge plugin.
 *
 *      Backed by Parsedown 1.7.4 (MIT, see Parsedown-LICENSE.txt).
 *
 *      The renderer is wrapped in a subclass so the emitted markup keeps
 *      matching source/plugin/aigeo_knowledge/static/css/aigeo-knowledge.css:
 *
 *        - heading levels are emitted as authored (h1..h6). The stylesheet
 *          styles the full range inside .aigeo-doc-body, so no clamping is
 *          needed and the document outline stays semantically correct.
 *        - tables are wrapped in <div class="aigeo-doc-table">  (.aigeo-doc-table table)
 *        - links get class="aigeo-link", and external links get
 *          target="_blank" rel="noopener noreferrer"
 *
 *      Safety: safeMode + markupEscaped are both enabled, which preserves the
 *      previous behaviour of escaping raw HTML instead of passing it through,
 *      and filters dangerous URL schemes (javascript:, data:, vbscript:).
 */

if(!defined('IN_DISCUZ')) exit('Access Denied');

require_once dirname(__FILE__).'/Parsedown.php';

if(!class_exists('AigeoKnowledgeParsedown')) {

class AigeoKnowledgeParsedown extends Parsedown
{
	/**
	 * Wrap tables so the existing .aigeo-doc-table styles (border, radius,
	 * horizontal scroll on narrow screens) keep applying. Done in the
	 * *Complete hook so table parsing itself is untouched.
	 */
	protected function blockTableComplete(array $Block)
	{
		if(method_exists(get_parent_class($this), 'blockTableComplete')) {
			$Block = parent::blockTableComplete($Block);
		}
		$Block['element'] = array(
			'name' => 'div',
			'attributes' => array('class' => 'aigeo-doc-table'),
			'handler' => 'elements',
			'text' => array($Block['element']),
		);
		return $Block;
	}

	/**
	 * Add the site link class, and only send genuinely external links to a new
	 * tab. Relative and in-page anchors must not get target="_blank".
	 */
	protected function inlineLink($Excerpt)
	{
		$Inline = parent::inlineLink($Excerpt);
		return $this->decorateLink($Inline);
	}

	protected function inlineUrl($Excerpt)
	{
		$Inline = parent::inlineUrl($Excerpt);
		return $this->decorateLink($Inline);
	}

	protected function inlineUrlTag($Excerpt)
	{
		$Inline = parent::inlineUrlTag($Excerpt);
		return $this->decorateLink($Inline);
	}

	/**
	 * Parsedown builds images by reusing inlineLink() and merging its
	 * attributes, which would otherwise copy the anchor decorations onto
	 * <img>. Strip them back off.
	 */
	protected function inlineImage($Excerpt)
	{
		$Inline = parent::inlineImage($Excerpt);
		if(isset($Inline['element']['attributes'])) {
			unset($Inline['element']['attributes']['class']);
			unset($Inline['element']['attributes']['target']);
			unset($Inline['element']['attributes']['rel']);
		}
		return $Inline;
	}

	protected function decorateLink($Inline)
	{
		if(!isset($Inline['element']['attributes']['href'])) {
			return $Inline;
		}
		$href = (string)$Inline['element']['attributes']['href'];
		$Inline['element']['attributes']['class'] = 'aigeo-link';
		if(preg_match('#^(https?:)?//#i', $href)) {
			$Inline['element']['attributes']['target'] = '_blank';
			$Inline['element']['attributes']['rel'] = 'noopener noreferrer';
		}
		return $Inline;
	}

}

}

/**
 * Shared, configured renderer instance.
 */
function aigeo_k_markdown_parser() {
	static $parser = null;
	if($parser === null) {
		$parser = new AigeoKnowledgeParsedown();
		$parser->setSafeMode(true);
		$parser->setMarkupEscaped(true);
		$parser->setBreaksEnabled(false);
		$parser->setUrlsLinked(true);
	}
	return $parser;
}
