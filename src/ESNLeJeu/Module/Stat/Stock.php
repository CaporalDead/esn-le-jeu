<?php

namespace Jhiino\ESNLeJeu\Module\Stat;

use Exception;
use Jhiino\ESNLeJeu\Module;
use Symfony\Component\DomCrawler\Crawler;

class Stock extends Module
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

        try {
            $progression = $crawler->filter('#stats > dl:nth-child(3) > dd > a > span')->text();
            $stock       = $crawler->filter('#stats > dl:nth-child(3) > dd > a')->text();

            preg_match('/%   (.*\ \$)/', $stock, $matches);

            $this->logger->info(sprintf('Progression de votre action ce jour : %s', $progression));
            $this->logger->info(sprintf('Cours de votre action : %s', $matches[1]));
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
