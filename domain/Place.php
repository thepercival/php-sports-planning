<?php

namespace SportsPlanning;

use SportsHelpers\PlaceLocationInterface;

final class Place extends Identifiable implements Resource, PlaceLocationInterface, \Stringable
{
    private int $placeNr;

    public function __construct(protected Poule $poule, int|null $placeNr)
    {
        if( $placeNr === null ) {
            $placeNr = $poule->getPlaces()->count() + 1;
        }
        $this->placeNr = $placeNr;
        $poule->getPlaces()->add($this);
    }

    public function getPoule(): Poule
    {
        return $this->poule;
    }

    #[\Override]
    public function getPouleNr(): int
    {
        return $this->poule->getNumber();
    }

    #[\Override]
    public function getPlaceNr(): int
    {
        return $this->placeNr;
    }

    public function getUniqueNumber(): int
    {
        return pow(2, $this->placeNr - 1);
    }

    #[\Override]
    public function getUniqueIndex(): string
    {
        return $this->__toString();
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->getPouleNr() . '.' . $this->getPlaceNr();
    }
}
