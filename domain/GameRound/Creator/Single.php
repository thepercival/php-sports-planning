<?php

declare(strict_types=1);

namespace SportsPlanning\GameRound\Creator;

use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;
use SportsHelpers\Sport\Variant\WithPoule\Single as SingleWithPoule;
use SportsPlanning\Combinations\AssignedCounter;
use SportsPlanning\Combinations\Output\GameRound as GameRoundOutput;
use SportsPlanning\GameRound\Together as TogetherGameRound;
use SportsPlanning\GameRound\Together\Game;
use SportsPlanning\GameRound\Together\GamePlace;
use SportsPlanning\Place;
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
//        $this->assignedTogetherMap = $assignedCounter->getAssignedTogetherMap();
        $places = $poule->getPlaces()->toArray();
        $remainingGamePlaces = [];
        $totalNrOfGamesPerPlace = $variantWithPoule->getTotalNrOfGamesPerPlace();
        for ($gameRoundNumber = 1 ; $gameRoundNumber <= $totalNrOfGamesPerPlace ; $gameRoundNumber++) {
            $gamePlaces = array_map(fn(Place $place) => new GamePlace($gameRoundNumber, $place), $places);
            $remainingGamePlaces = $this->assignGameRound(
                $variantWithPoule,
                $assignedCounter,
                array_values($gamePlaces),
                $remainingGamePlaces,
                $gameRound
            );

            $gameRound = $gameRound->createNext();
        }
        if (count($remainingGamePlaces) > 0) {
            $this->assignGameRound($variantWithPoule, $assignedCounter, $remainingGamePlaces, [], $gameRound, true);
        }
        if (count($gameRound->getLeaf()->getGames()) === 0) {
            $gameRound->getLeaf()->detachFromPrevious();
        }
        return $gameRound->getFirst();
    }


    /**
     * @param SingleWithPoule $variantWithPoule
     * @param AssignedCounter $assignedCounter
     * @param list<GamePlace> $unSortedGamePlaces
     * @param list<GamePlace> $remainingGamePlaces
     * @param TogetherGameRound $gameRound
     * @param bool $finalGameRound
     * @return list<GamePlace>
     */
    protected function assignGameRound(
        SingleWithPoule $variantWithPoule,
        AssignedCounter $assignedCounter,
        array $unSortedGamePlaces,
        array $remainingGamePlaces,
        TogetherGameRound $gameRound,
        bool $finalGameRound = false
    ): array {
        $newRemainingGamePlaces = [];
        $choosableGamePlaces = $this->sortGamePlaces($assignedCounter, $unSortedGamePlaces);
        $remainingGamePlaces = $this->sortGamePlaces($assignedCounter, $remainingGamePlaces);
        $choosableGamePlaces = array_merge($remainingGamePlaces, $choosableGamePlaces);
        while (count($choosableGamePlaces) > 0) {
            $bestGamePlace = $this->getBestGamePlace($assignedCounter, $newRemainingGamePlaces, $choosableGamePlaces);
            if ($bestGamePlace === null) {
                break;
            }
            $idx = array_search($bestGamePlace, $choosableGamePlaces, true);
            if ($idx !== false) {
                array_splice($choosableGamePlaces, $idx, 1);
            }
            array_push($newRemainingGamePlaces, $bestGamePlace);
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
     * @param AssignedCounter $assignedCounter
     * @param list<GamePlace> $gamePlaces
     * @return list<GamePlace>
     */
    protected function sortGamePlaces(AssignedCounter $assignedCounter, array $gamePlaces): array
    {
        uasort(
            $gamePlaces,
            function (GamePlace $gamePlaceA, GamePlace $gamePlaceB) use ($assignedCounter, $gamePlaces): int {
                $nrOfAssignedGamesA = $assignedCounter->getAssignedPlaceCounter($gamePlaceA->getPlace())?->count() ?? 0;
                $nrOfAssignedGamesB = $assignedCounter->getAssignedPlaceCounter($gamePlaceB->getPlace())?->count() ?? 0;
                if ($nrOfAssignedGamesA !== $nrOfAssignedGamesB) {
                    return $nrOfAssignedGamesA - $nrOfAssignedGamesB;
                }
                $placesToCompareA = $this->getOtherGamePlaces($gamePlaceA, $gamePlaces);
                $scoreA = $this->getScore($assignedCounter, $gamePlaceA->getPlace(), $placesToCompareA);
                $placesToCompareB = $this->getOtherGamePlaces($gamePlaceB, $gamePlaces);
                $scoreB = $this->getScore($assignedCounter, $gamePlaceA->getPlace(), $placesToCompareB);
                return $scoreA - $scoreB;
            }
        );
        return array_values($gamePlaces);
    }

    /**
     * @param list<GamePlace> $gamePlaces
     * @param list<GamePlace> $choosableGamePlaces
     * @return GamePlace|null
     */
    protected function getBestGamePlace(
        AssignedCounter $assignedCounter,
        array $gamePlaces,
        array $choosableGamePlaces
    ): GamePlace|null {
        $bestGamePlace = null;
        $lowestScore = null;
        foreach ($choosableGamePlaces as $choosableGamePlace) {
            $score = $this->getScore($assignedCounter, $choosableGamePlace->getPlace(), $gamePlaces);
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
     * @param AssignedCounter $assignedCounter ,
     * @param Place $place
     * @param list<GamePlace> $gamePlaces
     * @return int
     */
    protected function getScore(AssignedCounter $assignedCounter, Place $place, array $gamePlaces): int
    {
        $score = 0;
        foreach ($gamePlaces as $gamePlace) {
            if ($place === $gamePlace->getPlace()) {
                return 100000;
            }
            $placeCounter = $assignedCounter->getTogetherPlaceCounter($place, $gamePlace->getPlace());
            $score += $placeCounter !== null ? $placeCounter->count() : 0;
        }
        return $score;
    }
}
