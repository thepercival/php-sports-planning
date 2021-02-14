<?php

namespace SportsPlanning\GameGenerator;

use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Game\Place\Together as TogetherGamePlace;
use SportsPlanning\Place;
use SportsPlanning\Poule;

class TogetherCounter
{
    /**
     * @var array | PlaceCounter[][]
     */
    protected array $placeCounters = [];

    public function __construct()
    {
    }

    public function addPlaces(Poule $poule)
    {
        foreach ($poule->getPlaces() as $placeIt) {
            $this->placeCounters[$placeIt->getLocation()] = [];
            foreach ($poule->getPlaces() as $coPlace) {
                if ($coPlace === $placeIt) {
                    continue;
                }
                $this->placeCounters[$placeIt->getLocation()][$coPlace->getLocation()] = new PlaceCounter($coPlace);
            }
        }
    }

    /**
     * @param Poule $poule
     * @param array|GameRoundPlace[] $base
     * @param array|GameRoundPlace[] $choosable
     * @param int $nrOfGamePlaces
     * @return TogetherGame
     */
    public function createGame(Poule $poule, array $base, array $choosable, int $nrOfGamePlaces): TogetherGame
    {
        while (count($base) < $nrOfGamePlaces && count($choosable) > 0) {
            $gameRoundPlace = $this->getBestPlace($base, $choosable);
            array_splice($choosable, array_search($gameRoundPlace, $choosable, true), 1);
            $base[] = $gameRoundPlace;
        }
        $this->increment($this->mapToPlaces($base));
        $game = new TogetherGame($poule);
        foreach ($base as $basePlace) {
            new TogetherGamePlace($game, $basePlace->getPlace(), $basePlace->getGameRoundNumber());
        }
        return $game;
    }

    /**
     * @param array|GameRoundPlace[] $gameRoundPlaces
     * @return array|Place[]
     */
    protected function mapToPlaces(array $gameRoundPlaces): array
    {
        return array_map(function (GameRoundPlace $gameRoundPlace): Place {
            return $gameRoundPlace->getPlace();
        }, $gameRoundPlaces);
    }

    /**
     * @param array|GameRoundPlace[] $base
     * @param array|GameRoundPlace[] $choosable
     * @return GameRoundPlace
     */
    protected function getBestPlace(array $base, $choosable): GameRoundPlace
    {
        $basePlaces = $this->mapToPlaces($base);
        $bestPlace = null;
        $lowestScore = null;
        while (count($choosable) > 0) {
            $choosableGameRoundPlace = array_shift($choosable);
            $score = $this->getScore($choosableGameRoundPlace->getPlace(), $basePlaces);
            if ($lowestScore === null || $score < $lowestScore) {
                $lowestScore = $score;
                $bestPlace = $choosableGameRoundPlace;
            }
        }
        return $bestPlace;
    }

    protected function getScore(Place $place, array $basePlaces): int
    {
        $score = 0;
        foreach ($basePlaces as $basePlace) {
            $score += $this->getPlaceCounter($place, $basePlace)->getCounter();
        }
        return $score;
    }

    protected function getPlaceCounter(Place $place, Place $coPlace): PlaceCounter
    {
        return $this->placeCounters[$place->getLocation()][$coPlace->getLocation()];
    }

    /**
     * @param array|Place[] $places
     */
    protected function increment(array $places)
    {
        foreach ($places as $placeIt) {
            foreach ($places as $coPlace) {
                if ($coPlace === $placeIt) {
                    continue;
                }
                $this->getPlaceCounter($placeIt, $coPlace)->increment();
            }
        }
    }
}
