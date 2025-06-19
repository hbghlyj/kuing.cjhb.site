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
    /** @var Translator|null */
    private static $translator = null;

    /** @var string|null */
    private static $translatorLang = null;
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
                if (preg_match('/^[a-zA-Z]{2}_[a-zA-Z]{2}$/', $locale)) {
                    $locale = strtolower(substr($locale, 0, 2)) . '_' . strtoupper(substr($locale, 3, 2));
                    if (file_exists('src/translations/'.$locale.'.php')) {
                        return $locale;
                    }
                }
            }
        }
        return 'en_US';
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

        if (self::$translator === null || self::$translatorLang !== $lang) {
            $t = new Translator($lang);
            $t->addLoader('array', new ArrayLoader());
            if (file_exists('src/translations/'.$lang.'.php')) {
                include 'src/translations/'.$lang.'.php';
            }
            self::$translator = $t;
            self::$translatorLang = $lang;
        } else {
            $t = self::$translator;
        }

        if ($array !== null) {
            return $t->trans($string, $array);
        }
        return $t->trans($string);
    }
}
