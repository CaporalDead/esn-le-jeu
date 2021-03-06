<?php

namespace Jhiino\ESNLeJeu\Module\Stat;

use Jhiino\ESNLeJeu\Module;
use Symfony\Component\DomCrawler\Crawler;

class Popularity extends Module
{
    /**
     * @var string
     */
    const HOME_URI = '/';

    /**
     * Action
     */
    public function fire()
    {
        $body    = $this->client->get(self::HOME_URI);
        $crawler = new Crawler($body);

        preg_match('!\d+(?:\.\d+)?!', $crawler->filter('div.meter > span')->attr('style'), $matches);

        $popularity = reset($matches);

        $this->logger->info(sprintf('Popularité : %s%%', $popularity));
    }
}
