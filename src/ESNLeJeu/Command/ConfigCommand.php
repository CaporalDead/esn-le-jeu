<?php namespace Jhiino\ESNLeJeu\Command;

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
            'account'   => [
                'login'    => 'my-login',
                'password' => 'my-password',
                'esn'      => 'Le nom de mon ESN',
                'email'    => 'mon@email.tld',
            ],
            'modules'   => [
                'everytime' => [
                    ['\Jhiino\ESNLeJeu\Module\Stat\Popularity'],
                    ['\Jhiino\ESNLeJeu\Module\Stat\Tender'],
                    ['\Jhiino\ESNLeJeu\Module\Stat\Stock'],
                    ['\Jhiino\ESNLeJeu\Module\Stat\Dashboard'],
                ],
                'audit'     => [
                    ['\Jhiino\ESNLeJeu\Module\Audit\FireEmployees'],
                    ['\Jhiino\ESNLeJeu\Module\Audit\RenegociateContracts'],
                ],
                'flannel'   => [
                    ['\Jhiino\ESNLeJeu\Module\Complaint\Flannel'],
                ],
                'business'  => [
                    ['\Jhiino\ESNLeJeu\Module\Tender\Bid'],
                ],
            ],
            'tenders'   => [
                'hire'                => true,
                'min_weeks'           => 6,
                'min_interest_margin' => 0.22,
                'trade_promotion'     => 0.97,
                'max_bid_per_hour'    => 100,
            ],
            'employees' => [
                'hire_employees'  => true,
                'hire_freelances' => false,
            ],
            'audit'     => [
                'fire'                  => false,
                'max_fire_per_hour'     => 30,
                'renegotiate_contracts' => true,
                'renegotiate_type'      => [
                    'very_good' => true,
                    'good'      => true,
                    'bad'       => true,
                    'very_bad'  => true,
                ],
                'break_type'            => [
                    'very_good' => false,
                    'good'      => false,
                    'bad'       => false,
                    'very_bad'  => false,
                ],
            ],
            'mailer'    => [
                'host'       => 'smtp.gmail.com',
                'port'       => 465,
                'security'   => 'ssl',
                'username'   => 'my-login',
                'password'   => 'my-password',
                'from'       => 'mon@email.tld',
                'from_as'    => 'ESN Le Bot',
                'recipients' =>
                    ['mon@email.tld', 'peut-etre-un-autre@email.tld'],
            ],
            'logger'    => [
                'levels' => [
                    'EMERGENCY',
                    'ALERT',
                    'CRITICAL',
                    'ERROR',
                    'WARNING',
                    'NOTICE',
                    'INFO',
                    'DEBUG',
                ],
            ],
        ];

        file_put_contents($destination, Yaml::dump($template));
    }
}
