<?php

namespace Jhiino\ESNLeJeu\Entity;

class ObjectDetails
{
    /**
     * @var Crawler
     */
    public $crawler;

    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $numRow;

    /**
     * EmployeeDetails constructor.
     *
     * @param Crawler $crawler
     * @param int     $id
     * @param int     $numRow
     */
    public function __construct(Crawler $crawler, $id, $numRow)
    {
        $this->crawler = $crawler;
        $this->id      = $id;
        $this->numRow  = $numRow;
    }
}
