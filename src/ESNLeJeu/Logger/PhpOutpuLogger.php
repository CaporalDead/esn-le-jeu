<?php

namespace Jhiino\ESNLeJeu\Logger;

class PhpOutpuLogger extends ConfigurableLogger
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
        if (! in_array(strtoupper($level), $this->levels)) {
            return;
        }

        $target = in_array(strtoupper($level), ['DEBUG', 'INFO', 'NOTICE', 'WARNING']) ? 'php://stdout' : 'php://stderr';
        $output = vsprintf('[%s] : %s%s', [
            $level,
            $message,
            PHP_EOL
        ]);

        file_put_contents($target, $output);
    }
}