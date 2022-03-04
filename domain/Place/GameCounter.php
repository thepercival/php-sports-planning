<?php

namespace SportsPlanning\Place;

use SportsPlanning\Place;
use SportsPlanning\Resource\GameCounter as ResourceGameCounter;

class GameCounter extends ResourceGameCounter
{
    public function __construct(protected Place $place, int $nrOfGames = 0)
    {
        parent::__construct($place, $nrOfGames);
    }

    public function getPlace(): Place
    {
        return $this->place;
    }
}
