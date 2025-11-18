<?php

declare(strict_types=1);

namespace SportsPlanning\GameRound\Together;

use SportsPlanning\Place;

final class GamePlace implements \Stringable
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

    #[\Override]
    public function __toString(): string
    {
        return $this->getPlace()->getPlaceNr() . '(' .$this->getGameRoundNumber() . ')';
    }
}
