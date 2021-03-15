<?php

namespace SportsPlanning;

use SportsHelpers\Identifiable;

class Referee extends Identifiable implements Resource
{
    protected int $priority;

    public function __construct(protected Planning $planning, protected int $number)
    {
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

    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }
}
