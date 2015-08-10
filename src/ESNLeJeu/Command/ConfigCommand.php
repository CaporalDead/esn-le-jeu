<?php

namespace Jhiino\ESNLeJeu\Command;

use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class ConfigCommand extends Command
{
    /**
     * Configure the command
     */
    public function configure()
    {
        $this
            ->setName('bot:config:generate')
            ->setDescription('Génère un fichier de configuration par défaut')
            ->addArgument('destination', InputArgument::OPTIONAL, 'Chemin de destination du fichier', getcwd());
    }

    /**
     * Entry point
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->writeConfigFile($input->getArgument('destination'));

        return 0;
    }

    public function writeConfigFile($destination)
    {
        if (is_dir($destination)) {
            $destination .= DIRECTORY_SEPARATOR . 'parameters.yml';
        }

        if (file_exists($destination)) {
            throw new RuntimeException('A file already exists at ' . $destination);
        }

        $template = [
            'audit'       => [
                'fire'                  => false,
                'renegotiate_type'      => [
                    'bad'       => true,
                    'good'      => true,
                    'very_bad'  => true,
                    'very_good' => true,
                ],
                'break_type'            => [
                    'bad'       => false,
                    'good'      => false,
                    'very_bad'  => false,
                    'very_good' => false,
                ],
                'max_fire_per_hour'     => 300,
                'renegotiate_contracts' => true,
            ],
            'development' => [
                'activate' => true,
            ],
            'account'     => [
                'esn'      => 'Le nom de mon ESN',
                'login'    => 'my-login',
                'password' => 'my-password',
                'email'    => 'mon@email.tld',
            ],
            'employees'   => [
                'hire_freelances' => false,
                'hire_employees'  => false,
            ],
            'modules'     => [
                'audit'     => [
                    '\Jhiino\ESNLeJeu\Module\Audit\FireEmployees',
                    '\Jhiino\ESNLeJeu\Module\Audit\RenegociateContracts',
                ],
                'everytime' => [
                    '\Jhiino\ESNLeJeu\Module\Stat\Popularity',
                    '\Jhiino\ESNLeJeu\Module\Stat\Tender',
                    '\Jhiino\ESNLeJeu\Module\Stat\Stock',
                    '\Jhiino\ESNLeJeu\Module\Stat\Dashboard',
                ],
                'business'  => [
                    '\Jhiino\ESNLeJeu\Module\Tender\Bid',
                ],
                'flannel'   => [
                    '\Jhiino\ESNLeJeu\Module\Complaint\Flannel',
                ],
            ],
            'mailer'      => [
                'username'   => 'my-login',
                'from_as'    => 'ESN Le Bot',
                'from'       => 'mon@email.tld',
                'recipients' => [
                    'mon@email.tld',
                    'peut-etre-un-autre@email.tld',
                ],
                'host'       => 'smtp.gmail.com',
                'security'   => 'ssl',
                'password'   => 'my-password',
                'port'       => 465,
            ],
            'tenders'     => [
                'min_interest_margin' => 0.22,
                'hire'                => false,
                'min_weeks'           => 5,
                'trade_promotion'     => 0.95,
                'max_bid_per_hour'    => 500,
            ],
            'logger'      => [
                'levels' => [
                    0 => 'EMERGENCY',
                    1 => 'ALERT',
                    2 => 'CRITICAL',
                    3 => 'ERROR',
                    4 => 'WARNING',
                    5 => 'NOTICE',
                    6 => 'INFO',
                ],
            ],
        ];

        file_put_contents($destination, Yaml::dump($template));
    }
}
