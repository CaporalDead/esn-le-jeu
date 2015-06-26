<?php

namespace Jhiino\ESNLeJeu\Module;

use Jhiino\ESNLeJeu\Config\ConfigAwareInterface;
use Jhiino\ESNLeJeu\Entity\Applicant;
use Jhiino\ESNLeJeu\Entity\CareerProfiles;
use Jhiino\ESNLeJeu\Entity\NewApplicant;
use Jhiino\ESNLeJeu\Entity\Options;
use Jhiino\ESNLeJeu\Entity\Ressource;
use Jhiino\ESNLeJeu\Entity\Scheduler;
use Jhiino\ESNLeJeu\Entity\Tender;
use Jhiino\ESNLeJeu\Helper\Node;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\DomCrawler\Crawler;

class TendersModule extends Module implements ConfigAwareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var string
     */
    const URI = '/place-de-marche.php';

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

    /**
     * @var bool
     */
    protected $hire;

    /*
     * @return Tender[]
     */
    public function tenders()
    {
        /** @var Tender[] $tenders */
        $tenders = [];

        foreach (CareerProfiles::getArrayOf() as $careerProfile) {
            $page = 1;

            do {
                $url      = vsprintf('%s?C=%s&P=%s', [self::URI, $careerProfile, $page]);
                $body     = $this->client->getConnection()->get($url)->send()->getBody(true);
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
                        $tender = self::parseFromHtml($child, $careerProfile, $page);

                        if ($tender instanceof Tender && $tender->weeks >= $this->minWeeks) {
                            $tenders[] = $tender;
                        }
                    }
                });

                $page++;
            } while (true);
        }

        $this->logger->info(sprintf('Appels d\'offres selon critères (>= %s semaines) : %s', $this->minWeeks, count($tenders)));

        return $tenders;
    }

    /**
     * @param Crawler $crawler
     * @param         $careerProfile
     * @param         $page
     *
     * @return Tender
     */
    public static function parseFromHtml(Crawler $crawler, $careerProfile, $page)
    {
        $id       = preg_replace('/\D/', '', $crawler->attr('id'));
        $customer = filter_var(trim($crawler->filter('td:nth-child(1)')->html()), FILTER_SANITIZE_STRING);
        $weeks    = preg_replace('/\D/', '', $crawler->filter('td:nth-child(3)')->html());
        $budget   = preg_replace('/\D/', '', $crawler->filter('td:nth-child(4)')->html());

        return new Tender($id, $customer, $careerProfile, $weeks, $budget, $page);
    }

    /**
     * @param Tender    $tender
     * @param Ressource $ressource
     */
    private function evaluateMargin(Tender &$tender, Ressource $ressource)
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
     * Répondre aux appels d'offres
     *
     * @return array
     */
    public function bidOnTenders()
    {
        $tenders       = $this->tenders();
        $bids          = [];
        $allIdles      = [];
        $allApplicants = [];

        do {
            // Indexer le tableau
            $tenders = array_values($tenders);
            // Sélection d'une offre au hasard
            /** @var Tender $tender */
            $key    = array_rand($tenders, 1);
            $tender = $tenders[$key];

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

                    Scheduler::getInstance()->waitBeforeNextBid();
                }
            }

            // Supprimer l'offre du tableau
            unset($tenders[$key]);
        } while (count($bids) < $this->maxBidPerHour && ! empty($tenders));

        $this->logger->info(sprintf('Inter missions à placer : %s', count($allIdles)));
        $this->logger->info(sprintf('Réponses aux appels d\'offres : %s', count($bids)));
        $this->logger->info(sprintf('Recrutements : %s', count($allApplicants)));

        return [
            'bids'          => $bids,
            'idles'         => $allIdles,
            'newApplicants' => $allApplicants
        ];
    }

    /**
     * @param Tender $tender
     *
     * @return bool
     */
    private function bid(Tender $tender)
    {
        $return    = false;
        $ressource = $tender->ressource;

        if ($ressource instanceof Ressource && null != $ressource->id && $tender->margin >= $this->minInterestMargin) {
            $post    = [
                'a'        => 'AOC',
                'id_ao'    => $tender->id,
                'numrow'   => rand(1, 30),
                'tarifv'   => $tender->businessProposal,
                'num_cand' => $ressource->id,
                'typ_cand' => $ressource::CODE
            ];
            $body    = $this->client->getConnection()->post(self::AJAX_ACTION_URI, [], $post)->send()->getBody(true);
            $crawler = new Crawler($body);
            $node    = Node::nodeExists($crawler, 'td.pannel_e > b');

            if ($node && 0 === stripos($node->html(), 'Bravo')) {
                $return = true;
            }
        }

        $this->logger->debug(vsprintf('%s : Offre [%s], Ressource [%s], Profil [%s], Marge brute [%s]', [
            ($return) ? 'Placement OK' : 'Placement KO',
            $tender->id,
            $tender->ressource->id,
            $tender->careerProfile,
            $tender->margin
        ]));

        return $return;
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