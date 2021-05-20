<?php
declare(strict_types=1);

namespace SportsPlanning\GameGenerator\GameMode;

use Exception;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;
use SportsPlanning\Field;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Game\Place\Together as TogetherGamePlace;
use SportsPlanning\GameGenerator\GameRoundPlace;
use SportsPlanning\PlaceCounter;
use SportsPlanning\Place;
use SportsPlanning\Planning;
use SportsPlanning\Poule;

class SingleHelper
{
    /**
     * @var array<string,array<string,PlaceCounter>>
     */
    protected array $placeCounters = [];
    protected Field|null $defaultField = null;

    public function __construct(protected Planning $planning)
    {
    }

    protected function getDefaultField(): Field
    {
        if ($this->defaultField === null) {
            throw new Exception('geen standaard veld gedefinieerd', E_ERROR);
        }
        return $this->defaultField;
    }

    public function setDefaultField(Field $field): void
    {
        $this->defaultField = $field;
    }


    public function addPlaces(Poule $poule): void
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
     * @param SingleSportVariant $sportVariant
     * @param list<GameRoundPlace> $base
     * @param list<GameRoundPlace> $choosable
     * @return TogetherGame
     */
    public function createGame(Poule $poule, SingleSportVariant $sportVariant, array $base, array $choosable): TogetherGame
    {
        while (count($base) < $sportVariant->getNrOfGamePlaces() && count($choosable) > 0) {
            $gameRoundPlace = $this->getBestPlace($base, $choosable);
            $idx = array_search($gameRoundPlace, $choosable, true);
            if ($idx !== false) {
                array_splice($choosable, $idx, 1);
            }
            $base[] = $gameRoundPlace;
        }
        $this->increment($this->mapToPlaces($base));
        $game = new TogetherGame($this->planning, $poule, $this->getDefaultField());
        foreach ($base as $basePlace) {
            new TogetherGamePlace($game, $basePlace->getPlace(), $basePlace->getGameRoundNumber());
        }
        return $game;
    }

    /**
     * @param list<GameRoundPlace> $gameRoundPlaces
     * @return list<Place>
     */
    protected function mapToPlaces(array $gameRoundPlaces): array
    {
        return array_map(function (GameRoundPlace $gameRoundPlace): Place {
            return $gameRoundPlace->getPlace();
        }, $gameRoundPlaces);
    }

    /**
     * @param list<GameRoundPlace> $base
     * @param list<GameRoundPlace> $choosable
     * @return GameRoundPlace
     */
    protected function getBestPlace(array $base, array $choosable): GameRoundPlace
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
        if ($bestPlace === null) {
            throw new Exception('de beste plek mag niet leeg zijn', E_ERROR);
        }
        return $bestPlace;
    }

    /**
     * @param Place $place
     * @param list<Place> $basePlaces
     * @return int
     */
    protected function getScore(Place $place, array $basePlaces): int
    {
        $score = 0;
        foreach ($basePlaces as $basePlace) {
            $score += $this->getPlaceCounter($place, $basePlace)->count();
        }
        return $score;
    }

    protected function getPlaceCounter(Place $place, Place $coPlace): PlaceCounter
    {
        return $this->placeCounters[$place->getLocation()][$coPlace->getLocation()];
    }

    /**
     * @param list<Place> $places
     * @return void
     */
    protected function increment(array $places): void
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
