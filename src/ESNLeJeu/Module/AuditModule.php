<?php

namespace Jhiino\ESNLeJeu\Module;

use Jhiino\ESNLeJeu\Config\ConfigAwareInterface;
use Jhiino\ESNLeJeu\Helper\Node;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\DomCrawler\Crawler;

class AuditModule extends Module implements ConfigAwareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var string
     */
    const URI_FIRE = '/ressources-humaines.php';

    /**
     * @var string
     */
    const URI_RENEGOTIATE = '/contrats.php';

    protected $paramsToVariable = [
        'very_good' => 'RE',
        'good'      => 'RN',
        'bad'       => 'RF',
        'very_bad'  => 'RI',
    ];

    /**
     * @var bool
     */
    protected $fire;

    /**
     * @var int
     */
    protected $maxFirePerHour;

    /**
     * @var bool
     */
    protected $renegotiateContracts;

    /**
     * @var array
     */
    protected $contractsToRenegotiate = [];

    /**
     * @var
     */
    protected $contractsToBreak = [];

    /**
     * Permet de virer les salariés trop payés
     *
     * @return int
     */
    public function fireEmployees()
    {
        /** @var int $result */
        $result = 0;
        $page   = 1;

        if (! $this->fire) {
            return $result;
        }

        do {
            $url  = vsprintf('%s?C=%s&P=%s', [self::URI_FIRE, 'STS', $page]);
            $body = $this->client->getConnection()->get($url)->send()->getBody(true);

            $crawler = new Crawler($body);

            $children = $crawler->filter(self::CSS_FILTER);

            if (0 == $children->count()) {
                break;
            }

            $children->each(function (Crawler $crawler) use (&$result) {
                $button = Node::buttonExists($crawler, 'td:nth-child(4) > a.btn', 'Infos');

                // Si bouton info
                if ($button) {
                    // Analyser les possibilités
                    $temp    = explode(',', $button->attr('onclick'));
                    $id      = preg_replace('/\D/', '', $temp[0]);
                    $numrow  = rand(1, 30);
                    $post    = [
                        'a'      => 'VRH',
                        'id_r'   => $id,
                        'numrow' => $numrow
                    ];
                    $html    = $this->client->getConnection()->post(self::AJAX_ACTION_URI, [], $post)->send();
                    $crawler = new Crawler($html);
                    $button  = Node::buttonExists($crawler, 'td:nth-child(1) > div > a.btn', 'Virer');

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

                        $this->logger->debug('Et hop un employé mis à la porte');

                        $result++;
                    }
                }
            });

            // Max par heure
            if ($result > $this->maxFirePerHour) {
                $this->logger->debug('On a viré beaucoup de monde, on fais une pause jusqu\'à la prochaine heure.');

                break;
            }

            $page++;
        } while (true);

        return $result;
    }

    public function renegotiateContracts()
    {
        /** @var array $results */
        $results = [];

        if (! $this->renegotiateContracts) {
            return $results;
        }

        $todo = [];

        foreach ($this->contractsToRenegotiate as $type => $status) {
            if (true === $status) {
                $todo[] = $this->paramsToVariable[$type];
            }
        }

        $break = [];

        foreach ($this->contractsToBreak as $type => $status) {
            if (true === $status) {
                $break[] = $this->paramsToVariable[$type];
            }
        }

        foreach ($todo as $type) {
            $this->logger->debug(sprintf('On attaque les renégo pour le type %s', $type));

            $page = 1;

            do {
                $this->logger->debug(sprintf('On affiche la page %s', $page));

                $url      = vsprintf('%s?C=%s&P=%s', [self::URI_RENEGOTIATE, $type, $page]);
                $body     = $this->client->getConnection()->get($url)->send()->getBody(true);
                $crawler  = new Crawler($body);
                $children = $crawler->filter(self::CSS_FILTER);

                if (0 == $children->count()) {
                    $this->logger->debug('Rien à faire par ici...');

                    break;
                }

                $children->each(function (Crawler $crawler) use (&$results, $type, $break) {
                    $button = Node::buttonExists($crawler, 'td:nth-child(6) > a.btn', 'Détails');

                    // Si bouton détails
                    if ($button) {
                        $this->logger->debug('Bouton détails trouvé');

                        // Analyser les possibilités
                        $temp    = explode(',', $button->attr('onclick'));
                        $id      = preg_replace('/\D/', '', $temp[0]);
                        $numrow  = rand(1, 30);
                        $post    = [
                            'a'      => 'CIF',
                            'id_r'   => $id,
                            'numrow' => $numrow
                        ];
                        $html    = $this->client->getConnection()->post(self::AJAX_ACTION_URI, [], $post)->send()->getBody(true);
                        $crawler = new Crawler($html);
                        $button  = Node::buttonExists($crawler, 'td:nth-child(1) > div > a.tuto-renego', 'Renégocier', true);

                        // Si bouton renégocier
                        if ($button) {
                            $this->logger->debug('On peut renégocier');

                            $post = [
                                'a'      => 'C_RENOGO0',
                                'id_r'   => $id,
                                'numrow' => $numrow
                            ];

                            $html    = $this->client->getConnection()->post(self::AJAX_ACTION_URI, [], $post)->send()->getBody(true);
                            $crawler = new Crawler($html);
                            $button  = Node::buttonExists($crawler, 'td:nth-child(1) > div.curved2 > div > a.positif', '5% - Négociation amicale', true);

                            if ($button) {
                                $this->logger->debug('On tente la renégo amicale');

                                $post    = [
                                    'a'      => 'C_RENOGO1',
                                    'id_r'   => $id,
                                    'numrow' => $numrow
                                ];
                                $html    = $this->client->getConnection()->post(self::AJAX_ACTION_URI, [], $post)->send()->getBody(true);
                                $crawler = new Crawler($html);
                                $button  = Node::buttonExists($crawler, 'td:nth-child(1) > div.curved2 > div > a.positif', 'Accepter', true);

                                // Accepter
                                if ($button) {
                                    $post = [
                                        'a'      => 'C_RENOGO11',
                                        'id_r'   => $id,
                                        'numrow' => $numrow
                                    ];

                                    $this->client->getConnection()->post(self::AJAX_ACTION_URI, [], $post)->send()->getBody(true);

                                    $this->logger->debug('Une renégo de contrat à 5%');

                                    $results++;
                                }
                            }
                        } elseif (in_array($type, $break)) {
                            $this->logger->debug('On tente de rompre le contrat de type ' . $type);

                            $button  = Node::buttonExists($crawler, 'div:nth-child(1) > a:nth-child(4)', 'Rompre', true);

                            if ($button) {
                                $this->logger->debug('On romps le contrat');

                                $numrow  = rand(1, 30);
                                $post    = [
                                    'a'      => 'CRC',
                                    'id_r'   => $id,
                                    'numrow' => $numrow
                                ];

                                $this->client->getConnection()->post(self::AJAX_ACTION_URI, [], $post)->send()->getBody(true);
                            }
                        }
                    } else {
                        $this->logger->debug('Pas de bouton détails');
                    }
                });

                $page++;
            } while (true);
        }

        return $results;
    }

    /**
     * @param array $parameters
     *
     * @return $this
     */
    public function applyConfig(array $parameters = [])
    {
        $parameters = array_merge($this->getDefaultConfiguration(), $parameters[$this->getConfigKey()]);

        $this->fire                   = $parameters['fire'];
        $this->maxFirePerHour         = $parameters['max_fire_per_hours'];
        $this->renegotiateContracts   = $parameters['renegotiate_contracts'];
        $this->contractsToRenegotiate = $parameters['renegotiate_type'];
        $this->contractsToBreak       = $parameters['break_type'];

        return $this;
    }

    /**
     * @return string
     */
    public function getConfigKey()
    {
        return 'audit';
    }

    /**
     * @return array
     */
    public function getDefaultConfiguration()
    {
        return [
            'fire'                  => false,
            'max_fire_per_hours'    => 30,
            'renegociate_contracts' => true,
            'renegotiate_type'      => [
                'very_good' => true,
                'good'      => true,
                'bad'       => true,
                'very_bad'  => true,
            ],
            'break_type'      => [
                'very_good' => false,
                'good'      => false,
                'bad'       => false,
                'very_bad'  => false,
            ],
        ];
    }
}