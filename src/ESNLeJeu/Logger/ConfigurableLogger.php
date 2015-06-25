<?php

namespace Jhiino\ESNLeJeu\Logger;

use Jhiino\ESNLeJeu\Config\ConfigAwareInterface;
use Psr\Log\AbstractLogger;

abstract class ConfigurableLogger extends AbstractLogger implements ConfigAwareInterface
{
    /**
     * @var array
     */
    protected $levels = [];

    /**
     * @param array $parameters
     *
     * @return $this
     */
    public function applyConfig(array $parameters = [])
    {
        $parameters = array_merge($this->getDefaultConfiguration(), $parameters[$this->getConfigKey()]);

        $this->levels = $parameters['levels'];

        return $this;
    }

    /**
     * @return string
     */
    public function getConfigKey()
    {
        return 'logger';
    }

    /**
     * @return array
     */
    public function getDefaultConfiguration()
    {
        return [
            'levels' => [],
        ];
    }
}