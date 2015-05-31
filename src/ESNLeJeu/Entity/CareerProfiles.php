<?php namespace Jhiino\ESNLeJeu\Entity;

use ReflectionClass;

class CareerProfiles
{
    const ALL                    = 'ALL';
    const ADMINISTRATEUR_ERP     = '1';
    const ANALYSTE               = '2';
    const ASSISTANT_UTILISATEURS = '3';
    const DEVELOPPEUR_JUNIOR     = '4';
    const ARCHITECTE_RESEAUX     = '5';
    const CHEF_DE_PROJETS        = '6';
    const CONSULTANT_EXPERT      = '7';
    const DIRECTEUR_DE_PROJETS   = '8';
    const FORMATEUR              = '9';
    const INGENIEUR_D_ETUDES     = '10';
    const RESPONSABLE_EQUIPE     = '11';
    const DBA                    = '12';
    const INGENIEUR_RESEAUX      = '13';
    const INGENIEUR_SYSTEMES     = '14';
    const GRAPHISTE_WEB          = '15';
    const TECHNICIEN_SUPPORT     = '16';
    const ARCHITECTE_CLOUD       = '17';
    const TECHNICIEN_RESEAUX     = '18';
    const WEBMASTER              = '19';
    const COMMUNITY_MANAGER      = '20';
    const DEVELOPPEUR_SENIOR     = '21';

    public static function getArrayOf()
    {
        return [
            'ADMINISTRATEUR_ERP'     => self::ADMINISTRATEUR_ERP,
            'ANALYSTE'               => self::ANALYSTE,
            'ASSISTANT_UTILISATEURS' => self::ASSISTANT_UTILISATEURS,
            'DEVELOPPEUR_JUNIOR'     => self::DEVELOPPEUR_JUNIOR,
            'ARCHITECTE_RESEAUX'     => self::ARCHITECTE_RESEAUX,
            'CHEF_DE_PROJETS'        => self::CHEF_DE_PROJETS,
            'CONSULTANT_EXPERT'      => self::CONSULTANT_EXPERT,
            'DIRECTEUR_DE_PROJETS'   => self::DIRECTEUR_DE_PROJETS,
            'FORMATEUR'              => self::FORMATEUR,
            'INGENIEUR_D_ETUDES'     => self::INGENIEUR_D_ETUDES,
            'RESPONSABLE_EQUIPE'     => self::RESPONSABLE_EQUIPE,
            'DBA'                    => self::DBA,
            'INGENIEUR_RESEAUX'      => self::INGENIEUR_RESEAUX,
            'INGENIEUR_SYSTEMES'     => self::INGENIEUR_SYSTEMES,
            'GRAPHISTE_WEB'          => self::GRAPHISTE_WEB,
            'TECHNICIEN_SUPPORT'     => self::TECHNICIEN_SUPPORT,
            'ARCHITECTE_CLOUD'       => self::ARCHITECTE_CLOUD,
            'TECHNICIEN_RESEAUX'     => self::TECHNICIEN_RESEAUX,
            'WEBMASTER'              => self::WEBMASTER,
            'COMMUNITY_MANAGER'      => self::COMMUNITY_MANAGER,
            'DEVELOPPEUR_SENIOR'     => self::DEVELOPPEUR_SENIOR,
        ];
    }

    /**
     * @param $CareerProfile
     *
     * @return mixed
     */
    public static function getName($CareerProfile)
    {
        $reflectionClass = new ReflectionClass(__CLASS__);
        $constants = array_flip($reflectionClass->getConstants());

        return $constants[$CareerProfile];
    }
}