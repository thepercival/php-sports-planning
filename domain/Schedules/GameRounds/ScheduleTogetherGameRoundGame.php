<?php

declare(strict_types=1);

namespace SportsPlanning\Schedules\GameRounds;


use SportsPlanning\Combinations\PlaceNrCombination;

final class ScheduleTogetherGameRoundGame implements \Stringable
{
    /**
     * @param list<ScheduleTogetherGameRoundGamePlace> $gamePlaces
     */
    public function __construct(ScheduleTogetherGameRound $gameRound, private array $gamePlaces)
    {
        $gameRound->addGame($this);
    }

    public function getNumber(): int
    {
        $number = 0;
        foreach ($this->gamePlaces as $gamePlace) {
            $number += pow(2, $gamePlace->getPlaceNr() - 1);
        }
        return $number;
    }

    /**
     * @return list<ScheduleTogetherGameRoundGamePlace>
     */
    public function getGamePlaces(): array
    {
        return $this->gamePlaces;
    }

    public function count(): int
    {
        return count($this->gamePlaces);
    }

    public function has(int $placeNr): bool
    {
        $uniqueNumber = pow(2, $placeNr - 1);
        return ($this->getNumber() & $uniqueNumber) > 0;
    }

    public function hasOverlap(ScheduleTogetherGameRoundGame $game): bool
    {
        return ($this->getNumber() & $game->getNumber()) > 0;
    }

    public function equals(ScheduleTogetherGameRoundGame $game): bool
    {
        return ($this->getNumber() === $game->getNumber());
    }

    public function toPlaceNrCombination(): PlaceNrCombination
    {
        $placeNrs = array_map(fn(ScheduleTogetherGameRoundGamePlace $gamePlace) => $gamePlace->getPlaceNr(), $this->gamePlaces);
        return new PlaceNrCombination($placeNrs);
    }

    #[\Override]
    public function __toString(): string
    {
        return join(' & ', $this->gamePlaces);
    }
}
