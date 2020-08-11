<?php

namespace SportsPlanning;

use Voetbal\Referee as RefereeBase;

class Referee implements Resource
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var int
     */
    protected $number;
    /**
     * @var int
     */
    protected $priority;
    /**
     * @var Planning
     */
    protected $planning;

    public function __construct(Planning $planning, int $number)
    {
        $this->planning = $planning;
        $this->number = $number;
        $this->priority = 1;
    }

    public function getPlanning(): Planning
    {
        return $this->planning;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority)
    {
        $this->priority = $priority;
    }
}
