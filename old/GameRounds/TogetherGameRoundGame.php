<?php

declare(strict_types=1);

namespace old\GameRounds;

use SportsPlanning\Combinations\PlaceNrCombination;
use SportsPlanning\Place;

final class TogetherGameRoundGame implements \Stringable
{
    /**
     * @param list<TogetherGameRoundGamePlace> $gamePlaces
     */
    public function __construct(TogetherGameRound $gameRound, private array $gamePlaces)
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
     * @return list<TogetherGameRoundGamePlace>
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

    public function hasOverlap(TogetherGameRoundGame $game): bool
    {
        return ($this->getNumber() & $game->getNumber()) > 0;
    }

    public function equals(TogetherGameRoundGame $game): bool
    {
        return ($this->getNumber() === $game->getNumber());
    }

    public function toPlaceNrCombination(): PlaceNrCombination
    {
        $places = array_map(fn(TogetherGameRoundGamePlace $gamePlace) => $gamePlace->getPlace(), $this->gamePlaces);
        return new PlaceNrCombination($places);
    }

    #[\Override]
    public function __toString(): string
    {
        return join(' & ', $this->gamePlaces);
    }
}
