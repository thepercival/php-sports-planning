<?php

declare(strict_types=1);

namespace SportsPlanning\GameRound\Creator\Against;

use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsPlanning\Combinations\MultipleCombinationsCounter\Against as AgainstCounter;
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
    public function __construct(LoggerInterface $logger)
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

        $sortedHomeAways = $this->sortHomeAways(
            $homeAways,
            $assignedMap,
            $assignedWithMap,
            $assignedAgainstMap,
            $assignedHomeMap
        );
//        $this->logger->info('XXX');
//        $this->outputUnassignedHomeAways($homeAways);
        if ($this->assignGameRound(
                $variantWithPoule,
                $homeAwayCreator,
                $sortedHomeAways,
                $sortedHomeAways,
                $this->getAssignedSportCounters($poule),
                $assignedMap,
                $assignedWithMap,
                $assignedAgainstMap,
                $assignedHomeMap,
                $gameRound
            ) === false) {
            throw new \Exception('creation of homeaway can not be false', E_ERROR);
        }
        return $gameRound;
    }

    /**
     * @param VariantWithPoule $variantWithPoule
     * @param GppHomeAwayCreator $homeAwayCreator
     * @param list<AgainstHomeAway> $homeAwaysForGameRound
     * @param list<AgainstHomeAway> $homeAways
     * @param array<int, PlaceCounter> $assignedSportMap ,
     * @param array<int, PlaceCounter> $assignedMap
     * @param array<int, PlaceCombinationCounter> $assignedWithMap
     * @param IndirectMap $assignedAgainstMap
     * @param array<int, PlaceCounter> $assignedHomeMap
     * @param AgainstGameRound $gameRound
     * @param int $nrOfHomeAwaysTried
     * @return bool
     */
    protected function assignGameRound(
        VariantWithPoule $variantWithPoule,
        GppHomeAwayCreator $homeAwayCreator,
        array $homeAwaysForGameRound,
        array $homeAways,
        array $assignedSportMap,
        array $assignedMap,
        array $assignedWithMap,
        IndirectMap $assignedAgainstMap,
        array $assignedHomeMap,
        AgainstGameRound $gameRound,
        int $nrOfHomeAwaysTried = 0
    ): bool {
        if ($this->isCompleted($variantWithPoule, $assignedSportMap)) {
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

//            if ($gameRound->getNumber() === 2) {
//                $this->gameRoundOutput->output($gameRound);
//                $this->logger->info('pre sort after 2');
//                //$this->outputUnassignedTotals($homeAways);
//                $this->outputUnassignedHomeAways($homeAways);
//                // $this->gameRoundOutput->outputHomeAways($homeAways, null, "unassigned");
//                $qw = 12;
//            }

            //if ($this->getDifferenceNrOfGameRounds($assignedMap) >= 5) {
            //                $this->gameRoundOutput->output($gameRound);
            //                $this->gameRoundOutput->outputHomeAways($homeAways, $gameRound, 'presort after gameround ' . $gameRound->getNumber() . ' completed');

            $sortedHomeAways = $this->sortHomeAways(
                $homeAways,
                $assignedMap,
                $assignedWithMap,
                $assignedAgainstMap,
                $assignedHomeMap
            );

//            if ($gameRound->getNumber() === 2) {
//                $this->logger->info('post sort after 2');
//                //$this->outputUnassignedTotals($homeAways);
//                $this->outputUnassignedHomeAways($sortedHomeAways);
//                // $this->gameRoundOutput->outputHomeAways($homeAways, null, "unassigned");
//                $qw = 12;
//            }
//
//            if ($gameRound->getNumber() === 14) {
//                $this->gameRoundOutput->outputHomeAways($sortedHomeAways, $gameRound, 'postsort after gameround ' . $gameRound->getNumber() . ' completed');
//            }

//            $this->gameRoundOutput->outputHomeAways($homeAways, null, 'postsort after gameround ' . $gameRound->getNumber() . ' completed');
            // $gamesList = array_values($gamesForBatchTmp);
//            shuffle($homeAways);
            return $this->assignGameRound(
                $variantWithPoule,
                $homeAwayCreator,
                $sortedHomeAways,
                $homeAways,
                $assignedSportMap,
                $assignedMap,
                $assignedWithMap,
                $assignedAgainstMap,
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

        if ($this->isHomeAwayAssignable($variantWithPoule, $gameRound, $homeAway, $assignedSportMap)) {
            $assignedMapTry = $this->copyCounters($assignedMap);
            $assignedWithMapTry = $this->copyWithCounters($assignedWithMap);
            // $assignedAgainstMapTry = $this->copyAgainstCounters($assignedAgainstMap);
            $assignedHomeMapTry = $this->copyCounters($assignedHomeMap);
            $assignedSportMapTry = $this->copyCounters($assignedSportMap);
            $this->assignHomeAway(
                $gameRound,
                $homeAway,
                $assignedSportMapTry,
                $assignedMapTry,
                $assignedWithMapTry,
                $assignedAgainstMap,
                $assignedHomeMapTry
            );
//            if ($gameRound->getNumber() === 7 ) {
//                $this->gameRoundOutput->outputHomeAways($gameRound->getHomeAways(), null, 'homeawys of gameround 7');
//                $this->gameRoundOutput->outputHomeAways($homeAwaysForGameRound, null,'choosable homeawys of gameround 7');
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
                $variantWithPoule,
                $homeAwayCreator,
                $homeAwaysForGameRoundTmp,
                $homeAways,
                $assignedSportMapTry,
                $assignedMapTry,
                $assignedWithMapTry,
                $assignedAgainstMap,
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
            $variantWithPoule,
            $homeAwayCreator,
            $homeAwaysForGameRound,
            $homeAways,
            $assignedSportMap,
            $assignedMap,
            $assignedWithMap,
            $assignedAgainstMap,
            $assignedHomeMap,
            $gameRound,
            $nrOfHomeAwaysTried
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

    /**
     * @param list<AgainstHomeAway> $homeAways
     * @param array<int, PlaceCounter> $assignedMap
     * @param array<int, PlaceCombinationCounter> $assignedWithMap
     * @param IndirectMap $assignedAgainstMap
     * @param array<int, PlaceCounter> $assignedHomeMap
     * @return list<AgainstHomeAway>
     */
    protected function sortHomeAways(
        array $homeAways,
        array $assignedMap,
        array $assignedWithMap,
        IndirectMap $assignedAgainstMap,
        array $assignedHomeMap
    ): array {
        uasort($homeAways, function (
            AgainstHomeAway $homeAwayA,
            AgainstHomeAway $homeAwayB
        ) use ($assignedMap, $assignedWithMap, $assignedAgainstMap, $assignedHomeMap): int {
            list($amountA, $nrOfPlacesA) = $this->getLeastAmountAssigned($homeAwayA, $assignedMap);
            list($amountB, $nrOfPlacesB) = $this->getLeastAmountAssigned($homeAwayB, $assignedMap);
            if ($amountA !== $amountB) {
                return $amountA - $amountB;
            }
            if ($nrOfPlacesA !== $nrOfPlacesB) {
                return $nrOfPlacesB - $nrOfPlacesA;
            }
            $amountWithA = $this->getWithAmountAssigned($homeAwayA, $assignedWithMap);
            $amountWithB = $this->getWithAmountAssigned($homeAwayB, $assignedWithMap);
            if ($amountWithA !== $amountWithB) {
                return $amountWithA - $amountWithB;
            }
            $amountAgainstA = $this->getAgainstAmountAssigned($homeAwayA, $assignedAgainstMap);
            $amountAgainstB = $this->getAgainstAmountAssigned($homeAwayB, $assignedAgainstMap);
            if ($amountAgainstA !== $amountAgainstB) {
                return $amountAgainstA - $amountAgainstB;
            }
            list($amountHomeA, $nrOfPlacesHomeA) = $this->getLeastAmountAssigned($homeAwayA, $assignedHomeMap);
            list($amountHomeB, $nrOfPlacesHomeB) = $this->getLeastAmountAssigned($homeAwayB, $assignedHomeMap);
            if ($amountHomeA !== $amountHomeB) {
                return $amountHomeA - $amountHomeB;
            }
            if ($nrOfPlacesHomeA !== $nrOfPlacesHomeB) {
                return $nrOfPlacesHomeA - $nrOfPlacesHomeB;
            }
            for ($depth = 2; $depth <= 2; $depth++) {
                $amountAgainst2A = $this->getAgainstAmountAssignedAt($homeAwayA, $assignedAgainstMap, $depth);
                $amountAgainst2B = $this->getAgainstAmountAssignedAt($homeAwayB, $assignedAgainstMap, $depth);
                if ($amountAgainst2A !== $amountAgainst2B) {
                    return $amountAgainst2A - $amountAgainst2B;
                }
            }
            return 0; // $nrOfPlacesHomeA - $nrOfPlacesHomeB;
        });
        return array_values($homeAways);
    }

    protected function getAgainstAmountAssigned(AgainstHomeAway $homeAway, IndirectMap $assignedAgainstMap): int
    {
        return $this->getAgainstAmountAssignedAt($homeAway, $assignedAgainstMap, 1);
    }

    protected function getAgainstAmountAssignedAt(
        AgainstHomeAway $homeAway,
        IndirectMap $assignedAgainstMap,
        int $depth
    ): int {
        $amount = 0;
        foreach ($homeAway->getHome()->getPlaces() as $homePlace) {
            foreach ($homeAway->getAway()->getPlaces() as $awayPlace) {
                $amount += $assignedAgainstMap->count($homePlace, $awayPlace, $depth);
            }
        }
        return $amount;
    }
}
