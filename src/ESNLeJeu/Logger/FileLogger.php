<?php

namespace Jhiino\ESNLeJeu\Logger;

use Psr\Log\AbstractLogger;
use RuntimeException;

class FileLogger extends AbstractLogger
{
    protected $filename;

    public function __construct($filename)
    {
        $this->filename = $filename;
    }

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
        if (! is_writable($this->filename)) {
            throw new RuntimeException(sprintf('Can\'t write in [%s].', $this->filename));
        }

        $output = vsprintf('[%s] : %s%s', [
            $level,
            $message,
            PHP_EOL
        ]);

        file_put_contents($this->filename, $output, FILE_APPEND);
    }
}