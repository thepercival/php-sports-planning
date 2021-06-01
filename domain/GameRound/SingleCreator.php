<?php
declare(strict_types=1);

namespace SportsPlanning\GameRound;

use Exception;
use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;
use SportsPlanning\Combinations\GamePlaceStrategy;
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
    ): SingleGameRound {
        $gameRound = new SingleGameRound();
        $this->assignedTogetherMap = $assignedCounter->getAssignedTogetherMap();
        $places = $poule->getPlaces()->toArray();
        for ($i = 1 ; $i <= $totalNrOfGamesPerPlace ; $i++) {
            if ($poule->getInput()->getGamePlaceStrategy() === GamePlaceStrategy::RandomlyAssigned) {
                shuffle($places);
            }
            $this->assignGameRound(array_values($places), $gameRound);
            $gameRound = $gameRound->createNext();
        }
        return $gameRound->getFirst();
    }


    /**
     * @param list<Place> $unSortedPlaces
     * @param SingleGameRound $gameRound
     */
    protected function assignGameRound(array $unSortedPlaces, SingleGameRound $gameRound): void
    {
        $gamePlaces = [];
        $places = $this->sortPlaces($unSortedPlaces);
        while (count($places) > 0) {
            $bestPlace = $this->getBestPlace($gamePlaces, $places);
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
                $this->assignPlaceCombination($placeCombination);
                $gamePlaces = [];
            }
        }
        if (count($gamePlaces) > 0) {
            $placeCombination = new PlaceCombination($gamePlaces);
            $gameRound->add($placeCombination);
            $this->assignPlaceCombination($placeCombination);
        }
    }

    /**
     * @param list<Place> $places
     * @return list<Place>
     */
    protected function sortPlaces(array $places): array
    {
        uasort($places, function (Place $placeA, Place $placeB) use ($places) : int {
            $placesToCompareA = $this->getOtherPlaces($placeA, $places);
            $scoreA = $this->getScore($placeA, $placesToCompareA);
            $placesToCompareB = $this->getOtherPlaces($placeB, $places);
            $scoreB = $this->getScore($placeB, $placesToCompareB);
            return $scoreA - $scoreB;
        });
        return array_values($places);
    }

    /**
     * @param list<Place> $gamePlaces
     * @param list<Place> $choosablePlaces
     * @return Place|null
     */
    protected function getBestPlace(array $gamePlaces, array $choosablePlaces): Place|null
    {
        $bestPlace = null;
        $lowestScore = null;
        foreach ($choosablePlaces as $choosablePlace) {
            $score = $this->getScore($choosablePlace, $gamePlaces);
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
     * @return int
     */
    protected function getScore(Place $place, array $gamePlaces): int
    {
        $score = 0;
        foreach ($gamePlaces as $gamePlace) {
            $score += $this->getPlaceCounter($place, $gamePlace)->count();
        }
        return $score;
    }

    /**
     * @param Place $place
     * @param Place $coPlace
     * @return PlaceCounter
     */
    protected function getPlaceCounter(Place $place, Place $coPlace): PlaceCounter
    {
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
                $this->getPlaceCounter($placeIt, $coPlace)->increment();
            }
        }
    }
}
