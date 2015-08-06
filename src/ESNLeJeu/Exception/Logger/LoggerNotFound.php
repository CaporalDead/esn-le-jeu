<?php

namespace Jhiino\ESNLeJeu\Exception\Logger;

use Exception;

class LoggerNotFound extends Exception
{
    public function __construct($type = null)
    {
        $message = 'Logger not found';

        if (null !== $type) {
            $message .= sprintf(' [type : %s]', $type);
        }

        parent::__construct($message);
    }
}