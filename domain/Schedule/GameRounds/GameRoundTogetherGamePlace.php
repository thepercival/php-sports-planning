<?php

declare(strict_types=1);

namespace SportsPlanning\Schedule\GameRounds;

class GameRoundTogetherGamePlace implements \Stringable
{
    public function __construct(protected int $gameRoundNumber, protected int $placeNr)
    {
    }

    public function getGameRoundNumber(): int
    {
        return $this->gameRoundNumber;
    }

    public function getPlaceNr(): int
    {
        return $this->placeNr;
    }

    public function __toString(): string
    {
        return $this->getPlaceNr() . '(' .$this->getGameRoundNumber() . ')';
    }
}
