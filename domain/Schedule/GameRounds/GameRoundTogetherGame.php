<?php

declare(strict_types=1);

namespace SportsPlanning\Schedule\GameRounds;

use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Schedule\GameRounds\TogetherGameRound as GameRound;

class GameRoundTogetherGame implements \Stringable
{
    /**
     * @param list<GameRoundTogetherGamePlace> $gamePlaces
     */
    public function __construct(GameRound $gameRound, private array $gamePlaces)
    {
        $gameRound->addGame($this);
    }

    public function createUniqueNumber(): int
    {
        $number = 0;
        foreach ($this->gamePlaces as $gamePlace) {
            $placeNr = $gamePlace->getPlaceNr();
            $number += pow(2, $placeNr - 1);
        }
        return $number;
    }

    /**
     * @return list<GameRoundTogetherGamePlace>
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
        return ($this->createUniqueNumber() & $placeNr) > 0;
    }

    public function hasOverlap(self $game): bool
    {
        return ($this->createUniqueNumber() & $game->createUniqueNumber()) > 0;
    }

    public function equals(self $game): bool
    {
        return ($this->createUniqueNumber() === $game->createUniqueNumber());
    }

    /**
     * @return list<DuoPlaceNr>
     */
    public function convertToDuoPlaceNrs(): array
    {
        $duoPlaceNrs = [];
        {
            $gamePlaces = $this->gamePlaces;
            foreach ($this->gamePlaces as $gamePlaceOne) {
                $placeNrOne = $gamePlaceOne->getPlaceNr();
                foreach ($gamePlaces as $gamePlaceTwo) {
                    $placeNrTwo = $gamePlaceTwo->getPlaceNr();
                    if ($placeNrOne >= $placeNrTwo) {
                        continue;
                    }
                    $duoPlaceNrs[] = new DuoPlaceNr($placeNrOne,$placeNrTwo);
                }
            }
        }
        return $duoPlaceNrs;
    }

    /**
     * @return list<int>
     */
    public function convertToPlaceNrs(): array
    {
        return array_map(fn(GameRoundTogetherGamePlace $gamePlace) => $gamePlace->getPlaceNr(), $this->gamePlaces);
    }

    public function __toString(): string
    {
        return join(' & ', $this->gamePlaces);
    }
}
