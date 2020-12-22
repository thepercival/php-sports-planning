<?php
declare(strict_types=1);

namespace SportsPlanning\GameGenerator;

use SportsPlanning\Place;
use SportsPlanning\Place as PoulePlace;

class GameRoundPlace
{
    private int $gameRoundNumber;
    private Place $place;

    public function __construct( int $gameRoundNumber, PoulePlace $place)
    {
        $this->place = $place;
        $this->gameRoundNumber = $gameRoundNumber;
    }

    public function getPlace(): Place {
        return $this->place;
    }

    public function getGameRoundNumber(): int
    {
        return $this->gameRoundNumber;
    }
}