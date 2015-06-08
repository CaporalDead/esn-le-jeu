<?php namespace Jhiino\ESNLeJeu\Module;

use Jhiino\ESNLeJeu\Entity\Applicant;
use Jhiino\ESNLeJeu\Entity\CareerProfiles;
use Jhiino\ESNLeJeu\Entity\NewApplicant;
use Jhiino\ESNLeJeu\Entity\Ressource;
use Jhiino\ESNLeJeu\Entity\Scheduler;
use Jhiino\ESNLeJeu\Entity\Tender;
use Symfony\Component\DomCrawler\Crawler;

class TendersModule extends Module
{
    /**
     * @var string
     */
    const HIRE = true;

    /**
     * @var string
     */
    const URI = '/place-de-marche.php';

    /*
     * @return Tender[]
     */
    public function tenders()
    {
        /** @var Tender[] $tenders */
        $tenders = [];

        foreach (CareerProfiles::getArrayOf() as $useless => $careerProfile) {
            $page = 1;

            do {
                $url  = vsprintf('%s?C=%s&P=%s', [self::URI, $careerProfile, $page]);
                $body = $this->client->getConnection()->get($url)->send()->getBody(true);

                $crawler = new Crawler($body);

                $children = $crawler->filter(self::CSS_FILTER);

                if (0 == $children->count()) {
                    break;
                }

                $children->each(
                    function (Crawler $child) use (&$tenders, $careerProfile, $page) {
                        if ('répondu' != trim($child->filter('td:nth-child(5)')->html())
                            && null == $child->filter('td:nth-child(3)')->filter('span.ui-icon-locked')->getNode(0)
                        ) {
                            $tender = self::parseFromHtml($child, $careerProfile, $page);

                            if ($tender instanceof Tender && $tender->weeks >= Tender::MIN_WEEKS) {
                                $tenders[] = $tender;
                            }
                        }
                    }
                );

                $page++;
            } while (true);
        }

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

        if ($margin >= Tender::MIN_INTEREST_MARGIN && $margin > $tender->margin) {
            $tender->margin    = $margin;
            $tender->ressource = $ressource;
        }
    }

    /**
     * Répondre aux appels d'offres
     *
     * @param array $tenders
     *
     * @return array
     */
    public function bidOnTenders(array $tenders = [])
    {
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
            if ($tender->weeks >= Tender::MIN_WEEKS) {
                // 1 - Pour chaque offre, essayer de placer un idle
                /** @var Ressource[] $idles */
                $idles = EmployeesModule::idlesForCareerProfile($this->client, $tender->careerProfile, $tender);
                $allIdles += $idles;

                /** @var Ressource $ressource */
                foreach ($idles as $ressource) {
                    $this->evaluateMargin($tender, $ressource);
                }

                // 2 - Sinon essayer de recruter
                if (null == $tender->ressource && self::HIRE) {
                    /** @var Ressource[] $idles */
                    $newApplicants = EmployeesModule::applicantsForCareerProfile($this->client, $tender->careerProfile);

                    /** @var NewApplicant $newApplicant */
                    foreach ($newApplicants as $newApplicant) {
                        $this->evaluateMargin($tender, $newApplicant);
                    }

                    // Recrutement
                    if ($tender->ressource instanceof NewApplicant) {
                        $applicant = EmployeesModule::hire($this->client, $newApplicant, $tender);

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

                    Scheduler::waitBeforeNextBid();
                }
            }

            // Supprimer l'offre du tableau
            unset($tenders[$key]);
        } while (count($bids) < Tender::MAX_BID_PER_HOUR && ! empty($tenders));

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
        $ressource = $tender->ressource;

        if ($ressource instanceof Ressource
            && null != $ressource->id
            && $tender->margin >= Tender::MIN_INTEREST_MARGIN
        ) {

            // Se rendre sur la page en question
//        $url  = vsprintf(self::URI . '?C=%s&P=%s', [$tender->careerProfile, $tender->page]);
//        $body = $this->client->getConnection()->get($url)->send()->getBody(true);
//        $crawler = new Crawler($body);

            $post = [
                'a'        => 'AOC',
//            'colorchange' => rawurldecode($crawler->filter('.colorchange')->html()),
                'id_ao'    => $tender->id,
                'numrow'   => rand(1, 30),
                'tarifv'   => $tender->businessProposal,
                'num_cand' => $ressource->id,
                'typ_cand' => $ressource::CODE
            ];

            $body    = $this->client->getConnection()->post(self::AJAX_ACTION_URI, [], $post)->send()->getBody(true);
            $crawler = new Crawler($body);

            if ($crawler->filter('td.pannel_e > b')->count()) {
                if (0 === stripos($crawler->filter('td.pannel_e > b')->html(), 'Bravo')) {
                    return true;
                }
            } else {
                print(PHP_EOL . 'Une erreur est survenue dans le placement d\'une ressource : ' . PHP_EOL);
                var_dump($tender);
                var_dump($crawler);
            }
        }

        return false;
    }
}