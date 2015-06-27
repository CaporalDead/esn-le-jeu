<?php

namespace Jhiino\ESNLeJeu\Entity;

class Tender
{
    /**
     * ID de l'appel d'offre
     *
     * @var int
     */
    public $id;

    /**
     * Nom du client
     *
     * @var string
     */
    public $customer;

    /**
     * Compétence demandée
     *
     * @var string
     */
    public $careerProfile;

    /**
     * Nombre de semaines de l'appel d'offre
     *
     * @var int
     */
    public $weeks;

    /**
     * Budget du client
     *
     * @var int
     */
    public $budget;

    /**
     * Prix proposé au client
     *
     * @var int
     */
    public $businessProposal;

    /**
     * Page de l'offre
     *
     * @var int
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