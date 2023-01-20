<?php

declare(strict_types=1);

namespace SportsPlanning\GameRound\Creator;

use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;
use SportsHelpers\Sport\Variant\WithPoule\Single as SingleWithPoule;
use SportsPlanning\Combinations\AssignedCounter;
use SportsPlanning\Combinations\Output\GameRound;
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
        AssignedCounter $singleAssignedCounter
    ): TogetherGameRound {
        $nrOfPlaces = count($poule->getPlaces());
        $variantWithPoule = new SingleWithPoule($nrOfPlaces, $sportVariant);
        $gameRound = new TogetherGameRound();
        $places = $poule->getPlaces()->toArray();
        $remainingGamePlaces = [];
        $totalNrOfGamesPerPlace = $variantWithPoule->getTotalNrOfGamesPerPlace();
        for ($gameRoundNumber = 1 ; $gameRoundNumber <= $totalNrOfGamesPerPlace ; $gameRoundNumber++) {
            $gamePlaces = array_map(fn(Place $place) => new GamePlace($gameRoundNumber, $place), $places);
            $remainingGamePlaces = $this->assignGameRound(
                $variantWithPoule,
                $singleAssignedCounter,
                array_values($gamePlaces),
                $remainingGamePlaces,
                $gameRound
            );
            $singleAssignedCounter->assignTogether($gameRound->toPlaceCombinations(), true);
            $gameRound = $gameRound->createNext();
        }
        if (count($remainingGamePlaces) > 0) {
            $this->assignGameRound($variantWithPoule, $singleAssignedCounter, $remainingGamePlaces, [], $gameRound, true);
            $singleAssignedCounter->assignTogether($gameRound->toPlaceCombinations(), true);

        }
        if (count($gameRound->getLeaf()->getGames()) === 0) {
            $gameRound->getLeaf()->detachFromPrevious();
        }
        return $gameRound->getFirst();
    }



    /**
     * @param SingleWithPoule $variantWithPoule
     * @param AssignedCounter $singleAssignedCounter
     * @param list<GamePlace> $unSortedGamePlaces
     * @param list<GamePlace> $remainingGamePlaces
     * @param TogetherGameRound $gameRound
     * @param bool $finalGameRound
     * @return list<GamePlace>
     */
    protected function assignGameRound(
        SingleWithPoule $variantWithPoule,
        AssignedCounter $singleAssignedCounter,
        array $unSortedGamePlaces,
        array $remainingGamePlaces,
        TogetherGameRound $gameRound,
        bool $finalGameRound = false
    ): array {
        $newRemainingGamePlaces = [];

        $choosableGamePlaces = $this->sortGamePlaces($singleAssignedCounter, $unSortedGamePlaces);
        $remainingGamePlaces = $this->sortGamePlaces($singleAssignedCounter, $remainingGamePlaces);
        $choosableGamePlaces = array_merge($remainingGamePlaces, $choosableGamePlaces);
        while (count($choosableGamePlaces) > 0) {
            $bestGamePlace = $this->getBestGamePlace($singleAssignedCounter, $newRemainingGamePlaces, $choosableGamePlaces);
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
     * @param AssignedCounter $singleAssignedCounter
     * @param list<GamePlace> $gamePlaces
     * @return list<GamePlace>
     */
    protected function sortGamePlaces(AssignedCounter $singleAssignedCounter,array $gamePlaces): array
    {
        uasort(
            $gamePlaces,
            function (GamePlace $gamePlaceA, GamePlace $gamePlaceB) use ($singleAssignedCounter, $gamePlaces): int {
                $nrOfAssignedGamesA = $singleAssignedCounter->getAssignedMap()->count($gamePlaceA->getPlace());
                $nrOfAssignedGamesB = $singleAssignedCounter->getAssignedMap()->count($gamePlaceB->getPlace());
                if ($nrOfAssignedGamesA !== $nrOfAssignedGamesB) {
                    return $nrOfAssignedGamesA - $nrOfAssignedGamesB;
                }
                $placesToCompareA = $this->getOtherGamePlaces($gamePlaceA, $gamePlaces);
                $scoreA = $this->getScore($singleAssignedCounter, $gamePlaceA->getPlace(), $placesToCompareA);
                $placesToCompareB = $this->getOtherGamePlaces($gamePlaceB, $gamePlaces);
                $scoreB = $this->getScore($singleAssignedCounter, $gamePlaceB->getPlace(), $placesToCompareB);
                return $scoreA - $scoreB;
            }
        );
        return array_values($gamePlaces);
    }

    /**
     * @param AssignedCounter $singleAssignedCounter
     * @param list<GamePlace> $gamePlaces
     * @param list<GamePlace> $choosableGamePlaces
     * @return GamePlace|null
     */
    protected function getBestGamePlace(
        AssignedCounter $singleAssignedCounter,
        array $gamePlaces,
        array $choosableGamePlaces
    ): GamePlace|null {
        $bestGamePlace = null;
        $lowestScore = null;
        foreach ($choosableGamePlaces as $choosableGamePlace) {
            $score = $this->getScore($singleAssignedCounter, $choosableGamePlace->getPlace(), $gamePlaces);
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
     * @param AssignedCounter $singleAssignedCounter
     * @param Place $place
     * @param list<GamePlace> $gamePlaces
     * @return int
     */
    protected function getScore(AssignedCounter $singleAssignedCounter, Place $place, array $gamePlaces): int
    {
        $score = 0;
        foreach ($gamePlaces as $gamePlace) {
            if ($place === $gamePlace->getPlace()) {
                return 100000;
            }
            $placeCounter = $singleAssignedCounter->getTogetherPlaceCounter($place, $gamePlace->getPlace());
            $score += $placeCounter !== null ? $placeCounter->count() : 0;
        }
        return $score;
    }


}
