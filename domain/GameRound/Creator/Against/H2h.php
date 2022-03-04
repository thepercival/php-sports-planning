<?php

declare(strict_types=1);

namespace SportsPlanning\GameRound\Creator\Against;

use Psr\Log\LoggerInterface;
use SportsPlanning\Combinations\Indirect\Map as IndirectMap;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsPlanning\SportVariant\WithPoule as VariantWithPoule;
use SportsPlanning\Combinations\AgainstHomeAway;
use SportsPlanning\Combinations\HomeAwayCreator\H2h as H2hHomeAwayCreator;
use SportsPlanning\Combinations\Output\GameRound as GameRoundOutput;
use SportsPlanning\Combinations\PlaceCombinationCounter;
use SportsPlanning\GameRound\Against as AgainstGameRound;
use SportsPlanning\GameRound\Creator\Against as AgainstCreator;
use SportsPlanning\Place;
use SportsPlanning\PlaceCounter;
use SportsPlanning\Poule;
use SportsPlanning\Schedule\Creator\AssignedCounter;

class H2h extends AgainstCreator
{
    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
    }

    public function createGameRound(
        Poule $poule,
        AgainstH2h $sportVariant,
        H2hHomeAwayCreator $homeAwayCreator,
        AssignedCounter $assignedCounter
    ): AgainstGameRound {
        $variantWithPoule = new VariantWithPoule($sportVariant, $poule);

        $gameRound = new AgainstGameRound();
        $assignedMap = $assignedCounter->getAssignedMap();
        $assignedWithMap = $assignedCounter->getAssignedWithMap();
        $assignedAgainstMap = $assignedCounter->getAssignedAgainstMap();
        $assignedHomeMap = $assignedCounter->getAssignedHomeMap();
        $homeAways = $this->createHomeAwaysForOneH2H($homeAwayCreator);
        // $this->outputUnassignedHomeAways($homeAways);
        if ($this->assignGameRound(
                $variantWithPoule,
                $homeAwayCreator,
                $homeAways,
                $homeAways,
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
     * @param H2hHomeAwayCreator $homeAwayCreator
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
        H2hHomeAwayCreator $homeAwayCreator,
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
                $homeAways = $this->createHomeAwaysForOneH2H($homeAwayCreator);
            }

//            if ($gameRound->getNumber() === 14) {
//                $this->gameRoundOutput->output($gameRound);
//                $this->outputUnassignedTotals($homeAways);
//                $this->outputUnassignedHomeAways($homeAways);
//                // $this->gameRoundOutput->outputHomeAways($homeAways, null, "unassigned");
//                $qw = 12;
//            }

            if ($this->isOverAssigned($variantWithPoule, $gameRound->getNumber(), $homeAways)) {
                return false;
            }

            //if ($this->getDifferenceNrOfGameRounds($assignedMap) >= 5) {
            //                $this->gameRoundOutput->output($gameRound);
            //                $this->gameRoundOutput->outputHomeAways($homeAways, $gameRound, 'presort after gameround ' . $gameRound->getNumber() . ' completed');
            $nextHomeAways = $homeAways;
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
                $nextHomeAways,
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
     * @param H2hHomeAwayCreator $homeAwayCreator
     * @return list<AgainstHomeAway>
     */
    protected function createHomeAwaysForOneH2H(H2hHomeAwayCreator $homeAwayCreator): array
    {
        return $homeAwayCreator->createForOneH2H();
    }
}
