<?php namespace Jhiino\ESNLeJeu\Entity;

use DateTime;

class Scheduler
{
    const PROD_MODE = true;

    /**
     * Baratin entre 6h et 8h du matin
     * @return bool
     */
    public static function isFlannelTime()
    {
        $now = new DateTime();

        $startTime = new DateTime();
        $startTime->setTime(6, 0, 0);

        // Si tard en cas d'OPA réussie
        $stopTime = new DateTime();
        $stopTime->setTime(11, 59, 59);

        return ($now >= $startTime) && ($now <= $stopTime);
    }

    /**
     * Réponses aux appels d'offres entre 6h et 23h
     * @return bool
     */
    public static function isBusinessTime()
    {
        $now = new DateTime();

        $startTime = new DateTime();
        $startTime->setTime(6, 0, 0);

        $stopTime = new DateTime();
        $stopTime->setTime(22, 59, 59);

        return ($now >= $startTime) && ($now <= $stopTime);
    }

    public static function waitForStart()
    {
        if (self::PROD_MODE) {
            sleep(rand(1, 300));
        }
    }

    public static function waitBeforeNextStep()
    {
        if (self::PROD_MODE) {
            sleep(rand(10, 60));
        }
    }

    public static function waitBeforeNextBid()
    {
        if (self::PROD_MODE) {
            usleep(rand(876543, 2598765));
        } else {
            usleep(10000);
        }
    }

    public static function waitBeforeNextComplaint()
    {
        usleep(rand(100000, 456789));
    }
} 