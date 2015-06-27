<?php

namespace Jhiino\ESNLeJeu\Logger;

use Jhiino\ESNLeJeu\Mailer;

class MailLogger extends ConfigurableLogger implements NeedToBeFlushedInterface
{
    /**
     * @var array
     */
    protected $buffer = [];

    /**
     * @var Mailer
     */
    private $mailer;

    public function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;
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
        if (! in_array(strtoupper($level), $this->levels)) {
            return;
        }

        $output = vsprintf('[%s] : %s%s', [
            $level,
            $message,
            PHP_EOL
        ]);

        $this->buffer[] = $output;
    }

    /**
     * @return $this
     */
    public function flush()
    {
        $this->mailer->sendOutput('Compte rendu d\'activité', $this->buffer);

        $this->buffer = [];

        return $this;
    }
}