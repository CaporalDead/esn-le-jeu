<?php namespace Jhiino\ESNLeJeu\Entity;

use DateTime;

class Scheduler
{
    /**
     * Baratin
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

        return Options::FLANNEL && ($now >= $startTime) && ($now <= $stopTime);
    }

    /**
     * Audit
     * @return bool
     */
    public static function isAuditTime()
    {
        $now = new DateTime();

        $startTime = new DateTime();
        $startTime->setTime(6, 0, 0);

        $stopTime = new DateTime();
        $stopTime->setTime(6, 59, 59);

        return (($now >= $startTime) && ($now <= $stopTime));
    }

    /**
     * Réponses aux appels d'offres
     * @return bool
     */
    public static function isBusinessTime()
    {
        $now = new DateTime();

        $startTime = new DateTime();
        $startTime->setTime(6, 0, 0);

        $stopTime = new DateTime();
        $stopTime->setTime(23, 59, 59);

        return (($now >= $startTime) && ($now <= $stopTime)) || Options::DEVELOPMENT;
    }

    public static function waitForStart()
    {
        if (! Options::DEVELOPMENT) {
            sleep(rand(1, 99));
        }
    }

    public static function waitBeforeNextStep()
    {
        if (! Options::DEVELOPMENT) {
            sleep(rand(1, 29));
        }
    }

    public static function waitBeforeNextBid()
    {
        if (! Options::DEVELOPMENT) {
            usleep(rand(876543, 1598765));
        } else {
            usleep(10000);
        }
    }

    public static function waitBeforeNextComplaint()
    {
        if (! Options::DEVELOPMENT) {
            usleep(rand(100000, 456789));
        } else {
            usleep(10000);
        }

    }
} 