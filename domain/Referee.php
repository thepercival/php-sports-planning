<?php

namespace SportsPlanning;

use SportsHelpers\Identifiable;

class Referee extends Identifiable implements Resource
{
    public int $priority;

    public function __construct(public readonly int $refereeNr)
    {
        $this->priority = 1;
    }

    public function getUniqueIndex(): string
    {
        return (string)$this->refereeNr;
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
