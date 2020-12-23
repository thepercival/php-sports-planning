<?php

namespace SportsPlanning\Game;

use SportsHelpers\Identifiable;
use SportsPlanning\Game;
use SportsPlanning\Place as PoulePlace;

abstract class Place extends Identifiable
{
    private PoulePlace $place;

    public function __construct(PoulePlace $place)
    {
        $this->place = $place;
    }

    public function getPlace(): PoulePlace
    {
        return $this->place;
    }
}
