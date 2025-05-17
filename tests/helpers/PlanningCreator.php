<?php

declare(strict_types=1);

namespace SportsPlanning\TestHelper;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstOneVsTwo;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\Input;
use SportsPlanning\Referee\PlanningRefereeInfo;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;

trait PlanningCreator
{
//    protected function getAgainstGppSportVariant(
//        int $nrOfHomePlaces = 1,
//        int $nrOfAwayPlaces = 1,
//        int $nrOfGamesPerPlace = 1
//    ): AgainstGpp {
//        return new AgainstGpp($nrOfHomePlaces, $nrOfAwayPlaces, $nrOfGamesPerPlace);
//    }


    protected function getLogger(): LoggerInterface
    {
        $logger = new Logger("test-logger");
//        $processor = new UidProcessor();
//        $logger->pushProcessor($processor);

        $handler = new StreamHandler('php://stdout', Logger::INFO);
        $logger->pushHandler($handler);
        return $logger;
    }

    protected function getDefaultNrOfReferees(): int
    {
        return 2;
    }

    /**
     * @param list<int> $pouleStructureAsArray
     * @param list<SportWithNrOfFieldsAndNrOfCycles>|null $sportsWithNrOfFieldsAndNrOfCycles
     * @param PlanningRefereeInfo|null $refereeInfo
     * @return Input
     */
    protected function createInput(
        array $pouleStructureAsArray,
        array $sportsWithNrOfFieldsAndNrOfCycles = null,
        PlanningRefereeInfo|null $refereeInfo = null,
        bool $perPoule = false
    ) {
        if ($sportsWithNrOfFieldsAndNrOfCycles === null) {
            $sportsWithNrOfFieldsAndNrOfCycles = [
                new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1)
            ];
        }
        if ($refereeInfo === null) {
            $refereeInfo = new PlanningRefereeInfo($this->getDefaultNrOfReferees());
        }
        $configurationValidator = new Input\ConfigurationValidator();
        $configuration = $configurationValidator->createReducedAndValidatedInputConfiguration(
            new PouleStructure(...$pouleStructureAsArray),
            $sportsWithNrOfFieldsAndNrOfCycles,
            $refereeInfo,
            $perPoule
        );
        $input = new Input( $configuration );

        return $input;
    }

//    protected function createPlanning(
//        Input $input,
//        SportRange $nrOfGamesPerBatchRange = null,
//        int $maxNrOfGamesInARow = 0,
//        bool $disableThrowOnTimeout = false,
//        bool $showHighestCompletedBatchNr = false,
//        TimeoutState|null $timeoutState = null,
//        int|null $allowedGppMargin = null
//    ): Planning {
//        if ($nrOfGamesPerBatchRange === null) {
//            $nrOfGamesPerBatchRange = new SportRange(1, 1);
//        }
//        $planning = new Planning($input, $nrOfGamesPerBatchRange, $maxNrOfGamesInARow);
//        if ($timeoutState !== null) {
//            $planning->setTimeoutState($timeoutState);
//        }
//
//        $scheduleCreator = new ScheduleCreator($this->getLogger());
//        if( $allowedGppMargin === null ) {
//            $allowedGppMargin = $scheduleCreator->getMaxGppMargin($input, $input->getPoule(1));
//        }
//        $schedules = $scheduleCreator->createFromInput($input, $allowedGppMargin);
//
//        $gameCreator = new GameCreator($this->getLogger());
//        $gameCreator->createGames($planning, $schedules);
//
//        $gameAssigner = new GameAssigner($this->getLogger());
//        if ($disableThrowOnTimeout) {
//            $gameAssigner->disableThrowOnTimeout();
//        }
//        if ($showHighestCompletedBatchNr) {
//            $gameAssigner->showHighestCompletedBatchNr();
//        }
//        $gameAssigner->assignGames($planning);
//
//        if (PlanningState::Succeeded !== $planning->getState()) {
//            throw new Exception("planning could not be created", E_ERROR);
//        }
//        return $planning;
//    }
}
