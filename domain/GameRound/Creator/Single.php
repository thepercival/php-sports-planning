<?php

declare(strict_types=1);

namespace SportsPlanning\GameRound\Creator;

use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;
use SportsHelpers\Sport\Variant\WithPoule\Single as SingleWithPoule;
use SportsPlanning\Combinations\AssignedCounter;
use SportsPlanning\Combinations\Output\GameRound as GameRoundOutput;
use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\Combinations\PlaceCounterMap;
use SportsPlanning\GameRound\Together as TogetherGameRound;
use SportsPlanning\GameRound\Together\Game;
use SportsPlanning\GameRound\Together\GamePlace;
use SportsPlanning\Place;
use SportsPlanning\PlaceCounter;
use SportsPlanning\Poule;

class Single
{
    protected GameRoundOutput $gameRoundOutput;
//    /**
//     * @var array<string,array<string,PlaceCounter>>
//     */
//    protected array $assignedTogetherMap = [];

    public function __construct(LoggerInterface $logger)
    {
        $this->gameRoundOutput = new GameRoundOutput($logger);
    }

    public function createGameRound(
        Poule $poule,
        SingleSportVariant $sportVariant,
        AssignedCounter $assignedCounter
    ): TogetherGameRound {
        $nrOfPlaces = count($poule->getPlaces());
        $variantWithPoule = new SingleWithPoule($nrOfPlaces, $sportVariant);
        $gameRound = new TogetherGameRound();
        $assignedMap = $assignedCounter->getAssignedMap();
        $assignedTogetherMap = $assignedCounter->getAssignedTogetherMap();
        $places = $poule->getPlaces()->toArray();
        $remainingGamePlaces = [];
        $totalNrOfGamesPerPlace = $variantWithPoule->getTotalNrOfGamesPerPlace();
        for ($gameRoundNumber = 1 ; $gameRoundNumber <= $totalNrOfGamesPerPlace ; $gameRoundNumber++) {
            $gamePlaces = array_map(fn(Place $place) => new GamePlace($gameRoundNumber, $place), $places);
            $remainingGamePlaces = $this->assignGameRound(
                $variantWithPoule,
                $assignedMap,
                $assignedTogetherMap,
                array_values($gamePlaces),
                $remainingGamePlaces,
                $gameRound
            );
            foreach( $gameRound->toPlaceCombinations() as $placeCombination ) {
                $this->assignToTogetherMap($assignedTogetherMap, $placeCombination);
                foreach( $placeCombination->getPlaces() as $place ) {
                    $assignedMap = $assignedMap->addPlace($place);
                }
            }

            $gameRound = $gameRound->createNext();
        }
        if (count($remainingGamePlaces) > 0) {
            $this->assignGameRound($variantWithPoule, $assignedMap, $assignedTogetherMap, $remainingGamePlaces, [], $gameRound, true);
        }
        if (count($gameRound->getLeaf()->getGames()) === 0) {
            $gameRound->getLeaf()->detachFromPrevious();
        }
        return $gameRound->getFirst();
    }


    /**
     * @param SingleWithPoule $variantWithPoule
     * @param PlaceCounterMap $assignedMap
     * @param array<string,array<string,PlaceCounter>> $assignedTogetherMap
     * @param list<GamePlace> $unSortedGamePlaces
     * @param list<GamePlace> $remainingGamePlaces
     * @param TogetherGameRound $gameRound
     * @param bool $finalGameRound
     * @return list<GamePlace>
     */
    protected function assignGameRound(
        SingleWithPoule $variantWithPoule,
        PlaceCounterMap $assignedMap,
        array $assignedTogetherMap,
        array $unSortedGamePlaces,
        array $remainingGamePlaces,
        TogetherGameRound $gameRound,
        bool $finalGameRound = false
    ): array {
        $newRemainingGamePlaces = [];

        $choosableGamePlaces = $this->sortGamePlaces($assignedMap, $assignedTogetherMap, $unSortedGamePlaces);
        $remainingGamePlaces = $this->sortGamePlaces($assignedMap, $assignedTogetherMap, $remainingGamePlaces);
        $choosableGamePlaces = array_merge($remainingGamePlaces, $choosableGamePlaces);
        while (count($choosableGamePlaces) > 0) {
            $bestGamePlace = $this->getBestGamePlace($assignedTogetherMap, $newRemainingGamePlaces, $choosableGamePlaces);
            if ($bestGamePlace === null) {
                break;
            }
            $idx = array_search($bestGamePlace, $choosableGamePlaces, true);
            if ($idx !== false) {
                array_splice($choosableGamePlaces, $idx, 1);
            }
            $newRemainingGamePlaces[] = $bestGamePlace;
            if (count($newRemainingGamePlaces) === $variantWithPoule->getSportVariant()->getNrOfGamePlaces()) {
                new Game($gameRound, $newRemainingGamePlaces);
                $newRemainingGamePlaces = [];
            }
        }
        if ($finalGameRound && count($newRemainingGamePlaces) > 0) {
            new Game($gameRound, $newRemainingGamePlaces);
        }
        return $newRemainingGamePlaces;
    }

    /**
     * @param array<string,array<string,PlaceCounter>> $assignedTogetherMap
     * @return void
     */
    protected function assignToTogetherMap(array &$assignedTogetherMap, PlaceCombination $placeCombination): void
    {
        $places = $placeCombination->getPlaces();
        foreach ($places as $placeIt) {
            foreach ($places as $coPlace) {
                if ($coPlace === $placeIt) {
                    continue;
                }
                $assignedTogetherMap[$placeIt->getLocation()][$coPlace->getLocation()]->increment();
            }
        }
    }

    /**
     * @param PlaceCounterMap $assignedMap
     * @param array<string,array<string,PlaceCounter>> $assignedTogetherMap
     * @param list<GamePlace> $gamePlaces
     * @return list<GamePlace>
     */
    protected function sortGamePlaces(
        PlaceCounterMap $assignedMap,
        array $assignedTogetherMap,
        array $gamePlaces): array
    {
        uasort(
            $gamePlaces,
            function (GamePlace $gamePlaceA, GamePlace $gamePlaceB) use ($assignedMap, $assignedTogetherMap, $gamePlaces): int {
                $nrOfAssignedGamesA = $assignedMap->count($gamePlaceA->getPlace());
                $nrOfAssignedGamesB = $assignedMap->count($gamePlaceB->getPlace());
                if ($nrOfAssignedGamesA !== $nrOfAssignedGamesB) {
                    return $nrOfAssignedGamesA - $nrOfAssignedGamesB;
                }
                $placesToCompareA = $this->getOtherGamePlaces($gamePlaceA, $gamePlaces);
                $scoreA = $this->getScore($assignedTogetherMap, $gamePlaceA->getPlace(), $placesToCompareA);
                $placesToCompareB = $this->getOtherGamePlaces($gamePlaceB, $gamePlaces);
                $scoreB = $this->getScore($assignedTogetherMap, $gamePlaceA->getPlace(), $placesToCompareB);
                return $scoreA - $scoreB;
            }
        );
        return array_values($gamePlaces);
    }

    /**
     * @param array<string,array<string,PlaceCounter>> $assignedTogetherMap
     * @param list<GamePlace> $gamePlaces
     * @param list<GamePlace> $choosableGamePlaces
     * @return GamePlace|null
     */
    protected function getBestGamePlace(
        array $assignedTogetherMap,
        array $gamePlaces,
        array $choosableGamePlaces
    ): GamePlace|null {
        $bestGamePlace = null;
        $lowestScore = null;
        foreach ($choosableGamePlaces as $choosableGamePlace) {
            $score = $this->getScore($assignedTogetherMap, $choosableGamePlace->getPlace(), $gamePlaces);
            if ($lowestScore === null || $score < $lowestScore) {
                $lowestScore = $score;
                $bestGamePlace = $choosableGamePlace;
            }
        }
        return $bestGamePlace;
    }

//    /**
//     * @param Place $place
//     * @param list<Place> $gamePlaces
//     * @param list<Place> $allPlaces
//     * @return list<Place>
//     */
//    protected function getPlacesToCompare(Place $place, array $gamePlaces, array $allPlaces): array
//    {
//        if (count($gamePlaces) === 0) {
//            return $this->getOtherGamePlaces($place, $allPlaces);
//        }
//        return $gamePlaces;
//    }

    /**
     * @param GamePlace $gamePlace
     * @param list<GamePlace> $gamePlaces
     * @return list<GamePlace>
     */
    protected function getOtherGamePlaces(GamePlace $gamePlace, array $gamePlaces): array
    {
        $idx = array_search($gamePlace, $gamePlaces, true);
        if ($idx === false) {
            return $gamePlaces;
        }
        array_splice($gamePlaces, $idx, 1);
        return $gamePlaces;
    }

    /**
     * @param array<string,array<string,PlaceCounter>> $assignedTogetherMap
     * @param Place $place
     * @param list<GamePlace> $gamePlaces
     * @return int
     */
    protected function getScore(array $assignedTogetherMap, Place $place, array $gamePlaces): int
    {
        $score = 0;
        foreach ($gamePlaces as $gamePlace) {
            if ($place === $gamePlace->getPlace()) {
                return 100000;
            }
            $placeCounter = $this->getTogetherPlaceCounter($assignedTogetherMap, $place, $gamePlace->getPlace());
            $score += $placeCounter !== null ? $placeCounter->count() : 0;
        }
        return $score;
    }

    /**
     * @param array<string,array<string,PlaceCounter>> $assignedTogetherMap
     * @param Place $place
     * @param Place $coPlace
     * @return PlaceCounter|null
     */
    protected function getTogetherPlaceCounter(array $assignedTogetherMap, Place $place, Place $coPlace): PlaceCounter|null
    {
        if (!isset($assignedTogetherMap[$place->getLocation()])
            || !isset($assignedTogetherMap[$place->getLocation()][$coPlace->getLocation()])) {
            return null;
        }
        return $assignedTogetherMap[$place->getLocation()][$coPlace->getLocation()];
    }
}
