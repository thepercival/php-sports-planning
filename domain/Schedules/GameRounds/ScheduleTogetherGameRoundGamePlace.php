<?php

declare(strict_types=1);

namespace SportsPlanning\Schedules\GameRounds;

final class ScheduleTogetherGameRoundGamePlace implements \Stringable
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

    #[\Override]
    public function __toString(): string
    {
        return $this->placeNr . '(' .$this->getGameRoundNumber() . ')';
    }
}
