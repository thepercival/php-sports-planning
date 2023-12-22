<?php

namespace SportsPlanning;

use SportsHelpers\PlaceLocationInterface;

class Place extends Identifiable implements Resource, PlaceLocationInterface, \Stringable
{
    private int $placeNr;

    public function __construct(protected Poule $poule)
    {
        $this->placeNr = $poule->getPlaces()->count() + 1;
        $poule->getPlaces()->add($this);
    }

    public function getPoule(): Poule
    {
        return $this->poule;
    }

    public function getPouleNr(): int
    {
        return $this->poule->getNumber();
    }

    public function getPlaceNr(): int
    {
        return $this->placeNr;
    }

    public function getUniqueNumber(): int
    {
        return pow(2, $this->placeNr - 1);
    }

    public function getUniqueIndex(): string
    {
        return $this->__toString();
    }

    public function __toString(): string
    {
        return $this->getPouleNr() . '.' . $this->getPlaceNr();
    }
}
