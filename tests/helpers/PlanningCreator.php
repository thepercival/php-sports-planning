<?php

declare(strict_types=1);

namespace SportsPlanning\TestHelper;

use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use SportsHelpers\PouleStructure;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsHelpers\Sport\Variant\AllInOneGame as AllInOneGameSportVariant;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;
use SportsHelpers\SportRange;
use SportsPlanning\Combinations\GamePlaceStrategy;
use SportsPlanning\Game\Assigner as GameAssigner;
use SportsPlanning\Game\Creator as GameCreator;
use SportsPlanning\Input;
use SportsPlanning\Planning;
use SportsPlanning\Planning\State as PlanningState;
use SportsPlanning\Referee\Info as RefereeInfo;
use SportsPlanning\Schedule\Creator\Service as ScheduleCreatorService;

trait PlanningCreator
{
    protected function getAgainstSportVariant(
        int $nrOfHomePlaces = 1,
        int $nrOfAwayPlaces = 1,
        int $nrOfH2H = 1,
        int $nrOfGamesPerPlace = 0
    ): AgainstSportVariant {
        return new AgainstSportVariant($nrOfHomePlaces, $nrOfAwayPlaces, $nrOfH2H, $nrOfGamesPerPlace);
    }

    protected function getSingleSportVariant(int $nrOfGameRounds = 1, int $nrOfGamePlaces = 1): SingleSportVariant
    {
        return new SingleSportVariant($nrOfGamePlaces, $nrOfGameRounds);
    }

    protected function getAllInOneGameSportVariant(int $nrOfGameRounds = 1): AllInOneGameSportVariant
    {
        return new AllInOneGameSportVariant($nrOfGameRounds);
    }

    protected function getAgainstSportVariantWithFields(
        int $nrOfFields,
        int $nrOfHomePlaces = 1,
        int $nrOfAwayPlaces = 1,
        int $nrOfH2H = 1,
        int $nrOfGamesPerPlace = 0
    ): SportVariantWithFields {
        return new SportVariantWithFields(
            $this->getAgainstSportVariant($nrOfHomePlaces, $nrOfAwayPlaces, $nrOfH2H, $nrOfGamesPerPlace),
            $nrOfFields
        );
    }

    protected function getSingleSportVariantWithFields(int $nrOfFields, int $nrOfGameRounds = 1, int $nrOfGamePlaces = 1): SportVariantWithFields
    {
        return new SportVariantWithFields($this->getSingleSportVariant($nrOfGameRounds, $nrOfGamePlaces), $nrOfFields);
    }

    protected function getAllInOneGameSportVariantWithFields(int $nrOfFields, int $nrOfGameRounds = 1): SportVariantWithFields
    {
        return new SportVariantWithFields($this->getAllInOneGameSportVariant($nrOfGameRounds), $nrOfFields);
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
     * @param list<SportVariantWithFields>|null $sportVariantsWithFields
     * @param GamePlaceStrategy|null $gamePlaceStrategy
     * @param RefereeInfo|null $refereeInfo
     * @return Input
     */
    protected function createInput(
        array $pouleStructureAsArray,
        array $sportVariantsWithFields = null,
        GamePlaceStrategy $gamePlaceStrategy = null,
        RefereeInfo|null $refereeInfo = null
    ) {
        if ($sportVariantsWithFields === null) {
            $sportVariantsWithFields = [$this->getAgainstSportVariantWithFields(2)];
        }
        if ($gamePlaceStrategy === null) {
            $gamePlaceStrategy = GamePlaceStrategy::EquallyAssigned;
        }
        if ($refereeInfo === null) {
            $refereeInfo = new RefereeInfo($this->getDefaultNrOfReferees());
        }
        $input = new Input(
            new PouleStructure(...$pouleStructureAsArray),
            $sportVariantsWithFields,
            $gamePlaceStrategy,
            $refereeInfo
        );

        return $input;
    }

    protected function createPlanning(
        Input $input,
        SportRange $nrOfGamesPerBatchRange = null,
        int $maxNrOfGamesInARow = 0,
        bool $disableThrowOnTimeout = false,
        bool $showHighestCompletedBatchNr = false
    ): Planning {
        if ($nrOfGamesPerBatchRange === null) {
            $nrOfGamesPerBatchRange = new SportRange(1, 1);
        }
        $planning = new Planning($input, $nrOfGamesPerBatchRange, $maxNrOfGamesInARow);

        $scheduleCreatorService = new ScheduleCreatorService($this->getLogger());
        $schedules = $scheduleCreatorService->createSchedules($input);

        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);

        $gameAssigner = new GameAssigner($this->getLogger());
        if ($disableThrowOnTimeout) {
            $gameAssigner->disableThrowOnTimeout();
        }
        if ($showHighestCompletedBatchNr) {
            $gameAssigner->showHighestCompletedBatchNr();
        }
        $gameAssigner->assignGames($planning);

        if (PlanningState::Succeeded !== $planning->getState()) {
            throw new Exception("planning could not be created", E_ERROR);
        }
        return $planning;
    }
}
