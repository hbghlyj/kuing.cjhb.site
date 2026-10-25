<?php
define('IN_DISCUZ', true);
require 'source/class/discuz/discuz_upload.php';
$sanitize = new ReflectionMethod('discuz_upload', 'sanitize_svg');
$file = tempnam(sys_get_temp_dir(), 'svg-marker-');
try {
    file_put_contents($file, <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="120" height="80" viewBox="0 0 120 80">
  <defs>
    <marker id="arrow" markerWidth="10" markerHeight="8" markerUnits="strokeWidth" refX="9" refY="4" orient="auto-start-reverse" viewBox="0 0 10 8" onload="alert(1)">
      <path d="M0 0L10 4L0 8Z" fill="context-stroke"/>
      <script>alert(1)</script>
    </marker>
    <marker id="_arrow:mid" markerWidth="4" markerHeight="4" refX="2" refY="2" orient="auto"><circle cx="2" cy="2" r="2"/></marker>
  </defs>
  <path id="direct" d="M10 20L100 20" marker-end="url(#arrow)"/>
  <path id="styled" d="M10 40L50 40L100 40" style="stroke:black;marker-start:url(#arrow);marker-mid:url(#_arrow:mid);marker-end:url(#arrow) !important"/>
  <path id="external" d="M10 60L100 60" marker-end="url(https://example.test/arrow.svg#arrow)"/>
  <path id="unsafeStyle" d="M10 70L100 70" style="marker-end:url(data:image/svg+xml,evil)"/>
</svg>
SVG);
    if(!$sanitize->invoke(null, $file)) throw new RuntimeException('Valid marker SVG rejected');
    $document = new DOMDocument();
    $document->load($file);
    $xpath = new DOMXPath($document);
    $xpath->registerNamespace('s', 'http://www.w3.org/2000/svg');
    $marker = $xpath->query('//s:marker[@id="arrow"]')->item(0);
    foreach(['markerWidth' => '10', 'markerHeight' => '8', 'markerUnits' => 'strokeWidth', 'refX' => '9', 'refY' => '4', 'orient' => 'auto-start-reverse'] as $key => $value) {
        if(!$marker || $marker->getAttribute($key) !== $value) throw new RuntimeException('Marker geometry lost: '.$key);
    }
    foreach(['direct' => ['marker-end' => 'url(#arrow)'], 'styled' => ['marker-start' => 'url(#arrow)', 'marker-mid' => 'url(#_arrow:mid)', 'marker-end' => 'url(#arrow)']] as $id => $attributes) {
        $path = $xpath->query('//s:path[@id="'.$id.'"]')->item(0);
        foreach($attributes as $key => $value) {
            if($path->getAttribute($key) !== $value) throw new RuntimeException('Local marker reference lost: '.$id.' '.$key);
        }
    }
    foreach(['external', 'unsafeStyle'] as $id) {
        if($xpath->query('//s:path[@id="'.$id.'"]')->item(0)->hasAttribute('marker-end')) throw new RuntimeException('External marker reference survived');
    }
    if($xpath->query('//s:script|//@onload|//@style')->length) throw new RuntimeException('Active SVG content survived');
    if($xpath->query('//s:marker[@id="arrow"]/s:path')->length !== 1) throw new RuntimeException('Arrow drawing lost');
    echo "SVG local markers and geometry survive sanitization; external URLs and scripts do not\n";
} finally {
    unlink($file);
}
