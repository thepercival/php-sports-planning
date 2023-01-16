<?php

declare(strict_types=1);

namespace SportsPlanning\GameRound\Creator\Against;

use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsPlanning\Combinations\AssignedCounter;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Combinations\HomeAwayCreator\H2h as H2hHomeAwayCreator;
use SportsPlanning\Combinations\Mapper;
use SportsPlanning\Combinations\PlaceCombinationCounterMap\Ranged as RangedPlaceCombinationCounterMap;
use SportsPlanning\Combinations\PlaceCounterMap;
use SportsPlanning\Combinations\StatisticsCalculator\Against\H2h as H2hStatisticsCalculator;
use SportsPlanning\GameRound\Against as AgainstGameRound;
use SportsPlanning\GameRound\Creator\Against as AgainstCreator;
use SportsPlanning\Poule;
use SportsPlanning\SportVariant\WithPoule\Against\H2h as AgainstH2hWithPoule;
use SportsPlanning\Combinations\Amount\Range as AmountRange;

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
        AssignedCounter $assignedCounter,
        AmountRange $homeAmountRange
    ): AgainstGameRound {
        $againstH2hWithPoule = new AgainstH2hWithPoule($poule, $sportVariant);
        $mapper = new Mapper();
        $gameRound = new AgainstGameRound();
        $homeAways = $homeAwayCreator->createForOneH2H($againstH2hWithPoule);

        $statisticsCalculator = new H2hStatisticsCalculator(
            $againstH2hWithPoule,
            new RangedPlaceCombinationCounterMap($assignedCounter->getAssignedHomeMap(), $homeAmountRange),
            0,
            new PlaceCounterMap( array_values( $mapper->getPlaceMap($poule) ) ),
            $this->logger
        );

        // $this->outputUnassignedHomeAways($homeAways);
        if ($this->assignGameRound(
                $againstH2hWithPoule,
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
     * @param AgainstH2hWithPoule $againstWithPoule
     * @param H2hHomeAwayCreator $homeAwayCreator
     * @param list<HomeAway> $homeAwaysForGameRound
     * @param list<HomeAway> $homeAways
     * @param H2hStatisticsCalculator $statisticsCalculator,
     * @param AgainstGameRound $gameRound
     * @param int $nrOfHomeAwaysTried
     * @return bool
     */
    protected function assignGameRound(
        AgainstH2hWithPoule $againstWithPoule,
        H2hHomeAwayCreator $homeAwayCreator,
        array $homeAwaysForGameRound,
        array $homeAways,
        H2hStatisticsCalculator $statisticsCalculator,
        AgainstGameRound $gameRound,
        int $nrOfHomeAwaysTried = 0
    ): bool {
        if ($statisticsCalculator->allAssigned()) {
            return true;
        }

        if ($this->isGameRoundCompleted($againstWithPoule, $gameRound)) {
//            $this->logger->info("gameround " . $gameRound->getNumber() . " completed");

            $nextGameRound = $this->toNextGameRound($gameRound, $homeAways);
            if (count($homeAways) === 0) {
                $homeAways = $homeAwayCreator->createForOneH2H($againstWithPoule);
            }

//            if ($gameRound->getNumber() === 14) {
//                $this->gameRoundOutput->output($gameRound);
//                $this->outputUnassignedTotals($homeAways);
//                $this->outputUnassignedHomeAways($homeAways);
//                // $this->gameRoundOutput->outputHomeAways($homeAways, null, "unassigned");
//                $qw = 12;
//            }


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
                $againstWithPoule,
                $homeAwayCreator,
                $nextHomeAways,
                $homeAways,
                $statisticsCalculator,
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

        if ($this->isHomeAwayAssignable($gameRound, $homeAway)) {

            $gameRound->add($homeAway);
            $statisticsCalculatorTry = $statisticsCalculator->addHomeAway($homeAway);

//            if ($gameRound->getNumber() === 15 ) {
//                $this->gameRoundOutput->outputHomeAways($gameRound->getHomeAways(), null, 'homeawys of gameround 15');
//                $this->gameRoundOutput->outputHomeAways($homeAwaysForGameRound, null,'choosable homeawys of gameround 15');
//                // $this->gameRoundOutput->outputHomeAways($homeAways, null, "unassigned");
//                $qw = 12;
//            }
            $homeAwaysForGameRoundTmp = array_values(
                array_filter(
                    $homeAwaysForGameRound,
                    function (HomeAway $homeAway) use ($gameRound): bool {
                        return !$gameRound->isHomeAwayPlaceParticipating($homeAway);
                    }
                )
            );
            if ($this->assignGameRound(
                $againstWithPoule,
                $homeAwayCreator,
                $homeAwaysForGameRoundTmp,
                $homeAways,
                $statisticsCalculatorTry,
                $gameRound
            )) {
                return true;
            }
            $this->releaseHomeAway($gameRound, $homeAway);
        }
        $homeAwaysForGameRound[] = $homeAway;
        ++$nrOfHomeAwaysTried;
        return $this->assignGameRound(
            $againstWithPoule,
            $homeAwayCreator,
            $homeAwaysForGameRound,
            $homeAways,
            $statisticsCalculator,
            $gameRound,
            $nrOfHomeAwaysTried
        );
    }


    protected function isHomeAwayAssignable(AgainstGameRound $gameRound, HomeAway $homeAway): bool {
        foreach ($homeAway->getPlaces() as $place) {
            if ($gameRound->isParticipating($place) ) {
                return false;
            }
        }
        return true;
    }
}
