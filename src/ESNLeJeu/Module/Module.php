<?php

namespace Jhiino\ESNLeJeu\Module;

use Jhiino\ESNLeJeu\Client;

abstract class Module
{
    /**
     * @var string
     */
    const AJAX_ACTION_URI = '/pannel-action-ajax.php';

    /**
     * @var string
     */
    const CSS_FILTER = '#table tr[id^="r-"]:not([id^="r-zone"])';

    /**
     * @var Client
     */
    protected $client;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }
}