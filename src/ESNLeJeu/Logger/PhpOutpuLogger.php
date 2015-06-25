<?php

namespace Jhiino\ESNLeJeu\Logger;

use Psr\Log\AbstractLogger;

class PhpOutpuLogger extends AbstractLogger
{
    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     *
     * @return null
     */
    public function log($level, $message, array $context = [])
    {
        $target = in_array($level, ['DEBUG', 'INFO', 'NOTICE', 'WARNING']) ? 'php://stdout' : 'php://stderr';
        $output = vsprintf('[%s] : %s%s', [
            $level,
            $message,
            PHP_EOL
        ]);

        file_put_contents($target, $output);
    }
}