<?php

declare(strict_types=1);

namespace SportsPlanning\GameRound\Creator\Against;

use Psr\Log\LoggerInterface;
use SportsHelpers\Dev\ByteFormatter;
use SportsHelpers\Output;
use SportsHelpers\Sport\Variant\Against;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsPlanning\Combinations\MultipleCombinationsCounter\Against as AgainstCounter;
use SportsPlanning\GameRound\Creator\Against\H2h as H2hGameRoundCreator;
use SportsPlanning\GameRound\Creator\StatisticsCalculator;
use SportsPlanning\Schedule\TimeoutState;
use SportsPlanning\SportVariant\WithPoule as VariantWithPoule;
use SportsPlanning\Combinations\AgainstHomeAway;
use SportsPlanning\Combinations\HomeAwayCreator\GamesPerPlace as GppHomeAwayCreator;
use SportsPlanning\Combinations\Output\GameRound as GameRoundOutput;
use SportsPlanning\Combinations\Indirect\Map as IndirectMap;
use SportsPlanning\Combinations\PlaceCombinationCounter;
use SportsPlanning\GameRound\Against as AgainstGameRound;
use SportsPlanning\GameRound\Creator\Against as AgainstCreator;
use SportsPlanning\PlaceCounter;
use SportsPlanning\Poule;
use SportsPlanning\Schedule\Creator\AssignedCounter;

class GamesPerPlace extends AgainstCreator
{
    public function __construct(LoggerInterface $logger, protected TimeoutState|null $timeoutState)
    {
        parent::__construct($logger);
    }

    public function createGameRound(
        Poule $poule,
        AgainstGpp $sportVariant,
        GppHomeAwayCreator $homeAwayCreator,
        AssignedCounter $assignedCounter,
    ): AgainstGameRound {
        $variantWithPoule = new VariantWithPoule($sportVariant, $poule);
//        $assignedCounterEmpty = new AssignedCounter($poule, [$sportVariant]);
        $gameRound = new AgainstGameRound();
        $assignedMap = $assignedCounter->getAssignedMap();
        $assignedWithMap = $assignedCounter->getAssignedWithMap();
        $assignedAgainstMap = $assignedCounter->getAssignedAgainstMap();
        $assignedHomeMap = $assignedCounter->getAssignedHomeMap();

        $homeAways = $this->createHomeAways($homeAwayCreator, $sportVariant);

        $statisticsCalculator = new StatisticsCalculator(
            $variantWithPoule,
            $this->getAssignedSportCounters($poule),
            $assignedMap,
            $assignedWithMap,
            $assignedAgainstMap,
            $assignedHomeMap,
        );

        //$time_start = microtime(true);
//        $sortedHomeAways = $this->sortHomeAways(
//            $homeAways,
//            $assignedMap,
//            $assignedWithMap,
//            $assignedAgainstMap,
//            $assignedHomeMap
//        );
        //die("T:" . microtime(true) - $time_start);

//        $this->logger->info('XXX');
        // $this->outputUnassignedHomeAways($homeAways);
        if ($this->assignGameRound(
                $variantWithPoule,
                $homeAwayCreator,
                $homeAways,
                $homeAways,
                $statisticsCalculator,
                $gameRound
            ) === false) {
            throw new \Exception('creation of homeaway can not be false', E_ERROR);
        }
        return $gameRound;
    }

    /**
     * @param VariantWithPoule $variantWithPoule
     * @param GppHomeAwayCreator $homeAwayCreator
     * @param list<AgainstHomeAway>|null $homeAwaysForGameRound
     * @param list<AgainstHomeAway> $homeAways
     * @param StatisticsCalculator $statisticsCalculator,
     * @param AgainstGameRound $gameRound
     * @param int $nrOfHomeAwaysTried
     * @param int $depth
     * @return bool
     */
    protected function assignGameRound(
        VariantWithPoule $variantWithPoule,
        GppHomeAwayCreator $homeAwayCreator,
        array|null $homeAwaysForGameRound,
        array $homeAwaysX,
        StatisticsCalculator $statisticsCalculator,
        AgainstGameRound $gameRound,
        int $nrOfHomeAwaysTried = 0,
        int $depth = 0
    ): bool {
        $mem_usage = memory_get_usage();
        $out = 'GR'.$gameRound->getNumber().' Now usage: ' . round($mem_usage / (1024*1024)) . 'MB of memory.';
        $homeAways = $homeAwaysX;
        $mem_usage = memory_get_usage();
        $out = 'GR'.$gameRound->getNumber().' Now usage: ' . round($mem_usage / (1024*1024)) . 'MB of memory.';
        if ($statisticsCalculator->allAssigned()) {

//            $this->gameRoundOutput->output($gameRound, 'ASSIGNED HOMEAWAYS');
//            $this->outputHomeAwayWithTotals($assignedWithMap);
//            $this->outputPlacesAgainstTotals($variantWithPoule->getPoule(), $assignedAgainstMap);
//            $this->outputUnassignedHomeAwayTotals($homeAways);
            return true;
        }

        if ($this->isGameRoundCompleted($variantWithPoule, $gameRound)) {
//            $this->logger->info("gameround " . $gameRound->getNumber() . " completed");

            $nextGameRound = $this->toNextGameRound($gameRound, $homeAways);
            if (count($homeAways) === 0) {
                $sportVariant = $variantWithPoule->getSportVariant();
                if (!($sportVariant instanceof AgainstGpp)) {
                    throw new \Exception('wrong sportvariant', E_ERROR);
                }
                $homeAways = $this->createHomeAways($homeAwayCreator, $sportVariant);
            }

            if ($gameRound->getNumber() === 6) {

//                $x = $this->getMemoryUsageInMB($gameRound);
//                $this->logger->info('gameRound in MB: ' . $x);

                /* Currently used memory */
                $mem_usage = memory_get_usage();
                $out = 'GR'.$gameRound->getNumber().' Now usage: ' . round($mem_usage / (1024*1024)) . 'MB of memory.';$this->logger->info('GR'.$gameRound->getNumber().' Now usage: ' . round($mem_usage / (1024*1024)) . 'MB of memory.');
                $this->logger->info($out);

//                $this->gameRoundOutput->output($gameRound, 'ASSIGNED HOMEAWAYS');
//                $statisticsCalculator->output($this->logger, true, true);
//                // $this->outputUnassignedTotals($homeAways);
//                die();
            }

            if ($gameRound->getNumber() > 1) { // 7 = 567MB

                $mem_usage = memory_get_usage();
                $out = 'GR'.$gameRound->getNumber().' Now usage: ' . round($mem_usage / (1024*1024)) . 'MB of memory.';
                $out = 'GR'.$gameRound->getNumber().' depth: ' . $depth;
                $this->logger->info($out);
            }

//            $sortedHomeAways = $this->sortHomeAways(
//                $homeAways,
//                $assignedMap,
//                $assignedWithMap,
//                $assignedAgainstMap,
//                $assignedHomeMap
//            );


//            shuffle($homeAways);
            $mem_usage = memory_get_usage();
            $out = 'GR'.$gameRound->getNumber().' Now usage: ' . round($mem_usage / (1024*1024)) . 'MB of memory.';
            return $this->assignGameRound(
                $variantWithPoule,
                $homeAwayCreator,
                null,
                $homeAways,
                $statisticsCalculator,
                $nextGameRound,
                0,
                $depth + 1
            );
        }
        if( $homeAwaysForGameRound === null ) {
            $homeAwaysForGameRound = $homeAways;
        }

        if ($nrOfHomeAwaysTried === count($homeAwaysForGameRound)) {
            return false;
        }
        $homeAway = array_shift($homeAwaysForGameRound);
        if ($homeAway === null) {
            return false;
        }

        if ($this->isHomeAwayAssignable($gameRound, $homeAway, $statisticsCalculator)) {

//            $assignedMapTry = $this->copyCounters($assignedMap);
//            $assignedWithMapTry = $this->copyWithCounters($assignedWithMap);
//            $assignedHomeMapTry = $this->copyCounters($assignedHomeMap);
//            $assignedSportMapTry = $this->copyCounters($assignedSportMap);
            $gameRound->add($homeAway);
            $statisticsCalculatorTry = $statisticsCalculator->addHomeAway($homeAway);
//            $assignedAgainstMapTry =
//                $this->assignHomeAway(
//                $gameRound,
//                $homeAway,
//                $assignedSportMapTry,
//                $assignedMapTry,
//                $assignedWithMapTry,
//                $assignedAgainstMap,
//                $assignedHomeMapTry
//            );
//            if ($gameRound->getNumber() > 11 ) {
//                $this->gameRoundOutput->output($gameRound, 'homeawys of gameround');
////                $this->gameRoundOutput->outputHomeAways($gameRound->getHomeAways(), null, 'homeawys of gameround');
////                $this->gameRoundOutput->outputHomeAways($homeAwaysForGameRound, null,'choosable homeawys of gameround');
//                // $this->gameRoundOutput->outputHomeAways($homeAways, null, "unassigned");
//                $qw = 12;
//            }
            $c = (memory_get_usage() / (1024*1024)) . 'MB';
            $homeAwaysForGameRoundTmp = array_values(
                array_filter(
                    $homeAwaysForGameRound,
                    function (AgainstHomeAway $homeAway) use ($gameRound): bool {
                        return !$gameRound->isHomeAwayPlaceParticipating($homeAway);
                    }
                )
            );
            $d = (memory_get_usage() / (1024*1024)) . 'MB';
            if ($this->assignGameRound(
                $variantWithPoule,
                $homeAwayCreator,
                $homeAwaysForGameRoundTmp,
                $homeAways,
                $statisticsCalculatorTry,
                $gameRound,
                0,
                $depth + 1
            )) {
                return true;
            }
            unset($homeAwaysForGameRoundTmp);
            $this->releaseHomeAway($gameRound, $homeAway);
        }
        $c = (memory_get_usage() / (1024*1024)) . 'MB';
        $homeAwaysForGameRound[] = $homeAway;
        ++$nrOfHomeAwaysTried;
        return $this->assignGameRound(
            $variantWithPoule,
            $homeAwayCreator,
            $homeAwaysForGameRound,
            $homeAways,
            $statisticsCalculator,
            $gameRound,
            $nrOfHomeAwaysTried,
            $depth + 1
        );
    }

    /**
     * @param GppHomeAwayCreator $homeAwayCreator
     * @param AgainstGpp $sportVariant
     * @return list<AgainstHomeAway>
     */
    protected function createHomeAways(GppHomeAwayCreator $homeAwayCreator, AgainstGpp $sportVariant): array
    {
        return $homeAwayCreator->create($sportVariant);
    }

    protected function isHomeAwayAssignable(
        AgainstGameRound $gameRound,
        AgainstHomeAway $homeAway,
        StatisticsCalculator $statisticsCalculator
    ): bool {
        $c = (memory_get_usage() / (1024*1024)) . 'MB';
        if ($gameRound->isHomeAwayPlaceParticipating($homeAway) ) {
            return false;
        }
        $d = (memory_get_usage() / (1024*1024)) . 'MB';
        if( !$statisticsCalculator->minimalWithCanStillBeAssigned($gameRound, $homeAway) ) {
            return false;
        }
        $e = (memory_get_usage() / (1024*1024)) . 'MB';
        if( !$statisticsCalculator->minimalAgainstCanStillBeAssigned($gameRound, $homeAway) ) {
            return false;
        }
        $f = (memory_get_usage() / (1024*1024)) . 'MB';
        if( $statisticsCalculator->withWillBeOverAssigned($homeAway) ) {
            return false;
        }
        $g = (memory_get_usage() / (1024*1024)) . 'MB';
        if( $statisticsCalculator->againstWillBeOverAssigned($homeAway) ) {
            return false;
        }
        $h = (memory_get_usage() / (1024*1024)) . 'MB';
        foreach ($homeAway->getPlaces() as $place) {
            if ( $statisticsCalculator->sportWillBeOverAssigned($place) ) {
                return false;
            }
        }
        $i = (memory_get_usage() / (1024*1024)) . 'MB';
        return true;
    }






//    /**
//     * @param list<AgainstHomeAway> $homeAways
//     * @param array<int, PlaceCounter> $assignedMap
//     * @param array<int, PlaceCombinationCounter> $assignedWithMap
//     * @param IndirectMap $assignedAgainstMap
//     * @param array<int, PlaceCounter> $assignedHomeMap
//     * @return list<AgainstHomeAway>
//     */
//    protected function sortHomeAways(
//        array $homeAways,
//        array $assignedMap,
//        array $assignedWithMap,
//        IndirectMap $assignedAgainstMap,
//        array $assignedHomeMap
//    ): array {
//        return $homeAways;
//        uasort($homeAways, function (
//            AgainstHomeAway $homeAwayA,
//            AgainstHomeAway $homeAwayB
//        ) use ($assignedMap, $assignedWithMap, $assignedAgainstMap, $assignedHomeMap): int {
//            list($amountA, $nrOfPlacesA) = $this->getLeastAmountAssigned($homeAwayA, $assignedMap);
//            list($amountB, $nrOfPlacesB) = $this->getLeastAmountAssigned($homeAwayB, $assignedMap);
//            if ($amountA !== $amountB) {
//                return $amountA - $amountB;
//            }
////            if ($nrOfPlacesA !== $nrOfPlacesB) {
////                return $nrOfPlacesB - $nrOfPlacesA;
////            }
////            $amountWithA = $this->getWithAmountAssigned($homeAwayA, $assignedWithMap);
////            $amountWithB = $this->getWithAmountAssigned($homeAwayB, $assignedWithMap);
////            if ($amountWithA !== $amountWithB) {
////                return $amountWithA - $amountWithB;
////            }
////            $amountAgainstA = $this->getAgainstAmountAssigned($homeAwayA, $assignedAgainstMap);
////            $amountAgainstB = $this->getAgainstAmountAssigned($homeAwayB, $assignedAgainstMap);
////            if ($amountAgainstA !== $amountAgainstB) {
////                return $amountAgainstA - $amountAgainstB;
////            }
////            list($amountHomeA, $nrOfPlacesHomeA) = $this->getLeastAmountAssigned($homeAwayA, $assignedHomeMap);
////            list($amountHomeB, $nrOfPlacesHomeB) = $this->getLeastAmountAssigned($homeAwayB, $assignedHomeMap);
////            if ($amountHomeA !== $amountHomeB) {
////                return $amountHomeA - $amountHomeB;
////            }
////            if ($nrOfPlacesHomeA !== $nrOfPlacesHomeB) {
////                return $nrOfPlacesHomeA - $nrOfPlacesHomeB;
////            }
////            for ($depth = 2; $depth <= 2; $depth++) {
////                $amountAgainst2A = $this->getAgainstAmountAssignedAt($homeAwayA, $assignedAgainstMap, $depth);
////                $amountAgainst2B = $this->getAgainstAmountAssignedAt($homeAwayB, $assignedAgainstMap, $depth);
////                if ($amountAgainst2A !== $amountAgainst2B) {
////                    return $amountAgainst2A - $amountAgainst2B;
////                }
////            }
//            return 0;
//        });
//        return array_values($homeAways);
//        // return $homeAways;
//    }

//    protected function getAgainstAmountAssigned(AgainstHomeAway $homeAway, IndirectMap $assignedAgainstMap): int
//    {
//        return $this->getAgainstAmountAssignedAt($homeAway, $assignedAgainstMap, 1);
//    }

//    protected function getAgainstAmountAssignedAt(
//        AgainstHomeAway $homeAway,
//        IndirectMap $assignedAgainstMap,
//        int $depth
//    ): int {
//        $amount = 0;
//        foreach ($homeAway->getHome()->getPlaces() as $homePlace) {
//            foreach ($homeAway->getAway()->getPlaces() as $awayPlace) {
//                $amount += $assignedAgainstMap->count($homePlace, $awayPlace, $depth);
//            }
//        }
//        return $amount;
//    }



    /**
     * @param VariantWithPoule $variantWithPoule
     * @param int $currentGameRoundNumber
     * @param list<AgainstHomeAway> $homeAways
     * @return bool
     */
    protected function isOverAssigned(
        VariantWithPoule $variantWithPoule,
        int $currentGameRoundNumber,
        array $homeAways
    ): bool {
//        $sportVariant = $this->getSportVariant();
//        if ($sportVariant instanceof AgainstGpp
//                && !$sportVariant->allPlacesPlaySameNrOfGames(count($poule->getPlaces()))
//            ) {
//            return false;
//        }
        $poule = $variantWithPoule->getPoule();
        $unassignedMap = [];
        foreach ($poule->getPlaces() as $place) {
            $unassignedMap[$place->getNumber()] = new PlaceCounter($place);
        }
        foreach ($homeAways as $homeAway) {
            foreach ($homeAway->getPlaces() as $place) {
                $unassignedMap[$place->getNumber()]->increment();
            }
        }

        $maxNrOfGameGroups = $variantWithPoule->getNrOfGameGroups();
        foreach ($poule->getPlaces() as $place) {
            if ($currentGameRoundNumber + $unassignedMap[$place->getNumber()]->count() > $maxNrOfGameGroups) {
                return true;
            }
        }
        return false;
    }

    function getMemoryUsageInMB($var): float {
        $mem = memory_get_usage();
        $tmp = unserialize(serialize($var));
        // Return the unserialized memory usage
        return (memory_get_usage() - $mem) / (1024*1024);
    }

}
