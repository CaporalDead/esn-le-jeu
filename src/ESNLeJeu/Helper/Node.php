<?php namespace Jhiino\ESNLeJeu\Helper;

use Symfony\Component\DomCrawler\Crawler;

class Node
{
    /**
     * @param Crawler $crawler
     * @param         $cssPath
     * @param string  $value
     * @param bool    $utf8Decode
     *
     * @return bool|Crawler
     */
    public static function buttonExists(Crawler $crawler, $cssPath, $value = '', $utf8Decode = false)
    {
        $button = $crawler->filter($cssPath);


        if ($button->count()) {
            $buttonValue = ($utf8Decode) ? $button->html() : utf8_decode($button->html());

            if ($value == $buttonValue) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Crawler $crawler
     * @param         $cssPath
     *
     * @return bool|Crawler
     */
    public static function nodeExists(Crawler $crawler, $cssPath)
    {
        $node = $crawler->filter($cssPath);

        return (null != $node->count()) ? $node : false;
    }
}