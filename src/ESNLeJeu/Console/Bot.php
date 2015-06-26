<?php namespace Jhiino\ESNLeJeu\Console;

use Jhiino\ESNLeJeu\Command\BotCommand;
use Jhiino\ESNLeJeu\Command\ConfigCommand;
use Jhiino\ESNLeJeu\Version;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

class Bot extends Application
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct('Bot ESN Le Jeu', Version::$version);
    }

    /**
     * Get app commands
     *
     * @return array|Command[]
     */
    protected function getDefaultCommands()
    {
        $commands   = parent::getDefaultCommands();
        $commands[] = new BotCommand();
        $commands[] = new ConfigCommand();

        return $commands;
    }
}