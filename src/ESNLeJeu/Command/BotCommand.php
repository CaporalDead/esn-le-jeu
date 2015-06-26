<?php

namespace Jhiino\ESNLeJeu\Command;

use Jhiino\ESNLeJeu\Client;
use Jhiino\ESNLeJeu\Exception\ConfigFileNotFound;
use Jhiino\ESNLeJeu\Logger\NeedToBeFlushedInterface;
use Jhiino\ESNLeJeu\Mailer;
use Jhiino\ESNLeJeu\Orchestra;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Jhiino\ESNLeJeu\Logger\PhpOutputLogger;
use Jhiino\ESNLeJeu\Logger\MailLogger;
use Jhiino\ESNLeJeu\Logger\FileLogger;

class BotCommand extends Command
{
    /**
     * Default config file
     *
     * @var string
     */
    protected $configFile = 'parameters.yml';

    /**
     * Config template
     *
     * @var string
     */
    protected $templateFile = 'parameters.yml.dist';

    /**
     * Default logger type
     *
     * @var string
     */
    protected $defaultLoggerType = 'phpoutput';

    /**
     * Mapping type to logger class
     *
     * @var array
     */
    protected $availableLoggers = [
        'phpoutput' => PhpOutputLogger::class,
        'mail'      => MailLogger::class,
        'file'      => FileLogger::class,
    ];

    /**
     * Config
     *
     * @var array
     */
    protected $config = [];

    /**
     * Logger
     *
     * @var LoggerInterface
     */
    protected $logger = null;

    /**
     * Configure the command
     */
    public function configure()
    {
        $this
            ->setName('bot:run')
            ->setDescription('Lance le bot ESN')
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Fichier de configuration.', $this->configFile)
            ->addOption('logger', 'l', InputOption::VALUE_REQUIRED, 'Le logger utilisé', $this->defaultLoggerType);
    }

    /**
     * Entry point
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws ConfigFileNotFound
     * @throws LoggerNotFound
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->findConfigFile($input->getOption('config'));
        $this->findLogger($input->getOption('logger'));

        $client    = new Client();
        $orchestra = new Orchestra($client, $this->config);

        $client->setLogger($this->logger);
        $orchestra->setLogger($this->logger);

        $orchestra->run();

        if ($this->logger instanceof NeedToBeFlushedInterface) {
            $this->logger->flush();
        }

        return 0;
    }

    protected function findConfigFile($configFile)
    {
        $filePath     = null;
        $templatePath = dirname(__FILE__) . '/../../../config/' . $this->templateFile;

        if (file_exists($configFile)) {
            $filePath = $configFile;
        } elseif (file_exists(dirname(__FILE__) . '/../../../config/' . $configFile)) {
            $filePath = dirname(__FILE__) . '/../../../config/' . $configFile;
        } else {
            throw new ConfigFileNotFound($configFile, $templatePath);
        }

        $this->config = Yaml::parse($filePath);
    }

    protected function findLogger($type)
    {
        switch ($type) {
            case 'phpoutput':
                $this->logger = new PhpOutputLogger();

                break;
            case 'file':
                $logfile      = dirname(__FILE__) . '/../../../storage/bot.log';
                $this->logger = new FileLogger($logfile);

                break;
            case 'mail':
                $mailer       = (new Mailer())->applyConfig($this->config);
                $this->logger = (new MailLogger($mailer))->applyConfig($this->config);

                break;
            default:
                throw new LoggerNotFound($type);
        }
    }
}