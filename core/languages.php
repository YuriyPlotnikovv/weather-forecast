<?php
global $MESS, $PATH, $LANG;

$allowed_languages = ['en', 'ru'];

function getLangFromBrowser($allowed_languages)
{
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $browser_languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);

        foreach ($browser_languages as $lang) {
            $lang = substr($lang, 0, 2);
            if (in_array($lang, $allowed_languages)) {
                return $lang;
            }
        }
    }
    return 'en';
}

$lang = getLangFromBrowser($allowed_languages);
$LANG = $lang;
$MESS = include $_SERVER['DOCUMENT_ROOT'] . "/lang/{$lang}.php";
