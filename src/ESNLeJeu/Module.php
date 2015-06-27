<?php

namespace Jhiino\ESNLeJeu;

use Jhiino\ESNLeJeu\Config\ConfigAwareInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

abstract class Module implements LoggerAwareInterface, ConfigAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var string
     */
    const CSS_FILTER = '#table tr[id^="r-"]:not([id^="r-zone"])';

    /**
     * @var string
     */
    const AJAX_ACTION_URI = '/pannel-action-ajax.php';

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $configKey = null;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Action
     */
    public abstract function fire();

    /**
     * @param array $parameters
     *
     * @return $this
     */
    public function applyConfig(array $parameters = [])
    {
        return $this;
    }

    /**
     * Clé pour la configuration
     *
     * @return string
     */
    public function getConfigKey()
    {
        return $this->configKey;
    }

    /**
     * @return array
     */
    public function getDefaultConfiguration()
    {
        return [];
    }
}
