<?php

namespace SportsPlanning;

use SportsHelpers\Identifiable;

class Place extends Identifiable implements Resource, \Stringable
{
    protected int $number;
    protected string|null $location = null;

    public function __construct(protected Poule $poule)
    {
        $this->number = $poule->getPlaces()->count() + 1;
        $poule->getPlaces()->add($this);
    }

    public function getPoule(): Poule
    {
        return $this->poule;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getUniqueNumber(): int
    {
        return pow(2, $this->getNumber() - 1);
    }

    public function getUniqueIndex(): string
    {
        return $this->getLocation();
    }

    public function getLocation(): string
    {
        if ($this->location === null) {
            $this->location = $this->poule->getNumber() . '.' . $this->number;
        }
        return $this->location;
    }

    public function __toString(): string
    {
        return $this->getLocation();
    }
}
