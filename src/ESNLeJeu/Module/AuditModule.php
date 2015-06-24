<?php namespace Jhiino\ESNLeJeu\Module;

use Jhiino\ESNLeJeu\Entity\Applicant;
use Jhiino\ESNLeJeu\Entity\CareerProfiles;
use Jhiino\ESNLeJeu\Entity\NewApplicant;
use Jhiino\ESNLeJeu\Entity\Options;
use Jhiino\ESNLeJeu\Entity\Ressource;
use Jhiino\ESNLeJeu\Entity\Scheduler;
use Jhiino\ESNLeJeu\Entity\Tender;
use Jhiino\ESNLeJeu\Helper\Node;
use Symfony\Component\DomCrawler\Crawler;

class AuditModule extends Module
{
    /**
     * @var string
     */
    const URI_FIRE = '/ressources-humaines.php';

    /**
     * @var string
     */
    const URI_RENEGOTIATE = '/contrats.php';

    /**
     * Permet de virer les salariés trop payés
     * @return int
     */
    public function fireEmployees()
    {
        /** @var int $result */
        $result = 0;

        $page = 1;

        do {
            $url  = vsprintf('%s?C=%s&P=%s', [self::URI_FIRE, 'STS', $page]);
            $body = $this->client->getConnection()->get($url)->send()->getBody(true);

            $crawler = new Crawler($body);

            $children = $crawler->filter(self::CSS_FILTER);

            if (0 == $children->count()) {
                break;
            }

            $children->each(
                function (Crawler $crawler) use (&$result) {

                    $button = Node::buttonExists($crawler, 'td:nth-child(4) > a.btn', 'Infos');

                    // Si bouton info
                    if ($button) {

                        // Analyser les possibilités
                        $temp   = explode(',', $button->attr('onclick'));
                        $id     = preg_replace('/\D/', '', $temp[0]);
                        $numrow = rand(1, 30);

                        $post = [
                            'a'      => 'VRH',
                            'id_r'   => $id,
                            'numrow' => $numrow
                        ];

                        $html    = $this->client->getConnection()->post(self::AJAX_ACTION_URI, [], $post)->send();
                        $crawler = new Crawler($html);

                        $button = Node::buttonExists($crawler, 'td:nth-child(1) > div > a.btn', 'Virer');

                        if ($button) {

                            // Virer
                            $post = [
                                'a'      => 'V',
                                'id_r'   => $id,
                                'numrow' => $numrow
                            ];

                            $this->client->getConnection()->post(self::AJAX_ACTION_URI, [], $post)->send();

                            // Confirmer
                            $post = [
                                'a'      => 'VC',
                                'id_r'   => $id,
                                'numrow' => $numrow
                            ];

                            $this->client->getConnection()->post(self::AJAX_ACTION_URI, [], $post)->send();

                            $result++;
                        }
                    }
                }
            );

            $page++;
        } while (true);

        return $result;
    }

    public function renegotiateContracts()
    {
        /** @var array $results */
        $results = [];

        foreach (['RE', 'RN', 'RF', 'RI'] as $type) {
            $page = 1;

            do {
                $url = vsprintf('%s?C=%s&P=%s', [self::URI_RENEGOTIATE, $type, $page]);

                $body    = $this->client->getConnection()->get($url)->send()->getBody(true);
                $crawler = new Crawler($body);

                $children = $crawler->filter(self::CSS_FILTER);

                if (0 == $children->count()) {
                    break;
                }

                $children->each(
                    function (Crawler $crawler) use (&$results) {

                        $button = Node::buttonExists($crawler, 'td:nth-child(6) > a.btn', 'Détails');

                        // Si bouton détails
                        if ($button) {
                            // Analyser les possibilités
                            $temp   = explode(',', $button->attr('onclick'));
                            $id     = preg_replace('/\D/', '', $temp[0]);
                            $numrow = rand(1, 30);

                            $post = [
                                'a'      => 'CIF',
                                'id_r'   => $id,
                                'numrow' => $numrow
                            ];

                            $html    = $this->client->getConnection()->post(self::AJAX_ACTION_URI, [], $post)->send()->getBody(true);
                            $crawler = new Crawler($html);

                            $button = Node::buttonExists($crawler, 'td:nth-child(1) > div > a.tuto-renego', 'Renégocier', true);

                            // Si bouton renégocier
                            if ($button) {

                                $post = [
                                    'a'      => 'C_RENOGO0',
                                    'id_r'   => $id,
                                    'numrow' => $numrow
                                ];

                                $html    = $this->client->getConnection()->post(self::AJAX_ACTION_URI, [], $post)->send()->getBody(true);
                                $crawler = new Crawler($html);

                                $button = Node::buttonExists($crawler, 'td:nth-child(1) > div.curved2 > div > a.positif', '5% - Négociation amicale', true);

                                // Négociation 5%
                                if ($button) {
                                    $post = [
                                        'a'      => 'C_RENOGO1',
                                        'id_r'   => $id,
                                        'numrow' => $numrow
                                    ];

                                    $html    = $this->client->getConnection()->post(self::AJAX_ACTION_URI, [], $post)->send()->getBody(true);
                                    $crawler = new Crawler($html);

                                    $button = Node::buttonExists($crawler, 'td:nth-child(1) > div.curved2 > div > a.positif', 'Accepter', true);

                                    // Accepter
                                    if ($button) {
                                        $post = [
                                            'a'      => 'C_RENOGO11',
                                            'id_r'   => $id,
                                            'numrow' => $numrow
                                        ];

                                        $this->client->getConnection()->post(self::AJAX_ACTION_URI, [], $post)->send()->getBody(true);

                                        $results++;
                                    }
                                }
                            }
                        }
                    }
                );

                // Max par heure
                if ($results > Options::AUDIT_MAX_FIRE_PER_HOUR) {
                    break;
                }

                $page++;
            } while (true);
        }

        return $results;
    }
}