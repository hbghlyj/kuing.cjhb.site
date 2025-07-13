<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/lib/DocPHT.php';

$parser = new DocPHT\Lib\MediaWikiParsedown();
$input = '$$E=mc^2$$ [example](https://example.com)';
$output = $parser->text($input);

if (strpos($output, '<a href="https://example.com">example</a>') !== false) {
    echo "Markdown link parsed successfully\n";
    exit(0);
}

echo "Failed to parse trailing markdown link\n";
exit(1);

