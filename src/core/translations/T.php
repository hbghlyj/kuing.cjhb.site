<?php

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

namespace DocPHT\Core\Translator;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\ArrayLoader;

class T
{
    /**
     * Detect preferred language from HTTP headers
     *
     * @return string
     */
    public static function detectLang(): string
    {
        $accept = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        if ($accept) {
            $parts = explode(',', $accept);
            if (!empty($parts[0])) {
                $locale = str_replace('-', '_', trim($parts[0]));
                // try full locale
                if (file_exists('src/translations/'.$locale.'.php')) {
                    return $locale;
                }
                // try language only
                $base = strtolower(substr($locale, 0, 2));
                $candidate = $base.'_'.strtoupper($base);
                if (file_exists('src/translations/'.$candidate.'.php')) {
                    return $candidate;
                }
            }
        }
        return 'en_EN';
    }
    /**
     * Trans static method for string translations
     *
     * @param  string $string
     * @param  array $array
     *
     * @return string
     */
    public static function trans($string, $array = null)
    {
        $lang = self::detectLang();
        $t = new Translator($lang);
        $t->addLoader('array', new ArrayLoader());
        if (file_exists('src/translations/'.$lang.'.php')) {
            include 'src/translations/'.$lang.'.php';
        }

        if (isset($array)) {
            return $t->trans($string, $array);
        } else {
            return $t->trans($string);
        }

    }
}