<?php

declare(strict_types=1);

namespace SportsPlanning\GameRound\Creator;

use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsHelpers\SportRange;
use SportsPlanning\Combinations\GamePlaceStrategy;
use SportsPlanning\Combinations\HomeAwayCreator;
use SportsPlanning\Combinations\AgainstHomeAway;
use SportsPlanning\Combinations\Output\GameRound as GameRoundOutput;
use SportsPlanning\Combinations\PlaceCombinationCounter;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\GameRound\CreatorInterface;
use SportsPlanning\Schedule\Creator\AssignedCounter;
use SportsPlanning\GameRound\Against as AgainstGameRound;
use SportsPlanning\Place;
use SportsPlanning\PlaceCounter;
use SportsPlanning\Poule;

/**
 * @template-implements CreatorInterface<AgainstGameRound>
 */
class Against implements CreatorInterface
{
    protected int $nrOfGamesPerGameRound = 0;
    protected int $totalNrOfGamesPerPlace = 0;
    protected GameRoundOutput $gameRoundOutput;

    public function __construct(
        protected AgainstSportVariant $sportVariant,
        protected int $gamePlaceStrategy,
        LoggerInterface $logger
    ) {
        $this->gameRoundOutput = new GameRoundOutput($logger);
    }

    public function createGameRound(
        Poule $poule,
        AssignedCounter $assignedCounter,
        int $totalNrOfGamesPerPlace,
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

//            if( $this->sportVariant->getNrOfHomePlaces() >= 2 && $this->sportVariant->getNrOfAwayPlaces() >= 2
//            && count($homeAways) > 200 ) {
//                $homeAways = array_splice($homeAways, 0, 200 );
//            }
        }
        if ($this->isGameRoundCompleted($gameRound)) {
//            $this->logger->info("gameround " . $gameRound->getNumber() . " completed");
//            if ($gameRound->getNumber() === 36) {
//                $this->gameRoundOutput->output($gameRound);
            ////                $qw = 12;
//            }

            $nextGameRound = $gameRound->createNext();

            //if ($this->getDifferenceNrOfGameRounds($assignedMap) >= 5) {
            //                $this->gameRoundOutput->output($gameRound);
            //                $this->gameRoundOutput->outputHomeAways($homeAways, $gameRound, 'presort after gameround ' . $gameRound->getNumber() . ' completed');
            $homeAways = $this->sortHomeAways($homeAways, $assignedMap, $assignedWithMap, $assignedHomeMap);
            //                $this->gameRoundOutput->outputHomeAways($homeAways, $gameRound, 'postsort after gameround ' . $gameRound->getNumber() . ' completed');
            //}

//            $this->gameRoundOutput->outputHomeAways($homeAways, null, 'postsort after gameround ' . $gameRound->getNumber() . ' completed');
            // $gamesList = array_values($gamesForBatchTmp);
//            shuffle($homeAways);
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

    protected function isGameRoundCompleted(AgainstGameRound $gameRound): bool
    {
        return count($gameRound->getHomeAways()) === $this->nrOfGamesPerGameRound;
    }

    /**
     * @param array<int, PlaceCounter> $assignedMap
     * @return bool
     */
    protected function isCompleted(array $assignedMap): bool
    {
        $nrOfIncompletePlaces = 0;
        foreach ($assignedMap as $assignedCounter) {
            if ($assignedCounter->count() < $this->totalNrOfGamesPerPlace) {
                $nrOfIncompletePlaces++;
            }
            if ($this->gamePlaceStrategy === GamePlaceStrategy::RandomlyAssigned
                && $nrOfIncompletePlaces >= $this->sportVariant->getNrOfGamePlaces()) {
                return false;
            }
            if ($this->gamePlaceStrategy === GamePlaceStrategy::EquallyAssigned
                && $nrOfIncompletePlaces > 0) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param array<int, PlaceCounter> $assignedMap
     * @return int
     */
    protected function getDifferenceNrOfGameRounds(array $assignedMap): int
    {
        $min = null;
        $max = null;
        foreach ($assignedMap as $assignedCounter) {
            $count = $assignedCounter->count();
            if ($min === null && $max === null) {
                $min = $count;
                $max = $count;
            }
            if ($count < $min) {
                $min = $count;
            }
            if ($count > $max) {
                $max = $count;
            }
        }
        return (int)$max - (int)$min;
    }

    /**
     * @param array<int, PlaceCounter> $assignedMap
     * @return int
     */
    protected function getMaxNrOfGameRounds(array $assignedMap): int
    {
        $max = null;
        foreach ($assignedMap as $assignedCounter) {
            $count = $assignedCounter->count();
            if ($max === null || $count > $max) {
                $max = $count;
            }
        }
        return (int)$max;
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
        array &$assignedMap,
        array &$assignedWithMap,
        array &$assignedHomeMap
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
