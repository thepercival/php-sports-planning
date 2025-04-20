<?php

declare(strict_types=1);

namespace SportsPlanning\Schedules\GameRounds;

readonly class TogetherGameRoundGamePlace implements \Stringable
{
    public function __construct(public int $gameRoundNumber, public int $placeNr)
    {

    }

    public function __toString(): string
    {
        return $this->placeNr . '('.$this->gameRoundNumber.')';
    }
}
