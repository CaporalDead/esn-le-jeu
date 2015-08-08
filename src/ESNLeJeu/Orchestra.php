<?php

namespace Jhiino\ESNLeJeu;

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
    protected $everyTime = [];

    /**
     * @var array
     */
    protected $audit = [];

    /**
     * @var array
     */
    protected $flannel = [];

    /**
     * @var array
     */
    protected $business = [];

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

        Scheduler::getInstance()->applyConfig($this->config);
    }

    protected function initClient()
    {
        $this->client->applyConfig($this->config);
    }

    public function run()
    {
        $this->logger->info(str_repeat('-', 30));
        $this->logger->info('Actions du ' . date('Y-m-d H:i:s'));

        Scheduler::getInstance()->waitForStart();

        $this->executeEverytime();
        $this->executeWhileAudit();
        $this->executeWhileFlannel();
        $this->executeWhileBusiness();

        $this->logger->info(str_repeat('-', 30));
    }

    protected function executeEverytime()
    {
        foreach ($this->everyTime as $module) {
            $this->execute($module);
        }
    }

    protected function executeWhileAudit()
    {
        if (! Scheduler::getInstance()->isAuditTime()) {
            return;
        }

        foreach ($this->audit as $module) {
            $this->execute($module);
        }
    }

    protected function executeWhileFlannel()
    {
        if (! Scheduler::getInstance()->isFlannelTime()) {
            return;
        }

        foreach ($this->flannel as $module) {
            $this->execute($module);
        }
    }

    protected function executeWhileBusiness()
    {
        if (Scheduler::getInstance()->isFlannelTime()) {
            return;
        }

        foreach ($this->business as $module) {
            $this->execute($module);
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

        $this->everyTime = array_merge(is_array($parameters['everytime']) ? $parameters['everytime'] : [], $this->everyTime);
        $this->audit     = array_merge(is_array($parameters['audit']) ? $parameters['audit'] : [], $this->audit);
        $this->flannel   = array_merge(is_array($parameters['flannel']) ? $parameters['flannel'] : [], $this->flannel);
        $this->business  = array_merge(is_array($parameters['business']) ? $parameters['business'] : [], $this->business);

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

    protected function execute($moduleClassName)
    {
        $module = new $moduleClassName($this->client);

        if ($module instanceof LoggerAwareInterface) {
            if (null === $this->logger) {
                $this->logger = new PhpOutputLogger();
            }

            $module->setLogger($this->logger);
        }

        if ($module instanceof ConfigAwareInterface) {
            $module->applyConfig($this->config);
        }

        $module->fire();

        Scheduler::getInstance()->waitBeforeNextStep();
    }
}