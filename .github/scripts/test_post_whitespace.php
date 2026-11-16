<?php
// Run the actual final display transformation, independent of database setup.
$root = dirname(__DIR__, 2);
$source = file_get_contents($root.'/source/function/function_discuzcode.php');
$start = strpos($source, "\t\$segments = preg_split(");
$end = strpos($source, "\n\tfor(\$i = 0;", $start);
if($start === false || $end === false) throw new RuntimeException('Display transformation not found');
$format = static function($message) use ($source, $start, $end) {
	eval(substr($source, $start, $end - $start));
	return $result;
};
$tikz = "\\begin{tikzpicture}\n  \\draw (0,0)  -- (1,1);\n\\end{tikzpicture}";
$expected = str_replace("\n", '<br>', $tikz);
if($format($tikz) !== $expected) throw new RuntimeException('TikZ spaces changed');
if($format("a  b\r\nc   d\re\nf") !== 'a  b<br>c   d<br>e<br>f') throw new RuntimeException('Spaces or newline variants changed');
if($format("a<br>b\n\nc") !== 'a<br>b<br><br>c') throw new RuntimeException('Explicit or empty lines changed');
$style = "<style>p {  color: red;\n}</style>";
if($format($style) !== $style) throw new RuntimeException('Style content was formatted as text');
$html = '<blockquote>a  b</blockquote><table><tr><td>c  d</td></tr></table>';
if($format($html) !== $html) throw new RuntimeException('Quote/table markup changed');
foreach(['template/default/common/module.css' => '.t_f[id^="postmessage_"]', 'template/default/touch/common/common.css' => '.plc .pi .message'] as $file => $selector) {
	if(!preg_match('/'.preg_quote($selector, '/').'\s*\{\s*white-space:\s*pre-wrap\s*;?\s*\}/', file_get_contents($root.'/'.$file))) {
		throw new RuntimeException('Missing scoped pre-wrap: '.$file);
	}
}
foreach(['template/default/forum/viewthread_node_body.htm', 'template/default/touch/forum/viewthread.htm'] as $file) {
	$template = file_get_contents($root.'/'.$file);
	if(str_contains($template, 'post-message-text')) throw new RuntimeException('Unexpected body wrapper changes the crawler DOM: '.$file);
}
echo "Post spaces, TikZ, newline variants, quotes, tables and scoped CSS passed\n";
// Source formatting inside a pre-wrap cell must not precede the real message.
$body = file_get_contents($root.'/template/default/forum/viewthread_node_body.htm');
preg_match_all('/<td class="t_f" id="postmessage_\$post\[pid\]">(.*?)<\/td>/s', $body, $cells);
if(count($cells[1]) !== 3) throw new RuntimeException('Unexpected post body branches');
foreach($cells[1] as $cell) {
	if(str_contains($cell, "\n") || str_contains($cell, "\r")) throw new RuntimeException('Template newline inside pre-wrap post cell');
}
