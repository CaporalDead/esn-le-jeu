<?php namespace Jhiino\ESNLeJeu;

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

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @return StatsModule
     */
    public function stats()
    {
        return new StatsModule($this->client);
    }

    /**
     * @return TendersModule
     */
    public function tenders()
    {
        return new TendersModule($this->client);
    }

    /**
     * @return ComplaintsModule
     */
    public function complaints()
    {
        return new ComplaintsModule($this->client);
    }

    /**
     * @return EmployeesModule
     */
    public function employees()
    {
        return new EmployeesModule($this->client);
    }

    /**
     * @return AuditModule
     */
    public function audit()
    {
        return new AuditModule($this->client);
    }
}