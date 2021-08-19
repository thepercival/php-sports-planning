<?php
declare(strict_types=1);

namespace SportsPlanning\TestHelper;

use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;
use SportsHelpers\SportRange;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;
use SportsPlanning\Combinations\GamePlaceStrategy;
use SportsPlanning\Planning;
use SportsPlanning\Planning\GameCreator;
use SportsPlanning\Input;
use SportsHelpers\PouleStructure;
use SportsHelpers\SelfReferee;

trait PlanningCreator
{
    protected function getAgainstSportVariant(int $nrOfHomePlaces = 1, int $nrOfAwayPlaces = 1, int $nrOfH2H = 1, int $nrOfGamesPerPlace = 0): AgainstSportVariant
    {
        return new AgainstSportVariant($nrOfHomePlaces, $nrOfAwayPlaces, $nrOfH2H, $nrOfGamesPerPlace);
    }

    protected function getSingleSportVariant(int $nrOfGameRounds = 1, int $nrOfGamePlaces = 1): SingleSportVariant
    {
        return new SingleSportVariant($nrOfGamePlaces, $nrOfGameRounds);
    }

    protected function getAgainstSportVariantWithFields(
        int $nrOfFields,
        int $nrOfHomePlaces = 1, int $nrOfAwayPlaces = 1,
        int $nrOfH2H = 1, int $nrOfGamesPerPlace = 0): SportVariantWithFields
    {
        return new SportVariantWithFields($this->getAgainstSportVariant($nrOfHomePlaces, $nrOfAwayPlaces, $nrOfH2H, $nrOfGamesPerPlace), $nrOfFields);
    }

    protected function getSingleSportVariantWithFields(int $nrOfFields, int $nrOfGameRounds = 1, int $nrOfGamePlaces = 1): SportVariantWithFields
    {
        return new SportVariantWithFields($this->getSingleSportVariant($nrOfGameRounds, $nrOfGamePlaces), $nrOfFields);
    }

    protected function getLogger(): LoggerInterface
    {
        $logger = new Logger("test-logger");
        $processor = new UidProcessor();
        $logger->pushProcessor($processor);

        $handler = new StreamHandler('php://stdout', LOG_INFO);
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
     * @param int|null $nrOfReferees
     * @param int|null $selfReferee
     * @return Input
     */
    protected function createInput(
        array $pouleStructureAsArray,
        array $sportVariantsWithFields = null,
        int $gamePlaceStrategy = null,
        int $nrOfReferees = null,
        int $selfReferee = null
    ) {
        if ($sportVariantsWithFields === null) {
            $sportVariantsWithFields = [$this->getAgainstSportVariantWithFields(2)];
        }
        if ($gamePlaceStrategy === null) {
            $gamePlaceStrategy = GamePlaceStrategy::EquallyAssigned;
        }
        if ($nrOfReferees === null) {
            $nrOfReferees = $this->getDefaultNrOfReferees();
        }
        if ($selfReferee === null) {
            $selfReferee = SelfReferee::DISABLED;
        }
        return new Input(
            new PouleStructure(...$pouleStructureAsArray),
            $sportVariantsWithFields,
            $gamePlaceStrategy,
            $nrOfReferees,
            $selfReferee
        );
    }

    protected function createPlanning(
        Input $input,
        SportRange $nrOfGamesPerBatchRange = null,
        int $maxNrOfGamesInARow = 0): Planning
    {
        if ($nrOfGamesPerBatchRange === null) {
            $nrOfGamesPerBatchRange = new SportRange(1, 1);
        }
        $planning = new Planning($input, $nrOfGamesPerBatchRange, $maxNrOfGamesInARow);
        $gameCreator = new GameCreator($this->getLogger());
        // $gameCreator->disableThrowOnTimeout();
        $gameCreator->createAssignedGames($planning);
        if (Planning::STATE_SUCCEEDED !== $planning->getState()) {
            throw new Exception("planning could not be created", E_ERROR);
        }
        return $planning;
    }
}
