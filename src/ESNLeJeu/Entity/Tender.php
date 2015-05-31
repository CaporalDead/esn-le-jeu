<?php namespace Jhiino\ESNLeJeu\Entity;

class Tender
{
    const MIN_WEEKS           = 1;
    const MIN_INTEREST_MARGIN = 0.10;
    const TRADE_PROMOTION     = 0.92;
    const MAX_BID_PER_HOUR    = 50;

    /**
     * @var int : id de l'appel d'offre
     */
    public $id;

    /**
     * @var string : nom du client
     */
    public $customer;

    /**
     * @var string : compétence demandée
     */
    public $careerProfile;

    /**
     * @var int : nombre de semaines de l'appel d'offre
     */
    public $weeks;

    /**
     * @var int : budget du client
     */
    public $budget;

    /**
     * @var int : prix proposé au client
     */
    public $businessProposal;

    /**
     * @var int : page de l'offre
     */
    public $page;

    /**
     * @var Ressource
     */
    public $ressource = null;

    /**
     * @var float
     */
    public $margin = 0.0;

    /**
     * @param $id
     * @param $customer
     * @param $careerProfile
     * @param $weeks
     * @param $budget
     * @param $page
     */
    public function __construct($id, $customer, $careerProfile, $weeks, $budget, $page)
    {
        $this->id               = $id;
        $this->customer         = $customer;
        $this->careerProfile    = $careerProfile;
        $this->weeks            = $weeks;
        $this->budget           = $budget;
        $this->businessProposal = round($budget * self::TRADE_PROMOTION);
        $this->page             = $page;
    }
}