<?php

declare(strict_types=1);

namespace SportsPlanning\GameRound\Together;

use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\GameRound\Together as GameRound;
use SportsPlanning\Place;

class Game implements \Stringable
{
    /**
     * @param list<GamePlace> $gamePlaces
     */
    public function __construct(GameRound $gameRound, private array $gamePlaces)
    {
        $gameRound->addGame($this);
    }

    public function getNumber(): int
    {
        $number = 0;
        foreach ($this->gamePlaces as $gamePlace) {
            $place = $gamePlace->getPlace();
            $number += pow(2, $place->getPlaceNr() - 1);
        }
        return $number;
    }

    /**
     * @return list<GamePlace>
     */
    public function getGamePlaces(): array
    {
        return $this->gamePlaces;
    }

    public function count(): int
    {
        return count($this->gamePlaces);
    }

    public function has(Place $place): bool
    {
        return ($this->getNumber() & $place->getUniqueNumber()) > 0;
    }

    public function hasOverlap(Game $game): bool
    {
        return ($this->getNumber() & $game->getNumber()) > 0;
    }

    public function equals(Game $game): bool
    {
        return ($this->getNumber() === $game->getNumber());
    }

    /**
     * @return list<PlaceCombination>
     */
    public function toPlaceCombinationsOfTwo(): array
    {
        $placeCombinationsOfTwo = [];
        {
            $gamePlaces = $this->gamePlaces;
            foreach ($this->gamePlaces as $gamePlaceOne) {
                $placeOne = $gamePlaceOne->getPlace();
                foreach ($gamePlaces as $gamePlaceTwo) {
                    $placeTwo = $gamePlaceTwo->getPlace();
                    if ($placeOne->getPlaceNr() >= $placeTwo->getPlaceNr()) {
                        continue;
                    }
                    $placeCombinationsOfTwo[] = new PlaceCombination([$placeOne,$placeTwo]);
                }
            }
        }
        return $placeCombinationsOfTwo;
    }

    /**
     * @return list<Place>
     */
    public function toPlaces(): array
    {
        return array_map(fn(GamePlace $gamePlace) => $gamePlace->getPlace(), $this->gamePlaces);
    }

    public function __toString(): string
    {
        return join(' & ', $this->gamePlaces);
    }
}
