<?php namespace Jhiino\ESNLeJeu\Entity;

use Exception;

abstract class Ressource
{
    /**
     * @var int : nombre de jours travaillés dans l'année
     */
    const WORKED_DAYS_A_YEAR = 143;

    const CODE           = '';
    const TYPE_EMPLOYEE  = '(S)';
    const TYPE_FREELANCE = '(F)';

    /**
     * @var int : id du salarié
     */
    public $id;

    /**
     * @var string nom du salarié
     */
    public $name;

    /**
     * @var string
     */
    public $careerProfile;

    /**
     * @var int : salaire d'embauche
     */
    public $pay;

    /**
     * @var int : coût journalier avec charges
     */
    public $cost;

    /**
     * @var
     */
    public $type = self::TYPE_EMPLOYEE;

    /**
     * @param        $id
     * @param        $name
     * @param        $careerProfile
     * @param string $type
     * @param        $pay
     * @param        $cost
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
            $this->pay  = $pay;
            $this->cost = round($pay / self::WORKED_DAYS_A_YEAR);
        } elseif (null != $cost && filter_var($cost, FILTER_VALIDATE_INT)) {
            $this->cost = $cost;
            $this->pay  = round($cost * self::WORKED_DAYS_A_YEAR);
        } else {
            throw new Exception('Error on creating ressource : unable to get pay or cost.');
        }
    }
}