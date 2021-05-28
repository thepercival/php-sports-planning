<?php
declare(strict_types=1);

namespace SportsPlanning\GameRound;

use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsPlanning\Combinations\HomeAwayCreator;
use SportsPlanning\GameRound\AgainstGameRound;
use SportsPlanning\Combinations\AgainstHomeAway;
use SportsPlanning\Combinations\Output\GameRound as GameRoundOutput;
use SportsPlanning\Combinations\PlaceCombinationCounter;
use SportsPlanning\GameGenerator\AssignedCounter;
use SportsPlanning\GameRound\GameRoundCreator;
use SportsPlanning\Place;
use SportsPlanning\PlaceCounter;
use SportsPlanning\Poule;

/**
 * @template-implements GameRoundCreator<AgainstGameRound>
 */
class AgainstCreator implements GameRoundCreator
{
    protected int $nrOfGamesPerGameRound = 0;
    protected int $totalNrOfGamesPerPlace = 0;
    protected GameRoundOutput $gameRoundOutput;

    public function __construct(protected AgainstSportVariant $sportVariant, LoggerInterface $logger)
    {
        $this->gameRoundOutput = new GameRoundOutput($logger);
    }

    public function createGameRound(
        Poule $poule,
        AssignedCounter $assignedCounter,
        int $totalNrOfGamesPerPlace
    ): AgainstGameRound {
        $this->nrOfGamesPerGameRound = $this->sportVariant->getNrOfGamesOneGameRound($poule->getPlaces()->count());
        $this->totalNrOfGamesPerPlace = $totalNrOfGamesPerPlace;

        $gameRound = new AgainstGameRound();
        $assignedMap = $assignedCounter->getAssignedMap();
        $assignedWithMap = $assignedCounter->getAssignedWithMap();
        $assignedHomeMap = $assignedCounter->getAssignedHomeMap();
        $homeAwayCreator = new HomeAwayCreator($poule, $this->sportVariant);

        if ($this->assignGameRound($homeAwayCreator, [], $assignedMap, $assignedWithMap, $assignedHomeMap, $gameRound) === false) {
            throw new \Exception('creation of homeaway can not be false', E_ERROR);
        }
        return $gameRound;
    }

    /**
     * @param HomeAwayCreator $homeAwayCreator
     * @param list<AgainstHomeAway> $homeAways
     * @param array<int, PlaceCounter> $assignedMap
     * @param array<int, PlaceCombinationCounter> $assignedWithMap
     * @param array<int, PlaceCounter> $assignedHomeMap
     * @param AgainstGameRound $gameRound
     * @param int $nrOfHomeAwaysTried
     * @return bool
     */
    protected function assignGameRound(
        HomeAwayCreator $homeAwayCreator,
        array $homeAways,
        array $assignedMap,
        array $assignedWithMap,
        array $assignedHomeMap,
        AgainstGameRound $gameRound,
        int $nrOfHomeAwaysTried = 0
    ): bool {
//        if ($this->isCompleted($assignedMap)) {
//            $er = 12;
//        }
        if ($this->isCompleted($assignedMap)) {
            return true;
        }
        if (count($homeAways) === 0) {
            $homeAways = $homeAwayCreator->createForOneH2H();
        }
        if ($this->gameRoundCompleted($gameRound)) {
//            if( $gameRound->getNumber() === 10 ) {
//                $this->gameRoundOutput->output($gameRound);
//                $qw = 12;
//            }

            $nextGameRound = $gameRound->createNext();

//            $this->gameRoundOutput->output($gameRound);
//            $this->gameRoundOutput->outputHomeAways($homeAways, null, 'presort after gameround ' . $gameRound->getNumber() . ' completed');
            $homeAways = $this->sortHomeAways($homeAways, $assignedMap, $assignedWithMap, $assignedHomeMap);
//            $this->gameRoundOutput->outputHomeAways($homeAways, null, 'postsort after gameround ' . $gameRound->getNumber() . ' completed');
            // $gamesList = array_values($gamesForBatchTmp);
            return $this->assignGameRound($homeAwayCreator, $homeAways, $assignedMap, $assignedWithMap, $assignedHomeMap, $nextGameRound);
        }

        if ($nrOfHomeAwaysTried === count($homeAways)) {
            return false;
        }
        $homeAway = array_shift($homeAways);
        if ($homeAway === null) {
            return false;
        }

        if ($this->isHomeAwayAssignable($gameRound, $homeAway, $assignedMap)) {
            $assignedMapTry = $this->copyCounters($assignedMap);
            $assignedWithMapTry = $this->copyWithCounters($assignedWithMap);
            $assignedHomeMapTry = $this->copyCounters($assignedHomeMap);
            $this->assignHomeAway($gameRound, $homeAway, $assignedMapTry, $assignedWithMapTry, $assignedHomeMapTry);
//            $homeAwaysForBatchTmp = array_values(array_filter(
//                                                 $gamesForBatch,
//                                                 function (TogetherGame|AgainstGame $game) use ($batch): bool {
//                                                     return $this->areAllPlacesAssignable($batch, $game);
//                                                 }
//                                             ));
            if ($this->assignGameRound($homeAwayCreator, $homeAways, $assignedMapTry, $assignedWithMapTry, $assignedHomeMapTry, $gameRound, $nrOfHomeAwaysTried)) {
                return true;
            }
            $this->releaseHomeAway($gameRound, $homeAway);
        }
        $homeAways[] = $homeAway;
        ++$nrOfHomeAwaysTried;
        return $this->assignGameRound(
            $homeAwayCreator,
            $homeAways,
            $assignedMap,
            $assignedWithMap,
            $assignedHomeMap,
            $gameRound,
            $nrOfHomeAwaysTried
        );
    }

    /**
     * @param array<int, PlaceCounter> $placeCounters
     * @return array<int, PlaceCounter>
     */
    protected function copyCounters(array $placeCounters): array
    {
        return array_map(fn (PlaceCounter $placeCounter) => clone $placeCounter, $placeCounters);
    }

    /**
     * @param array<int, PlaceCombinationCounter> $placeCombinationCounters
     * @return array<int, PlaceCombinationCounter>
     */
    protected function copyWithCounters(array $placeCombinationCounters): array
    {
        return array_map(fn (PlaceCombinationCounter $placeCombinationCounter) => clone $placeCombinationCounter, $placeCombinationCounters);
    }

    protected function gameRoundCompleted(AgainstGameRound $gameRound): bool
    {
        return count($gameRound->getHomeAways()) === $this->nrOfGamesPerGameRound;
    }

    /**
     * @param array<int, PlaceCounter> $assignedMap
     * @return bool
     */
    protected function isCompleted(array $assignedMap): bool
    {
        foreach ($assignedMap as $assignedCounter) {
            if ($assignedCounter->count() < $this->totalNrOfGamesPerPlace) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param AgainstGameRound $gameRound
     * @param AgainstHomeAway $homeAway
     * @param array<int, PlaceCounter> $assignedMap
     * @param array<int, PlaceCombinationCounter> $assignedWithMap
     * @param array<int, PlaceCounter> $assignedHomeMap
     */
    protected function assignHomeAway(
        AgainstGameRound $gameRound,
        AgainstHomeAway $homeAway,
        array $assignedMap,
        array $assignedWithMap,
        array $assignedHomeMap
    ): void {
        foreach ($homeAway->getPlaces() as $place) {
            $assignedMap[$place->getNumber()]->increment();
        }
        $assignedWithMap[$homeAway->getHome()->getNumber()]->increment();
        $assignedWithMap[$homeAway->getAway()->getNumber()]->increment();
        foreach ($homeAway->getHome()->getPlaces() as $homePlace) {
            $assignedHomeMap[$homePlace->getNumber()]->increment();
        }
        $gameRound->add($homeAway);
    }

    /**
     * @param list<AgainstHomeAway> $homeAways
     * @param array<int, PlaceCounter> $assignedMap
     * @param array<int, PlaceCombinationCounter> $assignedWithMap
     * @param array<int, PlaceCounter> $assignedHomeMap
     * @return list<AgainstHomeAway>
     */
    protected function sortHomeAways(array $homeAways, array $assignedMap, array $assignedWithMap, array $assignedHomeMap): array
    {
        uasort($homeAways, function (
            AgainstHomeAway $homeAwayA,
            AgainstHomeAway $homeAwayB
        ) use ($assignedMap, $assignedWithMap, $assignedHomeMap): int {
            list($amountA, $nrOfPlacesA) = $this->getLeastAmountAssigned($homeAwayA, $assignedMap);
            list($amountB, $nrOfPlacesB) = $this->getLeastAmountAssigned($homeAwayB, $assignedMap);
            if ($amountA === $amountB) {
                if ($nrOfPlacesA === $nrOfPlacesB) {
                    $amountWithA = $this->getWithAmountAssigned($homeAwayA, $assignedWithMap);
                    $amountWithB = $this->getWithAmountAssigned($homeAwayB, $assignedWithMap);
                    if ($amountWithA === $amountWithB) {
                        list($amountHomeA, $nrOfPlacesHomeA) = $this->getLeastAmountAssigned($homeAwayA, $assignedHomeMap);
                        list($amountHomeB, $nrOfPlacesHomeB) = $this->getLeastAmountAssigned($homeAwayB, $assignedHomeMap);
                        if ($amountHomeA === $amountHomeB) {
                            return $nrOfPlacesHomeA - $nrOfPlacesHomeB;
                        }
                        return $amountHomeA - $amountHomeB;
                    }
                    return $amountWithA - $amountWithB;
                }
                return $nrOfPlacesB - $nrOfPlacesA;
            }
            return $amountA - $amountB;
        });
        return array_values($homeAways);
    }

    /**
     * @param AgainstHomeAway $homeAway
     * @param array<int, PlaceCounter> $assignedMap
     * @return list<int>
     */
    protected function getLeastAmountAssigned(AgainstHomeAway $homeAway, array $assignedMap): array
    {
        $leastAmount = -1;
        $nrOfPlaces = 0;
        foreach ($homeAway->getPlaces() as $place) {
            $amountAssigned = $assignedMap[$place->getNumber()]->count();
            if ($leastAmount === -1 || $amountAssigned < $leastAmount) {
                $leastAmount = $amountAssigned;
                $nrOfPlaces = 0;
            }
            if ($amountAssigned === $leastAmount) {
                $nrOfPlaces++;
            }
        }
        return [$leastAmount, $nrOfPlaces];
    }

    /**
     * @param AgainstHomeAway $homeAway
     * @param array<int, PlaceCombinationCounter> $assignedWithMap
     * @return int
     */
    protected function getWithAmountAssigned(AgainstHomeAway $homeAway, array $assignedWithMap): int
    {
        $homeWithAmountAssigned = $assignedWithMap[$homeAway->getHome()->getNumber()]->count();
        $awayWithAmountAssigned = $assignedWithMap[$homeAway->getAway()->getNumber()]->count();
        return $homeWithAmountAssigned + $awayWithAmountAssigned;
    }

    protected function releaseHomeAway(AgainstGameRound $gameRound, AgainstHomeAway $homeAway): void
    {
        $gameRound->remove($homeAway);
    }

    /**
     * @param AgainstGameRound $gameRound
     * @param AgainstHomeAway $homeAway
     * @param array<int, PlaceCounter> $assignedMap
     * @return bool
     */
    private function isHomeAwayAssignable(AgainstGameRound $gameRound, AgainstHomeAway $homeAway, array $assignedMap): bool
    {
        foreach ($homeAway->getPlaces() as $place) {
            if ($gameRound->isParticipating($place) || $this->willBeOverAssigned($place, $assignedMap)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param Place $place
     * @param array<int, PlaceCounter> $assignedMap
     * @return bool
     */
    private function willBeOverAssigned(Place $place, array $assignedMap): bool
    {
        return $assignedMap[$place->getNumber()]->count() > ($this->totalNrOfGamesPerPlace + 1);
    }
}
