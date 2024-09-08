<?php

declare(strict_types=1);

namespace SportsPlanning\Schedule\GameRounds;

use SportsPlanning\Combinations\DuoPlaceNr;

readonly class TogetherGameRoundGame implements \Stringable
{

    /**
     * @param list<TogetherGameRoundGamePlace> $gamePlaces
     */
    public function __construct(public array $gamePlaces)
    {
        $uniquePlaceNrs = [];
        foreach ($gamePlaces as $gamePlace) {
            if( array_key_exists($gamePlace->placeNr, $uniquePlaceNrs) ) {
                throw new \Exception('placeNr can be in a game only 1 time');
            }
            $uniquePlaceNrs[$gamePlace->placeNr] = true;
        }
    }

    public function createUniqueNumber(): int
    {
        return array_sum(array_map(fn(int $placeNr) => pow(2, $placeNr - 1), $this->convertToPlaceNrs()) );
    }

    public function count(): int
    {
        return count($this->gamePlaces);
    }

    public function has(int $placeNr): bool
    {
        return ($this->createUniqueNumber() & (pow(2, $placeNr - 1))) > 0;
    }

//    public function hasOverlap(self $game): bool
//    {
//        return ($this->createUniqueNumber() & $game->createUniqueNumber()) > 0;
//    }
//
//    public function equals(self $game): bool
//    {
//        return ($this->createUniqueNumber() === $game->createUniqueNumber());
//    }

    /**
     * @return list<DuoPlaceNr>
     */
    public function convertToDuoPlaceNrs(): array
    {
        $duoPlaceNrs = [];
        {
            $gamePlaces = $this->gamePlaces;
            foreach ($this->gamePlaces as $gamePlaceOne) {
                foreach ($gamePlaces as $gamePlaceTwo) {
                    if ($gamePlaceOne >= $gamePlaceTwo) {
                        continue;
                    }
                    $duoPlaceNrs[] = new DuoPlaceNr($gamePlaceOne->placeNr,$gamePlaceTwo->placeNr);
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
        return array_map(fn(TogetherGameRoundGamePlace $gamePlace) => $gamePlace->placeNr, $this->gamePlaces);
    }

    public function __toString(): string
    {
        return join( ' & ', $this->gamePlaces);
    }
}
