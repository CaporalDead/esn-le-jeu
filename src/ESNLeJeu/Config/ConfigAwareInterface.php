<?php

namespace Jhiino\ESNLeJeu\Config;

interface ConfigAwareInterface
{
    /**
     * @param array $parameters
     *
     * @return $this
     */
    public function applyConfig(array $parameters = []);

    /**
     * @return string
     */
    public function getConfigKey();

    /**
     * @return array
     */
    public function getDefaultConfiguration();
}