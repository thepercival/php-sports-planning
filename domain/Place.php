<?php

namespace SportsPlanning;

use SportsHelpers\Identifiable;

class Place extends Identifiable implements Resource
{
    protected string|null $location = null;

    public function __construct(protected Poule $poule, protected int $number)
    {
    }

    public function getPoule(): Poule
    {
        return $this->poule;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getUniqueIndex(): string {
        return $this->getLocation();
    }

    public function getLocation(): string
    {
        if ($this->location === null) {
            $this->location = $this->poule->getNumber() . '.' . $this->number;
        }
        return $this->location;
    }
}
