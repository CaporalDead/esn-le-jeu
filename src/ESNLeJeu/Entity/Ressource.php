<?php

namespace Jhiino\ESNLeJeu\Entity;

use Exception;

abstract class Ressource
{
    /**
     * Nombre de jours travaillés dans l'année
     *
     * @var int
     */
    const WORKED_DAYS_A_YEAR = 143;

    /**
     * @var string
     */
    const CODE = '';

    /**
     * Ressource de type Employé
     *
     * @var string
     */
    const TYPE_EMPLOYEE = '(S)';

    /**
     * Ressource de type "Freelance"
     *
     * @var string
     */
    const TYPE_FREELANCE = '(F)';

    /**
     * ID du salarié
     *
     * @var int
     */
    public $id;

    /**
     * Nom du salarié
     *
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $careerProfile;

    /**
     * Salaire d'embauche
     *
     * @var int
     */
    public $pay;

    /**
     * Coût journalier avec charges
     *
     * @var int
     */
    public $cost;

    /**
     * @var
     */
    public $type = self::TYPE_EMPLOYEE;

    /**
     * @param      $id
     * @param      $name
     * @param      $careerProfile
     * @param      $type
     * @param null $pay
     * @param null $cost
     *
     * @throws Exception
     */
    public function __construct($id, $name, $careerProfile, $type, $pay = null, $cost = null)
    {
        $this->id            = $id;
        $this->name          = $name;
        $this->careerProfile = $careerProfile;
        $this->type          = $type;

        if (null != $pay && filter_var($pay, FILTER_VALIDATE_INT)) {
            $this->pay  = intval($pay);
            $this->cost = intval(round($pay / self::WORKED_DAYS_A_YEAR));
        } elseif (null != $cost && filter_var($cost, FILTER_VALIDATE_INT)) {
            $this->cost = intval($cost);
            $this->pay  = intval(round($cost * self::WORKED_DAYS_A_YEAR));
        } else {
            throw new Exception('Error on creating ressource : unable to get pay or cost.');
        }
    }
}