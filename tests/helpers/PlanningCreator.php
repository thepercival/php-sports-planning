<?php

declare(strict_types=1);

namespace SportsPlanning\TestHelper;

use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use SportsHelpers\PouleStructure;

use SportsHelpers\Sport\Variant\AllInOneGame as AllInOneGameSportVariant;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;
use SportsHelpers\SportRange;
use SportsPlanning\Game\Assigner as GameAssigner;
use SportsPlanning\Game\Creator as GameCreator;
use SportsPlanning\Input;
use SportsPlanning\Planning;
use SportsPlanning\Planning\State as PlanningState;
use SportsPlanning\Planning\TimeoutState;
use SportsPlanning\Referee\Info as RefereeInfo;
use SportsPlanning\Schedule\Creator as ScheduleCreator;

trait PlanningCreator
{
    protected function getAgainstH2hSportVariant(
        int $nrOfHomePlaces = 1,
        int $nrOfAwayPlaces = 1,
        int $nrOfH2H = 1
    ): AgainstH2h {
        return new AgainstH2h($nrOfHomePlaces, $nrOfAwayPlaces, $nrOfH2H);
    }

    protected function getAgainstGppSportVariant(
        int $nrOfHomePlaces = 1,
        int $nrOfAwayPlaces = 1,
        int $nrOfGamesPerPlace = 1
    ): AgainstGpp {
        return new AgainstGpp($nrOfHomePlaces, $nrOfAwayPlaces, $nrOfGamesPerPlace);
    }

    protected function getSingleSportVariant(int $nrOfGamesPerPlace = 1, int $nrOfGamePlaces = 1): SingleSportVariant
    {
        return new SingleSportVariant($nrOfGamePlaces, $nrOfGamesPerPlace);
    }

    protected function getAllInOneGameSportVariant(int $nrOfGamesPerPlace = 1): AllInOneGameSportVariant
    {
        return new AllInOneGameSportVariant($nrOfGamesPerPlace);
    }

    protected function getAgainstH2hSportVariantWithFields(
        int $nrOfFields,
        int $nrOfHomePlaces = 1,
        int $nrOfAwayPlaces = 1,
        int $nrOfH2H = 1
    ): SportVariantWithFields {
        return new SportVariantWithFields(
            $this->getAgainstH2hSportVariant($nrOfHomePlaces, $nrOfAwayPlaces, $nrOfH2H),
            $nrOfFields
        );
    }

    protected function getAgainstGppSportVariantWithFields(
        int $nrOfFields,
        int $nrOfHomePlaces = 1,
        int $nrOfAwayPlaces = 1,
        int $nrOfGamesPerPlace = 1
    ): SportVariantWithFields {
        return new SportVariantWithFields(
            $this->getAgainstGppSportVariant($nrOfHomePlaces, $nrOfAwayPlaces, $nrOfGamesPerPlace),
            $nrOfFields
        );
    }

    protected function getSingleSportVariantWithFields(
        int $nrOfFields,
        int $nrOfGamesPerPlace = 1,
        int $nrOfGamePlaces = 1
    ): SportVariantWithFields {
        return new SportVariantWithFields(
            $this->getSingleSportVariant($nrOfGamesPerPlace, $nrOfGamePlaces),
            $nrOfFields
        );
    }

    protected function getAllInOneGameSportVariantWithFields(
        int $nrOfFields,
        int $nrOfGamesPerPlace = 1
    ): SportVariantWithFields {
        return new SportVariantWithFields($this->getAllInOneGameSportVariant($nrOfGamesPerPlace), $nrOfFields);
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
     * @param RefereeInfo|null $refereeInfo
     * @return Input
     */
    protected function createInput(
        array $pouleStructureAsArray,
        array $sportVariantsWithFields = null,
        RefereeInfo|null $refereeInfo = null,
        bool $perPoule = false
    ) {
        if ($sportVariantsWithFields === null) {
            $sportVariantsWithFields = [$this->getAgainstH2hSportVariantWithFields(2)];
        }
        if ($refereeInfo === null) {
            $refereeInfo = new RefereeInfo($this->getDefaultNrOfReferees());
        }
        $input = new Input(
            new PouleStructure(...$pouleStructureAsArray),
            $sportVariantsWithFields,
            $refereeInfo,
            $perPoule
        );

        return $input;
    }

    protected function createPlanning(
        Input $input,
        SportRange $nrOfGamesPerBatchRange = null,
        int $maxNrOfGamesInARow = 0,
        bool $disableThrowOnTimeout = false,
        bool $showHighestCompletedBatchNr = false,
        TimeoutState|null $timeoutState = null,
        int|null $allowedGppMargin = null
    ): Planning {
        if ($nrOfGamesPerBatchRange === null) {
            $nrOfGamesPerBatchRange = new SportRange(1, 1);
        }
        $planning = new Planning($input, $nrOfGamesPerBatchRange, $maxNrOfGamesInARow);
        if ($timeoutState !== null) {
            $planning->setTimeoutState($timeoutState);
        }

        $scheduleCreatorService = new ScheduleCreator($this->getLogger());
        if( $allowedGppMargin !== null) {
            $scheduleCreatorService->setAllowedGppMargin($allowedGppMargin);
        }
        $schedules = $scheduleCreatorService->createFromInput($input);

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
