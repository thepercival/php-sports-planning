<?php

declare(strict_types=1);

namespace SportsPlanning\TestHelper;

use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\SportVariants\AgainstOneVsOne;
use SportsHelpers\SportVariants\AgainstOneVsTwo;
use SportsHelpers\SportVariants\AgainstTwoVsTwo;
use SportsHelpers\SportVariants\Persist\SportPersistVariantWithNrOfFields;
use SportsHelpers\SportVariants\AllInOneGame;
use SportsHelpers\SportVariants\Single;
use SportsPlanning\Input;
use SportsPlanning\Input\Configuration;
use SportsPlanning\Planning;
use SportsPlanning\Planning\State as PlanningState;
use SportsPlanning\Planning\TimeoutState;
use SportsPlanning\Referee\Info as RefereeInfo;

trait PlanningCreator
{
//    protected function getAgainstGppSportVariant(
//        int $nrOfHomePlaces = 1,
//        int $nrOfAwayPlaces = 1,
//        int $nrOfGamesPerPlace = 1
//    ): AgainstGpp {
//        return new AgainstGpp($nrOfHomePlaces, $nrOfAwayPlaces, $nrOfGamesPerPlace);
//    }

    protected function getSingleSportVariant(int $nrOfGamesPerPlace = 1, int $nrOfGamePlaces = 1): Single
    {
        return new Single($nrOfGamePlaces, $nrOfGamesPerPlace);
    }

    protected function getAllInOneGameSportVariant(int $nrOfGamesPerPlace = 1): AllInOneGame
    {
        return new AllInOneGame($nrOfGamesPerPlace);
    }

    protected function getAgainstOneVsOneSportPersistVariantWithNrOfFields(
        int $nrOfFields,
        int $nrOfCycles = 1
    ): SportPersistVariantWithNrOfFields {
        return new SportPersistVariantWithNrOfFields( new AgainstOneVsOne($nrOfCycles), $nrOfFields );
    }

    protected function getAgainstOneVsTwoSportPersistVariantWithNrOfFields(
        int $nrOfFields,
        int $nrOfCycles = 1
    ): SportPersistVariantWithNrOfFields {
        return new SportPersistVariantWithNrOfFields( new AgainstOneVsTwo($nrOfCycles), $nrOfFields );
    }

    protected function getAgainstTwoVsTwoSportPersistVariantWithNrOfFields(
        int $nrOfFields,
        int $nrOfCycles = 1
    ): SportPersistVariantWithNrOfFields {
        return new SportPersistVariantWithNrOfFields( new AgainstTwoVsTwo($nrOfCycles), $nrOfFields );
    }

    protected function getSingleSportPersistVariantWithNrOfFields(
        int $nrOfFields,
        int $nrOfGamesPerPlace = 1,
        int $nrOfGamePlaces = 1
    ): SportPersistVariantWithNrOfFields {
        return new SportPersistVariantWithNrOfFields(
            $this->getSingleSportVariant($nrOfGamesPerPlace, $nrOfGamePlaces),
            $nrOfFields
        );
    }

    protected function getAllInOneGameSportPersistVariantWithFields(
        int $nrOfFields,
        int $nrOfGamesPerPlace = 1
    ): SportPersistVariantWithNrOfFields {
        return new SportPersistVariantWithNrOfFields($this->getAllInOneGameSportVariant($nrOfGamesPerPlace), $nrOfFields);
    }

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
     * @param list<SportPersistVariantWithNrOfFields>|null $sportPersistVariantsWithFields
     * @param RefereeInfo|null $refereeInfo
     * @return Input
     */
    protected function createInput(
        array $pouleStructureAsArray,
        array $sportPersistVariantsWithFields = null,
        RefereeInfo|null $refereeInfo = null,
        bool $perPoule = false
    ) {
        if ($sportPersistVariantsWithFields === null) {
            $sportPersistVariantsWithFields = [$this->getAgainstOneVsOneSportPersistVariantWithNrOfFields(2)];
        }
        if ($refereeInfo === null) {
            $refereeInfo = new RefereeInfo($this->getDefaultNrOfReferees());
        }
        $configurationValidator = new Input\ConfigurationValidator();
        $configuration = $configurationValidator->createReducedAndValidatedInputConfiguration(
            new PouleStructure(...$pouleStructureAsArray),
            $sportPersistVariantsWithFields,
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
