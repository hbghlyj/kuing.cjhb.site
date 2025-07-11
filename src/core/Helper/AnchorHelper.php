<?php
namespace DocPHT\Core\Helper;

class AnchorHelper
{
    /**
     * Generate a unique anchor for a heading title.
     *
     * @param string $title Heading text
     * @param array<string> $existing Anchors already used
     * @return string Anchor
     */
    public static function generate(string $title, array &$existing): string
    {
        $base = preg_replace('/[ %\/#]/', '-', strtolower($title));
        $anchor = $base;
        $counter = 2;
        while (in_array($anchor, $existing, true)) {
            $anchor = $base . '-' . $counter++;
        }
        $existing[] = $anchor;
        return $anchor;
    }
}
