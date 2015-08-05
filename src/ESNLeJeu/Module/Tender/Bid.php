<?php

namespace Jhiino\ESNLeJeu\Module\Tender;

use Jhiino\ESNLeJeu\Entity\Applicant;
use Jhiino\ESNLeJeu\Entity\CareerProfiles;
use Jhiino\ESNLeJeu\Entity\NewApplicant;
use Jhiino\ESNLeJeu\Entity\Scheduler;
use Jhiino\ESNLeJeu\Entity\Tender;
use Jhiino\ESNLeJeu\Helper\Node;
use Jhiino\ESNLeJeu\Module;
use Jhiino\ESNLeJeu\Module\EmployeesModule;
use Symfony\Component\DomCrawler\Crawler;

class Bid extends Module
{
    /**
     * @var string
     */
    const URI = '/place-de-marche.php';

    /**
     * @var Tender[]
     */
    protected $tenders = [];

    /**
     * @var bool
     */
    protected $hire;

    /**
     * @var int
     */
    protected $minWeeks;

    /**
     * @var float
     */
    protected $minInterestMargin;

    /**
     * @var float
     */
    protected $tradePromotion;

    /**
     * @var int
     */
    protected $maxBidPerHour;

    protected function findTenders()
    {
        $this->tenders = [];

        foreach (CareerProfiles::getArrayOf() as $careerProfile) {
            $page = 1;

            do {
                $body     = $this->client->get(self::URI, $careerProfile, ['C' => $careerProfile, 'P' => $page]);
                $crawler  = new Crawler($body);
                $children = $crawler->filter(self::CSS_FILTER);

                if (0 == $children->count()) {
                    break;
                }

                $children->each(function (Crawler $child) use (&$tenders, $careerProfile, $page) {
                    // Si le bouton voir existe et qu'il n'y a pas de cadenas
                    if (
                        Node::buttonExists($child, 'td:nth-child(5) > a.btn', 'Voir')
                        && ! Node::nodeExists($child, 'td:nth-child(3) > span.ui-icon-locked')
                    ) {
                        $tender = $this->parseFromHtml($child, $careerProfile, $page);

                        if ($tender instanceof Tender && $tender->weeks >= $this->minWeeks) {
                            $tenders[] = $tender;
                        }
                    }
                });

                $page++;
            } while (true);
        }

        $this->logger->info(sprintf('Appels d\'offres selon critères (>= %s semaines) : %s', $this->minWeeks, count($tenders)));
    }

    /**
     * @param Crawler $crawler
     * @param string $careerProfile
     * @param int    $page
     *
     * @return Tender
     */
    protected function parseFromHtml(Crawler $crawler, $careerProfile, $page)
    {
        $id       = preg_replace('/\D/', '', $crawler->attr('id'));
        $customer = filter_var(trim($crawler->filter('td:nth-child(1)')->html()), FILTER_SANITIZE_STRING);
        $weeks    = preg_replace('/\D/', '', $crawler->filter('td:nth-child(3)')->html());
        $budget   = preg_replace('/\D/', '', $crawler->filter('td:nth-child(4)')->html());

        return new Tender($id, $customer, $careerProfile, $weeks, $budget, $page, $this->tradePromotion);
    }

    /**
     * @param Tender    $tender
     * @param Ressource $ressource
     */
    protected function evaluateMargin(Tender &$tender, Ressource $ressource)
    {
        // Calcul de la marge
        $margin = round(($tender->businessProposal - $ressource->cost) / $tender->businessProposal, 5);

        $this->logger->debug(vsprintf('Offre[%s] : %s, Ressource[%s] : %s, Marge brute[%s], Marge nette[%s]', [
            $tender->id,
            $tender->businessProposal,
            $ressource->id,
            $ressource->cost,
            $margin,
            round($margin - 0.21, 5)
        ]));

        if ($margin >= $this->minInterestMargin && $margin > $tender->margin) {
            $this->logger->debug(sprintf('Nouvelle marge : %s', $margin));

            $tender->margin    = $margin;
            $tender->ressource = $ressource;
        }
    }

    /**
     * Action
     */
    public function fire()
    {
        $this->findTenders();

        $bids          = [];
        $allIdles      = [];
        $allApplicants = [];

        do {
            // Indexer le tableau
            $this->tenders = array_values($this->tenders);
            // Sélection d'une offre au hasard
            /** @var Tender $tender */
            $key    = array_rand($this->tenders, 1);
            $tender = $this->tenders[$key];

            // Contrôler le nombre minimal de semaines
            if ($tender->weeks >= $this->minWeeks) {
                // 1 - Pour chaque offre, essayer de placer un idle
                /** @var Ressource[] $idles */
                $idles = EmployeesModule::idlesForCareerProfile($this->client, $tender);
                $allIdles += $idles;

                /** @var Ressource $ressource */
                foreach ($idles as $ressource) {
                    $this->evaluateMargin($tender, $ressource);
                }

                // 2 - Sinon essayer de recruter
                if (null == $tender->ressource && $this->hire) {
                    /** @var Ressource[] $idles */
                    $newApplicants = EmployeesModule::applicantsForCareerProfile($this->client, $tender->careerProfile);

                    /** @var NewApplicant $newApplicant */
                    foreach ($newApplicants as $newApplicant) {
                        $this->evaluateMargin($tender, $newApplicant);
                    }

                    // Recrutement
                    if ($tender->ressource instanceof NewApplicant) {
                        $applicant = EmployeesModule::hire($this->client, $tender->ressource, $tender);

                        // Recrutement réussi ?
                        if ($applicant instanceof Applicant) {
                            $tender->ressource = $applicant;
                            $allApplicants[]   = $applicant;
                        } else {
                            $tender->margin    = 0.0;
                            $tender->ressource = null;
                        }
                    }
                }

                // 3 - Envoyer l'offre
                if (null != $tender->ressource) {
                    if ($this->bid($tender)) {
                        // Stats
                        $bids[] = $tender;
                    }

                    Scheduler::getInstance()->waitBeforeNextAction();
                }
            }

            // Supprimer l'offre du tableau
            unset($this->tenders[$key]);
        } while (count($bids) < $this->maxBidPerHour && ! empty($this->tenders));

        $this->logger->info(sprintf('Inter missions à placer : %s', count($allIdles)));
        $this->logger->info(sprintf('Réponses aux appels d\'offres : %s', count($bids)));
        $this->logger->info(sprintf('Recrutements : %s', count($allApplicants)));
    }

    /**
     * @param array $parameters
     *
     * @return $this
     */
    public function applyConfig(array $parameters = [])
    {
        $parameters = array_merge($this->getDefaultConfiguration(), $parameters[$this->getConfigKey()]);

        $this->hire              = $parameters['hire'];
        $this->minWeeks          = $parameters['min_weeks'];
        $this->minInterestMargin = $parameters['min_interest_margin'];
        $this->tradePromotion    = $parameters['trade_promotion'];
        $this->maxBidPerHour     = $parameters['max_bid_per_hour'];

        return $this;
    }

    /**
     * @return string
     */
    public function getConfigKey()
    {
        return 'tenders';
    }

    /**
     * @return array
     */
    public function getDefaultConfiguration()
    {
        return [
            'hire'                => true,
            'min_weeks'           => 6,
            'min_interest_margin' => 0.22,
            'trade_promotion'     => 0.97,
            'max_bid_per_hour'    => 100,
        ];
    }
}
