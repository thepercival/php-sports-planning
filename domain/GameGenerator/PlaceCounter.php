<?php


namespace SportsPlanning\GameGenerator;

use SportsPlanning\Place;

class PlaceCounter
{
    private Place $place;
    private int $counter = 0;

    public function __construct(Place $place)
    {
        $this->place = $place;
    }

    public function getNumber(): int
    {
        return $this->place->getNumber();
    }

    public function getCounter(): int
    {
        return $this->counter;
    }

    public function increment()
    {
        $this->counter++;
    }
}
