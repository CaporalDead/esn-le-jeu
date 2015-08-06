<?php

namespace Jhiino\ESNLeJeu\Module\Stat;

use Exception;
use Jhiino\ESNLeJeu\Module;
use Symfony\Component\DomCrawler\Crawler;

class Dashboard extends Module
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
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
