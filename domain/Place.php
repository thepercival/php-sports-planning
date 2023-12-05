<?php

namespace SportsPlanning;

use SportsHelpers\PlaceLocation;

class Place extends PlaceLocation implements Resource, \Stringable
{
    protected int|string|null $id = null;

    public function __construct(protected Poule $poule)
    {
        $this->placeNr = $poule->getPlaces()->count() + 1;
        parent::__construct( $poule->getNumber(), $this->placeNr);
        $poule->getPlaces()->add($this);
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function setId(int|string|null $id): void
    {
        $this->id = $id;
    }

    public function getPoule(): Poule
    {
        return $this->poule;
    }

    public function getUniqueNumber(): int
    {
        return pow(2, $this->placeNr - 1);
    }

    public function getUniqueIndex(): string
    {
        return $this->__toString();
    }
}
