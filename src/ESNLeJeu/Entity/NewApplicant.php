<?php namespace Jhiino\ESNLeJeu\Entity;

use Exception;

class NewApplicant extends Applicant
{
    const CODE = 'PE';

    /**
     * @var int : id du salarié
     */
    public $idTemp;

    /**
     * @param        $id
     * @param        $name
     * @param        $careerProfile
     * @param string $type
     * @param null   $pay
     * @param null   $cost
     * @param        $idTemp
     *
     * @throws Exception
     */
    public function __construct($id, $name, $careerProfile, $type, $pay = null, $cost = null, $idTemp = null)
    {
        parent::__construct($id, $name, $careerProfile, $type, $pay, $cost);
        $this->idTemp = $idTemp;
    }
}