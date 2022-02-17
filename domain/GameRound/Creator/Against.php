<?php

declare(strict_types=1);

namespace SportsPlanning\GameRound\Creator;

use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\VariantWithPoule;
use SportsPlanning\Combinations\AgainstHomeAway;
use SportsPlanning\Combinations\GamePlaceStrategy;
use SportsPlanning\Combinations\HomeAwayCreator\GamesPerPlace as GppHomeAwayCreator;
use SportsPlanning\Combinations\HomeAwayCreator\H2h as H2hHomeAwayCreator;
use SportsPlanning\Combinations\Output\GameRound as GameRoundOutput;
use SportsPlanning\Combinations\PlaceCombinationCounter;
use SportsPlanning\GameRound\Against as AgainstGameRound;
use SportsPlanning\GameRound\Creator\Against\GamesPerPlace as GppGameRoundCreator;
use SportsPlanning\GameRound\Creator\Against\H2h as H2hGameRoundCreator;
use SportsPlanning\Place;
use SportsPlanning\PlaceCounter;
use SportsPlanning\Poule;
use SportsPlanning\Schedule\Creator\AssignedCounter;

abstract class Against
{
    protected int $nrOfGamesPerGameRound = 0;
    protected int $totalNrOfGamesPerPlace = 0;
//    protected bool $mixedSport = false;
    protected int $maxNrOfGameRounds = 0;
    protected GameRoundOutput $gameRoundOutput;

    public function __construct(
        /*protected GamePlaceStrategy $gamePlaceStrategy,*/
        protected bool $hasAgainstMaybeUnequalSport,
        protected LoggerInterface $logger
    ) {
        $this->gameRoundOutput = new GameRoundOutput($logger);
    }

    /**
     * @param H2hHomeAwayCreator|GppHomeAwayCreator $homeAwayCreator
     * @return list<AgainstHomeAway>
     */
    protected function create(H2hHomeAwayCreator|GppHomeAwayCreator $homeAwayCreator): array
    {
        if ($homeAwayCreator instanceof H2hHomeAwayCreator) {
            return $homeAwayCreator->createForOneH2H();
        }
        return $homeAwayCreator->create();
    }

    /**
     * @param Poule $poule
     * @param H2hHomeAwayCreator|GppHomeAwayCreator $homeAwayCreator
     * @param list<AgainstHomeAway> $homeAwaysForGameRound
     * @param list<AgainstHomeAway> $homeAways
     * @param array<int, PlaceCounter> $assignedMap
     * @param array<int, PlaceCombinationCounter> $assignedWithMap
     * @param array<int, PlaceCounter> $assignedHomeMap
     * @param AgainstGameRound $gameRound
     * @param int $nrOfHomeAwaysTried
     * @return bool
     */
    protected function assignGameRound(
        Poule $poule,
        H2hHomeAwayCreator|GppHomeAwayCreator $homeAwayCreator,
        array $homeAwaysForGameRound,
        array $homeAways,
        array $assignedMap,
        array $assignedWithMap,
        array $assignedHomeMap,
        AgainstGameRound $gameRound,
        int $nrOfHomeAwaysTried = 0
    ): bool {
        if ($this->isCompleted($assignedMap)) {
            return true;
        }

        if ($this->isGameRoundCompleted($gameRound)) {
//            $this->logger->info("gameround " . $gameRound->getNumber() . " completed");

            $nextGameRound = $this->toNextGameRound($gameRound, $homeAways);
            if (count($homeAways) === 0) {
                $homeAways = $this->create($homeAwayCreator);
            }

//            if ($gameRound->getNumber() === 14) {
//                $this->gameRoundOutput->output($gameRound);
//                $this->outputUnassignedTotals($homeAways);
//                $this->outputUnassignedHomeAways($homeAways);
//                // $this->gameRoundOutput->outputHomeAways($homeAways, null, "unassigned");
//                $qw = 12;
//            }

            if ($this instanceof H2hGameRoundCreator && $this->isOverAssigned(
                    $poule,
                    $gameRound->getNumber(),
                    $homeAways
                )) {
                return false;
            }

            //if ($this->getDifferenceNrOfGameRounds($assignedMap) >= 5) {
            //                $this->gameRoundOutput->output($gameRound);
            //                $this->gameRoundOutput->outputHomeAways($homeAways, $gameRound, 'presort after gameround ' . $gameRound->getNumber() . ' completed');
            if ($this instanceof GppGameRoundCreator) {
                $sortedHomeAways = $this->sortHomeAways($homeAways, $assignedMap, $assignedWithMap, $assignedHomeMap);
            } else {
                $sortedHomeAways = $homeAways;
            }
//
//            if ($gameRound->getNumber() === 14) {
//                $this->gameRoundOutput->outputHomeAways($sortedHomeAways, $gameRound, 'postsort after gameround ' . $gameRound->getNumber() . ' completed');
//            }

//            $this->gameRoundOutput->outputHomeAways($homeAways, null, 'postsort after gameround ' . $gameRound->getNumber() . ' completed');
            // $gamesList = array_values($gamesForBatchTmp);
//            shuffle($homeAways);
            return $this->assignGameRound(
                $poule,
                $homeAwayCreator,
                $sortedHomeAways,
                $homeAways,
                $assignedMap,
                $assignedWithMap,
                $assignedHomeMap,
                $nextGameRound
            );
        }

        if ($nrOfHomeAwaysTried === count($homeAwaysForGameRound)) {
            return false;
        }
        $homeAway = array_shift($homeAwaysForGameRound);
        if ($homeAway === null) {
            return false;
        }

        if ($this->isHomeAwayAssignable($gameRound, $homeAway, $assignedMap)) {
            $assignedMapTry = $this->copyCounters($assignedMap);
            $assignedWithMapTry = $this->copyWithCounters($assignedWithMap);
            $assignedHomeMapTry = $this->copyCounters($assignedHomeMap);
            $this->assignHomeAway($gameRound, $homeAway, $assignedMapTry, $assignedWithMapTry, $assignedHomeMapTry);
//            if ($gameRound->getNumber() === 15 ) {
//                $this->gameRoundOutput->outputHomeAways($gameRound->getHomeAways(), null, 'homeawys of gameround 15');
//                $this->gameRoundOutput->outputHomeAways($homeAwaysForGameRound, null,'choosable homeawys of gameround 15');
//                // $this->gameRoundOutput->outputHomeAways($homeAways, null, "unassigned");
//                $qw = 12;
//            }
            $homeAwaysForGameRoundTmp = array_values(
                array_filter(
                    $homeAwaysForGameRound,
                    function (AgainstHomeAway $homeAway) use ($gameRound): bool {
                        return $gameRound->isHomeAwayPlaceParticipating($homeAway);
                    }
                )
            );
            if ($this->assignGameRound(
                $poule,
                $homeAwayCreator,
                $homeAwaysForGameRoundTmp,
                $homeAways,
                $assignedMapTry,
                $assignedWithMapTry,
                $assignedHomeMapTry,
                $gameRound
            )) {
                return true;
            }
            $this->releaseHomeAway($gameRound, $homeAway);
        }
        $homeAwaysForGameRound[] = $homeAway;
        ++$nrOfHomeAwaysTried;
        return $this->assignGameRound(
            $poule,
            $homeAwayCreator,
            $homeAwaysForGameRound,
            $homeAways,
            $assignedMap,
            $assignedWithMap,
            $assignedHomeMap,
            $gameRound,
            $nrOfHomeAwaysTried
        );
    }

    /**
     * @param AgainstGameRound $gameRound
     * @param list<AgainstHomeAway> $homeAways
     * @return AgainstGameRound
     */
    protected function toNextGameRound(AgainstGameRound $gameRound, array &$homeAways): AgainstGameRound
    {
        foreach ($gameRound->getHomeAways() as $homeAway) {
            $foundHomeAwayIndex = array_search($homeAway, $homeAways, true);
            if ($foundHomeAwayIndex !== false) {
                array_splice($homeAways, $foundHomeAwayIndex, 1);
            }
        }
        return $gameRound->createNext();
    }

    /**
     * @param array<int, PlaceCounter> $placeCounters
     * @return array<int, PlaceCounter>
     */
    protected function copyCounters(array $placeCounters): array
    {
        return array_map(fn(PlaceCounter $placeCounter) => clone $placeCounter, $placeCounters);
    }

    /**
     * @param array<int, PlaceCombinationCounter> $placeCombinationCounters
     * @return array<int, PlaceCombinationCounter>
     */
    protected function copyWithCounters(array $placeCombinationCounters): array
    {
        return array_map(
            fn(PlaceCombinationCounter $placeCombinationCounter) => clone $placeCombinationCounter,
            $placeCombinationCounters
        );
    }

    protected function isGameRoundCompleted(AgainstGameRound $gameRound): bool
    {
        return count($gameRound->getHomeAways()) === $this->nrOfGamesPerGameRound;
    }

    abstract protected function getSportVariant(): AgainstH2h|AgainstGpp;

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
            /*if ($this->gamePlaceStrategy === GamePlaceStrategy::RandomlyAssigned
                && $nrOfIncompletePlaces >= $this->getSportVariant()->getNrOfGamePlaces()) {
                return false;
            }
            if ($this->gamePlaceStrategy === GamePlaceStrategy::EquallyAssigned
                && $nrOfIncompletePlaces > 0) {
                return false;
            }*/
            if ($nrOfIncompletePlaces >= $this->getSportVariant()->getNrOfGamePlaces()) {
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
        $totalNrOfGamesPerPlace = $this->totalNrOfGamesPerPlace + ($this->hasAgainstMaybeUnequalSport ? 1 : 0);
//        if ($assignedMap[$place->getNumber()]->count() > $totalNrOfGamesPerPlace === true) {
//            $rofirjf = 123;
//        }
        return $assignedMap[$place->getNumber()]->count() > $totalNrOfGamesPerPlace;
    }

    /**
     * @param Poule $poule
     * @param int $currentGameRoundNumber
     * @param list<AgainstHomeAway> $homeAways
     * @return bool
     */
    protected function isOverAssigned(Poule $poule, int $currentGameRoundNumber, array $homeAways): bool
    {
        if ($this instanceof H2hGameRoundCreator) {
            return false;
        }
//        $sportVariant = $this->getSportVariant();
//        if ($sportVariant instanceof AgainstGpp
//                && !$sportVariant->allPlacesPlaySameNrOfGames(count($poule->getPlaces()))
//            ) {
//            return false;
//        }
        $unassignedMap = [];
        foreach ($poule->getPlaces() as $place) {
            $unassignedMap[$place->getNumber()] = new PlaceCounter($place);
        }
        foreach ($homeAways as $homeAway) {
            foreach ($homeAway->getPlaces() as $place) {
                $unassignedMap[$place->getNumber()]->increment();
            }
        }

        foreach ($poule->getPlaces() as $place) {
            if ($currentGameRoundNumber + $unassignedMap[$place->getNumber()]->count() > $this->maxNrOfGameRounds) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param list<AgainstHomeAway> $homeAways
     */
    protected function outputUnassignedHomeAways(array $homeAways): void
    {
        $this->logger->info('unassigned');
        foreach ($homeAways as $homeAway) {
            $this->logger->info($homeAway);
        }
    }

    /**
     * @param list<AgainstHomeAway> $homeAways
     */
    protected function outputUnassignedTotals(array $homeAways): void
    {
        $map = [];
        foreach ($homeAways as $homeAway) {
            foreach ($homeAway->getPlaces() as $place) {
                if (!isset($map[$place->getLocation()])) {
                    $map[$place->getLocation()] = new PlaceCounter($place);
                }
                $map[$place->getLocation()]->increment();
            }
        }
        foreach ($map as $location => $placeCounter) {
            $this->logger->info($location . ' => ' . $placeCounter->count());
        }
    }
}
