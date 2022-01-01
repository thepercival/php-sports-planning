<?php

declare(strict_types=1);

namespace SportsPlanning\GameRound\Together;

use SportsPlanning\Place;

class GamePlace implements \Stringable
{
    public function __construct(protected int $gameRoundNumber, protected Place $place)
    {
    }

    public function getGameRoundNumber(): int
    {
        return $this->gameRoundNumber;
    }

    public function getPlace(): Place
    {
        return $this->place;
    }

    public function __toString(): string
    {
        return $this->getPlace()->getNumber() . '(' .$this->getGameRoundNumber() . ')';
    }
}
