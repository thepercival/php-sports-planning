<?php

namespace SportsPlanning\Game;

use SportsHelpers\Identifiable;
use SportsPlanning\Place as PoulePlace;

abstract class GamePlaceAbstract extends Identifiable
{
    protected PoulePlace $place;

    public function __construct(PoulePlace $place)
    {
        $this->place = $place;
    }


    public function getPlace(): PoulePlace
    {
        return $this->place;
    }

    public function getPlaceLocation(): string
    {
        return (string)$this->place;
    }
}
