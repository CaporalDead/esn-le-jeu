<?php namespace Jhiino\ESNLeJeu\Entity;

class Tender
{
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
        $this->businessProposal = round($budget * Options::BID_TRADE_PROMOTION);
        $this->page             = $page;
    }
}