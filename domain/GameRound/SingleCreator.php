<?php
declare(strict_types=1);

namespace SportsPlanning\GameRound;

use Exception;
use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;
use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\Combinations\Output\GameRound as GameRoundOutput;
use SportsPlanning\GameGenerator\AssignedCounter;
use SportsPlanning\Place;
use SportsPlanning\PlaceCounter;
use SportsPlanning\Poule;

/**
 * @template-implements GameRoundCreator<SingleGameRound>
 */
class SingleCreator implements GameRoundCreator
{
    protected GameRoundOutput $gameRoundOutput;

    public function __construct(protected SingleSportVariant $sportVariant, LoggerInterface $logger)
    {
        $this->gameRoundOutput = new GameRoundOutput($logger);
    }

    public function createGameRound(
        Poule $poule,
        AssignedCounter $assignedCounter,
        int $totalNrOfGamesPerPlace
    ): SingleGameRound {
        $gameRound = new SingleGameRound();
        $assignedTogetherMap = $assignedCounter->getAssignedTogetherMap();
        for ($i = 1 ; $i <= $totalNrOfGamesPerPlace ; $i++) {
            $this->assignGameRound($poule->getPlaceList(), $assignedTogetherMap, $gameRound);
            $gameRound = $gameRound->createNext();
        }
        return $gameRound->getFirst();
    }


    /**
     * @param list<Place> $places
     * @param array<string, array<string, PlaceCounter>> &$assignedTogetherMap
     * @param SingleGameRound $gameRound
     */
    protected function assignGameRound(
        array $places,
        array &$assignedTogetherMap,
        SingleGameRound $gameRound
    ): void {
        $gamePlaces = [];
        $places = $this->sortPlaces($places, $assignedTogetherMap);
        while (count($places) > 0) {
            $bestPlace = $this->getBestPlace($gamePlaces, $places, $assignedTogetherMap);
            if ($bestPlace === null) {
                break;
            }
            $idx = array_search($bestPlace, $places, true);
            if ($idx !== false) {
                array_splice($places, $idx, 1);
            }
            array_push($gamePlaces, $bestPlace);
            if (count($gamePlaces) === $this->sportVariant->getNrOfGamePlaces()) {
                $placeCombination = new PlaceCombination($gamePlaces);
                $gameRound->add($placeCombination);
                $this->assignPlaceCombination($placeCombination, $assignedTogetherMap);
                $gamePlaces = [];
                // $places = $this->sortPlaces($places, $assignedTogetherMap);
            }
        }
        if (count($gamePlaces) > 0) {
            $placeCombination = new PlaceCombination($gamePlaces);
            $gameRound->add($placeCombination);
            $this->assignPlaceCombination($placeCombination, $assignedTogetherMap);
        }
    }

    /**
     * @param array<int|string, Place> $places
     * @param array<string,array<string,PlaceCounter>> &$assignedTogetherMap
     * @param list<Place>
     */
    protected function sortPlaces(array $places, array &$assignedTogetherMap): array
    {
        uasort($places, function (Place $placeA, Place $placeB) use ($places, $assignedTogetherMap) : int {
            $placesToCompareA = $this->getOtherPlaces($placeA, $places);
            $scoreA = $this->getScore($placeA, $placesToCompareA, $assignedTogetherMap);
            $placesToCompareB = $this->getOtherPlaces($placeB, $places);
            $scoreB = $this->getScore($placeB, $placesToCompareB, $assignedTogetherMap);
            return $scoreA - $scoreB;
        });
        return array_values($places);
    }

    /**
     * @param list<Place> $gamePlaces
     * @param list<Place> $choosablePlaces
     * @param array<string,array<string,PlaceCounter>> &$assignedTogetherMap
     * @return Place|null
     */
    protected function getBestPlace(array $gamePlaces, array $choosablePlaces, array &$assignedTogetherMap): Place|null
    {
        $bestPlace = null;
        $lowestScore = null;
        foreach ($choosablePlaces as $choosablePlace) {
            // $placesToCompare = $this->getPlacesToCompare($choosablePlace, $gamePlaces, $choosablePlaces);
            $score = $this->getScore($choosablePlace, $gamePlaces/*$placesToCompare*/, $assignedTogetherMap);
            if ($lowestScore === null || $score < $lowestScore) {
                $lowestScore = $score;
                $bestPlace = $choosablePlace;
            }
        }
        return $bestPlace;
    }

    /**
     * @param Place $place
     * @param list<Place> $gamePlaces
     * @param list<Place> $allPlaces
     * @return list<Place>
     */
    protected function getPlacesToCompare(Place $place, array $gamePlaces, array $allPlaces): array
    {
        if (count($gamePlaces) === 0) {
            return $this->getOtherPlaces($place, $allPlaces);
        }
        return $gamePlaces;
    }

    /**
     * @param Place $place
     * @param list<Place> $places
     * @return list<Place>
     */
    protected function getOtherPlaces(Place $place, array $places): array
    {
        $idx = array_search($place, $places, true);
        if ($idx === false) {
            return $places;
        }
        array_splice($places, $idx, 1);
        return array_values($places);
    }

    /**
     * @param Place $place
     * @param list<Place> $gamePlaces
     * @param array<string,array<string,PlaceCounter>> &$assignedTogetherMap
     * @return int
     */
    protected function getScore(Place $place, array $gamePlaces, array &$assignedTogetherMap): int
    {
        $score = 0;
        foreach ($gamePlaces as $gamePlace) {
            $score += $this->getPlaceCounter($place, $gamePlace, $assignedTogetherMap)->count();
        }
        return $score;
    }

    /**
     * @param Place $place
     * @param Place $coPlace
     * @param array<string,array<string,PlaceCounter>> &$assignedTogetherMap
     * @return PlaceCounter
     */
    protected function getPlaceCounter(Place $place, Place $coPlace, array &$assignedTogetherMap): PlaceCounter
    {
        return $assignedTogetherMap[$place->getLocation()][$coPlace->getLocation()];
    }

    /**
     * @param PlaceCombination $placeCombination
     * @param array<string,array<string,PlaceCounter>> $assignedTogetherMap
     * @return void
     */
    protected function assignPlaceCombination(PlaceCombination $placeCombination, array &$assignedTogetherMap): void
    {
        $places = $placeCombination->getPlaces();
        foreach ($places as $placeIt) {
            foreach ($places as $coPlace) {
                if ($coPlace === $placeIt) {
                    continue;
                }
                $this->getPlaceCounter($placeIt, $coPlace, $assignedTogetherMap)->increment();
            }
        }
    }
}
