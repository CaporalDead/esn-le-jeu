<?php namespace Jhiino\ESNLeJeu\Module;

use Jhiino\ESNLeJeu\Client;
use Jhiino\ESNLeJeu\Entity\Applicant;
use Jhiino\ESNLeJeu\Entity\Employee;
use Jhiino\ESNLeJeu\Entity\NewApplicant;
use Jhiino\ESNLeJeu\Entity\Options;
use Jhiino\ESNLeJeu\Entity\Ressource;
use Jhiino\ESNLeJeu\Entity\Tender;
use Symfony\Component\DomCrawler\Crawler;

class EmployeesModule extends Module
{
    /**
     * @var string
     */
    const IDLES_URI = '/place-de-marche-choix-empl-ajax.php';

    /**
     * @var string
     */
    const APPLICANTS_URI = '/banque-cv.php';

    /**
     * @param Client $client
     * @param Tender $tender
     *
     * @return Employee[]
     */
    public static function idlesForCareerProfile(Client $client, Tender $tender)
    {
        /** @var Employee[] $idles */
        $idles = [];
        $careerProfile = $tender->careerProfile;

        $url  = self::IDLES_URI . '?id_ao=' . $tender->id;
        $html = $client->getConnection()->get($url)->send()->getBody(true);

        $crawler = new Crawler($html);

        $children = $crawler->filter('#choix-emploi tr:nth-child(n+2)');

        if ($children->count() > 0) {
            $children->each(
                function (Crawler $child) use (&$idles, $careerProfile) {
                    $employee = self::parseIdleFromHtml($child, $careerProfile);

                    if (null != $employee) {
                        $idles[$employee->id] = $employee;
                    }
                }
            );
        }

        return $idles;
    }

    /**
     * @param Crawler $crawler
     * @param         $careerProfile
     *
     * @return Employee
     */
    public static function parseIdleFromHtml(Crawler $crawler, $careerProfile)
    {
        $temp = explode(',', $crawler->filter('td:nth-child(4)')->filter('a.btn')->attr('onclick'));
        $id   = preg_replace('/\D/', '', $temp[1]);
        $name = filter_var(trim($crawler->filter('td:nth-child(1)')->html()), FILTER_SANITIZE_STRING);
        preg_match('/\((S|F){1}\)/', $name, $matches);
        $type = reset($matches);
        $cost = preg_replace('/\D/', '', $crawler->filter('td:nth-child(3)')->html());

        $src = filter_var($crawler->filter('td:nth-child(2)')->html(), FILTER_SANITIZE_STRING);
        switch (utf8_decode($src)) {
            // Employé
            case 'intercontrat' :
                return new Employee($id, $name, $careerProfile, $type, null, $cost);
                break;

            // Promesse d'embauche
            case 'promesse signée':
                return new Applicant($id, $name, $careerProfile, $type, null, $cost);
                break;

            // Intercontrats des membres du groupe
            default :
                return null;
                break;
        }
    }

    /**
     * @param Client $client
     * @param        $careerProfile
     *
     * @return Applicant[]
     */
    public static function applicantsForCareerProfile(Client $client, $careerProfile)
    {
        /** @var Applicant[] $newApplicants */
        $newApplicants = [];

        $page = 1;

        do {
            $url  = vsprintf(self::APPLICANTS_URI . '?C=%s&P=%s', [$careerProfile, $page]);
            $html = $client->getConnection()->get($url)->send()->getBody(true);

            $crawler  = new Crawler($html);
            $children = $crawler->filter(self::CSS_FILTER);

            if (0 == $children->count()) {
                break;
            }

            $children->each(
                function (Crawler $child) use (&$newApplicants, $careerProfile) {

                    $newApplicant = self::parseApplicantFromHtml($child, $careerProfile);

                    if ($newApplicant instanceof NewApplicant) {
                        $newApplicants[$newApplicant->idTemp] = $newApplicant;
                    }
                }
            );

            $page++;
        } while (true);

        return $newApplicants;
    }

    /**
     * @param Crawler $crawler
     * @param         $careerProfile
     *
     * @return NewApplicant|null
     */
    public static function parseApplicantFromHtml(Crawler $crawler, $careerProfile)
    {
        $a = $crawler->filter('td:nth-child(4)')->filter('a.btn');

        if (null != $a->getNode(0) && "contacter" == $a->html()) {
            // A ce moment, l'id du tr n'est pas l'id du candidat
            $idTemp = preg_replace('/\D/', '', $crawler->attr('id'));
            $name   = filter_var(trim($crawler->filter('td:nth-child(1)')->html()), FILTER_SANITIZE_STRING);
            preg_match('/\((F){1}\)/', $crawler->filter('td:nth-child(2)')->html(), $matches);
            $type = reset($matches);
            $pay  = preg_replace('/\D/', '', $crawler->filter('td:nth-child(3)')->html());
            $cost = null;

            // Salarié ou freelance
            if ($type == Ressource::TYPE_FREELANCE) {
                $cost = $pay;
                $pay  = null;
            } else {
                $type = Ressource::TYPE_EMPLOYEE;
            }

            if( ($type == Ressource::TYPE_FREELANCE && Options::HIRE_FREELANCES)
                ||  ($type == Ressource::TYPE_EMPLOYEE && Options::HIRE_EMPLOYEES)
            ) {
                return new NewApplicant(null, $name, $careerProfile, $type, $pay, $cost, $idTemp);
            }
        }

        return null;
    }

    /**
     * @param Client       $client
     * @param NewApplicant $newApplicant
     * @param Tender       $tender
     *
     * @return bool|Applicant
     */
    public static function hire(Client $client, NewApplicant $newApplicant, Tender $tender)
    {


        $post = [
            'a'       => 'EC',
            'id_c'    => $newApplicant->idTemp,
            'numrow'  => rand(1, 30),
            'propsal' => ((Ressource::TYPE_EMPLOYEE == $newApplicant->type) ? $newApplicant->pay : $newApplicant->cost)
        ];

        $html    = $client->getConnection()->post(self::AJAX_ACTION_URI, [], $post)->send()->getBody(true);
        $crawler = new Crawler($html);

        if (null != $crawler->filter('span.positif')->getNode(0)) {
            // Trouver notre candidat et mettre à jour son id

            /** @var Applicant $possibleApplicant */
            foreach (self::idlesForCareerProfile($client, $tender) as $possibleApplicant) {
                if ($possibleApplicant instanceof Applicant
                    && $possibleApplicant->cost == $newApplicant->cost
                    && 0 === strpos($possibleApplicant->name, $newApplicant->name)
                ) {
                    $applicant = new Applicant($possibleApplicant->id, $newApplicant->name, $newApplicant->careerProfile, $newApplicant->type, $newApplicant->pay);

                    if (Options::DEVELOPMENT) {
                        print(vsprintf('%sRecrutement OK : Offre[%s], Ressource[%s], Profil[%s], Marge brute[%s]',
                            [
                                PHP_EOL,
                                $tender->id,
                                $applicant->id,
                                $tender->careerProfile,
                                $tender->margin
                            ]
                        ));
                    }

                    return $applicant;
                }
            }
        }

        if (Options::DEVELOPMENT) {
            print(vsprintf('%sRecrutement KO : Offre[%s], Ressource[%s], Profil[%s], Marge brute[%s], Message[%s]',
                [
                    PHP_EOL,
                    $tender->id,
                    $newApplicant->id,
                    $tender->careerProfile,
                    $tender->margin,
                    $crawler->html()
                ]
            ));
        }

        return false;
    }
}
