<?php

namespace Jhiino\ESNLeJeu\Module\Audit;

use Jhiino\ESNLeJeu\Helper\Node;
use Jhiino\ESNLeJeu\Module;
use Symfony\Component\DomCrawler\Crawler;

class RenegociateContract extends Module
{
    /**
     * URL de base pour les contrats � re n�gocier
     *
     * @var string
     */
    const URI_RENEGOTIATE = '/contrats.php';

    /**
     * Cl� pour la configuration
     *
     * @var string
     */
    protected $configKey = 'audit';

    /**
     * Indique si on autorise la re n�gociation de contrats
     *
     * @var bool
     */
    protected $renegotiateContracts;

    /**
     * Types de contrats � re n�gocier
     *
     * @var array
     */
    protected $contractsToRenegotiate = [];

    /**
     * Types de contrats � rompre
     *
     * @var array
     */
    protected $contractsToBreak = [];

    /**
     * Mapping entre les param�tres du module et les param�tres d'URL
     *
     * @var array
     */
    protected $paramsToVariable = [
        'very_good' => 'RE',
        'good'      => 'RN',
        'bad'       => 'RF',
        'very_bad'  => 'RI',
    ];

    /**
     * Renvoi les types de contrats � re n�gocier
     *
     * @return array
     */
    protected function getTypesToRenegociate()
    {
        $types = [];

        foreach ($this->contractsToRenegotiate as $type => $status) {
            if (true === $status) {
                $types[] = $this->paramsToVariable[$type];
            }
        }

        return $types;
    }

    /**
     * Renvoi les types de contrat � rompre si on ne peut pas les re n�gocier
     *
     * @return array
     */
    protected function getTypesToBreak()
    {
        $types = [];

        foreach ($this->contractsToBreak as $type => $status) {
            if (true === $status) {
                $types[] = $this->paramsToVariable[$type];
            }
        }

        return $types;
    }

    /**
     * Parse une page � la recherche de contrats � re n�gocier
     *
     * @param $type
     * @param $page
     *
     * @return Crawler
     */
    protected function parsePage($type, $page)
    {
        $url      = vsprintf('%s?C=%s&P=%s', [self::URI_RENEGOTIATE, $type, $page]);
        $body     = $this->client->getConnection()->get($url)->send()->getBody(true);
        $crawler  = new Crawler($body);
        $children = $crawler->filter(self::CSS_FILTER);

        return $children;
    }

    /**
     * Tente d'obtenir les d�tails d'un contrat
     *
     * @param $crawler
     *
     * @return bool|ContractDetails
     */
    protected function tryToGetDetails(Crawler $crawler)
    {
        $this->logger->debug('On cherche le bouton "D�tails"');

        $button = Node::buttonExists($crawler, 'td:nth-child(6) > a.btn', 'D�tails');

        if (! $button) {
            $this->logger->debug('Pas de bouton "D�tails"');

            return false;
        }

        $temp    = explode(',', $button->attr('onclick'));
        $id      = preg_replace('/\D/', '', $temp[0]);
        $numRow  = rand(1, 30);
        $post    = [
            'a'      => 'CIF',
            'id_r'   => $id,
            'numrow' => $numRow
        ];
        $html    = $this->client->getConnection()->post(self::AJAX_ACTION_URI, [], $post)->send()->getBody(true);
        $crawler = new Crawler($html);

        $this->logger->debug(sprintf('Les d�tails du contrat [%s %s]', $id, $numRow));

        return new ContractDetails($crawler, $id, $numRow);
    }

    /**
     * Tente de re n�gocier un contrat
     *
     * @param Crawler $crawler
     * @param         $idToRenegociate
     * @param         $numRow
     *
     * @return bool|Crawler
     */
    protected function tryToRenegociate(Crawler $crawler, $idToRenegociate, $numRow)
    {
        $this->logger->debug('On cherche le bouton "Ren�gocier"');

        $button = Node::buttonExists($crawler, 'td:nth-child(1) > div > a.tuto-renego', 'Ren�gocier', true);

        if (! $button) {
            $this->logger->debug('Pas de bouton "Ren�gocier"');

            return false;
        }

        $post    = [
            'a'      => 'C_RENOGO0',
            'id_r'   => $idToRenegociate,
            'numrow' => $numRow,
        ];
        $html    = $this->client->getConnection()->post(self::AJAX_ACTION_URI, [], $post)->send()->getBody(true);
        $crawler = new Crawler($html);

        $this->logger->debug(sprintf('On tente de ren�gocier le contrat [%s %s]', $idToRenegociate, $numRow));

        return $crawler;
    }

    /**
     * Tente une re n�gociation amicale � 5%
     *
     * @param Crawler $crawler
     * @param         $idToRenegociate
     * @param         $numRow
     *
     * @return bool|Crawler
     */
    protected function tryToFriendlyNegotiate(Crawler $crawler, $idToRenegociate, $numRow)
    {
        $this->logger->debug('On cherche le bouton "Ren�gocier � 5%"');

        $button = Node::buttonExists($crawler, 'td:nth-child(1) > div.curved2 > div > a.positif', '5% - N�gociation amicale', true);

        if (! $button) {
            $this->logger->debug('Pas de bouton "Ren�gocier � 5%"');

            return false;
        }

        $post    = [
            'a'      => 'C_RENOGO1',
            'id_r'   => $idToRenegociate,
            'numrow' => $numRow
        ];
        $html    = $this->client->getConnection()->post(self::AJAX_ACTION_URI, [], $post)->send()->getBody(true);
        $crawler = new Crawler($html);

        $this->logger->debug(sprintf('On tente de ren�gocier � 5% le contrat [%s %s]', $idToRenegociate, $numRow));

        return $crawler;
    }

    /**
     * Tente d'accepter une re n�gociation de contrat
     *
     * @param Crawler $crawler
     * @param         $idToRenegociate
     * @param         $numRow
     *
     * @return bool|Crawler
     */
    protected function tryToAccept(Crawler $crawler, $idToRenegociate, $numRow)
    {
        $this->logger->debug('On cherche le bouton "Accepter"');

        $button = Node::buttonExists($crawler, 'td:nth-child(1) > div.curved2 > div > a.positif', 'Accepter', true);

        if (! $button) {
            $this->logger->debug('Pas de bouton "Accepter"');

            return false;
        }

        $post    = [
            'a'      => 'C_RENOGO11',
            'id_r'   => $idToRenegociate,
            'numrow' => $numRow
        ];
        $html    = $this->client->getConnection()->post(self::AJAX_ACTION_URI, [], $post)->send()->getBody(true);
        $crawler = new Crawler($html);

        $this->logger->debug(sprintf('On d\'accepter la ren�gociation � 5% pour le contrat [%s %s]', $idToRenegociate, $numRow));

        return $crawler;
    }

    /**
     * Tente de rompre un contrat
     *
     * @param Crawler $crawler
     * @param         $idToBreak
     * @param         $numRow
     *
     * @return bool|Crawler
     */
    protected function tryToBreak(Crawler $crawler, $idToBreak, $numRow)
    {
        $this->logger->debug('On cherche le bouton "Rompre"');

        $button = Node::buttonExists($crawler, 'div:nth-child(1) > a:nth-child(4)', 'Rompre', true);

        if (! $button) {
            $this->logger->debug('Pas de bouton "Rompre"');

            return false;
        }

        $post    = [
            'a'      => 'CRC',
            'id_r'   => $idToBreak,
            'numrow' => $numRow
        ];
        $html    = $this->client->getConnection()->post(self::AJAX_ACTION_URI, [], $post)->send()->getBody(true);
        $crawler = new Crawler($html);

        $this->logger->debug(sprintf('On tente de rompre le contrat [%s %s]', $idToBreak, $numRow));

        return $crawler;
    }

    /**
     * Action
     */
    public function fire()
    {
        if (! $this->renegotiateContracts) {
            return;
        }

        $typesToRenegociate = $this->getTypesToRenegociate();
        $typesToBreak       = $this->getTypesToBreak();

        $numberOfContracts      = 0;
        $numberOfRenegotiations = 0;
        $numberOfBreaks         = 0;

        foreach ($typesToRenegociate as $currentType) {
            $this->logger->debug(sprintf('On attaque les ren�go pour le type %s', $currentType));

            $page = 1;

            do {
                $contracts = $this->parsePage($currentType, $page);
                $numberOfContracts += $contracts->count();

                if (0 == $contracts->count()) {
                    $this->logger->debug(sprintf('Aucun contrat n\'est � ren�gocier pour le type %s sur la page %s', $currentType, $page));

                    break;
                }

                $contracts->each(function (Crawler $crawler) use ($currentType, $typesToBreak, & $numberOfContracts, & $numberOfRenegotiations, & $numberOfBreaks) {
                    if ($details = $this->tryToGetDetails($crawler)) {
                        if ($renegociate = $this->tryToRenegociate($details->crawler, $details->id, $details->numRow)) {
                            if ($friendlyNegociation = $this->tryToFriendlyNegotiate($renegociate, $details->id, $details->numRow)) {
                                if (false !== $this->tryToAccept($friendlyNegociation, $details->id, $details->numRow)) {
                                    $numberOfRenegotiations++;
                                }
                            }
                        } elseif (in_array($currentType, $typesToBreak)) {
                            if (false !== $this->tryToBreak($details, $details->id, $details->numRow)) {
                                $numberOfBreaks++;
                            }
                        }
                    }
                });

                $page++;
            } while (true);
        }

        $this->logger->info(sprintf('Nombre de contrats � re n�gocier : %s', $numberOfContracts));
        $this->logger->info(sprintf('Nombre de contrats re n�goci�s : %s', $numberOfRenegotiations));
        $this->logger->info(sprintf('Nombre de contrats rompus : %s', $numberOfBreaks));
    }

    /**
     * @param array $parameters
     *
     * @return $this
     */
    public function applyConfig(array $parameters = [])
    {
        $parameters = array_merge($this->getDefaultConfiguration(), $parameters[$this->getConfigKey()]);

        $this->renegotiateContracts   = $parameters['renegotiate_contracts'];
        $this->contractsToRenegotiate = $parameters['renegotiate_type'];
        $this->contractsToBreak       = $parameters['break_type'];

        return $this;
    }
}
