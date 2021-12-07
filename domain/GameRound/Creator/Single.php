<?php

declare(strict_types=1);

namespace SportsPlanning\GameRound\Creator;

use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;
use SportsPlanning\Combinations\GamePlaceStrategy;
use SportsPlanning\Combinations\Output\GameRound as GameRoundOutput;
use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\GameRound\CreatorInterface;
use SportsPlanning\GameRound\Together as TogetherGameRound;
use SportsPlanning\GameRound\Together\Game;
use SportsPlanning\GameRound\Together\GamePlace;
use SportsPlanning\Place;
use SportsPlanning\PlaceCounter;
use SportsPlanning\Poule;
use SportsPlanning\Schedule\Creator\AssignedCounter;

/**
 * @template-implements CreatorInterface<TogetherGameRound>
 */
class Single implements CreatorInterface
{
    protected GameRoundOutput $gameRoundOutput;
    /**
     * @var array<string,array<string,PlaceCounter>>
     */
    protected array $assignedTogetherMap = [];

    public function __construct(protected SingleSportVariant $sportVariant, LoggerInterface $logger)
    {
        $this->gameRoundOutput = new GameRoundOutput($logger);
    }

    public function createGameRound(
        Poule $poule,
        AssignedCounter $assignedCounter,
        int $totalNrOfGamesPerPlace
    ): TogetherGameRound {
        $gameRound = new TogetherGameRound();
        $gamePlaceStrategy = $poule->getInput()->getGamePlaceStrategy();
        $this->assignedTogetherMap = $assignedCounter->getAssignedTogetherMap();
        $places = $poule->getPlaces()->toArray();
        $remainingGamePlaces = [];
        for ($gameRoundNumber = 1 ; $gameRoundNumber <= $totalNrOfGamesPerPlace ; $gameRoundNumber++) {
            if ($gamePlaceStrategy === GamePlaceStrategy::RandomlyAssigned) {
                shuffle($places);
            }
            $gamePlaces = array_map(fn (Place $place) => new GamePlace($gameRoundNumber, $place), $places);
            $remainingGamePlaces = $this->assignGameRound(array_values($gamePlaces), $remainingGamePlaces, $gameRound, $gamePlaceStrategy);

            $gameRound = $gameRound->createNext();
        }
        if (count($remainingGamePlaces) > 0) {
            $this->assignGameRound($remainingGamePlaces, [], $gameRound, $gamePlaceStrategy, true);
        }
        if (count($gameRound->getLeaf()->getGames()) === 0) {
            $gameRound->getLeaf()->detachFromPrevious();
        }
        return $gameRound->getFirst();
    }


    /**
     * @param list<GamePlace> $unSortedGamePlaces
     * @param list<GamePlace> $remainingGamePlaces
     * @param TogetherGameRound $gameRound
     * @param GamePlaceStrategy $gamePlaceStrategy
     * @param bool $finalGameRound
     * @return list<GamePlace>
     */
    protected function assignGameRound(
        array $unSortedGamePlaces,
        array $remainingGamePlaces,
        TogetherGameRound $gameRound,
        GamePlaceStrategy $gamePlaceStrategy,
        bool $finalGameRound = false
    ): array {
        $newRemainingGamePlaces = [];
        $choosableGamePlaces = $this->sortGamePlaces($unSortedGamePlaces);
        $remainingGamePlaces = $this->sortGamePlaces($remainingGamePlaces);
        $choosableGamePlaces = array_merge($remainingGamePlaces, $choosableGamePlaces);
        while (count($choosableGamePlaces) > 0) {
            $bestGamePlace = $this->getBestGamePlace($newRemainingGamePlaces, $choosableGamePlaces);
            if ($bestGamePlace === null) {
                break;
            }
            $idx = array_search($bestGamePlace, $choosableGamePlaces, true);
            if ($idx !== false) {
                array_splice($choosableGamePlaces, $idx, 1);
            }
            array_push($newRemainingGamePlaces, $bestGamePlace);
            if (count($newRemainingGamePlaces) === $this->sportVariant->getNrOfGamePlaces()) {
                $game = new Game($gameRound, $newRemainingGamePlaces);
                $this->assignPlaceCombination($game->toPlaceCombination());
                $newRemainingGamePlaces = [];
            }
        }
        if ($gamePlaceStrategy === GamePlaceStrategy::RandomlyAssigned) {
            return [];
        }
        if ($finalGameRound && count($newRemainingGamePlaces) > 0) {
            $game = new Game($gameRound, $newRemainingGamePlaces);
            $this->assignPlaceCombination($game->toPlaceCombination());
        }
        return $newRemainingGamePlaces;
    }

    /**
     * @param list<GamePlace> $gamePlaces
     * @return list<GamePlace>
     */
    protected function sortGamePlaces(array $gamePlaces): array
    {
        uasort($gamePlaces, function (GamePlace $gamePlaceA, GamePlace $gamePlaceB) use ($gamePlaces): int {
            $placesToCompareA = $this->getOtherGamePlaces($gamePlaceA, $gamePlaces);
            $scoreA = $this->getScore($gamePlaceA->getPlace(), $placesToCompareA);
            $placesToCompareB = $this->getOtherGamePlaces($gamePlaceB, $gamePlaces);
            $scoreB = $this->getScore($gamePlaceA->getPlace(), $placesToCompareB);
            return $scoreA - $scoreB;
        });
        return array_values($gamePlaces);
    }

    /**
     * @param list<GamePlace> $gamePlaces
     * @param list<GamePlace> $choosableGamePlaces
     * @return GamePlace|null
     */
    protected function getBestGamePlace(array $gamePlaces, array $choosableGamePlaces): GamePlace|null
    {
        $bestGamePlace = null;
        $lowestScore = null;
        foreach ($choosableGamePlaces as $choosableGamePlace) {
            $score = $this->getScore($choosableGamePlace->getPlace(), $gamePlaces);
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
     * @param Place $place
     * @param list<GamePlace> $gamePlaces
     * @return int
     */
    protected function getScore(Place $place, array $gamePlaces): int
    {
        $score = 0;
        foreach ($gamePlaces as $gamePlace) {
            if ($place === $gamePlace->getPlace()) {
                return 100000;
            }
            $placeCounter = $this->getPlaceCounter($place, $gamePlace->getPlace());
            $score += $placeCounter !== null ? $placeCounter->count() : 0;
        }
        return $score;
    }

    /**
     * @param Place $place
     * @param Place $coPlace
     * @return PlaceCounter|null
     */
    protected function getPlaceCounter(Place $place, Place $coPlace): PlaceCounter|null
    {
        if (!isset($this->assignedTogetherMap[$place->getLocation()])
        || !isset($this->assignedTogetherMap[$place->getLocation()][$coPlace->getLocation()])) {
            return null;
        }
        return $this->assignedTogetherMap[$place->getLocation()][$coPlace->getLocation()];
    }

    /**
     * @param PlaceCombination $placeCombination
     * @return void
     */
    protected function assignPlaceCombination(PlaceCombination $placeCombination): void
    {
        $places = $placeCombination->getPlaces();
        foreach ($places as $placeIt) {
            foreach ($places as $coPlace) {
                if ($coPlace === $placeIt) {
                    continue;
                }
                $this->getPlaceCounter($placeIt, $coPlace)?->increment();
            }
        }
    }
}
