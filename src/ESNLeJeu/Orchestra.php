<?php namespace Jhiino\ESNLeJeu;

use Jhiino\ESNLeJeu\Config\ConfigAwareInterface;
use Jhiino\ESNLeJeu\Entity\Scheduler;
use Jhiino\ESNLeJeu\Logger\PhpOutputLogger;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class Orchestra implements ConfigAwareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var array
     */
    protected $everyTime;

    /**
     * @var array
     */
    protected $audit;

    /**
     * @var array
     */
    protected $flannel;

    /**
     * @var array
     */
    protected $business;

    /**
     * @param Client $client
     * @param array  $config
     */
    public function __construct(Client $client, array $config = [])
    {
        $this->config = $config;
        $this->client = $client;

        $this->initClient();
        $this->applyConfig($this->config);
    }

    protected function initClient()
    {
        $this->client->applyConfig($this->config);
    }

    public function run()
    {
        $this->logger->info('------------------------------');
        $this->logger->info('Actions du ' . date('Y-m-d H:i:s'));

        Scheduler::getInstance()->waitForStart();

        $this->executeEverytime();
        $this->executeWhileAudit();
        $this->executeWhileFlannel();
        $this->executeWhileBusiness();

        $this->logger->info('------------------------------');
    }

    protected function executeEverytime()
    {
        foreach ($this->everyTime as $moduleInfo) {
            $this->execute($moduleInfo);
        }
    }

    protected function executeWhileAudit()
    {
        if (! Scheduler::getInstance()->isAuditTime()) {
            return;
        }

        foreach ($this->audit as $moduleInfo) {
            $this->execute($moduleInfo);
        }
    }

    protected function executeWhileFlannel()
    {
        if (! Scheduler::getInstance()->isFlannelTime()) {
            return;
        }

        foreach ($this->flannel as $moduleInfo) {
            $this->execute($moduleInfo);
        }
    }

    protected function executeWhileBusiness()
    {
        if (Scheduler::getInstance()->isFlannelTime()) {
            return;
        }

        foreach ($this->business as $moduleInfo) {
            $this->execute($moduleInfo);
        }
    }

    /**
     * @param array $parameters
     *
     * @return $this
     */
    public function applyConfig(array $parameters = [])
    {
        $parameters = array_merge($this->getDefaultConfiguration(), $parameters[$this->getConfigKey()]);

        $this->everyTime = $parameters['everytime'];
        $this->audit     = $parameters['audit'];
        $this->flannel   = $parameters['flannel'];
        $this->business  = $parameters['business'];

        return $this;
    }

    /**
     * @return string
     */
    public function getConfigKey()
    {
        return 'modules';
    }

    /**
     * @return array
     */
    public function getDefaultConfiguration()
    {
        return [
            'everytime' => [],
            'audit'     => [],
            'flannel'   => [],
            'business'  => [],
        ];
    }

    protected function execute($moduleInfo)
    {
        $moduleClassName = key($moduleInfo);
        $action          = current($moduleInfo);
        $module          = new $moduleClassName($this->client);

        if ($module instanceof LoggerAwareInterface) {
            if (null === $this->logger) {
                $this->logger = new PhpOutputLogger();
            }

            $module->setLogger($this->logger);
        }

        if ($module instanceof ConfigAwareInterface) {
            $module->applyConfig($this->config);
        }

        $module->$action();

        Scheduler::getInstance()->waitBeforeNextStep();
    }
}