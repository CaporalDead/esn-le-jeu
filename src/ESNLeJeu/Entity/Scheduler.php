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
     * @var bool
     */
    protected $isDevelopment = false;

    /**
     * @var Scheduler
     */
    protected static $instance;

    /**
     * Singleton
     */
    private function __construct()
    {
    }

    /**
     * @return Scheduler
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new Scheduler();
        }

        return self::$instance;
    }

    /**
     * @return bool
     */
    public function isDevelopment()
    {
        return $this->isDevelopment;
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
        $stopTime  = new DateTime();

        $startTime->setTime(6, 0, 0);
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
        $stopTime  = new DateTime();

        $startTime->setTime(6, 0, 0);
        $stopTime->setTime(23, 59, 59);

        return (($now >= $startTime) && ($now <= $stopTime)) || $this->isDevelopment();
    }

    /**
     * Tempo
     */
    public function waitForNextQuery()
    {
        if (! $this->isDevelopment()) {
            usleep(rand(400000, 800000));
        }
    }

    public function waitForStart()
    {
        if (! $this->isDevelopment()) {
            sleep(rand(1, 99));
        }
    }

    public function waitBeforeNextStep()
    {
        if (! $this->isDevelopment()) {
            sleep(rand(1, 29));
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
        $parameters = array_merge($this->getDefaultConfiguration(), $parameters[$this->getConfigKey()]);

        $this->isDevelopment = $parameters['activate'];

        return $this;
    }

    /**
     * @return string
     */
    public function getConfigKey()
    {
        return 'development';
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