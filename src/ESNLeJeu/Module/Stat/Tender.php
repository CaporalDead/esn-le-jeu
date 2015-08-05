<?php

namespace Jhiino\ESNLeJeu\Module\Stat;

use Jhiino\ESNLeJeu\Module;
use Symfony\Component\DomCrawler\Crawler;

class Tender extends Module
{
    /**
     * @var string
     */
    const PROPALES_URI = '/propale.php';

    /**
     * Action
     */
    public function fire()
    {
        // Offres gagnées
        $body    = $this->client->getConnection()->get(self::PROPALES_URI . '?C=PG')->getBody()->getContents();
        $crawler = new Crawler($body);
        $won     = preg_replace('/\D/', '', $crawler->filter('#main-content > div.nav-results > div')->html());

        // Offres perdues
        $body    = $this->client->getConnection()->get(self::PROPALES_URI . '?C=PP')->getBody()->getContents();
        $crawler = new Crawler($body);
        $lost    = preg_replace('/\D/', '', $crawler->filter('#main-content > div.nav-results > div')->html());

        $all        = $won + $lost;
        $percentage = ($all > 0) ? round($won / $all * 100, 2) : 0;

        $this->logger->info(vsprintf('Propales gagnées aujourd\'hui : %s/%s (%s%%)', [
            $won,
            $lost,
            $percentage,
        ]));
    }
}
