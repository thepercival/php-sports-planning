<?php

declare(strict_types=1);

namespace SportsPlanning\GameRound\Creator\Against;

use Psr\Log\LoggerInterface;
use SportsHelpers\Dev\ByteFormatter;
use SportsHelpers\Output;
use SportsHelpers\Sport\Variant\Against;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
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
    protected int $margin = 0;

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

        // Over all Sports
//        $assignedWithMap = $assignedCounter->getAssignedWithMap();
//        $assignedAgainstMap = $assignedCounter->getAssignedAgainstMap();

        $assignedHomeMap = $assignedCounter->getAssignedHomeMap();

        $homeAways = $this->createHomeAways($homeAwayCreator, $poule, $sportVariant);
        $homeAways = $this->initHomeAways($homeAways);

        // shuffle($homeAways);

        $statisticsCalculator = new StatisticsCalculator(
            $variantWithPoule,
            $this->getAssignedSportCounters($poule),
            $assignedMap,
            $assignedCounter->getWithMap($homeAways)/*$assignedWithMap*/,
            new IndirectMap()/*$assignedAgainstMap*/,
            $assignedHomeMap,
            $this->margin
        );

        $homeAways = $statisticsCalculator->sortHomeAways($homeAways);

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
     * @param list<AgainstHomeAway> $homeAwaysForGameRound,
     * @param list<AgainstHomeAway> $homeAways
     * @param StatisticsCalculator $statisticsCalculator,
     * @param AgainstGameRound $gameRound
     * @param int $depth
     * @return bool
     */
    protected function assignGameRound(
        VariantWithPoule $variantWithPoule,
        array $homeAwaysForGameRound,
        array $homeAways,
        StatisticsCalculator $statisticsCalculator,
        AgainstGameRound $gameRound,
        int $depth = 0
    ): bool {

        if( $variantWithPoule->getTotalNrOfGames() === $statisticsCalculator->getNrOfHomeAwaysAsigned() ) {

            if( $statisticsCalculator->allAssigned() ) {
//                $this->gameRoundOutput->output($gameRound, false, 'ASSIGNED HOMEAWAYS GR' . $gameRound->getNumber());
//                $statisticsCalculator->output($this->logger, true, true);
                return true;
            }
            return false;
        }

        if ($this->isGameRoundCompleted($variantWithPoule, $gameRound)) {
            $nextGameRound = $this->toNextGameRound($gameRound, $homeAways);

            if( !$statisticsCalculator->minimalSportCanStillBeAssigned() ) {
                return false;
            }

            $filteredHomeAways = $statisticsCalculator->filter($homeAways);

//            if( $gameRound->getNumber() === 27 ) {
//                $statisticsCalculator->output($this->logger, true, true);
//                $er = 12;
//            }
//            if( $gameRound->getNumber() > 20 ) {
//                $this->logger->info('GR'.$gameRound->getNumber().' completed');
//                $statisticsCalculator->output($this->logger, true, true);
//            }

            return $this->assignGameRound(
                $variantWithPoule,
                $filteredHomeAways,
                $filteredHomeAways,
                $statisticsCalculator,
                $nextGameRound,
                $depth + 1
            );
        }
        return $this->assignSingleGameRound(
            $variantWithPoule,
            $homeAwaysForGameRound,
            $homeAways,
            $statisticsCalculator,
            $gameRound,
            $depth + 1
        );
    }

    /**
     * @param VariantWithPoule $variantWithPoule
     * @param list<AgainstHomeAway> $homeAwaysForGameRound
     * @param list<AgainstHomeAway> $homeAways
     * @param StatisticsCalculator $statisticsCalculator,
     * @param AgainstGameRound $gameRound
     * @param int $depth
     * @return bool
     */
    protected function assignSingleGameRound(
        VariantWithPoule $variantWithPoule,
        array $homeAwaysForGameRound,
        array $homeAways,
        StatisticsCalculator $statisticsCalculator,
        AgainstGameRound $gameRound,
        int $depth = 0
    ): bool {

        $triedHomeAways = [];
        // $nrOfHomeAways = count($homeAwaysForGameRound);
        while($homeAway = array_shift($homeAwaysForGameRound)) {
//            if($gameRound->getNumber() === 2 ) {
//                $this->xxx++;
//                $this->logger->info('try nr ' . $this->xxx);
//            }
//              $id = 'GR' . $gameRound->getNumber() . ': ' . count($homeAwaysForGameRound) . ' -> ' . count($triedHomeAways);
////            $this->logger->info($id);

            if (!$this->isHomeAwayAssignable($homeAway, $statisticsCalculator)) {
                array_push($triedHomeAways, $homeAway);
                continue;
            }
            $gameRound->add($homeAway);

            $homeAwaysForGameRoundTmp = array_values(
                array_filter(
                    array_merge( $homeAwaysForGameRound, $triedHomeAways),
                    function (AgainstHomeAway $homeAway) use ($gameRound): bool {
                        return !$gameRound->isHomeAwayPlaceParticipating($homeAway);
                    }
                )
            );

            // if( $this->isGameRoundCompleted($variantWithPoule, $gameRound) ) {
            if ($this->assignGameRound(
                $variantWithPoule,
                $homeAwaysForGameRoundTmp,
                $homeAways,
                $statisticsCalculator->addHomeAway($homeAway),
                $gameRound,
                $depth + 1
            )) {
                return true;
            }
//            unset($homeAwaysForGameRoundTmp);
            $this->releaseHomeAway($gameRound, $homeAway);
            array_push($triedHomeAways, $homeAway);
            // }

        }
        return false;
    }
//
//    /**
//     * @param VariantWithPoule $variantWithPoule
//     * @param list<AgainstHomeAway>|null $homeAwaysForGameRound
//     * @param list<AgainstHomeAway> $homeAways
//     * @param StatisticsCalculator $statisticsCalculator,
//     * @param AgainstGameRound $gameRound
//     * @param int $nrOfHomeAwaysTried
//     * @param int $depth
//     * @return bool
//     */
//    protected function assignSingleGameRound(
//        VariantWithPoule $variantWithPoule,
//        array|null $homeAwaysForGameRound,
//        array $homeAways,
//        StatisticsCalculator $statisticsCalculator,
//        AgainstGameRound $gameRound,
//        int $nrOfHomeAwaysTried = 0,
//        int $depth = 0
//    ): bool {
//
////        $mem_usage = memory_get_usage();
////        $out = 'GR'.$gameRound->getNumber().' Now usage: ' . round($mem_usage / (1024*1024)) . 'MB of memory.';
////        $homeAways = $homeAways;
////        $mem_usage = memory_get_usage();
////        $out = 'GR'.$gameRound->getNumber().' Now usage: ' . round($mem_usage / (1024*1024)) . 'MB of memory.';
//        if ($statisticsCalculator->allAssigned()) {
//
////            $this->gameRoundOutput->output($gameRound, 'ASSIGNED HOMEAWAYS');
////            $this->outputHomeAwayWithTotals($assignedWithMap);
////            $this->outputPlacesAgainstTotals($variantWithPoule->getPoule(), $assignedAgainstMap);
////            $this->outputUnassignedHomeAwayTotals($homeAways);
//            return true;
//        }
//
//        if ($this->isGameRoundCompleted($variantWithPoule, $gameRound)) {
////            $this->logger->info("gameround " . $gameRound->getNumber() . " completed");
//
//            $nextGameRound = $this->toNextGameRound($gameRound, $homeAways);
//            if (count($homeAways) === 0) {
//                $sportVariant = $variantWithPoule->getSportVariant();
//                if (!($sportVariant instanceof AgainstGpp)) {
//                    throw new \Exception('wrong sportvariant', E_ERROR);
//                }
//                $homeAways = $this->createHomeAways($homeAwayCreator, $sportVariant);
//            }
//
//            if ($gameRound->getNumber() === 6) {
//
////                $x = $this->getMemoryUsageInMB($gameRound);
////                $this->logger->info('gameRound in MB: ' . $x);
//
//                /* Currently used memory */
//                $mem_usage = memory_get_usage();
//                $out = 'GR'.$gameRound->getNumber().' Now usage: ' . round($mem_usage / (1024*1024)) . 'MB of memory.';$this->logger->info('GR'.$gameRound->getNumber().' Now usage: ' . round($mem_usage / (1024*1024)) . 'MB of memory.');
//                $this->logger->info($out);
//
////                $this->gameRoundOutput->output($gameRound, 'ASSIGNED HOMEAWAYS');
////                $statisticsCalculator->output($this->logger, true, true);
////                // $this->outputUnassignedTotals($homeAways);
////                die();
//            }
//
//            if ($gameRound->getNumber() > 1) { // 7 = 567MB
//
//                $mem_usage = memory_get_usage();
//                $out = 'GR'.$gameRound->getNumber().' Now usage: ' . round($mem_usage / (1024*1024)) . 'MB of memory.';
//                $out = 'GR'.$gameRound->getNumber().' depth: ' . $depth;
//                $this->logger->info($out);
//            }
//
////            $sortedHomeAways = $this->sortHomeAways(
////                $homeAways,
////                $assignedMap,
////                $assignedWithMap,
////                $assignedAgainstMap,
////                $assignedHomeMap
////            );
//
//
////            shuffle($homeAways);
//            $mem_usage = memory_get_usage();
//            $out = 'GR'.$gameRound->getNumber().' Now usage: ' . round($mem_usage / (1024*1024)) . 'MB of memory.';
//            return $this->assignGameRound(
//                $variantWithPoule,
//                $homeAwayCreator,
//                null,
//                $homeAways,
//                $statisticsCalculator,
//                $nextGameRound,
//                0,
//                $depth + 1
//            );
//        }
//        if( $homeAwaysForGameRound === null ) {
//            $homeAwaysForGameRound = $homeAways;
//        }
//
//        if ($nrOfHomeAwaysTried === count($homeAwaysForGameRound)) {
//            return false;
//        }
//        $homeAway = array_shift($homeAwaysForGameRound);
//        if ($homeAway === null) {
//            return false;
//        }
//
//        if ($this->isHomeAwayAssignable($gameRound, $homeAway, $statisticsCalculator)) {
//
////            $assignedMapTry = $this->copyCounters($assignedMap);
////            $assignedWithMapTry = $this->copyWithCounters($assignedWithMap);
////            $assignedHomeMapTry = $this->copyCounters($assignedHomeMap);
////            $assignedSportMapTry = $this->copyCounters($assignedSportMap);
//            $gameRound->add($homeAway);
//            $statisticsCalculatorTry = $statisticsCalculator->addHomeAway($homeAway);
////            $assignedAgainstMapTry =
////                $this->assignHomeAway(
////                $gameRound,
////                $homeAway,
////                $assignedSportMapTry,
////                $assignedMapTry,
////                $assignedWithMapTry,
////                $assignedAgainstMap,
////                $assignedHomeMapTry
////            );
////            if ($gameRound->getNumber() > 11 ) {
////                $this->gameRoundOutput->output($gameRound, 'homeawys of gameround');
//////                $this->gameRoundOutput->outputHomeAways($gameRound->getHomeAways(), null, 'homeawys of gameround');
//////                $this->gameRoundOutput->outputHomeAways($homeAwaysForGameRound, null,'choosable homeawys of gameround');
////                // $this->gameRoundOutput->outputHomeAways($homeAways, null, "unassigned");
////                $qw = 12;
////            }
//            $c = (memory_get_usage() / (1024*1024)) . 'MB';
//            $homeAwaysForGameRoundTmp = array_values(
//                array_filter(
//                    $homeAwaysForGameRound,
//                    function (AgainstHomeAway $homeAway) use ($gameRound): bool {
//                        return !$gameRound->isHomeAwayPlaceParticipating($homeAway);
//                    }
//                )
//            );
//            $d = (memory_get_usage() / (1024*1024)) . 'MB';
//            if ($this->assignGameRound(
//                $variantWithPoule,
//                $homeAwayCreator,
//                $homeAwaysForGameRoundTmp,
//                $homeAways,
//                $statisticsCalculatorTry,
//                $gameRound,
//                0,
//                $depth + 1
//            )) {
//                return true;
//            }
//            unset($homeAwaysForGameRoundTmp);
//            $this->releaseHomeAway($gameRound, $homeAway);
//        }
//        $c = (memory_get_usage() / (1024*1024)) . 'MB';
//        $homeAwaysForGameRound[] = $homeAway;
//        ++$nrOfHomeAwaysTried;
//        return $this->assignGameRound(
//            $variantWithPoule,
//            $homeAwayCreator,
//            $homeAwaysForGameRound,
//            $homeAways,
//            $statisticsCalculator,
//            $gameRound,
//            $nrOfHomeAwaysTried,
//            $depth + 1
//        );

    /**
     * @param GppHomeAwayCreator $homeAwayCreator
     * @param Poule $poule
     * @param AgainstGpp $sportVariant
     * @return list<AgainstHomeAway>
     */
    protected function createHomeAways(
        GppHomeAwayCreator $homeAwayCreator,
        Poule $poule,
        AgainstGpp $sportVariant): array
    {
        $variantWithPoule = new VariantWithPoule($sportVariant, $poule);
        $totalNrOfGames = $variantWithPoule->getTotalNrOfGames();
        $homeAways = [];
        while ( count($homeAways) < $totalNrOfGames ) {
            $homeAways = array_merge($homeAways, $homeAwayCreator->create($sportVariant));
        }
        return $homeAways;
    }

    protected function isHomeAwayAssignable(
        AgainstHomeAway $homeAway, StatisticsCalculator $statisticsCalculator
    ): bool {

        if( !$statisticsCalculator->minimalAgainstCanStillBeAssigned($homeAway) ) {
            return false;
        }
        if( $statisticsCalculator->againstWillBeOverAssigned($homeAway) ) {
            return false;
        }
        foreach ($homeAway->getPlaces() as $place) {
            if ( $statisticsCalculator->sportWillBeOverAssigned($place, 1) ) {
                return false;
            }
        }
        if( !$statisticsCalculator->useWith()) {
            return true;
        }
        if( !$statisticsCalculator->minimalWithCanStillBeAssigned($homeAway) ) {
            return false;
        }
        if( $statisticsCalculator->withWillBeOverAssigned($homeAway) ) {
            return false;
        }
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

//    function getMemoryUsageInMB($var): float {
//        $mem = memory_get_usage();
//        $tmp = unserialize(serialize($var));
//        // Return the unserialized memory usage
//        return (memory_get_usage() - $mem) / (1024*1024);
//    }

    /**
     * @param list<AgainstHomeAway> $homeAways
     * @return list<AgainstHomeAway>
     */
    private function initHomeAways(array $homeAways): array {
        /** @var list<AgainstHomeAway> $newHomeAways */
        $newHomeAways = [];
        while( $homeAway = array_shift($homeAways) ) {
            if( (count($homeAways) % 2) === 0 ) {
                array_unshift($newHomeAways, $homeAway);
            } else {
                array_push($newHomeAways, $homeAway);
            }
        }

//        while( count($homeAways) > 0 ) {
//            if( (count($homeAways) % 2) === 0 ) {
//                $homeAway = array_shift($homeAways);
//            } else {
//                $homeAway = array_pop($homeAways);
//            }
//            array_push($newHomeAways, $homeAway);
//        }

        return $newHomeAways;
    }

}
