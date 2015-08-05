<?php

namespace Jhiino\ESNLeJeu\Exception\Config;

use Exception;

class ConfigFileNotFound extends Exception
{
    public function __construct($filePath = null, $templatePath = null)
    {
        $message = 'Config file not found';

        if (null !== $filePath) {
            $message .= sprintf(' [%s]', $filePath);
        }

        if (null !== $templatePath) {
            $message .= ', a template can be found at ' . $templatePath;
        }

        parent::__construct($message);
    }
}