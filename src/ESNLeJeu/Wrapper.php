<?php namespace Jhiino\ESNLeJeu;

use Jhiino\ESNLeJeu\Config\ConfigAwareInterface;
use Jhiino\ESNLeJeu\Module\AuditModule;
use Jhiino\ESNLeJeu\Module\ComplaintsModule;
use Jhiino\ESNLeJeu\Module\EmployeesModule;
use Jhiino\ESNLeJeu\Module\StatsModule;
use Jhiino\ESNLeJeu\Module\TendersModule;

class Wrapper
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var array
     */
    private $config;

    public function __construct(Client $client, array $config = [])
    {
        $this->client = $client;
        $this->config = $config;

        if ($this->client instanceof ConfigAwareInterface) {
            $this->client->applyConfig($this->config);
        }
    }

    /**
     * @return StatsModule
     */
    public function stats()
    {
        $module = new StatsModule($this->client);

        if ($module instanceof ConfigAwareInterface) {
            $module->applyConfig($this->config);
        }

        return $module;
    }

    /**
     * @return TendersModule
     */
    public function tenders()
    {
        $module = new TendersModule($this->client);

        if ($module instanceof ConfigAwareInterface) {
            $module->applyConfig($this->config);
        }

        return $module;
    }

    /**
     * @return ComplaintsModule
     */
    public function complaints()
    {
        $module = new ComplaintsModule($this->client);

        if ($module instanceof ConfigAwareInterface) {
            $module->applyConfig($this->config);
        }

        return $module;
    }

    /**
     * @return EmployeesModule
     */
    public function employees()
    {
        $module = new EmployeesModule($this->client);

        if ($module instanceof ConfigAwareInterface) {
            $module->applyConfig($this->config);
        }

        return $module;
    }

    /**
     * @return AuditModule
     */
    public function audit()
    {
        $module = new AuditModule($this->client);

        if ($module instanceof ConfigAwareInterface) {
            $module->applyConfig($this->config);
        }

        return $module;
    }
}