<?php

namespace Jhiino\ESNLeJeu\Entity;

use DateTime;
use Jhiino\ESNLeJeu\Config\ConfigAwareInterface;

class Scheduler implements ConfigAwareInterface
{
    /**
     * @var bool
     */
    protected $activate;

    /**
     * @var Scheduler
     */
    protected static $instance;

    private function __construct()
    {
    }

    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new Scheduler();
        }

        return self::$instance;
    }

    /**
     * Baratin
     *
     * @return bool
     */
    public function isFlannelTime()
    {
        $now = new DateTime();

        $startTime = new DateTime();
        $stopTime  = new DateTime();

        $startTime->setTime(6, 0, 0);
        // Si tard en cas d'OPA réussie
        $stopTime->setTime(11, 59, 59);

        return $this->activate && ($now >= $startTime) && ($now <= $stopTime);
    }

    /**
     * Audit
     *
     * @return bool
     */
    public function isAuditTime()
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
     *
     * @return bool
     */
    public function isBusinessTime()
    {
        $now = new DateTime();

        $startTime = new DateTime();
        $startTime->setTime(6, 0, 0);

        $stopTime = new DateTime();
        $stopTime->setTime(23, 59, 59);

        return (($now >= $startTime) && ($now <= $stopTime)) || Options::DEVELOPMENT;
    }

    public function waitForStart()
    {
        if (! Options::DEVELOPMENT) {
            sleep(rand(1, 99));
        }
    }

    public function waitBeforeNextStep()
    {
        if (! Options::DEVELOPMENT) {
            sleep(rand(1, 29));
        }
    }

    public function waitBeforeNextBid()
    {
        if (! Options::DEVELOPMENT) {
            usleep(rand(876543, 1598765));
        } else {
            usleep(10000);
        }
    }

    public function waitBeforeNextComplaint()
    {
        if (! Options::DEVELOPMENT) {
            usleep(rand(100000, 456789));
        } else {
            usleep(10000);
        }
    }

    public function isActivate()
    {
        return $this->activate;
    }

    /**
     * @param array $parameters
     *
     * @return $this
     */
    public function applyConfig(array $parameters = [])
    {
        $parameters = array_merge($this->getDefaultConfiguration(), $parameters);

        $this->activate = $parameters['activate'];

        return $this;
    }

    /**
     * @return string
     */
    public function getConfigKey()
    {
        return 'flannel';
    }

    /**
     * @return array
     */
    public function getDefaultConfiguration()
    {
        return [
            'activate' => false,
        ];
    }
}