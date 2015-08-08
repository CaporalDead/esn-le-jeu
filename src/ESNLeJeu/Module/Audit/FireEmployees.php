<?php

namespace Jhiino\ESNLeJeu\Module\Audit;

use Jhiino\ESNLeJeu\Entity\ObjectDetails;
use Jhiino\ESNLeJeu\Module;
use Symfony\Component\DomCrawler\Crawler;

class FireEmployees extends Module
{
    /**
     * @var string
     */
    const URI_FIRE = '/ressources-humaines.php';

    /**
     * Clé pour la configuration
     *
     * @var string
     */
    protected $configKey = 'audit';

    /**
     * @var bool
     */
    protected $fire;

    /**
     * @var int
     */
    protected $maxFirePerHour;

    /**
     * Parse une page à la recherche de contrats à re négocier
     *
     * @param $page
     *
     * @return Crawler
     */
    protected function parsePage($page)
    {
        $url      = vsprintf('%s?C=%s&P=%s', [self::URI_FIRE, 'STS', $page]);
        $body     = $this->client->get($url);
        $crawler  = new Crawler($body);
        $children = $crawler->filter(self::CSS_FILTER);

        return $children;
    }

    /**
     * Tente de récupérer les informations d'un employé
     *
     * @param Crawler $crawler
     *
     * @return bool|ObjectDetails
     */
    protected function tryToGetInfos(Crawler $crawler)
    {
        $this->logger->debug('On cherche le bouton "Infos"');

        $button = Node::buttonExists($crawler, 'td:nth-child(4) > a.btn', 'Infos');

        if (! $button) {
            $this->logger->debug('Pas de bouton "Infos"');

            return false;
        }

        $temp    = explode(',', $button->attr('onclick'));
        $id      = preg_replace('/\D/', '', $temp[0]);
        $numRow  = rand(1, 30);
        $post    = [
            'a'      => 'VRH',
            'id_r'   => $id,
            'numrow' => $numRow
        ];
        $html    = $this->client->post(self::AJAX_ACTION_URI, $post);
        $crawler = new Crawler($html);

        $this->logger->debug(sprintf('Les détails de l\'employé [%s %s]', $id, $numRow));

        return new ObjectDetails($crawler, $id, $numRow);
    }

    /**
     * Tente de virer un employé
     *
     * @param Crawler $crawler
     * @param         $idToFire
     * @param         $numRow
     *
     * @return bool|Crawler
     */
    protected function tryToFire(Crawler $crawler, $idToFire, $numRow)
    {
        $this->logger->debug('On cherche le bouton "Virer"');

        $button = Node::buttonExists($crawler, 'td:nth-child(1) > div > a.btn', 'Virer');

        if (! $button) {
            $this->logger->debug('Pas de bouton "Virer"');

            return false;
        }

        $post    = [
            'a'      => 'V',
            'id_r'   => $idToFire,
            'numrow' => $numRow
        ];
        $html    = $this->client->post(self::AJAX_ACTION_URI, $post);
        $crawler = new Crawler($html);

        return $crawler;
    }

    /**
     * Tente d'accepter de virer un employé
     *
     * @param Crawler $crawler
     * @param         $idToFire
     * @param         $numRow
     *
     * @return bool|Crawler
     */
    protected function tryToAccept(Crawler $crawler, $idToFire, $numRow)
    {
        $this->logger->debug('On cherche le bouton "Confirmer"');

        $button = Node::buttonExists($crawler, 'td:nth-child(1) > div > a.btn', 'Confirmer');

        if (! $button) {
            $this->logger->debug('Pas de bouton "Confirmer"');

            return false;
        }

        $post    = [
            'a'      => 'VC',
            'id_r'   => $idToFire,
            'numrow' => $numRow
        ];
        $html    = $this->client->post(self::AJAX_ACTION_URI, $post);
        $crawler = new Crawler($html);

        return $crawler;
    }

    /**
     * Action
     */
    public function fire()
    {
        if (! $this->fire) {
            return;
        }

        $numberOfEmployees      = 0;
        $numberOfEmployeesFired = 0;
        $page                   = 1;

        do {
            if ($numberOfEmployeesFired > $this->maxFirePerHour) {
                $this->logger->info(sprintf('On a atteins la limite max par heure : %s', $this->maxFirePerHour));

                break;
            }

            $employees = $this->parsePage($page);
            $numberOfEmployees += $employees->count();

            if (0 == $employees->count()) {
                $this->logger->debug(sprintf('Aucun employé à virer sur la page %s', $page));
            }

            $employees->each(function (Crawler $crawler) use (& $numberOfEmployeesFired) {
                if ($numberOfEmployeesFired > $this->maxFirePerHour) {
                    return;
                }

                if ($details = $this->tryToGetInfos($crawler)) {
                    if ($fire = $this->tryToFire($details->crawler, $details->id, $details->numRow)) {
                        if (false !== $this->tryToAccept()) {
                            $numberOfEmployeesFired++;
                        }
                    }
                }
            });

            $page++;
        } while (true);

        $this->logger->info(sprintf('Nombre d\'employés à virer : %s', $numberOfEmployees));
        $this->logger->info(sprintf('Nombre d\'employés virés : %s', $numberOfEmployeesFired));
        $this->logger->info(sprintf('Nombre d\'employés max à virer par heure : %s', $this->maxFirePerHour));
    }

    /**
     * @param array $parameters
     *
     * @return $this
     */
    public function applyConfig(array $parameters = [])
    {
        $parameters = array_merge($this->getDefaultConfiguration(), $parameters[$this->getConfigKey()]);

        $this->fire           = $parameters['fire'];
        $this->maxFirePerHour = $parameters['max_fire_per_hour'];

        return $this;
    }

    /**
     * @return array
     */
    public function getDefaultConfiguration()
    {
        return [
            'renegociate_contracts' => true,
            'max_fire_per_hour'     => 30,
        ];
    }
}
