<?php namespace Jhiino\ESNLeJeu;

use Jhiino\ESNLeJeu\Config\ConfigAwareInterface;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;

class Mailer implements ConfigAwareInterface
{
    /**
     * @var string
     */
    protected $host;

    /**
     * @var int
     */
    protected $port;

    /**
     * @var string
     */
    protected $security;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var Swift_SmtpTransport
     */
    protected $transport;

    /**
     * @var Swift_Mailer
     */
    protected $mailer;

    /**
     * @var array
     */
    protected $recipients;

    /**
     * @var string
     */
    protected $from;

    /**
     * @var string
     */
    protected $fromAs;

    /**
     * @param string $title
     * @param array  $output
     *
     * @return bool
     */
    public function sendOutput($title, array $output = [])
    {
        $message = Swift_Message::newInstance($title)
            ->setFrom([$this->from => $this->fromAs])
            ->setTo($this->recipients)
            ->setBody(implode(PHP_EOL, $output));

        $delivered = $this->mailer->send($message);

        return $delivered == count($this->recipients);
    }

    /**
     * @param array $parameters
     *
     * @return $this
     */
    public function applyConfig(array $parameters = [])
    {
        $parameters = array_merge($this->getDefaultConfiguration(), $parameters[$this->getConfigKey()]);

        $this->host       = $parameters['host'];
        $this->port       = $parameters['port'];
        $this->security   = $parameters['security'];
        $this->username   = $parameters['username'];
        $this->password   = $parameters['password'];
        $this->from       = $parameters['from'];
        $this->fromAs     = $parameters['from_as'];
        $this->recipients = $parameters['recipients'];

        $this->transport = Swift_SmtpTransport::newInstance($this->host, $this->port, $this->security);

        if (null !== $this->username) {
            $this->transport->setUsername($this->username);
        }

        if (null !== $this->password) {
            $this->transport->setPassword($this->password);
        }

        $this->mailer = Swift_Mailer::newInstance($this->transport);

        return $this;
    }

    /**
     * @return string
     */
    public function getConfigKey()
    {
        return 'mailer';
    }

    /**
     * @return array
     */
    public function getDefaultConfiguration()
    {
        return [
            'host'       => 'localhost',
            'port'       => 25,
            'security'   => null,
            'username'   => null,
            'password'   => null,
            'from'       => null,
            'from_as'    => null,
            'recipients' => [],
        ];
    }
}