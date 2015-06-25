<?php

namespace Jhiino\ESNLeJeu\Module;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\DomCrawler\Crawler;

class StatsModule extends Module implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var string
     */
    const HOME_URI = '/';

    /**
     * @var string
     */
    const PROPALES_URI = '/propale.php';

    /*
     * @return float
     */
    public function popularity()
    {
        $body    = $this->client->getConnection()->get(self::HOME_URI)->send()->getBody(true);
        $crawler = new Crawler($body);

        preg_match('!\d+(?:\.\d+)?!', $crawler->filter('div.meter > span')->attr('style'), $matches);

        $popularity = reset($matches);

        $this->logger->info(sprintf('Popularité : %s%%', $popularity));

        return $popularity;
    }

    public function tenders()
    {
        // Offres gagnées
        $body    = $this->client->getConnection()->get(self::PROPALES_URI . '?C=PG')->send()->getBody(true);
        $crawler = new Crawler($body);
        $won     = preg_replace('/\D/', '', $crawler->filter('#main-content > div.nav-results > div')->html());

        // Offres perdues
        $body    = $this->client->getConnection()->get(self::PROPALES_URI . '?C=PP')->send()->getBody(true);
        $crawler = new Crawler($body);
        $lost    = preg_replace('/\D/', '', $crawler->filter('#main-content > div.nav-results > div')->html());

        $all        = $won + $lost;
        $percentage = ($all > 0) ? round($won / $all * 100, 2) : 0;

        $this->logger->info(vsprintf('Propales gagnees ce jour : %s (%s%%)', [
            $won,
            $percentage,
        ]));

        return [
            'won'        => $won,
            'lost'       => $lost,
            'percentage' => $percentage,
        ];
    }
}