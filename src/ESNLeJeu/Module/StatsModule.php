<?php

namespace Jhiino\ESNLeJeu\Module;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @deprecated
 */
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
        $body    = $this->client->getConnection()->get(self::HOME_URI)->getBody()->getContents();
        $crawler = new Crawler($body);

        preg_match('!\d+(?:\.\d+)?!', $crawler->filter('div.meter > span')->attr('style'), $matches);

        $popularity = reset($matches);

        $this->logger->info(sprintf('Popularité : %s%%', $popularity));

        return $popularity;
    }

    public function tenders()
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

    public function stock()
    {
        $body    = $this->client->getConnection()->get(self::HOME_URI)->getBody()->getContents();
        $crawler = new Crawler($body);

        try {
            $progression = $crawler->filter('#stats > dl:nth-child(3) > dd > a > span')->text();
            $stock       = $crawler->filter('#stats > dl:nth-child(3) > dd > a')->text();

            preg_match('/%   (.*\ \$)/', $stock, $matches);

            $this->logger->info(sprintf('Progression de votre action ce jour : %s', $progression));
            $this->logger->info(sprintf('Cours de votre action : %s', $matches[1]));

            return [
                'progress' => $progression,
                'stock'    => $matches[1],
            ];
        } catch (\Exception $e) {
        }

        return [
            'progress' => '0 %',
            'stock'    => '0 $',
        ];
    }

    public function dashboard()
    {
        $body    = $this->client->getConnection()->get(self::HOME_URI)->getBody()->getContents();
        $crawler = new Crawler($body);

        try {
            $ca12Jours                         = $crawler->filter('#list-tb > div:nth-child(1) > div > a')->text();
            $marge12Jours                      = $crawler->filter('#list-tb > div:nth-child(2) > div > a')->text();
            $nombreEmployes                    = $crawler->filter('#list-tb > div:nth-child(3) > div > a')->text();
            $embaucheAujourdhui                = $crawler->filter('#list-tb > div:nth-child(4) > div > a')->text();
            $intercosAujourdhui                = $crawler->filter('#list-tb > div:nth-child(5) > div > a')->text();
            $tauxMecontents                    = $crawler->filter('#list-tb > div:nth-child(6) > div > a')->text();
            $demissionsLicenciementsAujourdhui = $crawler->filter('#list-tb > div:nth-child(7) > div > a')->text();
            $pex                               = $crawler->filter('#list-tb > div:nth-child(8) > div > a')->text();
            $bouclierAntiOPA                   = $crawler->filter('#list-tb > div:nth-child(9) > div > a')->text();
            $parachute                         = $crawler->filter('#list-tb > div:nth-child(10) > div > a')->text();

            $this->logger->info(sprintf('CA sur 12 jours : %s', $ca12Jours));
            $this->logger->info(sprintf('Marge sur 12 jours : %s', $marge12Jours));
            $this->logger->info(sprintf('Nombre d\'employés : %s', $nombreEmployes));
            $this->logger->info(sprintf('Embauche ce jour : %s', $embaucheAujourdhui));
            $this->logger->info(sprintf('ICS ce jour : %s', $intercosAujourdhui));
            $this->logger->info(sprintf('Taux mécontents : %s', $tauxMecontents));
            $this->logger->info(sprintf('Démissions/Licenciements ce jour : %s', $demissionsLicenciementsAujourdhui));
            $this->logger->info(sprintf('PEX disponibles : %s', $pex));
            $this->logger->info(sprintf('Bouclier anti-OPA : %s', $bouclierAntiOPA));
            $this->logger->info(sprintf('Parachute doré : %s $', $parachute));

            return [
                'ca12Jours'                         => $ca12Jours,
                'marge12Jours'                      => $marge12Jours,
                'nombreEmployes'                    => $nombreEmployes,
                'embaucheAujourdhui'                => $embaucheAujourdhui,
                'intercosAujourdhui'                => $intercosAujourdhui,
                'tauxMecontents'                    => $tauxMecontents,
                'demissionsLicenciementsAujourdhui' => $demissionsLicenciementsAujourdhui,
                'pex'                               => $pex,
                'bouclierAntiOPA'                   => $bouclierAntiOPA,
                'parachute'                         => $parachute
            ];
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return [
            'ca12Jours'                         => 0,
            'marge12Jours'                      => 0,
            'nombreEmployes'                    => 0,
            'embaucheAujourdhui'                => 0,
            'intercosAujourdhui'                => 0,
            'tauxMecontents'                    => 0,
            'demissionsLicenciementsAujourdhui' => 0,
            'pex'                               => 0,
            'bouclierAntiOPA'                   => 0,
            'parachute'                         => 0
        ];
    }
}